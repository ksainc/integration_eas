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
		$o->Provision->Device = new EasObject('Settings');
		$o->Provision->Device->Set = new EasObject('Settings');
		$o->Provision->Device->Set->Model = new EasProperty('Settings', $model);
		$o->Provision->Device->Set->FriendlyName = new EasProperty('Settings', $name);
		$o->Provision->Device->Set->UserAgent = new EasProperty('Settings', $agent);
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

			return $o->Provision;
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

			return $o->Provision;
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
			// evaluate response status
			if ($o->CollectionSync->Status->getContents() != '1' && $o->CollectionSync->Status->getContents() != '142') {
				throw new Exception("CollectionSync: Unknow error occured" . $o->CollectionSync->Status->getContents(), 1);
			}
			// return response message
			return $o->CollectionSync;
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
			return $o->CollectionCreate;
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
			return $o->CollectionUpdate;
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
	 * @return EasObject|null			EasObject on success / Null on failure
	 */
	public function deleteCollection(EasClient $DataStore, string $cht, string $cid): ?EasObject {
		
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
			return $o->CollectionDelete;
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
		if (isset($options['FILTER']) && isset($options['BODY'])) {
			$o->Sync->Collections->Collection->Options = new EasObject('AirSync');

			if (isset($options['FILTER'])) {
				$o->Sync->Collections->Collection->Options->FilterType = new EasProperty('AirSync', $options['FILTER']);
			}
			if (isset($options['BODY'])) {
				if ($options['BODY'] == EasTypes::BODY_TYPE_MIME) {
					$o->Sync->Collections->Collection->Options->MIMESupport = new EasProperty('AirSync', 2);
					$o->Sync->Collections->Collection->Options->MIMETruncation = new EasProperty('AirSync', 8);
				}
				$o->Sync->Collections->Collection->Options->BodyPreference = new EasObject('AirSyncBase');
				$o->Sync->Collections->Collection->Options->BodyPreference->Type = new EasProperty('AirSyncBase', $options['BODY']);
				$o->Sync->Collections->Collection->Options->BodyPreference->AllOrNone = new EasProperty('AirSyncBase', 1);
			}
		}

		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performEntitySync($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			// evaluate response status
			if ($o->Sync->Collections->Collection->Status->getContents() != '1') {
				throw new EasException($o->Collections->Collection->Status->getContents(), 'ES');
			}
			// return response object
			return $o->Sync->Collections->Collection;
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
	 * @return array|null 				Array on success / Null on failure
	 */
	public function syncEntitiesVarious(EasClient $DataStore, array $collections, array $options): ?array {
		
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
		$rs = $DataStore->performEntitySync($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			// evaluate response status
			if (isset($o->Sync->Status) && $o->Sync->Status->getContents() != '1') {
				throw new EasException($o->Sync->Status->getContents(), 'ES');
			}
			// evaluate, if response returned an array
			if (!is_array($o->Sync->Collections->Collection)) {
				// return response array
				return [$o->Sync->Collections->Collection];
			}
			else {
				// return response array
				return $o->Sync->Collections->Collection;
			}
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
			// evaluate response status
			if (isset($o->EntityEstimate->Status) && $o->EntityEstimate->Status->getContents() != '1') {
				throw new EasException($o->EntityEstimate->Status->getContents(), 'ES');
			}
			// return response message
			return $o->EntityEstimate->Response;
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
	 * @return array|null 				Array on success / Null on failure
	 */
	public function estimateEntitiesVarious(EasClient $DataStore, array $collections): ?array {
	
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
			// evaluate response status
			if (isset($o->EntityEstimate->Status) && $o->EntityEstimate->Status->getContents() != '1') {
				throw new EasException($o->EntityEstimate->Status->getContents(), 'ES');
			}
			// evaluate, if response returned an array
			if (!is_array($o->EntityEstimate->Response)) {
				// return response array
				return [$o->EntityEstimate->Response];
			}
			else {
				// return response array
				return $o->EntityEstimate->Response;
			}
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
	 * @param string $value				Search value 
	 * 
	 * @return EasObject|null 			EasObject on success / Null on failure
	 */
	public function searchEntities(EasClient $DataStore, string $cid, string $query, array $options = []): ?EasObject {
		
		// construct EntityEstimate request
		$o = new \stdClass();
		$o->Search = new EasObject('Search');
		$o->Search->Store = new EasObject('Search');
		// evaluate, if store option is present
		if (isset($options['STORE']) && $options['STORE'] == 'GAL') {
			$o->Search->Store->Name = new EasProperty('Search', 'GAL');
			$o->Search->Store->Query = new EasProperty('Search', $query);
		}
		else {
			$o->Search->Store->Name = new EasProperty('Search', 'Mailbox');
			$o->Search->Store->Query = new EasObject('Search');
			$o->Search->Store->Query->And = new EasObject('Search');
			// evaluate, if category option is present
			if (isset($options['CATEGORY']) && $options['CATEGORY'] == 'CLS') {
				$o->Search->Store->Query->And->Class = new EasProperty('AirSync', $cid);
				$options['BROAD'] = true;
			}
			elseif (isset($options['CATEGORY']) && $options['CATEGORY'] == 'CVS') {
				$o->Search->Store->Query->And->ConversationId = new EasProperty('AirSync', $cid);
			}
			else {
				$o->Search->Store->Query->And->CollectionId = new EasProperty('AirSync', $cid);
			}
			$o->Search->Store->Query->And->FreeText = new EasProperty('Search', $query);
		}

		$o->Search->Store->Options = new EasObject('Search');
		$o->Search->Store->Options->RebuildResults = new EasProperty('Search', null);
		// evaluate, if range option is present
		if (isset($options['RANGE'])) {
			$o->Search->Store->Options->Range = new EasProperty('Search', $options['RANGE']);
		}
		else {
			$o->Search->Store->Options->Range = new EasProperty('Search', '0-99');
		}
		// evaluate, if broad option is present
		if (isset($options['BROAD']) && $options['BROAD'] == true) {
			$o->Search->Store->Options->DeepTraversal = new EasProperty('Search', null);
		}
		// evaluate, if body option is present
		if (isset($options['BODY'])) {
			if ($options['BODY'] == EasTypes::BODY_TYPE_MIME) {
				$o->Search->Store->Options->MIMESupport = new EasProperty('AirSync', 2);
			}
			$o->Search->Store->Options->BodyPreference = new EasObject('AirSyncBase');
			$o->Search->Store->Options->BodyPreference->Type = new EasProperty('AirSyncBase', $options['BODY']);
			$o->Search->Store->Options->BodyPreference->AllOrNone = new EasProperty('AirSyncBase', 1);
		}

		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performEntitySearch($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			// evaluate response status
			if (isset($o->Search->Status) && $o->Search->Status->getContents() != '1') {
				throw new EasException($o->Search->Status->getContents(), 'FN');
			}
			// return response message
			return $o->Search->Response->Store;
		}
		else {
			// return blank response
			return null;
		}

	}

	/**
     * retrieve all information for specific entity from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * @param string $cid				Collection Id
	 * @param string $eid				Entity Id
	 * 
	 * @return EasObject|null 			EasObject on success / Null on failure
	 */
	public function fetchEntity(EasClient $DataStore, string $cid, string $eid, array $options = []): ?EasObject {
		
		// construct Entityoperation request
		$o = new \stdClass();
		$o->EntityOperations = new EasObject('EntityOperations');
		$o->EntityOperations->Fetch = new EasObject('EntityOperations');
		$o->EntityOperations->Fetch->Store = new EasProperty('EntityOperations', 'Mailbox');
		$o->EntityOperations->Fetch->EntityId = new EasProperty('AirSync', $eid);
		$o->EntityOperations->Fetch->CollectionId = new EasProperty('AirSync', $cid);

		if (isset($options['BODY'])) {
			$o->EntityOperations->Fetch->Options = new EasObject('EntityOperations');
			if ($options['BODY'] == EasTypes::BODY_TYPE_MIME) {
				$o->EntityOperations->Fetch->Options->MIMESupport = new EasProperty('AirSync', 2);
				//$o->EntityOperations->Fetch->Options->MIMETruncation = new EasProperty('AirSync', 8);
			}
			$o->EntityOperations->Fetch->Options->BodyPreference = new EasObject('AirSyncBase');
			$o->EntityOperations->Fetch->Options->BodyPreference->Type = new EasProperty('AirSyncBase', $options['BODY']);
			$o->EntityOperations->Fetch->Options->BodyPreference->AllOrNone = new EasProperty('AirSyncBase', 1);
		}
		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performEntityOperation($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			// evaluate response status
			if (isset($o->EntityOperations->Status) && $o->EntityOperations->Status->getContents() != '1') {
				throw new EasException($o->EntityOperations->Status->getContents(), 'EF');
			}
			// return response message
			return $o->EntityOperations->Response->Fetch;
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
		$rs = $DataStore->performEntitySync($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			// evaluate response status
			if (isset($o->Sync->Status) && $o->Sync->Status->getContents() != '1') {
				throw new EasException($o->Sync->Status->getContents(), 'EC');
			}
			// return response message
			return $o->Sync->Collections->Collection;
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
		$rs = $DataStore->performEntitySync($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			// evaluate response status
			if (isset($o->Sync->Status) && $o->Sync->Status->getContents() != '1') {
				throw new EasException($o->Sync->Status->getContents(), 'EU');
			}
			// return response message
			return $o->Sync->Collections->Collection;
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
		$rs = $DataStore->performEntitySync($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			// evaluate response status
			if (isset($o->Sync->Status) && $o->Sync->Status->getContents() != '1') {
				throw new EasException($o->Sync->Status->getContents(), 'ED');
			}
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

	/**
     * retrieve settings from remote storage
	 * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * 
	 * @return EasObject|null			EasObject on success / Null on failure
	 */
	public function retrieveSettings(EasClient $DataStore, string $class): ?EasObject {

		// construct command
		$o = new \stdClass();
		$o->Settings = new EasObject('Settings');
		$o->Settings->$class = new EasObject('Settings');
		$o->Settings->$class->Get = new EasObject('Settings', null);
		// evaluate settings class
		if ($class == 'Oof') {
			$o->Settings->$class->Get->BodyType = new EasProperty('Settings', 'Text');
		}
		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performSettings($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			// evaluate response status
			if ($o->Settings->Status->getContents() != '1' && $o->Settings->Status->getContents() != '110') {
				throw new EasException($o->Settings->Status->getContents(), 'ST');
			}
			// return response object
			return $o->Settings->$class->Get;
		}
		else {
			// return blank response
			return null;
		}

	}
	
}
