<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Pauli Järvinen 2020, 2021
 */

namespace OCA\Music\Utility;

use OCA\Music\AppFramework\BusinessLayer\BusinessLayerException;
use OCA\Music\AppFramework\Core\Logger;
use OCA\Music\BusinessLayer\PlaylistBusinessLayer;
use OCA\Music\BusinessLayer\RadioStationBusinessLayer;
use OCA\Music\BusinessLayer\TrackBusinessLayer;
use OCA\Music\Db\SortBy;
use OCA\Music\Db\Track;

use OCP\Files\File;
use OCP\Files\Folder;

/**
 * Class responsible of exporting playlists to file and importing playlist
 * contents from file.
 */
class PlaylistFileService {
	private $playlistBusinessLayer;
	private $radioStationBusinessLayer;
	private $trackBusinessLayer;
	private $logger;

	private const PARSE_LOCAL_FILES_ONLY = 1;
	private const PARSE_URLS_ONLY = 2;
	private const PARSE_LOCAL_FILES_AND_URLS = 3;

	public function __construct(
			PlaylistBusinessLayer $playlistBusinessLayer,
			RadioStationBusinessLayer $radioStationBusinessLayer,
			TrackBusinessLayer $trackBusinessLayer,
			Logger $logger) {
		$this->playlistBusinessLayer = $playlistBusinessLayer;
		$this->radioStationBusinessLayer = $radioStationBusinessLayer;
		$this->trackBusinessLayer = $trackBusinessLayer;
		$this->logger = $logger;
	}

	/**
	 * export the playlist to a file
	 * @param int $id playlist ID
	 * @param string $userId owner of the playlist
	 * @param Folder $userFolder home dir of the user
	 * @param string $folderPath target parent folder path
	 * @param string $collisionMode action to take on file name collision,
	 *								supported values:
	 *								- 'overwrite' The existing file will be overwritten
	 *								- 'keepboth' The new file is named with a suffix to make it unique
	 *								- 'abort' (default) The operation will fail
	 * @return string path of the written file
	 * @throws BusinessLayerException if playlist with ID not found
	 * @throws \OCP\Files\NotFoundException if the $folderPath is not a valid folder
	 * @throws \RuntimeException on name conflict if $collisionMode == 'abort'
	 * @throws \OCP\Files\NotPermittedException if the user is not allowed to write to the given folder
	 */
	public function exportToFile(
			int $id, string $userId, Folder $userFolder, string $folderPath, string $collisionMode='abort') : string {
		$playlist = $this->playlistBusinessLayer->find($id, $userId);
		$tracks = $this->playlistBusinessLayer->getPlaylistTracks($id, $userId);
		$targetFolder = Util::getFolderFromRelativePath($userFolder, $folderPath);

		// Name the file according the playlist. File names cannot contain the '/' character on Linux, and in
		// owncloud/Nextcloud, the whole name must fit 250 characters, including the file extension. Reserve
		// another 5 characters to fit the postfix like " (xx)" on name collisions. If there are more than 100
		// exports of the same playlist with overly long name, then this function will fail but we can live
		// with that :).
		$filename = \str_replace('/', '-', $playlist->getName());
		$filename = Util::truncate($filename, 250 - 5 - 5);
		$filename .= '.m3u8';
		$filename = self::checkFileNameConflict($targetFolder, $filename, $collisionMode);

		$content = "#EXTM3U\n#EXTENC: UTF-8\n";
		foreach ($tracks as $track) {
			$nodes = $userFolder->getById($track->getFileId());
			if (\count($nodes) > 0) {
				$caption = self::captionForTrack($track);
				$content .= "#EXTINF:{$track->getLength()},$caption\n";
				$content .= Util::relativePath($targetFolder->getPath(), $nodes[0]->getPath()) . "\n";
			}
		}
		$file = $targetFolder->newFile($filename);
		$file->putContent($content);

		return $userFolder->getRelativePath($file->getPath());
	}

	/**
	 * export all the radio stations of a user to a file
	 * @param string $userId user
	 * @param Folder $userFolder home dir of the user
	 * @param string $folderPath target parent folder path
	 * @param string $filename target file name
	 * @param string $collisionMode action to take on file name collision,
	 *								supported values:
	 *								- 'overwrite' The existing file will be overwritten
	 *								- 'keepboth' The new file is named with a suffix to make it unique
	 *								- 'abort' (default) The operation will fail
	 * @return string path of the written file
	 * @throws \OCP\Files\NotFoundException if the $folderPath is not a valid folder
	 * @throws \RuntimeException on name conflict if $collisionMode == 'abort'
	 * @throws \OCP\Files\NotPermittedException if the user is not allowed to write to the given folder
	 */
	public function exportRadioStationsToFile(
			string $userId, Folder $userFolder, string $folderPath, string $filename, string $collisionMode='abort') : string {
		$targetFolder = Util::getFolderFromRelativePath($userFolder, $folderPath);

		$filename = self::checkFileNameConflict($targetFolder, $filename, $collisionMode);

		$stations = $this->radioStationBusinessLayer->findAll($userId, SortBy::Name);

		$content = "#EXTM3U\n#EXTENC: UTF-8\n";
		foreach ($stations as $station) {
			$content .= "#EXTINF:1,{$station->getName()}\n";
			$content .= $station->getStreamUrl() . "\n";
		}
		$file = $targetFolder->newFile($filename);
		$file->putContent($content);

		return $userFolder->getRelativePath($file->getPath());
	}

	/**
	 * import playlist contents from a file
	 * @param int $id playlist ID
	 * @param string $userId owner of the playlist
	 * @param Folder $userFolder user home dir
	 * @param string $filePath path of the file to import
	 * @parma string $mode one of the following:
	 * 						- 'append' (dafault) Append the imported tracks after the existing tracks on the list
	 * 						- 'overwrite' Replace any previous tracks on the list with the imported tracks
	 * @return array with three keys:
	 * 			- 'playlist': The Playlist entity after the modification
	 * 			- 'imported_count': An integer showing the number of tracks imported
	 * 			- 'failed_count': An integer showing the number of tracks in the file which could not be imported
	 * @throws BusinessLayerException if playlist with ID not found
	 * @throws \OCP\Files\NotFoundException if the $filePath is not a valid file
	 * @throws \UnexpectedValueException if the $filePath points to a file of unsupported type
	 */
	public function importFromFile(int $id, string $userId, Folder $userFolder, string $filePath, string $mode='append') : array {
		$parsed = self::doParseFile(self::getFile($userFolder, $filePath), $userFolder, self::PARSE_LOCAL_FILES_ONLY);
		$trackFilesAndCaptions = $parsed['files'];
		$invalidPaths = $parsed['invalid_paths'];

		$trackIds = [];
		foreach ($trackFilesAndCaptions as $trackFileAndCaption) {
			$trackFile = $trackFileAndCaption['file'];
			if ($track = $this->trackBusinessLayer->findByFileId($trackFile->getId(), $userId)) {
				$trackIds[] = $track->getId();
			} else {
				$invalidPaths[] = $trackFile->getPath();
			}
		}

		if ($mode === 'overwrite') {
			$this->playlistBusinessLayer->removeAllTracks($id, $userId);
		}
		$playlist = $this->playlistBusinessLayer->addTracks($trackIds, $id, $userId);

		if (\count($invalidPaths) > 0) {
			$this->logger->log('Some files were not found from the user\'s music library: '
								. \json_encode($invalidPaths, JSON_PARTIAL_OUTPUT_ON_ERROR), 'warn');
		}

		return [
			'playlist' => $playlist,
			'imported_count' => \count($trackIds),
			'failed_count' => \count($invalidPaths)
		];
	}

	/**
	 * import stream URLs from a playlist file and store them as internet radio stations
	 * @param string $userId user
	 * @param Folder $userFolder user home dir
	 * @param string $filePath path of the file to import
	 * @return array with two keys:
	 * 			- 'stations': Array of RadioStation objects imported from the file
	 * 			- 'failed_count': An integer showing the number of entries in the file which were not valid URLs
	 * @throws \OCP\Files\NotFoundException if the $filePath is not a valid file
	 * @throws \UnexpectedValueException if the $filePath points to a file of unsupported type
	 */
	public function importRadioStationsFromFile(string $userId, Folder $userFolder, string $filePath) : array {
		$parsed = self::doParseFile(self::getFile($userFolder, $filePath), $userFolder, self::PARSE_URLS_ONLY);
		$trackFilesAndCaptions = $parsed['files'];
		$invalidPaths = $parsed['invalid_paths'];

		$stations = [];
		foreach ($trackFilesAndCaptions as $trackFileAndCaption) {
			$stations[] = $this->radioStationBusinessLayer->create(
					$userId, $trackFileAndCaption['caption'], $trackFileAndCaption['url']);
		}

		if (\count($invalidPaths) > 0) {
			$this->logger->log('Some entries in the file were not valid streaming URLs: '
					. \json_encode($invalidPaths, JSON_PARTIAL_OUTPUT_ON_ERROR), 'warn');
		}

		return [
			'stations' => $stations,
			'failed_count' => \count($invalidPaths)
		];
	}

	/**
	 * Parse a playlist file and return the contained files
	 * @param int $fileId playlist file ID
	 * @param Folder $baseFolder ancestor folder of the playlist and the track files (e.g. user folder)
	 * @throws \OCP\Files\NotFoundException if the $fileId is not a valid file under the $baseFolder
	 * @throws \UnexpectedValueException if the $filePath points to a file of unsupported type
	 * @return array
	 */
	public function parseFile(int $fileId, Folder $baseFolder) : array {
		$node = $baseFolder->getById($fileId)[0] ?? null;
		if ($node instanceof File) {
			return self::doParseFile($node, $baseFolder, self::PARSE_LOCAL_FILES_AND_URLS);
		} else {
			throw new \OCP\Files\NotFoundException();
		}
	}

	/**
	 * @param File $file The playlist file to parse
	 * @param Folder $baseFolder Base folder for the local files
	 * @param int $mode One of self::[PARSE_LOCAL_FILES_ONLY, PARSE_URLS_ONLY, PARSE_LOCAL_FILES_AND_URLS]
	 * @throws \UnexpectedValueException
	 * @return array
	 */
	private static function doParseFile(File $file, Folder $baseFolder, int $mode) : array {
		$mime = $file->getMimeType();

		if ($mime == 'audio/mpegurl') {
			$entries = self::parseM3uFile($file);
		} elseif ($mime == 'audio/x-scpls') {
			$entries = self::parsePlsFile($file);
		} else {
			throw new \UnexpectedValueException("file mime type '$mime' is not suported");
		}

		// find the parsed entries from the file system
		$trackFiles = [];
		$invalidPaths = [];
		$cwd = $baseFolder->getRelativePath($file->getParent()->getPath());

		foreach ($entries as $entry) {
			$path = $entry['path'];

			if (Util::startsWith($path, 'http', /*ignoreCase=*/true)) {
				if ($mode !== self::PARSE_LOCAL_FILES_ONLY) {
					$trackFiles[] = [
						'url' => $path,
						'caption' => $entry['caption']
					];
				} else {
					$invalidPaths[] = $path;
				}
			} else {
				if ($mode !== self::PARSE_URLS_ONLY) {
					$entryFile = self::findFile($baseFolder, $cwd, $path);

					if ($entryFile !== null) {
						$trackFiles[] = [
							'file' => $entryFile,
							'caption' => $entry['caption']
						];
					} else {
						$invalidPaths[] = $path;
					}
				} else {
					$invalidPaths[] = $path;
				}
			}
		}

		return [
			'files' => $trackFiles,
			'invalid_paths' => $invalidPaths
		];
	}

	private static function parseM3uFile(File $file) : array {
		$entries = [];

		// By default, files with extension .m3u8 are interpreted as UTF-8 and files with extension
		// .m3u as ISO-8859-1. These can be overridden with the tag '#EXTENC' in the file contents.
		$encoding = Util::endsWith($file->getPath(), '.m3u8', /*ignoreCase=*/true) ? 'UTF-8' : 'ISO-8859-1';

		$caption = null;

		$fp = $file->fopen('r');
		while ($line = \fgets($fp)) {
			$line = \mb_convert_encoding($line, /** @scrutinizer ignore-type */ \mb_internal_encoding(), $encoding);
			$line = \trim(/** @scrutinizer ignore-type */ $line);

			if ($line === '') {
				// empty line => skip
			} elseif (Util::startsWith($line, '#')) {
				// comment or extended format attribute line
				if ($value = self::extractExtM3uField($line, 'EXTENC')) {
					// update the used encoding with the explicitly defined one
					$encoding = $value;
				} elseif ($value = self::extractExtM3uField($line, 'EXTINF')) {
					// The format should be "length,caption". Set caption to null if the field is badly formatted.
					$parts = \explode(',', $value, 2);
					$caption = $parts[1] ?? null;
					if (\is_string($caption)) {
						$caption = \trim($caption);
					}
				}
			} else {
				$entries[] = [
					'path' => $line,
					'caption' => $caption
				];
				$caption = null; // the caption has been used up
			}
		}
		\fclose($fp);

		return $entries;
	}

	private static function parsePlsFile(File $file) : array {
		$files = [];
		$titles = [];

		$content = $file->getContent();

		// If the file doesn't seem to be UTF-8, then assume it to be ISO-8859-1
		if (!\mb_check_encoding($content, 'UTF-8')) {
			$content = \mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
		}

		$fp = \fopen("php://temp", 'r+');
		\assert($fp !== false, 'Unexpected error: opening temporary stream failed');

		\fputs($fp, /** @scrutinizer ignore-type */ $content);
		\rewind($fp);

		// the first line should always be [playlist]
		if (\trim(\fgets($fp)) != '[playlist]') {
			throw new \UnexpectedValueException('the file is not in valid PLS format');
		}

		// the rest of the non-empty lines should be in format "key=value"
		while ($line = \fgets($fp)) {
			// ignore empty and malformed lines
			if (\strpos($line, '=') !== false) {
				list($key, $value) = \explode('=', $line, 2);
				$key = \trim($key);
				$value = \trim($value);
				// we are interested only on the File# and Title# lines
				if (Util::startsWith($key, 'File')) {
					$idx = \substr($key, \strlen('File'));
					$files[$idx] = $value;
				} elseif (Util::startsWith($key, 'Title')) {
					$idx = \substr($key, \strlen('Title'));
					$titles[$idx] = $value;
				}
			}
		}
		\fclose($fp);

		$entries = [];
		foreach ($files as $idx => $file) {
			$entries[] = [
				'path' => $file,
				'caption' => $titles[$idx] ?? null
			];
		}

		return $entries;
	}

	private static function checkFileNameConflict(Folder $targetFolder, string $filename, string $collisionMode) : string {
		if ($targetFolder->nodeExists($filename)) {
			switch ($collisionMode) {
				case 'overwrite':
					$targetFolder->get($filename)->delete();
					break;
				case 'keepboth':
					$filename = $targetFolder->getNonExistingName($filename);
					break;
				default:
					throw new \RuntimeException('file already exists');
			}
		}
		return $filename;
	}

	private static function captionForTrack(Track $track) : string {
		$title = $track->getTitle();
		$artist = $track->getArtistName();

		return empty($artist) ? $title : "$artist - $title";
	}

	private static function extractExtM3uField($line, $field) : ?string {
		if (Util::startsWith($line, "#$field:")) {
			return \trim(\substr($line, \strlen("#$field:")));
		} else {
			return null;
		}
	}

	private static function findFile(Folder $baseFolder, string $cwd, string $path) : ?File {
		$absPath = Util::resolveRelativePath($cwd, $path);

		try {
			$file = $baseFolder->get($absPath);
			if ($file instanceof File) {
				return $file;
			} else {
				return null;
			}
		} catch (\OCP\Files\NotFoundException | \OCP\Files\NotPermittedException $ex) {
			/* In case the file is not found and the path contains any backslashes, consider the possibility
			 * that the path follows the Windows convention of using backslashes as path separators.
			 */
			if (\strpos($path, '\\') !== false) {
				$path = \str_replace('\\', '/', $path);
				return self::findFile($baseFolder, $cwd, $path);
			} else {
				return null;
			}
		}
	}

	/**
	 * @throws \OCP\Files\NotFoundException if the $path does not point to a file under the $baseFolder
	 */
	private static function getFile(Folder $baseFolder, string $path) : File {
		$node = $baseFolder->get($path);
		if (!($node instanceof File)) {
			throw new \OCP\Files\NotFoundException();
		}
		return $node;
	}

}
