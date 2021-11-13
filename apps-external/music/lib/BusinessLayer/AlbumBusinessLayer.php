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

namespace OCA\Music\BusinessLayer;

use OCA\Music\AppFramework\BusinessLayer\BusinessLayer;
use OCA\Music\AppFramework\BusinessLayer\BusinessLayerException;
use OCA\Music\AppFramework\Core\Logger;

use OCA\Music\Db\AlbumMapper;
use OCA\Music\Db\Album;
use OCA\Music\Db\Entity;
use OCA\Music\Db\SortBy;
use OCA\Music\Db\Track;

use OCA\Music\Utility\Util;

/**
 * Base class functions with the actually used inherited types to help IDE and Scrutinizer:
 * @phpstan-extends BusinessLayer<Album>
 */
class AlbumBusinessLayer extends BusinessLayer {
	protected $mapper; // eclipse the definition from the base class, to help IDE and Scrutinizer to know the actual type
	private $logger;

	private const MAX_SQL_ALBUM_ARGS = 998; // Some SQLite installations can't handle more than 999 query args. 1 slot reserved for `user_id`.

	public function __construct(AlbumMapper $albumMapper, Logger $logger) {
		parent::__construct($albumMapper);
		$this->mapper = $albumMapper;
		$this->logger = $logger;
	}

	/**
	 * {@inheritdoc}
	 * @see BusinessLayer::find()
	 * @return Album
	 */
	public function find(int $albumId, string $userId) : Entity {
		$album = parent::find($albumId, $userId);
		return $this->injectExtraFields([$album], $userId)[0];
	}

	/**
	 * {@inheritdoc}
	 * @see BusinessLayer::findById()
	 * @return Album[]
	 */
	public function findById(array $ids, string $userId=null, bool $preserveOrder=false) : array {
		$albums = parent::findById($ids, $userId, $preserveOrder);
		if ($userId !== null) {
			return $this->injectExtraFields($albums, $userId);
		} else {
			return $albums; // can't inject the extra fields without a user
		}
	}

	/**
	 * {@inheritdoc}
	 * @see BusinessLayer::findAll()
	 * @return Album[]
	 */
	public function findAll(string $userId, int $sortBy=SortBy::None, ?int $limit=null, ?int $offset=null,
							?string $createdMin=null, ?string $createdMax=null, ?string $updatedMin=null, ?string $updatedMax=null) : array {
		$albums = parent::findAll($userId, $sortBy, $limit, $offset, $createdMin, $createdMax, $updatedMin, $updatedMax);
		$effectivelyLimited = ($limit !== null && $limit < \count($albums));
		$everyAlbumIncluded = (!$effectivelyLimited && !$offset && !$createdMin && !$createdMax && !$updatedMin && !$updatedMax);
		return $this->injectExtraFields($albums, $userId, $everyAlbumIncluded);
	}

	/**
	 * Returns all albums filtered by artist (both album and track artists are considered)
	 * @return Album[] albums
	 */
	public function findAllByArtist(int $artistId, string $userId, ?int $limit=null, ?int $offset=null) : array {
		$albums = $this->mapper->findAllByArtist($artistId, $userId, $limit, $offset);
		return $this->injectExtraFields($albums, $userId);
	}

	/**
	 * Returns all albums filtered by album artist
	 * @return Album[] albums
	 */
	public function findAllByAlbumArtist(int $artistId, string $userId, ?int $limit=null, ?int $offset=null) : array {
		$albums = $this->mapper->findAllByAlbumArtist($artistId, $userId, $limit, $offset);
		$albums = $this->injectExtraFields($albums, $userId);
		\usort($albums, ['\OCA\Music\Db\Album', 'compareYearAndName']);
		return $albums;
	}

	/**
	 * Returns all albums filtered by genre
	 * @param int $genreId the genre to include
	 * @param string $userId the name of the user
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return Album[] albums
	 */
	public function findAllByGenre(int $genreId, string $userId, ?int $limit=null, ?int $offset=null) : array {
		$albums = $this->mapper->findAllByGenre($genreId, $userId, $limit, $offset);
		return $this->injectExtraFields($albums, $userId);
	}

	/**
	 * Returns all albums filtered by release year
	 * @param int $fromYear
	 * @param int $toYear
	 * @param string $userId the name of the user
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return Album[] albums
	 */
	public function findAllByYearRange(
			int $fromYear, int $toYear, string $userId, ?int $limit=null, ?int $offset=null) : array {
		$reverseOrder = false;
		if ($fromYear > $toYear) {
			$reverseOrder = true;
			Util::swap($fromYear, $toYear);
		}

		// Implement all the custom logic of this function here, without special Mapper function
		$albums = \array_filter($this->findAll($userId), function ($album) use ($fromYear, $toYear) {
			$years = $album->getYears();
			return (!empty($years) && \min($years) <= $toYear && \max($years) >= $fromYear);
		});

		\usort($albums, function ($album1, $album2) use ($reverseOrder) {
			return $reverseOrder
				? $album2->yearToAPI() - $album1->yearToAPI()
				: $album1->yearToAPI() - $album2->yearToAPI();
		});

		if ($limit !== null || $offset !== null) {
			$albums = \array_slice($albums, $offset ?: 0, $limit);
		}

		return $albums;
	}

	/**
	 * {@inheritdoc}
	 * @see BusinessLayer::findAllByName()
	 * @return Album[]
	 */
	public function findAllByName(
			string $name, string $userId, bool $fuzzy = false, ?int $limit=null, ?int $offset=null,
			?string $createdMin=null, ?string $createdMax=null, ?string $updatedMin=null, ?string $updatedMax=null) : array {
		$albums = parent::findAllByName($name, $userId, $fuzzy, $limit, $offset, $createdMin, $createdMax, $updatedMin, $updatedMax);
		return $this->injectExtraFields($albums, $userId);
	}

	/**
	 * Find most frequently played albums, judged by the total play count of the contained tracks
	 * @return Album[]
	 */
	public function findFrequentPlay(string $userId, ?int $limit=null, ?int $offset=null) : array {
		$countsPerAlbum = $this->mapper->getAlbumTracksPlayCount($userId, $limit, $offset);
		$ids = \array_keys($countsPerAlbum);
		return $this->findById($ids, $userId, /*preserveOrder=*/true);
	}

	/**
	 * Find most recently played albums
	 * @return Album[]
	 */
	public function findRecentPlay(string $userId, ?int $limit=null, ?int $offset=null) : array {
		$playTimePerAlbum = $this->mapper->getLatestAlbumPlayTimes($userId, $limit, $offset);
		$ids = \array_keys($playTimePerAlbum);
		return $this->findById($ids, $userId, /*preserveOrder=*/true);
	}

	/**
	 * Find least recently played albums
	 * @return Album[]
	 */
	public function findNotRecentPlay(string $userId, ?int $limit=null, ?int $offset=null) : array {
		$playTimePerAlbum = $this->mapper->getFurthestAlbumPlayTimes($userId, $limit, $offset);
		$ids = \array_keys($playTimePerAlbum);
		return $this->findById($ids, $userId, /*preserveOrder=*/true);
	}

	/**
	 * Add performing artists, release years, genres, and disk counts to the given album objects
	 * @param Album[] $albums
	 * @param string $userId
	 * @param bool $allAlbums Set to true if $albums contains all albums of the user.
	 *                        This has now effect on the outcome but helps in optimizing
	 *                        the database query.
	 * @return Album[]
	 */
	private function injectExtraFields(array $albums, string $userId, bool $allAlbums = false) : array {
		if (\count($albums) > 0) {
			// In case we are injecting data to a lot of albums, do not limit the
			// SQL SELECTs to only those albums. Very large amount of SQL host parameters
			// could cause problems with SQLite (see #239) and probably it would be bad for
			// performance also on other DBMSs. For the proper operation of this function,
			// it doesn't matter if we fetch data for some extra albums.
			$albumIds = ($allAlbums || \count($albums) >= self::MAX_SQL_ALBUM_ARGS)
					? null : Util::extractIds($albums);

			$artists = $this->mapper->getPerformingArtistsByAlbumId($albumIds, $userId);
			$years = $this->mapper->getYearsByAlbumId($albumIds, $userId);
			$diskCounts = $this->mapper->getDiscCountByAlbumId($albumIds, $userId);
			$genres = $this->mapper->getGenresByAlbumId($albumIds, $userId);

			foreach ($albums as &$album) {
				$albumId = $album->getId();
				$album->setArtistIds($artists[$albumId] ?? []);
				$album->setNumberOfDisks($diskCounts[$albumId] ?? 1);
				$album->setGenres($genres[$albumId] ?? null);
				$album->setYears($years[$albumId] ?? null);
			}
		}
		return $albums;
	}

	/**
	 * Returns the count of albums where the given Artist is featured in
	 * @param integer $artistId
	 * @return integer
	 */
	public function countByArtist(int $artistId) : int {
		return $this->mapper->countByArtist($artistId);
	}

	/**
	 * Returns the count of albums where the given artist is the album artist
	 * @param integer $artistId
	 * @return integer
	 */
	public function countByAlbumArtist(int $artistId) : int {
		return $this->mapper->countByAlbumArtist($artistId);
	}

	public function findAlbumOwner(int $albumId) : string {
		$entities = $this->findById([$albumId]);
		if (\count($entities) != 1) {
			throw new BusinessLayerException(
					'Expected to find one album but got ' . \count($entities));
		} else {
			return $entities[0]->getUserId();
		}
	}

	/**
	 * Adds an album if it does not exist already or updates an existing album
	 * @param string|null $name the name of the album
	 * @param integer $albumArtistId
	 * @param string $userId
	 * @return Album The added/updated album
	 */
	public function addOrUpdateAlbum(?string $name, int $albumArtistId, string $userId) : Album {
		$album = new Album();
		$album->setName(Util::truncate($name, 256)); // some DB setups can't truncate automatically to column max size
		$album->setUserId($userId);
		$album->setAlbumArtistId($albumArtistId);

		// Generate hash from the set of fields forming the album identity to prevent duplicates.
		// The uniqueness of album name is evaluated in case-insensitive manner.
		$lowerName = \mb_strtolower($album->getName() ?? '');
		$hash = \hash('md5', "$lowerName|$albumArtistId");
		$album->setHash($hash);

		return $this->mapper->updateOrInsert($album);
	}

	/**
	 * Check if given file is used as cover for the given album
	 * @param int $albumId
	 * @param int[] $fileIds
	 * @return boolean
	 */
	public function albumCoverIsOneOfFiles(int $albumId, array $fileIds) : bool {
		$albums = $this->findById([$albumId]);
		return (\count($albums) && \in_array($albums[0]->getCoverFileId(), $fileIds));
	}

	/**
	 * updates the cover for albums in the specified folder without cover
	 * @param integer $coverFileId the file id of the cover image
	 * @param integer $folderId the file id of the folder where the albums are looked from
	 * @return boolean True if one or more albums were influenced
	 */
	public function updateFolderCover(int $coverFileId, int $folderId) : bool {
		return $this->mapper->updateFolderCover($coverFileId, $folderId);
	}

	/**
	 * set cover file for a specified album
	 * @param int|null $coverFileId the file id of the cover image
	 * @param int $albumId the id of the album to be modified
	 */
	public function setCover(?int $coverFileId, int $albumId) {
		$this->mapper->setCover($coverFileId, $albumId);
	}

	/**
	 * removes the cover art from albums, replacement covers will be searched in a background task
	 * @param integer[] $coverFileIds the file IDs of the cover images
	 * @param string[]|null $userIds the users whose music library is targeted; all users are targeted if omitted
	 * @return Album[] albums which got modified, empty array if none
	 */
	public function removeCovers(array $coverFileIds, array $userIds=null) : array {
		return $this->mapper->removeCovers($coverFileIds, $userIds);
	}

	/**
	 * try to find cover arts for albums without covers
	 * @param string|null $userId target user; omit to target all users
	 * @return string[] users whose collections got modified
	 */
	public function findCovers(string $userId = null) : array {
		$affectedUsers = [];
		$albums = $this->mapper->getAlbumsWithoutCover($userId);
		foreach ($albums as $album) {
			if ($this->mapper->findAlbumCover($album['albumId'], $album['parentFolderId'])) {
				$affectedUsers[$album['userId']] = 1;
			}
		}
		return \array_keys($affectedUsers);
	}

	/**
	 * Given an array of track IDs, find corresponding uniqu album IDs, including only
	 * those album which have a cover art set.
	 * @param int[] $trackIds
	 * @return Album[] *Partial* albums, without any injected extra fields
	 */
	public function findAlbumsWithCoversForTracks(array $trackIds, string $userId, int $limit) : array {
		if (\count($trackIds) === 0) {
			return [];
		} else {
			$result = [];
			$idChunks = \array_chunk($trackIds, self::MAX_SQL_ALBUM_ARGS);
			foreach ($idChunks as $idChunk) {
				$resultChunk = $this->mapper->findAlbumsWithCoversForTracks($idChunk, $userId, $limit);
				$result = \array_merge($result, $resultChunk);
				$limit -= \count($resultChunk);
				if ($limit <= 0) {
					break;
				}
			}
			return $result;
		}
	}

	/**
	 * Given an array of Track objects, inject the corresponding Album object to each of them
	 * @param Track[] $tracks (in|out)
	 */
	public function injectAlbumsToTracks(array &$tracks, string $userId) {
		$albumIds = [];

		// get unique album IDs
		foreach ($tracks as $track) {
			$albumIds[$track->getAlbumId()] = 1;
		}
		$albumIds = \array_keys($albumIds);

		// get the corresponding entities from the business layer
		if (\count($albumIds) < $this->count($userId)) {
			$albums = $this->findById($albumIds, $userId);
		} else {
			$albums = $this->findAll($userId);
		}

		// create hash tables "id => entity" for the albums for fast access
		$albumMap = Util::createIdLookupTable($albums);

		// finally, set the references on the tracks
		foreach ($tracks as &$track) {
			$track->setAlbum($albumMap[$track->getAlbumId()] ?? new Album());
		}
	}
}
