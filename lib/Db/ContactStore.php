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

namespace OCA\EAS\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class ContactStore {

	private IDBConnection $_Store;
	private string $_CollectionTable = 'eas_collections';
	private string $_CollectionIdentifier = 'CC';
	private string $_EntityTable = 'eas_entities_contact';

	public function __construct(IDBConnection $db) {
		$this->_Store = $db;
	}

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
	 * @param string $id		collection id
	 * 
	 * @return bool
	 */
	public function confirmCollection(string $id): bool {
		
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
			return true;
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
	 * @param string $uri		collection uri
	 * 
	 * @return bool
	 */
	public function confirmCollectionByURI(string $uid, string $uri): bool {
		
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('id')
			->from($this->_CollectionTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('uri', $cmd->createNamedParameter($uri)));
		// execute command
		$data = $cmd->executeQuery()->fetch();
		$cmd->executeQuery()->closeCursor();
		// evaluate if anything was found
		if (is_array($data) && count($data) > 0) {
			return true;
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
	 * @param string $id		collection id
	 * 
	 * @return array 			of fields
	 */
	public function fetchCollection(string $id): array {
		
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
	 * @param string $uri		collection uri
	 * 
	 * @return array 			of collections
	 */
	public function fetchCollectionByURI(string $uid, string $uri): array {
		
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('*')
			->from($this->_CollectionTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('uri', $cmd->createNamedParameter($uri)));
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
	 * @return bool
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
	 * @param string $id		collection id
	 * @param array $data		collection data
	 * 
	 * @return bool
	 */
	public function modifyCollection(string $id, array $data) : bool {

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
	 * @param string $id		collection id
	 * 
	 * @return bool
	 */
	public function deleteCollection(string $id) : bool {

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
		$cmd = $this->db->getQueryBuilder();
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
	 * @param string $id		entity id
	 * 
	 * @return array
	 */
	public function fetchEntity(string $id): array {

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
	 * @param string $uri		entity uri
	 *  
	 * @return array
	 */
	public function fetchEntityByURI(string $uid, string $uri): array {

		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('*')
			->from($this->_EntityTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('uri', $cmd->createNamedParameter($uri)));
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
	 * @return bool
	 */
	public function confirmEntity(string $id): bool {

		// retrieve entry
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('*')
			->from($this->_EntityTable)
			->where($cmd->expr()->eq('id', $cmd->createNamedParameter($id)));
		// execute command and return results
		$entry = $this->findEntity($cmd);
		// evaluate, if and entry was retrieved
		if (count($entry) > 0) {
			return true;
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
	 * @return bool
	 */
	public function confirmEntityByUUID(string $uid, string $uuid): bool {

		// retrieve entry
		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->select('*')
			->from($this->_EntityTable)
			->where($cmd->expr()->eq('uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('uuid', $cmd->createNamedParameter($uuid)));
		// execute command and return results
		$entry = $this->findEntities($cmd);
		// evaluate, if and entry was retrieved
		if (count($entry) > 0) {
			return true;
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
	 * @return bool
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
		// return result
		return $cmd->getLastInsertId();
		
	}
	
	/**
	 * modify a entity entry in the data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $id		entity id
	 * @param array $data		entity data
	 * 
	 * @return bool
	 */
	public function modifyEntity(string $id, array $data) : bool {

		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->update($this->_EntityTable)
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
	 * delete a entity entry from the data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $id		entity id
	 * 
	 * @return bool
	 */
	public function deleteEntity(string $id) : bool {

		// construct data store command
		$cmd = $this->_Store->getQueryBuilder();
		$cmd->delete($this->_EntityTable)
			->where($cmd->expr()->eq('id', $cmd->createNamedParameter($id)));
		// execute command
		$cmd->executeStatement();
		// return result
		return true;
		
	}

}
