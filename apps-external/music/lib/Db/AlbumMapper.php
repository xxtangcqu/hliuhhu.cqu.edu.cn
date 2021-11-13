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
 * @copyright Pauli Järvinen 2016 - 2021
 */

namespace OCA\Music\Db;

use OCA\Music\Utility\Util;

use OCP\IDBConnection;

/**
 * Type hint a base class methdo to help Scrutinizer
 * @method Album updateOrInsert(Album $album)
 * @phpstan-extends BaseMapper<Album>
 */
class AlbumMapper extends BaseMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'music_albums', Album::class, 'name');
	}

	/**
	 * Override the base implementation to include data from multiple tables
	 *
	 * {@inheritdoc}
	 * @see BaseMapper::selectEntities()
	 */
	protected function selectEntities(string $condition, string $extension=null) : string {
		return "SELECT `*PREFIX*music_albums`.*, `artist`.`name` AS `album_artist_name`
				FROM `*PREFIX*music_albums`
				INNER JOIN `*PREFIX*music_artists` `artist`
				ON `*PREFIX*music_albums`.`album_artist_id` = `artist`.`id`
				WHERE $condition $extension";
	}

	/**
	 * Overridden from \OCA\Music\Db\BaseMapper to add support for sorting by artist.
	 *
	 * {@inheritdoc}
	 * @see BaseMapper::formatSortingClause()
	 */
	protected function formatSortingClause(int $sortBy) : ?string {
		if ($sortBy === SortBy::Parent) {
			return 'ORDER BY LOWER(`album_artist_name`), LOWER(`*PREFIX*music_albums`.`name`)';
		} else {
			return parent::formatSortingClause($sortBy);
		}
	}

	/**
	 * returns artist IDs mapped to album IDs
	 * does not include album_artist_id
	 *
	 * @param integer[]|null $albumIds IDs of the albums; get all albums of the user if null given
	 * @param string $userId the user ID
	 * @return array int => int[], keys are albums IDs and values are arrays of artist IDs
	 */
	public function getPerformingArtistsByAlbumId(?array $albumIds, string $userId) : array {
		$sql = 'SELECT DISTINCT `track`.`album_id`, `track`.`artist_id`
				FROM `*PREFIX*music_tracks` `track`
				WHERE `track`.`user_id` = ? ';
		$params = [$userId];

		if ($albumIds !== null) {
			$sql .= 'AND `track`.`album_id` IN ' . $this->questionMarks(\count($albumIds));
			$params = \array_merge($params, $albumIds);
		}

		$result = $this->execute($sql, $params);
		$artistIds = [];
		while ($row = $result->fetch()) {
			$artistIds[$row['album_id']][] = (int)$row['artist_id'];
		}
		return $artistIds;
	}

	/**
	 * returns release years mapped to album IDs
	 *
	 * @param integer[]|null $albumIds IDs of the albums; get all albums of the user if null given
	 * @param string $userId the user ID
	 * @return array int => int[], keys are albums IDs and values are arrays of years
	 */
	public function getYearsByAlbumId(?array $albumIds, string $userId) : array {
		$sql = 'SELECT DISTINCT `track`.`album_id`, `track`.`year`
				FROM `*PREFIX*music_tracks` `track`
				WHERE `track`.`user_id` = ?
				AND `track`.`year` IS NOT NULL ';
		$params = [$userId];

		if ($albumIds !== null) {
			$sql .= 'AND `track`.`album_id` IN ' . $this->questionMarks(\count($albumIds));
			$params = \array_merge($params, $albumIds);
		}

		$result = $this->execute($sql, $params);
		$years = [];
		while ($row = $result->fetch()) {
			$years[$row['album_id']][] = (int)$row['year'];
		}
		return $years;
	}

	/**
	 * returns genres mapped to album IDs
	 *
	 * @param integer[]|null $albumIds IDs of the albums; get all albums of the user if null given
	 * @param string $userId the user ID
	 * @return array int => Genre[], keys are albums IDs and values are arrays of *partial* Genre objects (only id and name properties set)
	 */
	public function getGenresByAlbumId(?array $albumIds, string $userId) : array {
		$sql = 'SELECT DISTINCT `album_id`, `genre_id`, `*PREFIX*music_genres`.`name` AS `genre_name`
				FROM `*PREFIX*music_tracks`
				LEFT JOIN `*PREFIX*music_genres`
				ON `genre_id` = `*PREFIX*music_genres`.`id`
				WHERE `*PREFIX*music_tracks`.`user_id` = ?
				AND `genre_id` IS NOT NULL ';
		$params = [$userId];

		if ($albumIds !== null) {
			$sql .= 'AND `album_id` IN ' . $this->questionMarks(\count($albumIds));
			$params = \array_merge($params, $albumIds);
		}

		$result = $this->execute($sql, $params);
		$genres = [];
		while ($row = $result->fetch()) {
			$genre = new Genre();
			$genre->setUserId($userId);
			$genre->setId((int)$row['genre_id']);
			$genre->setName($row['genre_name']);
			$genres[$row['album_id']][] = $genre;
		}
		return $genres;
	}

	/**
	 * returns number of disks per album ID
	 *
	 * @param integer[]|null $albumIds IDs of the albums; get all albums of the user if null given
	 * @param string $userId the user ID
	 * @return array int => int, keys are albums IDs and values are disk counts
	 */
	public function getDiscCountByAlbumId(?array $albumIds, string $userId) : array {
		$sql = 'SELECT `album_id`, MAX(`disk`) AS `disc_count`
				FROM `*PREFIX*music_tracks`
				WHERE `user_id` = ?
				GROUP BY `album_id` ';
		$params = [$userId];

		if ($albumIds !== null) {
			$sql .= 'HAVING `album_id` IN ' . $this->questionMarks(\count($albumIds));
			$params = \array_merge($params, $albumIds);
		}

		$result = $this->execute($sql, $params);
		$diskCountByAlbum = [];
		while ($row = $result->fetch()) {
			$diskCountByAlbum[$row['album_id']] = (int)$row['disc_count'];
		}
		return $diskCountByAlbum;
	}

	/**
	 * returns summed track play counts of each album of the user, omittig albums which have never been played
	 *
	 * @return array [int => int], keys are album IDs and values are play count sums; ordered largest counts first
	 */
	public function getAlbumTracksPlayCount(string $userId, ?int $limit=null, ?int $offset=null) : array {
		$sql = 'SELECT `album_id`, SUM(`play_count`) AS `sum_count`
				FROM `*PREFIX*music_tracks`
				WHERE `user_id` = ? AND `play_count` > 0
				GROUP BY `album_id`
				ORDER BY `sum_count` DESC, `album_id`'; // the second criterion is just to make the order predictable on even counts

		$result = $this->execute($sql, [$userId], $limit, $offset);
		$playCountByAlbum = [];
		while ($row = $result->fetch()) {
			$playCountByAlbum[$row['album_id']] = (int)$row['sum_count'];
		}
		return $playCountByAlbum;
	}

	/**
	 * returns the latest play time of each album of the user, omittig albums which have never been played
	 *
	 * @return array [int => string], keys are album IDs and values are date-times; ordered latest times first
	 */
	public function getLatestAlbumPlayTimes(string $userId, ?int $limit=null, ?int $offset=null) : array {
		$sql = 'SELECT `album_id`, MAX(`last_played`) AS `latest_time`
				FROM `*PREFIX*music_tracks`
				WHERE `user_id` = ? AND `last_played` IS NOT NULL
				GROUP BY `album_id`
				ORDER BY `latest_time` DESC';

		$result = $this->execute($sql, [$userId], $limit, $offset);
		$latestTimeByAlbum = [];
		while ($row = $result->fetch()) {
			$latestTimeByAlbum[$row['album_id']] = $row['latest_time'];
		}
		return $latestTimeByAlbum;
	}

	/**
	 * returns the latest play time of each album of the user, including albums which have never been played
	 *
	 * @return array [int => ?string], keys are album IDs and values are date-times (or null for never played);
	 *									ordered furthest times first
	 */
	public function getFurthestAlbumPlayTimes(string $userId, ?int $limit=null, ?int $offset=null) : array {
		$sql = 'SELECT `album_id`, MAX(`last_played`) AS `latest_time`
				FROM `*PREFIX*music_tracks`
				WHERE `user_id` = ?
				GROUP BY `album_id`
				ORDER BY `latest_time` ASC';

		$result = $this->execute($sql, [$userId], $limit, $offset);
		$latestTimeByAlbum = [];
		while ($row = $result->fetch()) {
			$latestTimeByAlbum[$row['album_id']] = $row['latest_time'];
		}
		return $latestTimeByAlbum;
	}

	/**
	 * returns albums of a specified artist
	 * The artist may be an album_artist or the artist of a track
	 *
	 * @param integer $artistId ID of the artist
	 * @param string $userId the user ID
	 * @return Album[]
	 */
	public function findAllByArtist(int $artistId, string $userId, ?int $limit=null, ?int $offset=null) : array {
		$sql = $this->selectEntities(
				'`*PREFIX*music_albums`.`id` IN (
					SELECT DISTINCT `album`.`id`
					FROM `*PREFIX*music_albums` `album`
					WHERE `album`.`album_artist_id` = ?
						UNION
					SELECT DISTINCT `track`.`album_id`
					FROM `*PREFIX*music_tracks` `track`
					WHERE `track`.`artist_id` = ?
				) AND `*PREFIX*music_albums`.`user_id` = ?',
				'ORDER BY LOWER(`*PREFIX*music_albums`.`name`)');
		$params = [$artistId, $artistId, $userId];
		return $this->findEntities($sql, $params, $limit, $offset);
	}

	/**
	 * returns albums of a specified artist
	 * The artist must album_artist on the album, artists of individual tracks are not considered
	 *
	 * @param integer $artistId ID of the artist
	 * @param string $userId the user ID
	 * @return Album[]
	 */
	public function findAllByAlbumArtist(int $artistId, string $userId, ?int $limit=null, ?int $offset=null) : array {
		$sql = $this->selectUserEntities('`album_artist_id` = ?');
		$params = [$userId, $artistId];
		return $this->findEntities($sql, $params, $limit, $offset);
	}

	/**
	 * @return Album[]
	 */
	public function findAllByGenre(int $genreId, string $userId, int $limit=null, int $offset=null) : array {
		$sql = $this->selectUserEntities('EXISTS '.
				'(SELECT 1 FROM `*PREFIX*music_tracks` `track`
				  WHERE `*PREFIX*music_albums`.`id` = `track`.`album_id`
				  AND `track`.`genre_id` = ?)');

		$params = [$userId, $genreId];
		return $this->findEntities($sql, $params, $limit, $offset);
	}

	/**
	 * @return boolean True if one or more albums were influenced
	 */
	public function updateFolderCover(int $coverFileId, int $folderId) : bool {
		$sql = 'SELECT DISTINCT `tracks`.`album_id`
				FROM `*PREFIX*music_tracks` `tracks`
				JOIN `*PREFIX*filecache` `files` ON `tracks`.`file_id` = `files`.`fileid`
				WHERE `files`.`parent` = ?';
		$params = [$folderId];
		$result = $this->execute($sql, $params);

		$updated = false;
		if ($result->rowCount()) {
			$sql = 'UPDATE `*PREFIX*music_albums`
					SET `cover_file_id` = ?
					WHERE `cover_file_id` IS NULL AND `id` IN (?)';
			$params = [$coverFileId, \join(",", $result->fetchAll(\PDO::FETCH_COLUMN))];
			$result = $this->execute($sql, $params);
			$updated = $result->rowCount() > 0;
		}

		return $updated;
	}

	/**
	 * Set file ID to be used as cover for an album
	 */
	public function setCover(?int $coverFileId, int $albumId) : void {
		$sql = 'UPDATE `*PREFIX*music_albums`
				SET `cover_file_id` = ?
				WHERE `id` = ?';
		$params = [$coverFileId, $albumId];
		$this->execute($sql, $params);
	}

	/**
	 * @param integer[] $coverFileIds
	 * @param string[]|null $userIds the users whose music library is targeted; all users are targeted if omitted
	 * @return Album[] albums which got modified (with incomplete data, only id and user are valid),
	 *         empty array if none
	 */
	public function removeCovers(array $coverFileIds, array $userIds=null) : array {
		// find albums using the given file as cover
		$sql = 'SELECT `id`, `user_id` FROM `*PREFIX*music_albums` WHERE `cover_file_id` IN ' .
			$this->questionMarks(\count($coverFileIds));
		$params = $coverFileIds;
		if ($userIds !== null) {
			$sql .= ' AND `user_id` IN ' . $this->questionMarks(\count($userIds));
			$params = \array_merge($params, $userIds);
		}
		$albums = $this->findEntities($sql, $params);

		// if any albums found, remove the cover from those
		$count = \count($albums);
		if ($count) {
			$sql = 'UPDATE `*PREFIX*music_albums`
				SET `cover_file_id` = NULL
				WHERE `id` IN ' . $this->questionMarks($count);
			$params = Util::extractIds($albums);
			$this->execute($sql, $params);
		}

		return $albums;
	}

	/**
	 * @param string|null $userId target user; omit to target all users
	 * @return array of dictionaries with keys [albumId, userId, parentFolderId]
	 */
	public function getAlbumsWithoutCover(string $userId = null) : array {
		$sql = 'SELECT DISTINCT `albums`.`id`, `albums`.`user_id`, `files`.`parent`
				FROM `*PREFIX*music_albums` `albums`
				JOIN `*PREFIX*music_tracks` `tracks` ON `albums`.`id` = `tracks`.`album_id`
				JOIN `*PREFIX*filecache` `files` ON `tracks`.`file_id` = `files`.`fileid`
				WHERE `albums`.`cover_file_id` IS NULL';
		$params = [];
		if ($userId !== null) {
			$sql .= ' AND `albums`.`user_id` = ?';
			$params[] = $userId;
		}
		$result = $this->execute($sql, $params);
		$return = [];
		while ($row = $result->fetch()) {
			$return[] = [
				'albumId' => (int)$row['id'],
				'userId' => $row['user_id'],
				'parentFolderId' => (int)$row['parent']
			];
		}
		return $return;
	}

	/**
	 * @return boolean True if a cover image was found and added for the album
	 */
	public function findAlbumCover(int $albumId, int $parentFolderId) : bool {
		$return = false;
		$imagesSql = 'SELECT `fileid`, `name`
					FROM `*PREFIX*filecache`
					JOIN `*PREFIX*mimetypes` ON `*PREFIX*mimetypes`.`id` = `*PREFIX*filecache`.`mimetype`
					WHERE `parent` = ? AND `*PREFIX*mimetypes`.`mimetype` LIKE \'image%\'';
		$params = [$parentFolderId];
		$result = $this->execute($imagesSql, $params);
		$images = $result->fetchAll();
		if (\count($images) > 0) {
			$getImageRank = function($imageName) {
				$coverNames = ['cover', 'albumart', 'album', 'front', 'folder'];
				foreach ($coverNames as $i => $coverName) {
					if (Util::startsWith($imageName, $coverName, /*$ignoreCase=*/true)) {
						return $i;
					}
				}
				return \count($coverNames);
			};

			\usort($images, function ($imageA, $imageB) use ($getImageRank) {
				return $getImageRank($imageA['name']) <=> $getImageRank($imageB['name']);
			});
			$imageId = (int)$images[0]['fileid'];
			$this->setCover($imageId, $albumId);
			$return = true;
		}
		return $return;
	}

	/**
	 * Given an array of track IDs, find corresponding uniqu album IDs, including only
	 * those album which have a cover art set.
	 * @param int[] $trackIds
	 * @return Album[]
	 */
	public function findAlbumsWithCoversForTracks(array $trackIds, string $userId, int $limit) : array {
		$sql = 'SELECT DISTINCT `albums`.*
				FROM `*PREFIX*music_albums` `albums`
				JOIN `*PREFIX*music_tracks` `tracks` ON `albums`.`id` = `tracks`.`album_id`
				WHERE `albums`.`cover_file_id` IS NOT NULL
				AND `albums`.`user_id` = ?
				AND `tracks`.`id` IN ' . $this->questionMarks(\count($trackIds));
		$params = \array_merge([$userId], $trackIds);

		return $this->findEntities($sql, $params, $limit);
	}

	/**
	 * Returns the count of albums where the given Artist is featured in
	 * @param integer $artistId
	 * @return integer
	 */
	public function countByArtist(int $artistId) : int {
		$sql = 'SELECT COUNT(*) AS count FROM (
					SELECT DISTINCT `track`.`album_id`
					FROM `*PREFIX*music_tracks` `track`
					WHERE `track`.`artist_id` = ?
						UNION
					SELECT `album`.`id`
					FROM `*PREFIX*music_albums` `album`
					WHERE `album`.`album_artist_id` = ?
				) tmp';
		$params = [$artistId, $artistId];
		$result = $this->execute($sql, $params);
		$row = $result->fetch();
		return (int)$row['count'];
	}

	/**
	 * Returns the count of albums where the given artist is the album artist
	 * @param integer $artistId
	 * @return integer
	 */
	public function countByAlbumArtist(int $artistId) : int {
		$sql = 'SELECT COUNT(*) AS count
				FROM `*PREFIX*music_albums` `album`
				WHERE `album`.`album_artist_id` = ?';
		$params = [$artistId];
		$result = $this->execute($sql, $params);
		$row = $result->fetch();
		return (int)$row['count'];
	}

	/**
	 * @see \OCA\Music\Db\BaseMapper::findUniqueEntity()
	 * @param Album $album
	 * @return Album
	 */
	protected function findUniqueEntity(Entity $album) : Entity {
		$sql = $this->selectUserEntities('`*PREFIX*music_albums`.`hash` = ?');
		return $this->findEntity($sql, [$album->getUserId(), $album->getHash()]);
	}
}
