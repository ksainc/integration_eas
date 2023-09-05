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
use OCA\EAS\Utile\Eas\EasProperty;


class RemoteCommonService {

	const CONTACTS_COLLECTION_TYPE = '9';
	const CALENDAR_COLLECTION_TYPE = '8';
	const TASKS_COLLECTION_TYPE = '7';
	const NOTES_COLLECTION_TYPE = '10';

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
	 * @param EasClient $DataStore		Storage Interface
	 * 
	 * @return object Folder List Object on success / Null on failure
	 */
	public function provisionInit(EasClient $DataStore, string $model, string $name, string $agent): ?object {
		
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
			$o = $o->Message;

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
	 * @param EasClient $DataStore		Storage Interface
	 * 
	 * @return object Folder List Object on success / Null on failure
	 */
	public function provisionAccept(EasClient $DataStore, string $token): ?object {
		
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
			$o = $o->Message;

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
	 * @param EasClient $DataStore		Storage Interface
	 * 
	 * @return object Folder List Object on success / Null on failure
	 */
	public function fetchFolders(EasClient $DataStore): ?object {
		
		// construct command
		$o = new \stdClass();
		$o->FolderSync = new EasObject('FolderHierarchy');
		$o->FolderSync->SyncKey = new EasProperty('FolderHierarchy', '0');
		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performFolderSync($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			// return response message
			return (object) ['Count' => $o->Message->FolderSync->Changes->Count,'Collections' => $o->Message->FolderSync->Changes->Add];
		}
		else {
			// return blank response
			return null;
		}
		
	}

	/**
     * retrieve list of specific folders starting with root folder from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $type - Folder Type
	 * @param string $base - Base Properties / D - Default / A - All / I - ID's
	 * @param object $additional - Additional Properties object of NonEmptyArrayOfPathsToElementType
	 * 
	 * @return object Folder Object on success / Null on failure
	 */
	public function fetchFoldersByType(EasClient $DataStore, string $type, string $base = 'D', object $additional = null, string $source = 'U'): ?object {
		
		return null;

	}

	/**
     * retrieve all information for specific folder from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $fid - Folder ID
	 * @param string $ftype - Folder ID Type (True - Distinguished / False - Normal)
	 * @param string $base - Base Properties / D - Default / A - All / I - ID's
	 * @param object $additional - Additional Properties object of NonEmptyArrayOfPathsToElementType
	 * 
	 * @return object Folder Object on success / Null on failure
	 */
	public function fetchFolder(EasClient $DataStore, string $fid, bool $ftype = false, string $base = 'D', object $additional = null): ?object {
		
		return null;

	}

	/**
     * create folder in remote storage
	 * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $fid - Folder ID
	 * @param string $fid - Item Data
	 * 
	 * @return object Folders Object on success / Null on failure
	 */
	public function createFolder(EasClient $DataStore, string $cid, string $name, string $type): ?object {

		// construct command
		$o = new \stdClass();
		$o->FolderCreate = new EasObject('FolderHierarchy');
		//$o->FolderCreate->SyncKey = new EasProperty('FolderHierarchy', '0');
		$o->FolderCreate->ParentId = new EasProperty('FolderHierarchy', $cid);
		$o->FolderCreate->Name = new EasProperty('FolderHierarchy', $name);
		$o->FolderCreate->Type = new EasProperty('FolderHierarchy', $type);
		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performFolderCreate($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			$o = $o->Message;

			switch ($o->FolderCreate->Status->getContents()) {
				case '2':
					throw new Exception("The parent folder already contains a folder with the same name. Create the folder under a different name.", 2);
					break;
				case '3':
					throw new Exception("The specified parent folder is a special system folder. Create the folder under a different parent.", 3);
					break;
				case '5':
					throw new Exception("The parent folder does not exist on the server, possibly because it has been deleted or moved.", 5);
					break;
				case '6':
					throw new Exception("An error occurred on the server.", 6);
					break;
				case '9':
					throw new Exception("Synchronization key mismatch or invalid synchronization key.", 9);
					break;
				case '10':
					throw new Exception("Incorrectly formatted request.", 10);
					break;
				case '11':
				case '12':
					throw new Exception("An unknown error occurred.", 11);
					break;
			}

			return $o->Message;
		}
		else {
			// return blank response
			return null;
		}

	}

	/**
     * delete folder from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore		Storage Interface
	 * @param string $cid				Id
	 * 
	 * @return object Attachement Collection Object on success / Null on failure
	 */
	public function deleteFolder(EasClient $DataStore, string $cid): ?bool {
		
		// construct command
		$o = new \stdClass();
		$o->FolderDelete = new EasObject('FolderHierarchy');
		//$o->FolderDelete->SyncKey = new EasProperty('FolderHierarchy', '0');
		$o->FolderDelete->Id = new EasProperty('FolderHierarchy', $cid);
		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performFolderDelete($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			$o = $o->Message;

			switch ($o->FolderDelete->Status->getContents()) {
				case '3':
					throw new Exception("The specified folder is a special system folder and cannot be deleted.", 3);
					break;
				case '4':
					throw new Exception("The specified folder does not exist.", 4);
					break;
				case '6':
					throw new Exception("An error occurred on the server.", 6);
					break;
				case '9':
					throw new Exception("Synchronization key mismatch or invalid synchronization key.", 9);
					break;
				case '10':
					throw new Exception("Incorrectly formatted request.", 10);
					break;
				case '11':
				case '12':
					throw new Exception("An unknown error occurred.", 11);
					break;
			}

			return true;
		}
		else {
			// return blank response
			return null;
		}

	}

	/**
     * update folder in remote storage
	 * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $fid - Folder ID
	 * 
	 * @return object Folders Object on success / Null on failure
	 */
	public function updateFolder(EasClient $DataStore, string $cid, string $pid, string $name, string $type): ?object {

		// construct command
		$o = new \stdClass();
		$o->FolderUpdate = new EasObject('FolderHierarchy');
		//$o->FolderCreate->SyncKey = new EasProperty('FolderHierarchy', '0');
		$o->FolderUpdate->ParentId = new EasProperty('FolderHierarchy', $pid);
		$o->FolderUpdate->Id = new EasProperty('FolderHierarchy', $cid);
		$o->FolderUpdate->Name = new EasProperty('FolderHierarchy', $name);
		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performFolderUpdate($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			$o = $o->Message;

			switch ($o->FolderUpdate->Status->getContents()) {
				case '2':
					throw new Exception("A folder with that name already exists or the specified folder is a special folder.", 2);
					break;
				case '3':
					throw new Exception("The specified folder is a special folder. Special folders cannot be updated.", 3);
					break;
				case '4':
					throw new Exception("The specified folder does not exist.", 4);
					break;
				case '5':
					throw new Exception("The specified parent folder does not exist.", 5);
					break;
				case '6':
					throw new Exception("An error occurred on the server.", 6);
					break;
				case '9':
					throw new Exception("Synchronization key mismatch or invalid synchronization key.", 9);
					break;
				case '10':
					throw new Exception("Incorrectly formatted request.", 10);
					break;
				case '11':
				case '12':
					throw new Exception("An unknown error occurred.", 11);
					break;
			}

			return $o->Message;
		}
		else {
			// return blank response
			return null;
		}

	}

	/**
     * retrieve list of changes for specific folder from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $fid - Folder ID
	 * @param string $state - Folder Synchronization State
	 * @param bool $ftype - Folder ID Type (True - Distinguished / False - Normal)
	 * @param int $max - Maximum Number of changes to list
	 * @param string $base - Base Properties / D - Default / A - All / I - ID's
	 * @param object $additional - Additional Properties object of NonEmptyArrayOfPathsToElementType
	 * 
	 * @return object Folder Changes Object on success / Null on failure
	 */
	public function fetchFolderChanges(EasClient $DataStore, string $cid, string $state, int $filter, int $max = 32): object {
		
		// construct Sync request
		$o = new \stdClass();
		$o->Sync = new EasObject('AirSync');
		$o->Sync->Collections = new EasObject('AirSync');
		$o->Sync->Collections->Collection = new EasObject('AirSync');
		$o->Sync->Collections->Collection->SyncKey = new EasProperty('AirSync', $state);
		$o->Sync->Collections->Collection->CollectionId = new EasProperty('AirSync', $cid);
		$o->Sync->Collections->Collection->GetChanges = new EasProperty('AirSync', 1);
		$o->Sync->Collections->Collection->DeletesAsMoves = new EasProperty('AirSync', 1);
		$o->Sync->Collections->Collection->WindowSize = new EasProperty('AirSync', $max);
		$o->Sync->Collections->Collection->Options = new EasObject('AirSync');
		$o->Sync->Collections->Collection->Options->FilterType = new EasProperty('AirSync', $filter);
		//$o->Sync->Collections->Collection->Options->MIMESupport = new EasProperty('AirSync', 2);
		//$o->Sync->Collections->Collection->Options->MIMETruncation = new EasProperty('AirSync', 8);
		$o->Sync->Collections->Collection->Options->BodyPreference = new EasObject('AirSyncBase');
		$o->Sync->Collections->Collection->Options->BodyPreference->Type = new EasProperty('AirSyncBase', 1);
		$o->Sync->Collections->Collection->Options->BodyPreference->AllOrNone = new EasProperty('AirSyncBase', 1);
		$o->Sync->WindowSize = new EasProperty('AirSync', $max);

		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performSync($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			$o = $o->Message;

			if (isset($o->Sync->Status)) {
				switch ($o->Sync->Status->getContents()) {
					case '6':
						throw new Exception("An error occurred on the server.", 6);
						break;
					case '9':
						throw new Exception("Synchronization key mismatch or invalid synchronization key.", 9);
						break;
					case '10':
						throw new Exception("Incorrectly formatted request.", 10);
						break;
					case '11':
					case '12':
						throw new Exception("An unknown error occurred.", 11);
						break;
				}
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
	 * @param EasClient $DataStore		Storage Interface
	 * 
	 * @return object Folder List Object on success / Null on failure
	 */
	public function fetchFolderEstimate(EasClient $DataStore, string $fid, string $state): ?object {
	
		// construct GetItemEstimate request
		$o = new \stdClass();
		$o->GetItemEstimate = new EasObject('GetItemEstimate');
		$o->GetItemEstimate->Collections = new EasObject('GetItemEstimate');
		
			$o->GetItemEstimate->Collections->Collection = new EasObject('GetItemEstimate');
			$o->GetItemEstimate->Collections->Collection->CollectionId = new EasProperty('GetItemEstimate', $fid);
			$o->GetItemEstimate->Collections->Collection->SyncKey = new EasProperty('AirSync', $state);
		
		
		// serialize request message
		$rq = $this->_encoder->stringFromObject($o);
		// execute request
		$rs = $DataStore->performGetItemEstimate($rq);
		// evaluate, if data was returned
		if (!empty($rs)) {
			// deserialize response message
			$o = $this->_decoder->stringToObject($rs);
			$o = $o->Message;
			// return response message
			return $o;
		}
		else {
			// return blank response
			return null;
		}
		
	}

	/**
     * retrieve all item ids in specific folder from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $fid - Folder ID
	 * @param string $ftype - Folder ID Type (True - Distinguished / False - Normal)
	 * @param string $ioff - Items Offset
	 * 
	 * @return object Item Object on success / Null on failure
	 */
	public function fetchItemsIds(EasClient $DataStore, string $fid, bool $ftype = false, int $ioff = 0): ?object {
		
		return null;

	}

	/**
     * retrieve information for specific item from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $uuid - Item UUID
	 * @param string $fid - Folder ID
	 * @param string $ftype - Folder ID Type (True - Distinguished / False - Normal)
	 * @param string $base - Base Properties / D - Default / A - All / I - ID's
	 * @param object $additional - Additional Properties object of NonEmptyArrayOfPathsToElementType
	 * 
	 * @return object Item Object on success / Null on failure
	 */
	public function findItem(EasClient $DataStore, string $fid, object $restriction, bool $ftype = false, string $base = 'D', object $additional = null): ?object {
		
		return null;

	}

	/**
     * retrieve all information for specific item by uuid from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $uuid - Item UUID
	 * @param string $fid - Folder ID
	 * @param string $ftype - Folder ID Type (True - Distinguished / False - Normal)
	 * @param string $base - Base Properties / D - Default / A - All / I - ID's
	 * @param object $additional - Additional Properties object of NonEmptyArrayOfPathsToElementType
	 * 
	 * @return array Item Object on success / Null on failure
	 */
	public function findItemByUUID(EasClient $DataStore, string $fid, string $uuid, bool $ftype = false, string $base = 'D', object $additional = null): ?object {
		
		return null;

	}

	/**
     * retrieve all information for specific item from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param array $ioc - Collection of Id Objects
	 * @param string $base - Base Properties / D - Default / A - All / I - ID's
	 * @param object $additional - Additional Properties object of NonEmptyArrayOfPathsToElementType
	 * 
	 * @return object Item Object on success / Null on failure
	 */
	public function fetchItem(EasClient $DataStore, array $ioc, string $base = 'D', object $additional = null): ?object {
		
		return null;

	}

	/**
     * create item in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $fid - Folder ID
	 * @param string $fid - Item Data
	 * 
	 * @return object Attachement Collection Object on success / Null on failure
	 */
	public function createItem(EasClient $DataStore, string $fid, object $data): ?object {
		
		return null;

	}

	/**
     * update item in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $fid - Folder ID
	 * @param string $iid - Item ID
	 * @param string $a - Item Append Commands
	 * @param string $u - Item Update Commands
	 * @param string $d - Item Delete Commands
	 * 
	 * @return object Items Array on success / Null on failure
	 */
	public function updateItem(EasClient $DataStore, string $fid, string $iid, array $additions = null, array $modifications = null, array $deletions = null): ?object {
		
		return null;

	}

	/**
     * delete item in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $ids - Item ID's Array
	 * @param string $fid - Item Data
	 * 
	 * @return object Attachement Collection Object on success / Null on failure
	 */
	public function deleteItem(EasClient $DataStore, array $ids = null, string $type = 'SoftDelete'): ?bool {
		
		return null;

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
	 * @param string $fid - Folder ID
	 * @param string $ftype - Folder ID Type (True - Distinguished / False - Normal)
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
