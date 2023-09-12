<?php
//declare(strict_types=1);

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

namespace OCA\EAS\Service\Remote;

use DateTime;
use Exception;
use Throwable;
use Psr\Log\LoggerInterface;

use OCA\EAS\AppInfo\Application;
use OCA\EAS\Utile\Eas\EasClient;
use OCA\EAS\Utile\Eas\EasXmlEncoder;
use OCA\EAS\Utile\Eas\EasXmlDecoder;
use OCA\EAS\Utile\Eas\EasObject;
use OCA\EAS\Utile\Eas\EasCollection;
use OCA\EAS\Utile\Eas\EasProperty;
use OCA\EAS\Utile\Eas\EasException;
use OCA\EAS\Utile\Eas\EasTypes;

class RemoteCommonService {

	/**
	 * @var LoggerInterface
	 */
	private $logger;
	/**
	 * @var EasXmlEncoder
	 */
	private $_encoder;
	/**
	 * @var EasXmlDecoder
	 */
	private $_decoder;

	/**
	 * Service to make requests to Ews v3 (JSON) API
	 */
	public function __construct (string $appName, LoggerInterface $logger) {

		$this->logger = $logger;
		$this->_encoder = new EasXmlEncoder();
		$this->_decoder = new EasXmlDecoder();

	}

	/**
     * retrieve list of all folders starting with root folder from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage interface
	 * 
	 * @return EasObject|null 			EasObject on success / Null on failure
	 */
	public function provisionInit(EasClient $DataStore, string $model, string $name, string $agent): ?EasObject {
		
		// construct provision command
		$o = new \stdClass();
		$o->Provision = new EasObject('Provision');
		$o->Provision->Policies = new EasObject('Provision');
		$o->Provision->Policies->Policy = new EasObject('Provision');
		$o->Provision->Policies->Policy->PolicyType = new EasProperty('Provision', 'MS-EAS-Provisioning-WBXML');
		$o->Provision->DeviceInformation = new EasObject('Settings');
		$o->Provision->DeviceInformation->Set = new EasObject('Settings');
		$o->Provision->DeviceInformation->Set->Model = new EasProperty('Settings', $model);
		$o->Provision->DeviceInformation->Set->FriendlyName = new EasProperty('Settings', $name);
		$o->Provision->DeviceInformation->Set->UserAgent = new EasProperty('Settings', $agent);
		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performProvision($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);

			switch ($o->Provision->Status->getContents()) {
				case '2':
					throw new Exception("Protocol error.", 2);
					break;
				case '3':
					throw new Exception("General server error.", 3);
					break;
			}

			return $o;
		}
		else {
			// return blank response
			return null;
		}
		
	}

	/**
     * retrieve list of all folders starting with root folder from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage interface
	 * 
	 * @return EasObject|null 			EasObject on success / Null on failure
	 */
	public function provisionAccept(EasClient $DataStore, string $token): ?EasObject {
		
		// construct provision command
		$o = new \stdClass();
		$o->Provision = new EasObject('Provision');
		$o->Provision->Policies = new EasObject('Provision');
		$o->Provision->Policies->Policy = new EasObject('Provision');
		$o->Provision->Policies->Policy->PolicyType = new EasProperty('Provision', 'MS-EAS-Provisioning-WBXML');
		$o->Provision->Policies->Policy->PolicyKey = new EasProperty('Provision', $token);
		$o->Provision->Policies->Policy->Status = new EasProperty('Provision', '1');
		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performProvision($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);

			switch ($o->Provision->Status->getContents()) {
				case '2':
					throw new Exception("Protocol error.", 2);
					break;
				case '3':
					throw new Exception("General server error.", 3);
					break;
			}

			return $o;
		}
		else {
			// return blank response
			return null;
		}
		
	}

	/**
     * retrieve list of all folders starting with root folder from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage interface
	 * @param string $cst				Synchronization State Token
	 * 
	 * @return EasObject|null 			EasObject on success / Null on failure
	 */
	public function syncCollections(EasClient $DataStore, string $cst = '0'): ?EasObject {
		
		// construct command
		$o = new \stdClass();
		$o->CollectionSync = new EasObject('CollectionHierarchy');
		$o->CollectionSync->SyncKey = new EasProperty('CollectionHierarchy', $cst);
		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performCollectionSync($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);

			if ($o->CollectionSync->Status->getContents() != '1' && $o->CollectionSync->Status->getContents() != '142') {
				throw new Exception("CollectionSync: Unknow error occured" . $o->CollectionSync->Status->getContents(), 1);
			}

			// return response message
			return $o;
		}
		else {
			// return blank response
			return null;
		}
		
	}

	/**
     * retrieve all information for specific folder from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * @param string $cid				Collection ID
	 * 
	 * @return EasObject|null 			EasObject on success / Null on failure
	 */
	public function fetchCollection(EasClient $DataStore, string $cid): ?EasObject {
		
		return null;

	}

	/**
     * create collection in remote storage
	 * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * @param string $cht				Collections Hierarchy Synchronization Token
	 * @param string $chl				Collections Hierarchy Location
	 * @param string $name				Collection Name
	 * @param int $type					Collection Type
	 * 
	 * @return EasObject|null			EasObject on success / Null on failure
	 */
	public function createCollection(EasClient $DataStore, string $cht, string $chl, string $name, int $type): ?EasObject {

		// construct command
		$o = new \stdClass();
		$o->CollectionCreate = new EasObject('CollectionHierarchy');
		$o->CollectionCreate->SyncKey = new EasProperty('CollectionHierarchy', $cht);
		$o->CollectionCreate->ParentId = new EasProperty('CollectionHierarchy', $chl);
		$o->CollectionCreate->Name = new EasProperty('CollectionHierarchy', $name);
		$o->CollectionCreate->Type = new EasProperty('CollectionHierarchy', $type);
		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performCollectionCreate($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			// evaluate response status
			if ($o->CollectionCreate->Status->getContents() != '1' && $o->CollectionCreate->Status->getContents() != '110') {
				throw new EasException($o->CollectionCreate->Status->getContents(), 'CC');
			}
			// return response object
			return $o;
		}
		else {
			// return blank response
			return null;
		}

	}

	/**
     * update collection in remote storage
	 * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * @param string $cht				Collections Hierarchy Synchronization Token
	 * @param string $chl				Collections Hierarchy Location
	 * @param string $cid				Collection Id
	 * @param string $name				Collection Name
	 * 
	 * @return EasObject|null			EasObject on success / Null on failure
	 */
	public function updateCollection(EasClient $DataStore, string $cht, string $chl, string $cid, string $name): ?EasObject {

		// construct command
		$o = new \stdClass();
		$o->CollectionUpdate = new EasObject('CollectionHierarchy');
		$o->CollectionUpdate->SyncKey = new EasProperty('CollectionHierarchy', $cht);
		$o->CollectionUpdate->Id = new EasProperty('CollectionHierarchy', $cid);
		$o->CollectionUpdate->ParentId = new EasProperty('CollectionHierarchy', $chl);
		$o->CollectionUpdate->Name = new EasProperty('CollectionHierarchy', $name);
		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performCollectionUpdate($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			// evaluate response status
			if ($o->CollectionUpdate->Status->getContents() != '1' && $o->CollectionUpdate->Status->getContents() != '110') {
				throw new EasException($o->CollectionUpdate->Status->getContents(), 'CU');
			}
			// return response object
			return $o;
		}
		else {
			// return blank response
			return null;
		}

	}

	/**
     * delete collection from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * @param string $cht				Collections Hierarchy Synchronization Token
	 * @param string $cid				Collection Id
	 * 
	 * @return bool 					True on success / Null on failure
	 */
	public function deleteCollection(EasClient $DataStore, string $cht, string $cid): ?bool {
		
		// construct command
		$o = new \stdClass();
		$o->CollectionDelete = new EasObject('CollectionHierarchy');
		$o->CollectionDelete->SyncKey = new EasProperty('CollectionHierarchy', $cht);
		$o->CollectionDelete->Id = new EasProperty('CollectionHierarchy', $cid);
		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performCollectionDelete($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			// evaluate response status
			if ($o->CollectionDelete->Status->getContents() != '1' && $o->CollectionDelete->Status->getContents() != '110') {
				throw new EasException($o->CollectionDelete->Status->getContents(), 'CD');
			}
			// return response object
			return true;
		}
		else {
			// return blank response
			return null;
		}

	}

	/**
     * retrieve list of entities for specific folder from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * @param string $cid				Collection Id
	 * @param string $cst				Collections Synchronization Token
	 * 
	 * @return EasObject|null 			EasObject on success / Null on failure
	 */
	public function syncEntities(EasClient $DataStore, string $cst, string $cid, array $options): ?EasObject {
		
		// construct Sync request
		$o = new \stdClass();
		$o->Sync = new EasObject('AirSync');
		$o->Sync->Collections = new EasObject('AirSync');
		$o->Sync->Collections->Collection = new EasObject('AirSync');
		$o->Sync->Collections->Collection->SyncKey = new EasProperty('AirSync', $cst);
		$o->Sync->Collections->Collection->CollectionId = new EasProperty('AirSync', $cid);

		if (isset($options['SUPPORTED']) && $options['SUPPORTED'] == true) {
			$o->Sync->Collections->Collection->Supported = new EasObject('AirSync');
			$o->Sync->Collections->Collection->Supported->Picture = new EasProperty('Contacts', null);
		}
		if (isset($options['CHANGES'])) {
			$o->Sync->Collections->Collection->GetChanges = new EasProperty('AirSync', $options['CHANGES']);
		}
		if (isset($options['LIMIT'])) {
			$o->Sync->Collections->Collection->WindowSize = new EasProperty('AirSync', $options['LIMIT']);
		}
		if (isset($options['FILTER']) && isset($options['MIME'])) {
			$o->Sync->Collections->Collection->Options = new EasObject('AirSync');

			if (isset($options['FILTER'])) {
				$o->Sync->Collections->Collection->Options->FilterType = new EasProperty('AirSync', $options['FILTER']);
			}
			if (isset($options['MIME'])) {
				$o->Sync->Collections->Collection->Options->MIMESupport = new EasProperty('AirSync', 2);
				$o->Sync->Collections->Collection->Options->MIMETruncation = new EasProperty('AirSync', 8);
				$o->Sync->Collections->Collection->Options->BodyPreference = new EasObject('AirSyncBase');
				$o->Sync->Collections->Collection->Options->BodyPreference->Type = new EasProperty('AirSyncBase', 4);
				$o->Sync->Collections->Collection->Options->BodyPreference->AllOrNone = new EasProperty('AirSyncBase', 1);
			}
		}

		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performSync($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			// evaluate response status
			if ($o->Sync->Collections->Collection->Status->getContents() != '1') {
				throw new EasException($o->Collections->Collection->Status->getContents(), 'CS');
			}
			// return response object
			return $o;
		}
		else {
			// return blank response
			return null;
		}

	}

	/**
     * retrieve list of entities for specific folder from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * @param array $collections		Collections List (cid, cst)
	 * 
	 * @return EasObject|null 			EasObject on success / Null on failure
	 */
	public function syncEntitiesVarious(EasClient $DataStore, array $collections, array $options): ?EasObject {
		
		// construct Sync request
		$o = new \stdClass();
		$o->Sync = new EasObject('AirSync');
		$o->Sync->Collections = new EasObject('AirSync');
		$o->Sync->Collections->Collection = new EasCollection('AirSync');
		
		foreach ($collections as $entry) {
			if (isset($entry['cid']) && isset($entry['cst'])) {

				$c = new EasObject('AirSync');
				$c->SyncKey = new EasProperty('AirSync', $entry['cst']);
				$c->CollectionId = new EasProperty('AirSync', $entry['cid']);
				
				$o->Sync->Collections->Collection[] = $c;

			}
		}

		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performSync($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);

			// TODO: Add error checking

			// return response object
			return $o;
		}
		else {
			// return blank response
			return null;
		}

	}

	/**
     * retrieve list of all folders starting with root folder from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * @param string $cid				Collection Id
	 * @param string $cst				Collections Synchronization Token
	 * 
	 * @return EasObject|null 			EasObject on success / Null on failure
	 */
	public function estimateEntities(EasClient $DataStore, string $cst, string $cid): ?EasObject {
	
		// construct EntityEstimate request
		$o = new \stdClass();
		$o->EntityEstimate = new EasObject('EntityEstimate');
		$o->EntityEstimate->Collections = new EasObject('EntityEstimate');
		
		$o->EntityEstimate->Collections->Collection = new EasObject('EntityEstimate');
		$o->EntityEstimate->Collections->Collection->SyncKey = new EasProperty('AirSync', $cst);
		$o->EntityEstimate->Collections->Collection->CollectionId = new EasProperty('EntityEstimate', $cid);

		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performEntityEstimate($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			
			// return response message
			return $o;
		}
		else {
			// return blank response
			return null;
		}
		
	}

	/**
     * retrieve list of all folders starting with root folder from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * @param array $collections		Collections List (cid, cst)
	 * 
	 * @return EasObject|null 			EasObject on success / Null on failure
	 */
	public function estimateEntitiesVarious(EasClient $DataStore, array $collections): ?EasObject {
	
		// construct EntityEstimate request
		$o = new \stdClass();
		$o->EntityEstimate = new EasObject('EntityEstimate');
		$o->EntityEstimate->Collections = new EasObject('EntityEstimate');
		$o->EntityEstimate->Collections->Collection = new EasCollection('EntityEstimate');
		
		foreach ($collections as $entry) {
			if (isset($entry['cid']) && isset($entry['cst'])) {
				$c = new EasObject('EntityEstimate');
				$c->SyncKey = new EasProperty('AirSync', $entry['cst']);
				$c->CollectionId = new EasProperty('EntityEstimate', $entry['cid']);
				$o->EntityEstimate->Collections->Collection[] = $c;
			}
		}
		
		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performEntityEstimate($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			
			// TODO: Add error checking
			
			// return response message
			return $o;
		}
		else {
			// return blank response
			return null;
		}
		
	}

	/**
     * retrieve information for specific item from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $uuid - Item UUID
	 * @param string $fid - Collection ID
	 * @param string $ftype - Collection ID Type (True - Distinguished / False - Normal)
	 * @param string $base - Base Properties / D - Default / A - All / I - ID's
	 * @param object $additional - Additional Properties object of NonEmptyArrayOfPathsToElementType
	 * 
	 * @return EasObject|null 			EasObject on success / Null on failure
	 */
	public function findEntities(EasClient $DataStore, string $fid, object $restriction, bool $ftype = false, string $base = 'D', object $additional = null): ?EasObject {
		
		return null;

	}

	/**
     * retrieve all information for specific entity from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * @param string $cid				Collection Id
	 * @param string $cst				Collection Synchronization Token
	 * @param string $eid				Entity Id
	 * 
	 * @return EasObject|null 			EasObject on success / Null on failure
	 */
	public function fetchEntity(EasClient $DataStore, string $cid, string $cst, string $eid): ?EasObject {
		
		// construct Entityoperation request
		/*
		$o = new \stdClass();
		$o->EntityOperations = new EasObject('EntityOperations');
		$o->EntityOperations->Fetch = new EasObject('EntityOperations');
		$o->EntityOperations->Fetch->Store = new EasProperty('EntityOperations', 'Mailbox');
		$o->EntityOperations->Fetch->CollectionId = new EasProperty('AirSync', $cid);
		$o->EntityOperations->Fetch->EntityId = new EasProperty('AirSync', $eid);
		*/

		// construct Sync request
		$o = new \stdClass();
		$o->Sync = new EasObject('AirSync');
		$o->Sync->Collections = new EasObject('AirSync');
		$o->Sync->Collections->Collection = new EasObject('AirSync');
		$o->Sync->Collections->Collection->SyncKey = new EasProperty('AirSync', $cst);
		$o->Sync->Collections->Collection->CollectionId = new EasProperty('AirSync', $cid);
		$o->Sync->Collections->Collection->GetChanges = new EasProperty('AirSync', '0');
		$o->Sync->Collections->Collection->Commands = new EasObject('AirSync');
		$o->Sync->Collections->Collection->Commands->Fetch = new EasObject('AirSync');
		$o->Sync->Collections->Collection->Commands->Fetch->EntityId = new EasProperty('AirSync', $eid);

		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performSync($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			
			// return response message
			return $o;
		}
		else {
			// return blank response
			return null;
		}

	}

	/**
     * create entity in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * @param string $cid				Collection Id
	 * @param string $cst				Collection Synchronization Token
	 * @param string $ec				Entity Type
	 * @param EasObject $ed				Entity Object
	 * 
	 * @return EasObject|null 			EasObject on success / Null on failure
	 */
	public function createEntity(EasClient $DataStore, string $cid, string $cst, string $ec, EasObject $ed): ?EasObject {
		
		// construct Sync request
		$o = new \stdClass();
		$o->Sync = new EasObject('AirSync');
		$o->Sync->Collections = new EasObject('AirSync');
		$o->Sync->Collections->Collection = new EasObject('AirSync');
		$o->Sync->Collections->Collection->SyncKey = new EasProperty('AirSync', $cst);
		$o->Sync->Collections->Collection->CollectionId = new EasProperty('AirSync', $cid);
		$o->Sync->Collections->Collection->GetChanges = new EasProperty('AirSync', '0');
		$o->Sync->Collections->Collection->Options = new EasObject('AirSync');
		$o->Sync->Collections->Collection->Options->BodyPreference = new EasObject('AirSyncBase');
		$o->Sync->Collections->Collection->Options->BodyPreference->Type = new EasProperty('AirSyncBase', 2);
		$o->Sync->Collections->Collection->Commands = new EasObject('AirSync');
		$o->Sync->Collections->Collection->Commands->Add = new EasObject('AirSync');
		$o->Sync->Collections->Collection->Commands->Add->Class = new EasProperty('AirSync', $ec);
		$o->Sync->Collections->Collection->Commands->Add->ClientId = new EasProperty('AirSync', \OCA\EAS\Utile\UUID::v4());
		$o->Sync->Collections->Collection->Commands->Add->Data = $ed;

		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performSync($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			
			// return response message
			return $o;
		}
		else {
			// return blank response
			return null;
		}

	}

	/**
     * update item in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * @param string $cid				Collection Id
	 * @param string $cst				Collection Synchronization Token
	 * @param string $eid				Entity Id
	 * @param EasObject $ed				Entity Object
	 * 
	 * @return EasObject|null 			EasObject on success / Null on failure
	 */
	public function updateEntity(EasClient $DataStore, string $cid, string $cst, string $eid, object $data): ?EasObject {
		
		// construct Sync request
		$o = new \stdClass();
		$o->Sync = new EasObject('AirSync');
		$o->Sync->Collections = new EasObject('AirSync');
		$o->Sync->Collections->Collection = new EasObject('AirSync');
		$o->Sync->Collections->Collection->SyncKey = new EasProperty('AirSync', $state);
		$o->Sync->Collections->Collection->CollectionId = new EasProperty('AirSync', $cid);
		$o->Sync->Collections->Collection->GetChanges = new EasProperty('AirSync', '0');
		$o->Sync->Collections->Collection->Commands = new EasObject('AirSync');
		$o->Sync->Collections->Collection->Commands->Modify = new EasObject('AirSync');
		$o->Sync->Collections->Collection->Commands->Modify->EntityId = new EasProperty('AirSync', $eid);
		$o->Sync->Collections->Collection->Commands->Modify->Data = $data;

		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performSync($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			
			// return response message
			return $o;
		}
		else {
			// return blank response
			return null;
		}

	}

	/**
     * delete item in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * @param string $cid				Collection Id
	 * @param string $cst				Collection Synchronization Token
	 * @param string $eid				Entity Id
	 * 
	 * @return EasObject|null 			True on success / Null on failure
	 */
	public function deleteEntity(EasClient $DataStore, string $cid, string $cst, string $eid): ?bool {
		
		// construct Sync request
		$o = new \stdClass();
		$o->Sync = new EasObject('AirSync');
		$o->Sync->Collections = new EasObject('AirSync');
		$o->Sync->Collections->Collection = new EasObject('AirSync');
		$o->Sync->Collections->Collection->SyncKey = new EasProperty('AirSync', $cst);
		$o->Sync->Collections->Collection->CollectionId = new EasProperty('AirSync', $cid);
		$o->Sync->Collections->Collection->DeletesAsMoves = new EasProperty('AirSync', '0');
		$o->Sync->Collections->Collection->GetChanges = new EasProperty('AirSync', '0');
		$o->Sync->Collections->Collection->Commands = new EasObject('AirSync');
		$o->Sync->Collections->Collection->Commands->Delete = new EasObject('AirSync');
		$o->Sync->Collections->Collection->Commands->Delete->EntityId = new EasProperty('AirSync', $eid);

		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performSync($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			
			// return response message
			return true;
		}
		else {
			// return blank response
			return null;
		}

	}

	/**
     * retrieve item attachment(s) from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $ids - Attachement ID's (array)
	 * 
	 * @return object Attachement Collection Object on success / Null on failure
	 */
	public function fetchAttachment(EasClient $DataStore, array $batch): ?array {
		
		return null;

	}

	/**
     * create item attachment(s) from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param array $batch - Collection of FileAttachmentType Objects
	 * 
	 * @return object Attachement Collection Object on success / Null on failure
	 */
	public function createAttachment(EasClient $DataStore, string $iid, array $batch): array {

		return null;

	}

	/**
     * delete item attachment(s) from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param array $batch - Collection of String Attachemnt Id(s)
	 * 
	 * @return object Attachement Collection Object on success / Null on failure
	 */
	public function deleteAttachment(EasClient $DataStore, array $batch): array {
		
		return null;

	}

	/**
     * retrieve time zone information from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $uuid - Item UUID
	 * @param string $fid - Collection ID
	 * @param string $ftype - Collection ID Type (True - Distinguished / False - Normal)
	 * 
	 * @return object Item Object on success / Null on failure
	 */
	public function fetchTimeZone(EasClient $DataStore, string $zone = null): ?object {
		
		return null;

	}

	/**
     * connect to event nofifications
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * 
	 * @return object Items Object on success / Null on failure
	 */
	public function connectEvents(EasClient $DataStore, int $duration, array $ids = null, array $dids = null, array $types = null): ?object {
		
		return null;

	}

	/**
     * disconnect from event nofifications
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * 
	 * @return object Items Object on success / Null on failure
	 */
	public function disconnectEvents(EasClient $DataStore, string $id): ?bool {
		
		return null;

	}

	/**
     * observe event nofifications
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * 
	 * @return object Items Object on success / Null on failure
	 */
	public function fetchEvents(EasClient $DataStore, string $id, string $token): ?object {
		
		return null;

	}

}
