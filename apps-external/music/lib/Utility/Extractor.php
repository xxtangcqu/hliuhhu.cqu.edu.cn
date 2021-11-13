<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @copyright Morris Jobke 2013, 2014
 */

namespace OCA\Music\Utility;

use OCP\Files\File;

interface Extractor {

	/**
	 * get metadata info for a media file
	 *
	 * @param File $file the file
	 * @return array extracted data
	 */
	public function extract(File $file): array;

	/**
	 * extract embedded cover art image from media file
	 *
	 * @param File $file the media file
	 * @return array|null Dictionary with keys 'mimetype' and 'content', or null if not found
	 */
	public function parseEmbeddedCoverArt(File $file) : ?array;
}
