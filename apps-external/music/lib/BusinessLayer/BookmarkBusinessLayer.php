<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Gavin E <no.emai@address.for.me>
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Gavin E 2020
 * @copyright Pauli Järvinen 2020, 2021
 */

namespace OCA\Music\BusinessLayer;

use OCA\Music\AppFramework\BusinessLayer\BusinessLayer;
use OCA\Music\AppFramework\Core\Logger;

use OCA\Music\Db\BookmarkMapper;
use OCA\Music\Db\Bookmark;

use OCA\Music\Utility\Util;

use OCP\AppFramework\Db\DoesNotExistException;

/**
 * Base class functions with the actually used inherited types to help IDE and Scrutinizer:
 * @method Bookmark find(int $bookmarkId, string $userId)
 * @method Bookmark[] findAll(string $userId, int $sortBy=SortBy::None, int $limit=null, int $offset=null)
 * @method Bookmark[] findAllByName(string $name, string $userId, bool $fuzzy=false, int $limit=null, int $offset=null)
 * @phpstan-extends BusinessLayer<Bookmark>
 */
class BookmarkBusinessLayer extends BusinessLayer {
	protected $mapper; // eclipse the definition from the base class, to help IDE and Scrutinizer to know the actual type
	private $logger;

	public function __construct(BookmarkMapper $bookmarkMapper, Logger $logger) {
		parent::__construct($bookmarkMapper);
		$this->mapper = $bookmarkMapper;
		$this->logger = $logger;
	}

	/**
	 * @param int $type One of [Bookmark::TYPE_TRACK, Bookmark::TYPE_PODCAST_EPISODE]
	 */
	public function addOrUpdate(string $userId, int $type, int $entryId, int $position, ?string $comment) : Bookmark {
		$bookmark = new Bookmark();
		$bookmark->setUserId($userId);
		$bookmark->setType($type);
		$bookmark->setEntryId($entryId);
		$bookmark->setPosition($position);
		$bookmark->setComment(Util::truncate($comment, 256));

		return $this->mapper->insertOrUpdate($bookmark);
	}

	/**
	 * @param int $type One of [Bookmark::TYPE_TRACK, Bookmark::TYPE_PODCAST_EPISODE]
	 * @throws DoesNotExistException if such bookmark does not exist
	 */
	public function findByEntry(int $type, int $entryId, string $userId) : Bookmark {
		return $this->mapper->findByEntry($type, $entryId, $userId);
	}
}
