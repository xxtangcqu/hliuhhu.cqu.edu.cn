<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Pauli Järvinen 2016 - 2021
 */

namespace OCA\Music\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Mapper;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IDBConnection;

use OCA\Music\AppFramework\Db\UniqueConstraintViolationException;
use OCA\Music\Utility\Util;

/**
 * Common base class for data access classes of the Music app
 * @phpstan-template EntityType of Entity
 * @phpstan-method EntityType findEntity(string $sql, array $params)
 * @phpstan-method EntityType[] findEntities(string $sql, array $params, ?int $limit=null, ?int $offset=null)
 */
abstract class BaseMapper extends Mapper {
	const SQL_DATE_FORMAT = 'Y-m-d H:i:s.v';

	protected $nameColumn;
	/** @phpstan-var class-string<EntityType> $entityClass */
	protected $entityClass;

	/**
	 * @phpstan-param class-string<EntityType> $entityClass
	 */
	public function __construct(IDBConnection $db, string $tableName, string $entityClass, string $nameColumn) {
		parent::__construct($db, $tableName, $entityClass);
		$this->nameColumn = $nameColumn;
		// eclipse the base class property to help phpstan
		$this->entityClass = $entityClass;
	}

	/**
	 * Create an empty object of the entity class bound to this mapper
	 * @phpstan-return EntityType
	 */
	public function createEntity() : Entity {
		return new $this->entityClass();
	}

	/**
	 * Find a single entity by id and user_id
	 * @throws DoesNotExistException if the entity does not exist
	 * @throws MultipleObjectsReturnedException if more than one entity exists
	 * @phpstan-return EntityType
	 */
	public function find(int $id, string $userId) : Entity {
		$sql = $this->selectUserEntities("`{$this->getTableName()}`.`id` = ?");
		return $this->findEntity($sql, [$userId, $id]);
	}

	/**
	 * Find all entities matching the given IDs. Specifying the owning user is optional.
	 * @param integer[] $ids  IDs of the entities to be found
	 * @param string|null $userId
	 * @return Entity[]
	 * @phpstan-return EntityType[]
	 */
	public function findById(array $ids, string $userId=null) : array {
		$count = \count($ids);
		$condition = "`{$this->getTableName()}`.`id` IN ". $this->questionMarks($count);

		if (empty($userId)) {
			$sql = $this->selectEntities($condition);
		} else {
			$sql = $this->selectUserEntities($condition);
			$ids = \array_merge([$userId], $ids);
		}

		return $this->findEntities($sql, $ids);
	}

	/**
	 * Find all user's entities
	 * @param string|null $createdMin Optional minimum `created` timestamp.
	 * @param string|null $createdMax Optional maximum `created` timestamp.
	 * @param string|null $updatedMin Optional minimum `updated` timestamp.
	 * @param string|null $updatedMax Optional maximum `updated` timestamp.
	 * @return Entity[]
	 * @phpstan-return EntityType[]
	 */
	public function findAll(string $userId, int $sortBy=SortBy::None, int $limit=null, int $offset=null,
							?string $createdMin=null, ?string $createdMax=null, ?string $updatedMin=null, ?string $updatedMax=null) : array {
		$sorting = $this->formatSortingClause($sortBy);
		[$condition, $params] = $this->formatTimestampConditions($createdMin, $createdMax, $updatedMin, $updatedMax);
		$sql = $this->selectUserEntities($condition, $sorting);
		\array_unshift($params, $userId);
		return $this->findEntities($sql, $params, $limit, $offset);
	}

	/**
	 * Find all user's entities matching the given name
	 * @param string|null $createdMin Optional minimum `created` timestamp.
	 * @param string|null $createdMax Optional maximum `created` timestamp.
	 * @param string|null $updatedMin Optional minimum `updated` timestamp.
	 * @param string|null $updatedMax Optional maximum `updated` timestamp.
	 * @return Entity[]
	 * @phpstan-return EntityType[]
	 */
	public function findAllByName(
		?string $name, string $userId, bool $fuzzy=false, int $limit=null, int $offset=null,
		?string $createdMin=null, ?string $createdMax=null, ?string $updatedMin=null, ?string $updatedMax=null) : array {

		$nameCol = "`{$this->getTableName()}`.`{$this->nameColumn}`";
		if ($name === null) {
			$condition = "$nameCol IS NULL";
			$params = [$userId];
		} elseif ($fuzzy) {
			$condition = "LOWER($nameCol) LIKE LOWER(?)";
			$params = [$userId, self::prepareFuzzySearchString($name)];
		} else {
			$condition = "LOWER($nameCol) = LOWER(?)";
			$params = [$userId, $name];
		}

		[$timestampConds, $timestampParams] = $this->formatTimestampConditions($createdMin, $createdMax, $updatedMin, $updatedMax);
		if (!empty($timestampConds)) {
			$condition .= ' AND ' . $timestampConds;
			$params = \array_merge($params, $timestampParams);
		}

		$sql = $this->selectUserEntities($condition, "ORDER BY LOWER($nameCol)");

		return $this->findEntities($sql, $params, $limit, $offset);
	}

	/**
	 * Find all user's starred entities
	 * @return Entity[]
	 * @phpstan-return EntityType[]
	 */
	public function findAllStarred(string $userId, int $limit=null, int $offset=null) : array {
		$sql = $this->selectUserEntities(
				"`{$this->getTableName()}`.`starred` IS NOT NULL",
				"ORDER BY LOWER(`{$this->getTableName()}`.`{$this->nameColumn}`)");
		return $this->findEntities($sql, [$userId], $limit, $offset);
	}

	/**
	 * Find IDs of all user's entities of this kind
	 * @return int[]
	 */
	public function findAllIds(string $userId) : array {
		$sql = "SELECT `id` FROM `{$this->getTableName()}` WHERE `user_id` = ?";
		$result = $this->execute($sql, [$userId]);

		return \array_map('intval', $result->fetchAll(\PDO::FETCH_COLUMN));
	}

	/**
	 * Find IDs of all users owning any entities of this mapper
	 * @return string[]
	 */
	public function findAllUsers() : array {
		$sql = "SELECT DISTINCT(`user_id`) FROM `{$this->getTableName()}`";
		$result = $this->execute($sql);

		return $result->fetchAll(\PDO::FETCH_COLUMN);
	}

	/**
	 * Delete all entities with given IDs without specifying the user
	 * @param integer[] $ids  IDs of the entities to be deleted
	 */
	public function deleteById(array $ids) : void {
		$count = \count($ids);
		if ($count === 0) {
			return;
		}
		$this->deleteByCond('`id` IN ' . $this->questionMarks($count), $ids);
	}

	/**
	 * Delete all entities matching the given SQL condition
	 * @param string $condition SQL 'WHERE' condition (without the keyword 'WHERE')
	 * @param array $params SQL parameters for the condition
	 */
	protected function deleteByCond(string $condition, array $params) : void {
		$sql = "DELETE FROM `{$this->getTableName()}` WHERE ". $condition;
		$this->execute($sql, $params);
	}

	/**
	 * Delete all entities of the given user
	 */
	public function deleteAll(string $userId) : void {
		$sql = "DELETE FROM `{$this->getTableName()}` WHERE `user_id` = ?";
		$this->execute($sql, [$userId]);
	}

	/**
	 * Tests if entity with given ID and user ID exists in the database
	 */
	public function exists(int $id, string $userId) : bool {
		$sql = "SELECT 1 FROM `{$this->getTableName()}` WHERE `id` = ? AND `user_id` = ?";
		$result = $this->execute($sql, [$id, $userId]);
		return $result->rowCount() > 0;
	}

	/**
	 * Count all entities of a user
	 */
	public function count(string $userId) : int {
		$sql = "SELECT COUNT(*) AS count FROM `{$this->getTableName()}` WHERE `user_id` = ?";
		$result = $this->execute($sql, [$userId]);
		$row = $result->fetch();
		return \intval($row['count']);
	}

	/**
	 * {@inheritDoc}
	 * @see \OCP\AppFramework\Db\Mapper::insert()
	 * @phpstan-param EntityType $entity
	 * @phpstan-return EntityType
	 */
	public function insert(\OCP\AppFramework\Db\Entity $entity) : \OCP\AppFramework\Db\Entity {
		$now = new \DateTime();
		$nowStr = $now->format(self::SQL_DATE_FORMAT);
		$entity->setCreated($nowStr);
		$entity->setUpdated($nowStr);

		try {
			return parent::insert($entity); // @phpstan-ignore-line: no way to tell phpstan that the parent uses the template type
		} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
			throw new UniqueConstraintViolationException($e->getMessage(), $e->getCode(), $e);
		} catch (\OCP\DB\Exception $e) {
			// Nextcloud 21+
			if ($e->getReason() == \OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw new UniqueConstraintViolationException($e->getMessage(), $e->getCode(), $e);
			} else {
				throw $e;
			}
		}
	}

	/**
	 * {@inheritDoc}
	 * @see \OCP\AppFramework\Db\Mapper::update()
	 * @phpstan-param EntityType $entity
	 * @phpstan-return EntityType
	 */
	public function update(\OCP\AppFramework\Db\Entity $entity) : \OCP\AppFramework\Db\Entity {
		$now = new \DateTime();
		$entity->setUpdated($now->format(self::SQL_DATE_FORMAT));
		return parent::update($entity); // @phpstan-ignore-line: no way to tell phpstan that the parent uses the template type
	}

	/**
	 * Insert an entity, or if an entity with the same identity already exists,
	 * update the existing entity.
	 * Note: The functions insertOrUpate and updateOrInsert get the exactly same thing done. The only difference is
	 * that the former is optimized for cases where the entity doens't exist and the latter for cases where it does exist.
	 * @return Entity The inserted or updated entity, containing also the id field
	 * @phpstan-param EntityType $entity
	 * @phpstan-return EntityType
	 */
	public function insertOrUpdate(Entity $entity) : Entity {
		try {
			return $this->insert($entity);
		} catch (UniqueConstraintViolationException $ex) {
			$existingEntity = $this->findUniqueEntity($entity);
			$entity->setId($existingEntity->getId());
			$entity->setCreated($existingEntity->getCreated());
			return $this->update($entity);
		}
	}

	/**
	 * Update an entity whose unique constraint fields match the given entity. If such entity is not found,
	 * a new entity is inserted.
	 * Note: The functions insertOrUpate and updateOrInsert get the exactly same thing done. The only difference is
	 * that the former is optimized for cases where the entity doens't exist and the latter for cases where it does exist.
	 * @return Entity The inserted or updated entity, containing also the id field
	 * @phpstan-param EntityType $entity
	 * @phpstan-return EntityType
	 */
	public function updateOrInsert(Entity $entity) : Entity {
		try {
			$existingEntity = $this->findUniqueEntity($entity);
			$entity->setId($existingEntity->getId());
			return $this->update($entity);
		} catch (DoesNotExistException $ex) {
			try {
				return $this->insert($entity);
			} catch (UniqueConstraintViolationException $ex) {
				// the conflicting entry didn't exist an eyeblink ago but now it does
				// => this is essentially a concurrent update and it is anyway non-deterministic, which
				//    update happens last; cancel this update
				return $this->findUniqueEntity($entity);
			}
		}
	}

	/**
	 * Set the "starred" column of the given entities
	 * @param \DateTime|null $date
	 * @param integer[] $ids
	 * @param string $userId
	 * @return int number of modified entities
	 */
	public function setStarredDate(?\DateTime $date, array $ids, string $userId) : int {
		$count = \count($ids);
		if (!empty($date)) {
			$date = $date->format(self::SQL_DATE_FORMAT);
		}

		$sql = "UPDATE `{$this->getTableName()}` SET `starred` = ?
				WHERE `id` IN {$this->questionMarks($count)} AND `user_id` = ?";
		$params = \array_merge([$date], $ids, [$userId]);
		return $this->execute($sql, $params)->rowCount();
	}

	public function latestInsertTime(string $userId) : ?\DateTime {
		$sql = "SELECT MAX(`{$this->getTableName()}`.`created`) FROM `{$this->getTableName()}` WHERE `user_id` = ?";
		$result = $this->execute($sql, [$userId]);
		$createdTime = $result->fetch(\PDO::FETCH_COLUMN);

		return ($createdTime === null) ? null : new \DateTime($createdTime);
	}

	public function latestUpdateTime(string $userId) : ?\DateTime {
		$sql = "SELECT MAX(`{$this->getTableName()}`.`updated`) FROM `{$this->getTableName()}` WHERE `user_id` = ?";
		$result = $this->execute($sql, [$userId]);
		$createdTime = $result->fetch(\PDO::FETCH_COLUMN);

		return ($createdTime === null) ? null : new \DateTime($createdTime);
	}

	/**
	 * helper creating a string like '(?,?,?)' with the specified number of elements
	 */
	protected function questionMarks(int $count) : string {
		$questionMarks = [];
		for ($i = 0; $i < $count; $i++) {
			$questionMarks[] = '?';
		}
		return '(' . \implode(',', $questionMarks) . ')';
	}

	/**
	 * Build a SQL SELECT statement which selects all entities of the given user,
	 * and optionally applies other conditions, too.
	 * This is built upon `selectEntities` which may be overridden by the derived class.
	 * @param string|null $condition Optional extra condition. This will get automatically
	 *                               prefixed with ' AND ', so don't include that.
	 * @param string|null $extension Any extension (e.g. ORDER BY, LIMIT) to be added after
	 *                               the conditions in the SQL statement
	 */
	protected function selectUserEntities(string $condition=null, string $extension=null) : string {
		$allConditions = "`{$this->getTableName()}`.`user_id` = ?";

		if (!empty($condition)) {
			$allConditions .= " AND $condition";
		}

		return $this->selectEntities($allConditions, $extension);
	}

	/**
	 * Build a SQL SELECT statement which selects all entities matching the given condition.
	 * The derived class may override this if necessary.
	 * @param string $condition This will get automatically prefixed with ' WHERE '
	 * @param string|null $extension Any extension (e.g. ORDER BY, LIMIT) to be added after
	 *                               the conditions in the SQL statement
	 */
	protected function selectEntities(string $condition, string $extension=null) : string {
		return "SELECT * FROM `{$this->getTableName()}` WHERE $condition $extension ";
	}

	/**
	 * @return array with two values: The SQL condition as string and the SQL parameters as string[]
	 */
	protected function formatTimestampConditions(?string $createdMin, ?string $createdMax, ?string $updatedMin, ?string $updatedMax) : array {
		$conditions = [];
		$params = [];

		if (!empty($createdMin)) {
			$conditions[] = "`{$this->getTableName()}`.`created` >= ?";
			$params[] = $createdMin;
		}

		if (!empty($createdMax)) {
			$conditions[] = "`{$this->getTableName()}`.`created` <= ?";
			$params[] = $createdMax;
		}

		if (!empty($updatedMin)) {
			$conditions[] = "`{$this->getTableName()}`.`updated` >= ?";
			$params[] = $updatedMin;
		}

		if (!empty($updatedMax)) {
			$conditions[] = "`{$this->getTableName()}`.`updated` <= ?";
			$params[] = $updatedMax;
		}

		return [\implode(' AND ', $conditions), $params];
	}

	/**
	 * Convert given sorting condition to an SQL clause. Derived class may overide this if necessary.
	 * @param int $sortBy One of the constants defined in the class SortBy
	 */
	protected function formatSortingClause(int $sortBy) : ?string {
		if ($sortBy == SortBy::Name) {
			return "ORDER BY LOWER(`{$this->getTableName()}`.`{$this->nameColumn}`)";
		} elseif ($sortBy == SortBy::Newest) {
			return "ORDER BY `{$this->getTableName()}`.`id` DESC"; // abuse the fact that IDs are ever-incrementing values
		} else {
			return null;
		}
	}

	protected static function prepareFuzzySearchString(string $input) : string {
		// possibly multiparted query enclosed in quotation marks is handled as a single substing,
		// while the default interpretation of multipart string is that each of the parts can be found
		// separately as substing in the given order
		if (Util::startsWith($input, '"') && Util::endsWith($input, '"')) {
			// remove the quotation
			$pattern = \substr($input, 1, -1);
		} else {
			// split to parts by whitespace
			$parts = \preg_split('/\s+/', $input, -1, PREG_SPLIT_NO_EMPTY);
			// glue the parts back together with a wildcard charater
			$pattern = \implode('%', $parts);
		}
		return "%$pattern%";
	}

	/**
	 * Find an entity which has the same identity as the supplied entity.
	 * How the identity of the entity is defined, depends on the derived concrete class.
	 * @phpstan-param EntityType $entity
	 * @phpstan-return EntityType
	 */
	abstract protected function findUniqueEntity(Entity $entity) : Entity;
}
