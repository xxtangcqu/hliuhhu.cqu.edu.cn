<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Morris Jobke 2013, 2014
 * @copyright Pauli Järvinen 2016 - 2019
 */

namespace OCA\Music\Utility;

use OCA\Music\AppFramework\Core\Logger;

use OCP\Files\File;

/**
 * an extractor class for getID3
 */
class ExtractorGetID3 implements Extractor {
	private $getID3;
	private $logger;

	public function __construct(Logger $logger) {
		$this->logger = $logger;
		$this->getID3 = null; // lazy-loaded
	}

	/**
	 * Second stage constructor used to lazy-load the getID3 library once it's needed.
	 * This is to prevent polluting the namespace of occ when the user is not running
	 * Music app commands.
	 * See https://github.com/nextcloud/server/issues/17027.
	 */
	private function initGetID3() {
		if ($this->getID3 === null) {
			require_once __DIR__ . '/../../3rdparty/getID3/getid3/getid3.php';
			$this->getID3 = new \getID3();
			$this->getID3->encoding = 'UTF-8';
			$this->getID3->option_tags_html = false; // HTML-encoded tags are not needed
			// On 32-bit systems, getid3 tries to make a 2GB size check,
			// which does not work with fopen. Disable it.
			// Therefore the filesize (determined by getID3) could be wrong
			// (for files over ~2 GB) but this isn't used in any way.
			$this->getID3->option_max_2gb_check = false;
		}
	}

	/**
	 * get metadata info for a media file
	 *
	 * @param File $file the file
	 * @return array extracted data
	 */
	public function extract(File $file) : array {
		$this->initGetID3();

		try {
			$fp = $file->fopen('r');
		} catch (\Exception $e) {
			// There are probably more than one reason, why openeing a file may fail.
			// Some of the problems throw exceptions, and others just return null or false.
			$this->logger->log('Exception ' . \get_class($e) . ' when opening file', 'error');
		}

		if (empty($fp)) {
			$this->logger->log("Failed to open file {$file->getPath()} for metadata extraction", 'error');
			$metadata = [];
		} else {
			\mb_substitute_character(0x3F);
			$metadata = $this->getID3->analyze($file->getPath(), $file->getSize(), '', $fp);

			$this->getID3->CopyTagsToComments($metadata);

			if (\array_key_exists('error', $metadata)) {
				foreach ($metadata['error'] as $error) {
					$this->logger->log('getID3 error occured', 'debug');
					// sometimes $error is string but can't be concatenated to another string and weirdly just hide the log message
					$this->logger->log('getID3 error message: '. $error, 'debug');
				}
			}
		}

		return $metadata;
	}

	/**
	 * extract embedded cover art image from media file
	 *
	 * @param File $file the media file
	 * @return array|null Dictionary with keys 'mimetype' and 'content', or null if not found
	 */
	public function parseEmbeddedCoverArt(File $file) : ?array {
		$fileInfo = $this->extract($file);
		$pic = self::getTag($fileInfo, 'picture', true);
		\assert($pic === null || \is_array($pic));
		return $pic;
	}

	/**
	 * @param array $fileInfo
	 * @param string $tag
	 * @param bool $binaryValued
	 * @return string|int|array|null
	 */
	public static function getTag(array $fileInfo, string $tag, bool $binaryValued = false) {
		$value = $fileInfo['comments'][$tag][0]
				?? $fileInfo['comments']['text'][$tag]
				?? null;

		if (\is_string($value) && !$binaryValued) {
			// Ensure that the tag contains only valid utf-8 characters.
			// Illegal characters may result, if the file metadata has a mismatch
			// between claimed and actual encoding. Invalid characters could break
			// the database update.
			\mb_substitute_character(0xFFFD); // Use the Unicode REPLACEMENT CHARACTER (U+FFFD)
			$value = \mb_convert_encoding($value, 'UTF-8', 'UTF-8');
		}

		return $value;
	}

	/**
	 * @param array $fileInfo
	 * @param string[] $tags
	 * @param string|array|null $defaultValue
	 * @return string|int|array|null
	 */
	public static function getFirstOfTags(array $fileInfo, array $tags, $defaultValue = null) {
		foreach ($tags as $tag) {
			$value = self::getTag($fileInfo, $tag);
			if ($value !== null && $value !== '') {
				return $value;
			}
		}
		return $defaultValue;
	}
}
