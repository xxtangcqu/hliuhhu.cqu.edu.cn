<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Volkan Gezer <volkangezer@gmail.com>
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Morris Jobke 2014
 * @copyright Volkan Gezer 2014
 * @copyright Pauli Järvinen 2016 - 2021
 */

namespace OCA\Music\Db;

use OCP\IDBConnection;

/**
 * @phpstan-extends BaseMapper<Playlist>
 */
class PlaylistMapper extends BaseMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'music_playlists', Playlist::class, 'name');
	}

	/**
	 * @param int $trackId
	 * @return Playlist[]
	 */
	public function findListsContainingTrack($trackId) {
		$sql = $this->selectEntities('`track_ids` LIKE ?');
		$params = ['%|' . $trackId . '|%'];
		return $this->findEntities($sql, $params);
	}

	/**
	 * @see \OCA\Music\Db\BaseMapper::findUniqueEntity()
	 * @param Playlist $playlist
	 * @return Playlist
	 */
	protected function findUniqueEntity(Entity $playlist) : Entity {
		// The playlist table has no unique constraints, and hence, this function
		// should never be called.
		throw new \BadMethodCallException('not supported');
	}
}
