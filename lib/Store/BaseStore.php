<?php
declare(strict_types=1);

/**
* @copyright Copyright (c) 2023 Sebastian Krupinski <krupinski01@gmail.com>
*
* @author Sebastian Krupinski <krupinski01@gmail.com>
*
* @license AGPL-3.0-or-later
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as
* published by the Free Software Foundation, either version 3 of the
* License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCA\EAS\Store;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OC\DB\QueryBuilder\Literal;
use OCP\IDBConnection;

class BaseStore {

	protected IDBConnection $_Store;
	protected string $_CollectionTable = '';
	protected string $_CollectionIdentifier = '';
	protected string $_EntityTable = '';
	protected string $_EntityIdentifier = '';
	protected string $_ChronicleTable = '';

	/**
	 * retrieve collections from data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @return array 			of collections
	 */
	public function listCollections(): array {
		
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('*')
			->from($this->_CollectionTable)
			->where($cmd->expr()->eq('type', $cmd->createNamedParameter($this->_CollectionIdentifier)));
		// execute command
		$data = $cmd->executeQuery()->fetchAll();
		$cmd->executeQuery()->closeCursor();
		// return result or empty array
		if (is_array($data) && count($data) > 0) {
			return $data;
		}
		else {
			return [];
		}

	}

	/**
	 * retrieve collections for specific user from data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		user id
	 * 
	 * @return array 			of collections
	 */
	public function listCollectionsByUser(string $uid): array {
		
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('*')
			->from($this->_CollectionTable)
			->where($cmd->expr()->eq('type', $cmd->createNamedParameter($this->_CollectionIdentifier)))
			->andWhere($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)));
		// execute command
		$data = $cmd->executeQuery()->fetchAll();
		$cmd->executeQuery()->closeCursor();
		// return result or empty array
		if (is_array($data) && count($data) > 0) {
			return $data;
		}
		else {
			return [];
		}

	}

	/**
	 * confirm collection exists in data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param int $id			collection id
	 * 
	 * @return int|bool			collection id on success / false on failure
	 */
	public function confirmCollection(string $id): int|bool {
		
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('id')
			->from($this->_CollectionTable)
			->where($cmd->expr()->eq('id', $cmd->createNamedParameter($id)));
		// execute command
		$data = $cmd->executeQuery()->fetch();
		$cmd->executeQuery()->closeCursor();
		// evaluate if anything was found
		if (is_array($data) && count($data) > 0) {
			return (int) $data['id'];
		}
		else {
			return false;
		}

	}

	/**
	 * confirm collection exists in data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		user id
	 * @param string $uuid		collection uuid
	 * 
	 * @return int|bool			collection id on success / false on failure
	 */
	public function confirmCollectionByUUID(string $uid, string $uuid): int|bool {
		
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('id')
			->from($this->_CollectionTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('uuid', $cmd->createNamedParameter($uuid)));
		// execute command
		$data = $cmd->executeQuery()->fetch();
		$cmd->executeQuery()->closeCursor();
		// evaluate if anything was found
		if (is_array($data) && count($data) > 0) {
			return (int) $data['id'];
		}
		else {
			return false;
		}

	}

	/**
	 * retrieve collection from data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param int $id			collection id
	 * 
	 * @return array 			of properties
	 */
	public function fetchCollection(int $id): array {
		
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('*')
			->from($this->_CollectionTable)
			->where($cmd->expr()->eq('id', $cmd->createNamedParameter($id)));
		// execute command
		$data = $cmd->executeQuery()->fetch();
		$cmd->executeQuery()->closeCursor();
		// return result or empty array
		if (is_array($data) && count($data) > 0) {
			return $data;
		}
		else {
			return [];
		}

	}

	/**
	 * retrieve collection from data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		user id
	 * @param string $uuid		collection uuid
	 * 
	 * @return array 			of collections
	 */
	public function fetchCollectionByUUID(string $uid, string $uuid): array {
		
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('*')
			->from($this->_CollectionTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('uuid', $cmd->createNamedParameter($uuid)));
		// execute command
		$data = $cmd->executeQuery()->fetch();
		$cmd->executeQuery()->closeCursor();
		// return result or empty array
		if (is_array($data) && count($data) > 0) {
			return $data;
		}
		else {
			return [];
		}

	}

	/**
	 * create a collection entry in the data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param array $data		collection data
	 * 
	 * @return int				collection id
	 */
	public function createCollection(array $data) : int {

		// force type
		$data['type'] = $this->_CollectionIdentifier;
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->insert($this->_CollectionTable);
		foreach ($data as $column => $value) {
			$cmd->setValue($column, $cmd->createNamedParameter($value));
		}
		// execute command
		$cmd->executeStatement();
		// return result
		return $cmd->getLastInsertId();
		
	}
	
	/**
	 * modify a collection entry in the data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param int $id		collection id
	 * @param array $data		collection data
	 * 
	 * @return bool
	 */
	public function modifyCollection(int $id, array $data) : bool {

		// force type
		$data['type'] = $this->_CollectionIdentifier;
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->update($this->_CollectionTable)
			->where($cmd->expr()->eq('id', $cmd->createNamedParameter($id)));
		foreach ($data as $column => $value) {
			$cmd->set($column, $cmd->createNamedParameter($value));
		}
		// execute command
		$cmd->executeStatement();
		// return result
		return true;
		
	}

	/**
	 * delete a collection entry from the data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param int $id		collection id
	 * 
	 * @return bool
	 */
	public function deleteCollection(int $id) : bool {

		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->delete($this->_CollectionTable)
			->where($cmd->expr()->eq('id', $cmd->createNamedParameter($id)));
		// execute command
		$cmd->executeStatement();
		// return result
		return true;
		
	}

	/**
	 * retrieve entities for specific user from data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		user id
	 * 
	 * @return array 			of entities
	 */
	public function listEntities(string $uid): array {
		
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('*')
			->from($this->_EntityTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)));
		// execute command and return results
		return $this->findEntities($cmd);

	}

	/**
	 * retrieve entities for specific user and collection from data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		user id
	 * @param string $cid		collection id
	 * 
	 * @return array 			of entities
	 */
	public function listEntitiesByCollection(string $uid, string $cid): array {
		
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('*')
			->from($this->_EntityTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('cid', $cmd->createNamedParameter($cid)));;
		// execute command
		$data = $cmd->executeQuery()->fetchAll();
		$cmd->executeQuery()->closeCursor();
		// return result or empty array
		if (is_array($data) && count($data) > 0) {
			return $data;
		}
		else {
			return [];
		}

	}

	/**
	 * retrieve entities for specific user, collection and search parameters from data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		user id
	 * @param string $cid		collection id
	 * @param array $filter		filter options
	 * @param array $elements	data fields
	 * 
	 * @return array 			of entities
	 */
	public function findEntities(string $uid, string $cid, array $filter, array $elements = []): array {
		
		// evaluate if specific elements where requested
		if (!is_array($elements)) {
			$elements = ['*'];
		} 
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select($elements)
			->from($this->_EntityTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('cid', $cmd->createNamedParameter($cid)));
		
		foreach ($filter as $entry) {
			if (is_array($entry) && count($entry) == 3) {
				switch ($entry[1]) {
					case '=':
						$cmd->andWhere($cmd->expr()->eq($entry[0], $cmd->createNamedParameter($entry[2])));
						break;
					case '!=':
						$cmd->andWhere($cmd->expr()->neq($entry[0], $cmd->createNamedParameter($entry[2])));
						break;
					case '>':
						$cmd->andWhere($cmd->expr()->gt($entry[0], $cmd->createNamedParameter($entry[2])));
						break;
					case '>=':
						$cmd->andWhere($cmd->expr()->gte($entry[0], $cmd->createNamedParameter($entry[2])));
						break;
					case '<':
						$cmd->andWhere($cmd->expr()->lt($entry[0], $cmd->createNamedParameter($entry[2])));
						break;
					case '<=':
						$cmd->andWhere($cmd->expr()->lte($entry[0], $cmd->createNamedParameter($entry[2])));
						break;
				}
			}
		}
		// execute command
		$data = $cmd->executeQuery()->fetchAll();
		$cmd->executeQuery()->closeCursor();
		// return result or empty array
		if (is_array($data) && count($data) > 0) {
			return $data;
		}
		else {
			return [];
		}

	}

	/**
	 * delete entities for a specific user from data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		user id
	 * 
	 * @return mixed
	 */
	public function deleteEntitiesByUser(string $uid): mixed {

		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->delete($this->_EntityTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)));
		// execute command and return result
		return $cmd->executeStatement();

	}

	/**
	 * delete entities for a specific user collection from data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		user id
	 * 
	 * @return mixed
	 */
	public function deleteEntitiesByCollection(string $uid, string $cid): mixed {

		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->delete($this->_EntityTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('cid', $cmd->createNamedParameter($cid)));
		// execute command and return result
		return $cmd->executeStatement();

	}

	/**
	 * retrieve entity from data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param int $id		entity id
	 * 
	 * @return array
	 */
	public function fetchEntity(int $id): array {

		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('*')
			->from($this->_EntityTable)
			->where($cmd->expr()->eq('id', $cmd->createNamedParameter($id)));
		// execute command
		$data = $cmd->executeQuery()->fetch();
		$cmd->executeQuery()->closeCursor();
		// return result or empty array
		if (is_array($data) && count($data) > 0) {
			return $data;
		}
		else {
			return [];
		}

	}

	/**
	 * retrieve entity from data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		user id
	 * @param string $uuid		entity uuid
	 *  
	 * @return array
	 */
	public function fetchEntityByUUID(string $uid, string $uuid): array {

		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('*')
			->from($this->_EntityTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('uuid', $cmd->createNamedParameter($uuid)));
		// execute command
		$data = $cmd->executeQuery()->fetch();
		$cmd->executeQuery()->closeCursor();
		// return result or empty array
		if (is_array($data) && count($data) > 0) {
			return $data;
		}
		else {
			return [];
		}
	}

	/**
	 * retrieve entity from data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		user id
	 * @param string $rcid		remote collection id
	 * @param string $reid		remote entitiy id
	 *  
	 * @return array
	 */
	public function fetchEntityByRID(string $uid, string $rcid, string $reid): array {

		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('*')
			->from($this->_EntityTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('rcid', $cmd->createNamedParameter($rcid)))
			->andWhere($cmd->expr()->eq('reid', $cmd->createNamedParameter($reid)));
		// execute command
		$data = $cmd->executeQuery()->fetch();
		$cmd->executeQuery()->closeCursor();
		// return result or empty array
		if (is_array($data) && count($data) > 0) {
			return $data;
		}
		else {
			return [];
		}

	}

	/**
	 * confirm entity exists in data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $id		entity id
	 * 
	 * @return int|bool			entry id on success / false on failure
	 */
	public function confirmEntity(string $id): int|bool {

		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('id')
			->from($this->_EntityTable)
			->where($cmd->expr()->eq('id', $cmd->createNamedParameter($id)));
		// execute command
		$data = $cmd->executeQuery()->fetch();
		$cmd->executeQuery()->closeCursor();
		// evaluate if anything was found
		if (is_array($data) && count($data) > 0) {
			return (int) $data['id'];
		}
		else {
			return false;
		}

	}

	/**
	 * check if entity exists in data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		user id
	 * @param string $uuid		entity uuid
	 * 
	 * @return int|bool			entry id on success / false on failure
	 */
	public function confirmEntityByUUID(string $uid, string $uuid): int|bool {

		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('id')
			->from($this->_EntityTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('uuid', $cmd->createNamedParameter($uuid)));
		// execute command
		$data = $cmd->executeQuery()->fetch();
		$cmd->executeQuery()->closeCursor();
		// evaluate if anything was found
		if (is_array($data) && count($data) > 0) {
			return (int) $data['id'];
		}
		else {
			return false;
		}

	}

	/**
	 * create a entity entry in the data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param array $data		entity data
	 * 
	 * @return int				entity id
	 */
	public function createEntity(array $data) : int {

		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->insert($this->_EntityTable);
		foreach ($data as $column => $value) {
			$cmd->setValue($column, $cmd->createNamedParameter($value));
		}
		// execute command
		$cmd->executeStatement();
		// retreive id
		$id = $cmd->getLastInsertId();
		// chronicle operation
		$this->chronicle($data['uid'], $data['cid'], $id, $data['uuid'], 1);
		// return result
		return (int) $id;
		
	}
	
	/**
	 * modify a entity entry in the data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param int $id			entity id
	 * @param array $data		entity data
	 * 
	 * @return bool
	 */
	public function modifyEntity(int $id, array $data) : bool {

		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->update($this->_EntityTable)
			->where($cmd->expr()->eq('id', $cmd->createNamedParameter($id)));
		foreach ($data as $column => $value) {
			$cmd->set($column, $cmd->createNamedParameter($value));
		}
		// execute command
		$cmd->executeStatement();
		// chronicle operation
		$this->chronicle($data['uid'], $data['cid'], $id, $data['uuid'], 2);
		// return result
		return true;
		
	}

	/**
	 * delete a entity entry from the data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param int $id		entity id
	 * 
	 * @return bool
	 */
	public function deleteEntity(int $id) : bool {

		// retrieve original entity so we can chonicle it later
		$data = $this->fetchEntity($id);
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->delete($this->_EntityTable)
			->where($cmd->expr()->eq('id', $cmd->createNamedParameter($id)));
		// execute command
		$cmd->executeStatement();
		// chronicle operation
		$this->chronicle($data['uid'], $data['cid'], $id, $data['uuid'], 3);
		// return result
		return true;
		
	}

	/**
	 * chronicle a change to an entity to the data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		user id
	 * @param string $cid		collection id
	 * @param string $eid		entity id
	 * @param string $euuid		entity uuid
	 * @param string $operation		operation type (1 - Created, 2 - Modified, 3 - Deleted)
	 * 
	 * @return bool
	 */
	public function chronicle(string $uid, int $cid, int $eid, string $euuid, int $operation) : string {

		// capture current microtime
		$stamp = microtime(true);
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->insert($this->_ChronicleTable);
		$cmd->setValue('uid', $cmd->createNamedParameter($uid));
		$cmd->setValue('tag', $cmd->createNamedParameter($this->_EntityIdentifier));
		$cmd->setValue('cid', $cmd->createNamedParameter($cid));
		$cmd->setValue('eid', $cmd->createNamedParameter($eid));
		$cmd->setValue('euuid', $cmd->createNamedParameter($euuid));
		$cmd->setValue('operation', $cmd->createNamedParameter($operation));
		$cmd->setValue('stamp', $cmd->createNamedParameter($stamp));
		// execute command
		$cmd->executeStatement();
		// return stamp
		return base64_encode((string) $stamp);
		
	}


	/**
	 * reminisce changes to entities in data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		user id
	 * @param int $cid			collection id
	 * @param string $stamp		time stamp
	 * @param int $limit		results limit
	 * @param int $offset		results offset
	 * 
	 * @return bool
	 */
	public function reminisce(string $uid, int $cid, string $stamp, ?int $limit = null, ?int $offset = null) : array {

		// retrieve apex stamp
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select($cmd->func()->max('stamp'))
			->from($this->_ChronicleTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('tag', $cmd->createNamedParameter($this->_EntityIdentifier)))
			->andWhere($cmd->expr()->eq('cid', $cmd->createNamedParameter($cid)));
		$stampApex = $cmd->executeQuery()->fetchOne();
		$cmd->executeQuery()->closeCursor();
		// decode nadir stamp
		$stampNadir = base64_decode($stamp);

		// retrieve additions
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('eid', 'euuid', new Literal('MAX(operation) AS operation'))
			->from($this->_ChronicleTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('tag', $cmd->createNamedParameter($this->_EntityIdentifier)))
			->andWhere($cmd->expr()->eq('cid', $cmd->createNamedParameter($cid)))
			->groupBy('eid');
		// evaluate if valid nadir stamp exists
		if (is_numeric($stampNadir)) {
			$cmd->andWhere($cmd->expr()->gt('stamp', $cmd->createNamedParameter($stampNadir)));
			$cmd->andWhere($cmd->expr()->lte('stamp', $cmd->createNamedParameter($stampApex)));
		}
		// evaluate if limit exists
		if (is_numeric($limit)) {
			$cmd->setMaxResults($limit);
		}
		// evaluate if offset exists
		if (is_numeric($offset)) {
			$cmd->setFirstResult($offset);
		}

		// define place holder
		$chronicle = ['additions' => [], 'modifications' => [], 'deletions' => [], 'stamp' => base64_encode((string) $stampApex)];
		
		// execute command
		$rs = $cmd->executeQuery();
		// process result
		while (($entry = $rs->fetch()) !== false) {
			switch ($entry['operation']) {
				case 1:
					$chronicle['additions'][] = ['id' => $entry['eid'], 'uuid' => $entry['euuid']];
					break;
				case 2:
					$chronicle['modifications'][] = ['id' => $entry['eid'], 'uuid' => $entry['euuid']];
					break;
				case 3:
					$chronicle['deletions'][] = ['id' => $entry['eid'], 'uuid' => $entry['euuid']];
					break;
			}
		}
		$rs->closeCursor();

		// return stamp
		return $chronicle;
		
	}

}
