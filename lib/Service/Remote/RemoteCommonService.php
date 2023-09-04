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
	public function fetchFolders(EasClient $DataStore): ?object {
		
		// construct command
		$o = new \stdClass();
		$o->FolderSync = new \OCA\EAS\Utile\Eas\EasObject('FolderHierarchy');
		$o->FolderSync->SyncKey = new \OCA\EAS\Utile\Eas\EasProperty('FolderHierarchy', '0');
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
	public function createFolder(EasClient $DataStore, string $fid, object $data, bool $ftype = false): ?object {
		
		return null;

	}

	/**
     * delete folder from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param EasClient $DataStore - Storage Interface
	 * @param string $ids - Collection Id's List
	 * @param string $type - 
	 * 
	 * @return object Attachement Collection Object on success / Null on failure
	 */
	public function deleteFolder(EasClient $DataStore, array $batch = null, string $type = 'SoftDelete'): ?bool {
		
		return null;

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
	public function fetchFolderChanges(EasClient $DataStore, string $fid, string $state, bool $ftype = false, int $max = 512, string $base = 'I', object $additional = null): object {
		
		return null;

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
