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
use OCP\Files\IRootFolder;

use OCA\EAS\Db\EventStore;
use OCA\EAS\Service\CorrelationsService;
use OCA\EAS\Service\Local\LocalEventsService;
use OCA\EAS\Service\Remote\RemoteEventsService;
use OCA\EAS\Utile\Eas\EasClient;
use OCA\EAS\Objects\EventObject;
use OCA\EAS\Objects\HarmonizationStatisticsObject;

class EventsService {
	
	private LoggerInterface $logger;
	private Object $Configuration;
	private CorrelationsService $CorrelationsService;
	private LocalEventsService $LocalEventsService;
	private RemoteEventsService $RemoteEventsService;
	private IRootFolder $LocalFileStore;
	private EventStore $LocalStore;
	private EasClient $RemoteStore;

	public function __construct (LoggerInterface $logger,
								CorrelationsService $CorrelationsService,
								LocalEventsService $LocalEventsService,
								RemoteEventsService $RemoteEventsService,
								IRootFolder $LocalFileStore,
								EventStore $LocalStore) {
		$this->logger = $logger;
		$this->CorrelationsService = $CorrelationsService;
		$this->LocalEventsService = $LocalEventsService;
		$this->RemoteEventsService = $RemoteEventsService;
		$this->LocalStore = $LocalStore;
		$this->LocalFileStore = $LocalFileStore;
	}

	public function initialize($configuration, EasClient $RemoteStore) {

		$this->Configuration = $configuration;
		$this->RemoteStore = $RemoteStore;
		// assign data stores
		$this->LocalEventsService->initialize($this->LocalStore, $this->LocalFileStore->getUserFolder($this->Configuration->UserId));
		$this->RemoteEventsService->initialize($this->RemoteStore);

		// assign timezones
		$this->LocalEventsService->SystemTimeZone = $this->Configuration->SystemTimeZone;
		$this->RemoteEventsService->SystemTimeZone = $this->Configuration->SystemTimeZone;
		$this->LocalEventsService->UserTimeZone = $this->Configuration->UserTimeZone;
		$this->RemoteEventsService->UserTimeZone = $this->Configuration->UserTimeZone;
		// assign default folder
		$this->LocalEventsService->UserAttachmentPath = $this->Configuration->EventsAttachmentPath;

	}

	/**
	 * Perform harmonization for all events collection correlations
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
			$this->logger->debug('EWS - Deleted Events collection correlation for ' . $this->Configuration->UserId . ' due to missing Remote ID or Local ID');
			return $statistics;
		}

		// retrieve list of local changed objects
		$lCollectionChanges = [];
		//$lCollectionChanges = $this->LocalEventsService->fetchCollectionChanges($correlation->getloid(), (string) $correlation->getlostate());
		
		// delete and skip collection correlation if local collection is missing
		//$lcollection = $this->LocalEventsService->fetchCollection($lcid);
		//if (!isset($lcollection) || ($lcollection->Id != $lcid)) {
		//	$this->CorrelationsService->deleteByAffiliationId($this->Configuration->UserId, $caid);
		//	$this->CorrelationsService->delete($correlation);
		//	$this->logger->debug('EWS - Deleted Events collection correlation for ' . $this->Configuration->UserId . ' due to missing Local Collection');
		//	return $statistics;
		//}

		// retrieve list of remote changed object
		$rCollectionChanges = $this->RemoteEventsService->syncEntities($correlation->getroid(), (string) $correlation->getrostate());
		
		// delete and skip collection correlation if remote collection is missing
		//$rcollection = $this->RemoteEventsService->fetchCollection($rcid);
		//if (!isset($rcollection) || ($rcollection->Id != $rcid)) {
		//	$this->CorrelationsService->deleteByAffiliationId($this->Configuration->UserId, $caid);
		//	$this->CorrelationsService->delete($correlation);
		//	$this->logger->debug('EWS - Deleted Events collection correlation for ' . $this->Configuration->UserId . ' due to missing Remote Collection');
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
			// Make sure to store this for the next sync.
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
	 * @param string $uid	nextcloud user id
	 * @param string $lcid	local collection id
	 * @param string $loid	local object id
	 * @param string $rcid	remote collection id
	 * @param string $caid	correlation affiliation id
	 *
	 * @return string what action was performed
	 */
	function harmonizeLocalAltered ($uid, $lcid, $loid, $rcid, $caid): string {
		
		// create harmonize status place holder
		$status = 'NA'; // no actions
		// create/reset local object place holder
		$lo = null;
		// create/reset remote object place holder
		$ro = null;
		// retrieve local events object
		$lo = $this->LocalEventsService->fetchCollectionItem($lcid, $loid);
		// evaluate, if local event object was returned
		if (!($lo instanceof \OCA\EAS\Objects\EventObject)) {
			// return status of action
			return $status;
		}
		// try to retrieve correlation for remote and local object
		$ci = $this->CorrelationsService->findByLocalId($uid, 'EO', $loid, $lcid);
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
			// retrieve remote event object
			$ro = $this->RemoteEventsService->fetchCollectionItem($ci->getroid());
		}
		// if remote object retrieve failed, try to retrieve remote object by UUID
		if (!isset($ro) && !empty($lo->UUID)) {
			// retrieve list of remote ids and uids
			if (!isset($this->RemoteUUIDs)) {
				$this->RemoteUUIDs = $this->RemoteEventsService->fetchCollectionItemsUUID($rcid);
			}
			// search for uuid
			$k = array_search($lo->UUID, array_column($this->RemoteUUIDs, 'UUID'));
			if ($k !== false) {
				// retrieve remote event object
				$ro = $this->RemoteEventsService->fetchCollectionItem($this->RemoteUUIDs[$k]['ID']);
			}
		}
		// create remote object if none was found
		// update remote object if one was found
		if (isset($ro)) {
			// if correlation DOES NOT EXIST
			// use selected mode to resolve conflict
			if (!($ci instanceof \OCA\EAS\Db\Correlation)) {
				// update remote object if
				// local wins mode selected
				// chronology wins mode selected and local object is newer
				if ($this->Configuration->EventsPrevalence == 'L' || 
					($this->Configuration->EventsPrevalence == 'C' && ($lo->ModifiedOn > $ro->ModifiedOn))) {
					// delete all previous attachment(s) in remote store
					// work around for missing update command in eas
					$this->RemoteEventsService->deleteCollectionItemAttachment(array_column($ro->Attachments, 'Id'));
					// update remote object
					$ro = $this->RemoteEventsService->updateCollectionItem($rcid, $ro->ID, $lo);
					// assign status
					$status = 'RU'; // Remote Update
				}
				// update local object if
				// remote wins mode selected
				// chronology wins mode selected and remote object is newer
				if ($this->Configuration->EventsPrevalence == 'R' || 
					($this->Configuration->EventsPrevalence == 'C' && ($ro->ModifiedOn > $lo->ModifiedOn))) {
					// update local object
					$lo = $this->LocalEventsService->updateCollectionItem($lcid, $lo->ID, $ro);
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
				if ($this->Configuration->EventsPrevalence == 'L' || 
				   ($this->Configuration->EventsPrevalence == 'C' && ($lo->ModifiedOn > $ro->ModifiedOn))) {
					// delete all previous attachment(s) in remote store
					// work around for missing update command in eas
					$this->RemoteEventsService->deleteCollectionItemAttachment(array_column($ro->Attachments, 'Id'));
					// update remote object
					$ro = $this->RemoteEventsService->updateCollectionItem($rcid, $ro->ID, $lo);
					// assign status
					$status = 'RU'; // Rocal Update
				}
				// update local object if
				// remote wins mode selected
				// chronology wins mode selected and remote object is newer
				if ($this->Configuration->EventsPrevalence == 'R' || 
				   ($this->Configuration->EventsPrevalence == 'C' && ($ro->ModifiedOn > $lo->ModifiedOn))) {
					// update local object
					$lo = $this->LocalEventsService->updateCollectionItem($lcid, $lo->ID, $ro);
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
				$this->RemoteEventsService->deleteCollectionItemAttachment(array_column($ro->Attachments, 'Id'));
				// update remote object
				$ro = $this->RemoteEventsService->updateCollectionItem($rcid, $ro->ID, $lo);
				// assign status
				$status = 'RU'; // Remote Update
			}
		}
		else {
			// create remote object
			$ro = $this->RemoteEventsService->createCollectionItem($rcid, $lo);
			// assign status
			$status = 'RC'; // Remote Create
		}
		// update object correlation if one was found
		// create object correlation if none was found
		if ($ci instanceof \OCA\EAS\Db\Correlation) {
			$ci->setloid($lo->ID); // Local ID
			$ci->setlostate($lo->State); // Local State
			$ci->setlcid($lcid); // Local Parent ID
			$ci->setroid($ro->ID); // Remote ID
			$ci->setrostate($ro->State); // Remote State
			$ci->setrcid($rcid); // Remote Parent ID
			$this->CorrelationsService->update($ci);
		}
		elseif (isset($lo) && isset($ro)) {
			$ci = new \OCA\EAS\Db\Correlation();
			$ci->settype('EO'); // Correlation Type
			$ci->setuid($uid); // User ID
			$ci->setaid($caid); //Affiliation ID
			$ci->setloid($lo->ID); // Local ID
			$ci->setlostate($lo->State); // Local State
			$ci->setlcid($lcid); // Local Parent ID
			$ci->setroid($ro->ID); // Remote ID
			$ci->setrostate($ro->State); // Remote State
			$ci->setrcid($rcid); // Remote Parent ID
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
	 * @param string $uid	nextcloud user id
	 * @param string $lcid	local collection id
	 * @param string $loid	local object id
	 *
	 * @return string what action was performed
	 */
	function harmonizeLocalDelete ($uid, $lcid, $loid): string {
		
		// retrieve correlation
		$ci = $this->CorrelationsService->findByLocalId($uid, 'EO', $loid, $lcid);
		// evaluate correlation object
		if ($ci instanceof \OCA\EAS\Db\Correlation) {
			// destroy remote object
			$rs = $this->RemoteEventsService->deleteCollectionItem($ci->getroid());
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
		$ro = $this->RemoteEventsService->toEventObject($rdata);
		$ro->ID = $rcid;
		$ro->CID = $roid;
		$ro->RCID = $rcid;
		$ro->REID = $roid;
		// evaluate, if remote contact object was returned
		if (!($ro instanceof \OCA\EAS\Objects\EventObject)) {
			// return status of action
			return $status;
		}
		// find local object by remote collection and object id
		$lo = $this->LocalEventsService->fetchEntityByRID($uid, $rcid, $roid);
		// update local object if one was found
		// create local object if none was found
		if (isset($lo)) {
			// update local object if
			// remote wins mode selected
			// chronology wins mode selected and remote object is newer
			if ($this->Configuration->EventsPrevalence == 'R' || 
				($this->Configuration->EventsPrevalence == 'C' && ($ro->ModifiedOn > $lo->ModifiedOn))) {
				// update local object
				$lo = $this->LocalEventsService->updateEntity($uid, $lo->CID, $lo->ID, $ro);
				// assign status
				$status = 'LU'; // Local Update
			}
			// update remote object if
			// local wins mode selected
			// chronology wins mode selected and local object is newer
			elseif ($this->Configuration->EventsPrevalence == 'L' || 
				($this->Configuration->EventsPrevalence == 'C' && ($lo->ModifiedOn > $ro->ModifiedOn))) {
				// update remote object
				$ro = $this->RemoteEventsService->updateCollectionItem($rcid, $ro->ID, $lo);
				// assign status
				$status = 'RU'; // Remote Update
			}
		}
		else {
			// create local object
			$lo = $this->LocalEventsService->createEntity($uid, $lcid, $ro);
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
		$lo = $this->LocalEventsService->fetchEntityByRID($uid, $rcid, $roid);
		// evaluate correlation object
		if (isset($lo)) {
			// destroy local object
			$rs = $this->LocalEventsService->deleteEntity($uid, $lo->CID, $lo->ID);
			// return status of action
			return 'LD';
		}
		else {
			// return status of action
			return 'NA';
		}

	}
	
}
