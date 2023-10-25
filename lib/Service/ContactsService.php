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

namespace OCA\EAS\Service;

use Datetime;
use DateTimeZone;
use Exception;
use Throwable;
use Psr\Log\LoggerInterface;

use OCA\EAS\Db\ContactStore;
use OCA\EAS\Service\CorrelationsService;
use OCA\EAS\Service\Local\LocalContactsService;
use OCA\EAS\Service\Remote\RemoteContactsService;
use OCA\EAS\Utile\Eas\EasClient;
use OCA\EAS\Objects\ContactObject;
use OCA\EAS\Objects\HarmonizationStatisticsObject;

class ContactsService {
	
	private LoggerInterface $logger;
	private Object $Configuration;
	private CorrelationsService $CorrelationsService;
	private LocalContactsService $LocalContactsService;
	private RemoteContactsService $RemoteContactsService;
	private ContactStore $LocalStore;
	private EasClient $RemoteStore;
	

	public function __construct (LoggerInterface $logger,
								CorrelationsService $CorrelationsService,
								LocalContactsService $LocalContactsService,
								RemoteContactsService $RemoteContactsService,
								ContactStore $LocalStore) {
		$this->logger = $logger;
		$this->CorrelationsService = $CorrelationsService;
		$this->LocalContactsService = $LocalContactsService;
		$this->RemoteContactsService = $RemoteContactsService;
		$this->LocalStore = $LocalStore;
	}

	public function initialize($configuration, EasClient $RemoteStore) {

		$this->Configuration = $configuration;
		$this->RemoteStore = $RemoteStore;
		// assign data stores
		$this->LocalContactsService->initialize($this->LocalStore);
		$this->RemoteContactsService->initialize($this->RemoteStore);

	}

	/**
	 * Perform harmonization for all contacts collection correlations
	 * 
	 * @since Release 1.0.0
	 *
	 * @return HarmonizationStatisticsObject
	 */
	public function performHarmonization($correlation, $configuration) : object {
		
		// construct statistics object
		$statistics = new HarmonizationStatisticsObject();

		// set local and remote collection id's
		$caid = (string) $correlation->getid();
		$lcid = $correlation->getloid();
		$rcid = $correlation->getroid();
		// delete and skip collection correlation if remote id or local id is missing
		if (empty($lcid) || empty($rcid)){
			$this->CorrelationsService->deleteByAffiliationId($this->Configuration->UserId, $caid);
			$this->CorrelationsService->delete($correlation);
			$this->logger->debug('EWS - Deleted contacts collection correlation for ' . $this->Configuration->UserId . ' due to missing Remote ID or Local ID');
			return $statistics;
		}

		// retrieve list of local changed objects
		$lCollectionChanges = [];
		//$lCollectionChanges = $this->LocalContactsService->fetchCollectionChanges($correlation->getloid(), (string) $correlation->getlostate());
		
		// delete and skip collection correlation if local collection is missing
		//$lcollection = $this->LocalContactsService->fetchCollection($lcid);
		//if (!isset($lcollection) || ($lcollection->Id != $lcid)) {
		//	$this->CorrelationsService->deleteByAffiliationId($this->Configuration->UserId, $caid);
		//	$this->CorrelationsService->delete($correlation);
		//	$this->logger->debug('EWS - Deleted contacts collection correlation for ' . $this->Configuration->UserId . ' due to missing Local Collection');
		//	return $statistics;
		//}

		// retrieve list of remote changed object
		$rCollectionChanges = $this->RemoteContactsService->syncEntities($correlation->getroid(), (string) $correlation->getrostate());
		
		// delete and skip collection correlation if remote collection is missing
		//$rcollection = $this->RemoteContactsService->fetchCollection(0, 0, $rcid);
		//if (!isset($rcollection) || ($rcollection->Id != $rcid)) {
		//	$this->CorrelationsService->deleteByAffiliationId($this->Configuration->UserId, $caid);
		//	$this->CorrelationsService->delete($correlation);
		//	$this->logger->debug('EWS - Deleted contacts collection correlation for ' . $this->Configuration->UserId . ' due to missing Remote Collection');
		//	return $statistics;
		//}

		// evaluate if local mutations object was returned
		if (isset($lCollectionChanges['syncToken'])) {
			// process local created objects
			foreach ($lCollectionChanges['added'] as $iid) {
				// process create
				$as = $this->harmonizeLocalAltered(
					$this->Configuration->UserId, 
					$lcid, 
					$iid, 
					$rcid, 
					$caid
				);
				// increment statistics
				switch ($as) {
					case 'RC':
						$statistics->RemoteCreated += 1;
						break;
					case 'RU':
						$statistics->RemoteUpdated += 1;
						break;
					case 'LU':
						$statistics->LocalUpdated += 1;
						break;
				}
			}
			// process local modified items
			foreach ($lCollectionChanges['modified'] as $iid) {
				// process create
				$as = $this->harmonizeLocalAltered(
					$this->Configuration->UserId, 
					$lcid, 
					$iid, 
					$rcid, 
					$caid
				);
				// increment statistics
				switch ($as) {
					case 'RC':
						$statistics->RemoteCreated += 1;
						break;
					case 'RU':
						$statistics->RemoteUpdated += 1;
						break;
					case 'LU':
						$statistics->LocalUpdated += 1;
						break;
				}
			}
			// process local deleted items
			foreach ($lCollectionChanges['deleted'] as $iid) {
				// process delete
				$as = $this->harmonizeLocalDelete(
					$this->Configuration->UserId, 
					$lcid, 
					$iid
				);
				if ($as == 'RD') {
					// assign status
					$statistics->RemoteDeleted += 1;
				}
			}
			// update and deposit correlation local state
			$correlation->setlostate($lCollectionChanges['syncToken']);
			$this->CorrelationsService->update($correlation);
		}

		// evaluate if remote mutations object was returned
		// according to the EAS spec the change object can be blank if there is no changes 
		if (isset($rCollectionChanges->SyncKey)) {
			// evaluate if add property is an array and convert to array if needed
			if (isset($rCollectionChanges->Commands->Add) && !is_array($rCollectionChanges->Commands->Add)) {
				$rCollectionChanges->Commands->Add = [$rCollectionChanges->Commands->Add];
			}
			// process remote created objects
			foreach ($rCollectionChanges->Commands->Add as $Altered) {
				// process create
				$as = $this->harmonizeRemoteAltered(
					$this->Configuration->UserId, 
					$rcid, 
					$Altered->EntityId->getContents(),
					$Altered->Data, 
					$lcid, 
					$caid
				);
				// increment statistics
				switch ($as) {
					case 'LC':
						$statistics->LocalCreated += 1;
						break;
					case 'LU':
						$statistics->LocalUpdated += 1;
						break;
					case 'RU':
						$statistics->RemoteUpdated += 1;
						break;
				}
			}
			// evaluate if modify property is an array and convert to array if needed
			if (isset($rCollectionChanges->Commands->Modify) && !is_array($rCollectionChanges->Commands->Modify)) {
				$rCollectionChanges->Commands->Modify = [$rCollectionChanges->Commands->Modify];
			}
			// process remote modified objects
			foreach ($rCollectionChanges->Commands->Modify as $Altered) {
				// process create
				$as = $this->harmonizeRemoteAltered(
					$this->Configuration->UserId, 
					$rcid, 
					$Altered->EntityId->getContents(),
					$Altered->Data, 
					$lcid, 
					$caid
				);
				// increment statistics
				switch ($as) {
					case 'LC':
						$statistics->LocalCreated += 1;
						break;
					case 'LU':
						$statistics->LocalUpdated += 1;
						break;
					case 'RU':
						$statistics->RemoteUpdated += 1;
						break;
				}
			}
			// evaluate if delete property is an array and convert to array if needed
			if (isset($rCollectionChanges->Commands->Delete) && !is_array($rCollectionChanges->Commands->Delete)) {
				$rCollectionChanges->Commands->Delete = [$rCollectionChanges->Commands->Delete];
			}
			// process remote deleted objects
			foreach ($rCollectionChanges->Commands->Delete as $Deleted) {
				// process delete
				$as = $this->harmonizeRemoteDelete(
					$this->Configuration->UserId, 
					$rcid, 
					$Deleted->EntityId->getContents()
				);
				if ($as == 'LD') {
					// increment statistics
					$statistics->LocalDeleted += 1;
				}
			}
			// update and deposit correlation remote state
			$correlation->setrostate($rCollectionChanges->SyncKey->getContents());
			$this->CorrelationsService->update($correlation);
		}
		
		// return statistics
		return $statistics;

	}

	/**
	 * Perform harmonization for locally altered object
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		nextcloud user id
	 * @param string $lcid		local collection id
	 * @param string $loid		local object id
	 * @param string $rcid		remote collection id
	 * @param string $caid		correlation affiliation id
	 *
	 * @return string 			what action was performed
	 */
	function harmonizeLocalAltered ($uid, $lcid, $loid, $rcid, $caid): string {

		// create harmonize status place holder
		$status = 'NA'; // no actions
		// create/reset local object place holder
		$lo = null;
		// create/reset remote object place holder
		$ro = null;
		// retrieve local contacts object
		$lo = $this->LocalContactsService->fetchCollectionItem($lcid, $loid);
		// evaluate, if local contact object was returned
		if (!($lo instanceof \OCA\EAS\Objects\ContactObject)) {
			// return status of action
			return $status;
		}
		// try to retrieve correlation for remote and local object
		$ci = $this->CorrelationsService->findByLocalId($uid, 'CO', $loid, $lcid);
		// if correlation exists
		// compare local state to correlation state and stop processing if they match to prevent sync loop
		if ($ci instanceof \OCA\EAS\Db\Correlation && 
			$ci->getlostate() == $lo->State) {
			// return status of action
			return $status;
		}
		// if correlation exists, try to retrieve remote object
		if ($ci instanceof \OCA\EAS\Db\Correlation && 
			!empty($ci->getroid())) {		
			// retrieve remote contact object	
			$ro = $this->RemoteContactsService->fetchCollectionItem($ci->getroid());
		}
		// if remote object retrieve failed, try to retrieve remote object by UUID
		if (!isset($ro) && !empty($lo->UID)) {
			// retrieve list of remote ids and uids
			if (!isset($this->RemoteUUIDs)) {
				$this->RemoteUUIDs = $this->RemoteContactsService->fetchCollectionItemsUUID($rcid);
			}
			// search for uuid
			$k = array_search($lo->UID, array_column($this->RemoteUUIDs, 'UUID'));
			if ($k !== false) {
				// retrieve remote contact object
				$ro = $this->RemoteContactsService->fetchCollectionItem($this->RemoteUUIDs[$k]['ID']);
			}
		}
		// update logic if remote object was FOUND
		// create logic if remote object was NOT FOUND
		if (isset($ro)) {
			// if correlation DOES NOT EXIST
			// use selected mode to resolve conflict
			if (!($ci instanceof \OCA\EAS\Db\Correlation)) {
				// update remote object if
				// local wins mode selected
				// chronology wins mode selected and local object is newer
				if ($this->Configuration->ContactsPrevalence == 'L' || 
				($this->Configuration->ContactsPrevalence == 'C' && ($lo->ModifiedOn > $ro->ModifiedOn))) {
					// delete all previous attachment(s) in remote store
					// work around for missing update command in eas
					$this->RemoteContactsService->deleteCollectionItemAttachment(array_column($ro->Attachments, 'Id'));
					// update remote object
					$ro = $this->RemoteContactsService->updateCollectionItem($rcid, $ro->ID, $lo);
					// assign status
					$status = 'RU'; // Remote Update
				}
				// update local object if
				// remote wins mode selected
				// chronology wins mode selected and remote object is newer
				if ($this->Configuration->ContactsPrevalence == 'R' || 
				($this->Configuration->ContactsPrevalence == 'C' && ($ro->ModifiedOn > $lo->ModifiedOn))) {
					// update local object
					$lo = $this->LocalContactsService->updateCollectionItem($lcid, $lo->ID, $ro);
					// assign status
					$status = 'LU'; // Local Update
				}
			}
			// if correlation EXISTS
			// compare remote object state to correlation state
			// if states DO NOT MATCH use selected mode to resolve conflict
			elseif ($ci instanceof \OCA\EAS\Db\Correlation && 
					$ro->State != $ci->getrostate()) {
				// update remote object if
				// local wins mode selected
				// chronology wins mode selected and local object is newer
				if ($this->Configuration->ContactsPrevalence == 'L' || 
				   ($this->Configuration->ContactsPrevalence == 'C' && ($lo->ModifiedOn > $ro->ModifiedOn))) {
					// delete all previous attachment(s) in remote store
					// work around for missing update command in eas
					$this->RemoteContactsService->deleteCollectionItemAttachment(array_column($ro->Attachments, 'Id'));
					// update remote object
					$ro = $this->RemoteContactsService->updateCollectionItem($rcid, $ro->ID, $lo);
					// assign status
					$status = 'RU'; // Rocal Update
				}
				// update local object if
				// remote wins mode selected
				// chronology wins mode selected and remote object is newer
				if ($this->Configuration->ContactsPrevalence == 'R' || 
				   ($this->Configuration->ContactsPrevalence == 'C' && ($ro->ModifiedOn > $lo->ModifiedOn))) {
					// update local object
					$lo = $this->LocalContactsService->updateCollectionItem($lcid, $lo->ID, $ro);
					// assign status
					$status = 'LU'; // Local Update
				}
			}
			// if correlation EXISTS
			// compare remote object state to correlation state
			// if states DO MATCH update remote object
			elseif ($ci instanceof \OCA\EAS\Db\Correlation && 
					$ro->State == $ci->getrostate()) {
				// delete all previous attachment(s) in remote store
				// work around for missing update command in eas
				$this->RemoteContactsService->deleteCollectionItemAttachment(array_column($ro->Attachments, 'Id'));
				// update remote object
				$ro = $this->RemoteContactsService->updateCollectionItem($rcid, $ro->ID, $lo);
				// assign status
				$status = 'RU'; // Remote Update
			}
		}
		else {
			// create remote object
			$ro = $this->RemoteContactsService->createCollectionItem($rcid, $lo);
			// assign status
			$status = 'RC'; // Remote Create
		}
		// update object correlation if one was found
		// create object correlation if none was found
		if ($ci instanceof \OCA\EAS\Db\Correlation) {
			$ci->setloid($lo->ID); // Local ID
			$ci->setlostate($lo->State); // Local State
			$ci->setlcid($lcid); // Local Collection ID
			$ci->setroid($ro->ID); // Remote ID
			$ci->setrostate($ro->State); // Remote State
			$ci->setrcid($rcid); // Remote Collection ID
			$this->CorrelationsService->update($ci);
		}
		elseif (isset($lo) && isset($ro)) {
			$ci = new \OCA\EAS\Db\Correlation();
			$ci->settype('CO'); // Correlation Type
			$ci->setuid($uid); // User ID
			$ci->setaid($caid); //Affiliation ID
			$ci->setloid($lo->ID); // Local ID
			$ci->setlostate($lo->State); // Local State
			$ci->setlcid($lcid); // Local Collection ID
			$ci->setroid($ro->ID); // Remote ID
			$ci->setrostate($ro->State); // Remote State
			$ci->setrcid($rcid); // Remote Collection ID
			$this->CorrelationsService->create($ci);
		}
		// return status of action
		return $status;

	}

	/**
	 * Perform harmonization for locally deleted object
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		nextcloud user id
	 * @param string $lcid		local collection id
	 * @param string $loid		local object id
	 *
	 * @return string 			what action was performed
	 */
	function harmonizeLocalDelete ($uid, $lcid, $loid): string {

		// retrieve correlation
		$ci = $this->CorrelationsService->findByLocalId($uid, 'CO', $loid, $lcid);
		// validate result
		if ($ci instanceof \OCA\EAS\Db\Correlation) {
			// destroy remote object
			$rs = $this->RemoteContactsService->deleteCollectionItem($ci->getroid());
			// destroy correlation
			$this->CorrelationsService->delete($ci);
			// return status of action
			return 'RD';
		}
		else {
			// return status of action
			return 'NA';
		}
			
	}

	/**
	 * Perform harmonization for remotely altered object
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		nextcloud user id
	 * @param string $rcid		remote collection id
	 * @param string $ro		remote object
	 * @param string $lcid		local collection id
	 * @param string $caid		correlation affiliation id
	 *
	 * @return string 			what action was performed
	 */
	function harmonizeRemoteAltered ($uid, $rcid, $roid, $rdata, $lcid, $caid): string {
		
		// create harmonize status place holder
		$status = 'NA'; // no acction
		// create/reset local object place holder
		$lo = null;
		// convert remote object to contact object
		$ro = $this->RemoteContactsService->toContactObject($rdata);
		$ro->ID = $rcid;
		$ro->CID = $roid;
		$ro->RCID = $rcid;
		$ro->REID = $roid;
		// evaluate, if remote contact object was returned
		if (!($ro instanceof \OCA\EAS\Objects\ContactObject)) {
			// return status of action
			return $status;
		}
		// find local object by remote collection and object id
		$lo = $this->LocalContactsService->fetchEntityByRID($uid, $rcid, $roid);
		// update local object if one was found
		// create local object if none was found
		if (isset($lo)) {
			// update local object if
			// remote wins mode selected
			// chronology wins mode selected and remote object is newer
			if ($this->Configuration->ContactsPrevalence == 'R' || 
				($this->Configuration->ContactsPrevalence == 'C' && ($ro->ModifiedOn > $lo->ModifiedOn))) {
				// update local object
				$lo = $this->LocalContactsService->updateEntity($uid, $lo->CID, $lo->ID, $ro);
				// assign status
				$status = 'LU'; // Local Update
			}
			// update remote object if
			// local wins mode selected
			// chronology wins mode selected and local object is newer
			elseif ($this->Configuration->ContactsPrevalence == 'L' || 
				($this->Configuration->ContactsPrevalence == 'C' && ($lo->ModifiedOn > $ro->ModifiedOn))) {
				// update remote object
				$ro = $this->RemoteContactsService->updateCollectionItem($rcid, $ro->ID, $lo);
				// assign status
				$status = 'RU'; // Remote Update
			}
		}
		else {
			// create local object
			$lo = $this->LocalContactsService->createEntity($uid, $lcid, $ro);
			// assign status
			$status = 'LC'; // Local Create
		}
		// return status of action
		return $status;

	}

	/**
	 * Perform harmonization for remotely deleted object
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		nextcloud user id
	 * @param string $rcid		remote collection id
	 * @param string $roid		remote object id
	 *
	 * @return string 			what action was performed
	 */
	function harmonizeRemoteDelete ($uid, $rcid, $roid): string {

		// find local object by remote collection and object id
		$lo = $this->LocalContactsService->fetchEntityByRID($uid, $rcid, $roid);
		// evaluate correlation object
		if (isset($lo)) {
			// destroy local object
			$rs = $this->LocalContactsService->deleteEntity($uid, $lo->CID, $lo->ID);
			// return status of action
			return 'LD';
		}
		else {
			// return status of action
			return 'NA';
		}

	}

}
