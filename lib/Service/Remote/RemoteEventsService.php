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

use Datetime;
use DateTimeZone;
use DateInterval;
use Psr\Log\LoggerInterface;

use OCA\EAS\Service\Remote\RemoteCommonService;
use OCA\EAS\Objects\EventCollectionObject;
use OCA\EAS\Objects\EventObject;
use OCA\EAS\Objects\EventAttachmentObject;
use OCA\EAS\Utile\Eas\EasClient;
use OCA\EAS\Utile\Eas\EasCollection;
use OCA\EAS\Utile\Eas\EasObject;
use OCA\EAS\Utile\Eas\EasProperty;
use OCA\EAS\Utile\Eas\EasTypes;

class RemoteEventsService {
	
	private RemoteCommonService $RemoteCommonService;
	public ?EasClient $DataStore = null;

	public ?DateTimeZone $SystemTimeZone = null;
	public ?DateTimeZone $UserTimeZone = null;

	public function __construct (RemoteCommonService $RemoteCommonService) {
		
		$this->RemoteCommonService = $RemoteCommonService;
		
	}

    public function initialize(EasClient $DataStore) {

		$this->DataStore = $DataStore;

	}

	/**
     * retrieve properties for specific collection
     * 
     * @since Release 1.0.0
     * 
	 * @param string $cht				Collections Hierarchy Synchronization Token
	 * @param string $chl				Collections Hierarchy Location
	 * @param string $cid				Collection Id
	 * 
	 * @return EventCollectionObject	EventCollectionObject on success / Null on failure
	 */
	public function fetchCollection(string $cht, string $chl, string $cid): ?EventCollectionObject {

        // execute command
		$cr = $this->RemoteCommonService->fetchFolder($this->DataStore, $cid, false, 'I', $this->constructDefaultCollectionProperties());
        // process response
		if (isset($cr) && (count($cr->ContactsFolder) > 0)) {
		    $ec = new ContactCollectionObject(
				$cr->ContactsFolder[0]->FolderId->Id,
				$cr->ContactsFolder[0]->DisplayName,
				$cr->ContactsFolder[0]->FolderId->ChangeKey,
				$cr->ContactsFolder[0]->TotalCount
			);
			if (isset($cr->ContactsFolder[0]->ParentFolderId->Id)) {
				$ec->AffiliationId = $cr->ContactsFolder[0]->ParentFolderId->Id;
			}
			return $ec;
		} else {
			return null;
		}
        
    }

	/**
     * create collection in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param string $cht				Collections Hierarchy Synchronization Token
	 * @param string $chl				Collections Hierarchy Location
	 * @param string $name				Collection Name
	 * 
	 * @return EventCollectionObject	EventCollectionObject on success / Null on failure
	 */
	public function createCollection(string $cht, string $chl, string $name): ?EventCollectionObject {
        
		// execute command
		$rs = $RemoteCommonService->createCollection($this->DataStore, $cht, $chl, $name, EasTypes::COLLECTION_TYPE_USER_CALENDAR);
        // process response
		if (isset($rs->Status) && $rs->Status->getContents() == '1') {
		    return new EventCollectionObject(
				$rs->Id->getContents(),
				$name,
				$rs->SyncKey->getContents()
			);
		} else {
			return null;
		}

    }

    /**
     * update collection in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param string $cht				Collections Hierarchy Synchronization Token
	 * @param string $chl				Collections Hierarchy Location
	 * @param string $cid				Collection Id
	 * @param string $name				Collection Name
	 * 
	 * @return EventCollectionObject	EventCollectionObject on success / Null on failure
	 */
	public function updateCollection(string $cht, string $chl, string $cid, string $name): ?EventCollectionObject {
        
		// execute command
		$rs = $RemoteCommonService->updateCollection($this->DataStore, $cht, $chl, $cid, $name);
        // process response
		if (isset($rs->Status) && $rs->Status->getContents() == '1') {
		    return new EventCollectionObject(
				$rs->Id->getContents(),
				$name,
				$rs->SyncKey->getContents()
			);
		} else {
			return null;
		}

    }

    /**
     * delete collection in remote storage
     * 
     * @since Release 1.0.0
     * 
     * @param string $cht				Collections Hierarchy Synchronization Token
	 * @param string $cid				Collection Id
	 * 
	 * @return bool 					True on success / Null on failure
	 */
    public function deleteCollection(string $cht, string $cid): bool {
        
		// execute command
        $rs = $this->RemoteCommonService->deleteCollection($this->DataStore, $cht, $cid);
		// process response
        if (isset($rs->CollectionDelete->Status) && $rs->CollectionDelete->Status->getContents() == '1') {
            return true;
        } else {
            return false;
        }

    }

    /**
	 * retrieve alteration for specific collection
     * 
     * @since Release 1.0.0
	 * 
     * @param string $cid		Collection ID
	 * @param string $cst		Collections Synchronization Token
	 * 
	 * @return object
	 */
	public function reconcileCollection(string $cid, string $cst): ?object {

        // evaluate synchronization token, if empty or 0 retrieve initial synchronization token
        if (empty($cst) || $cst == '0') {
            // execute command
            $rs1 = $this->RemoteCommonService->reconcileCollection($this->DataStore, '0', $cid, []);
            // extract synchronization token
            $cst = $rs1->SyncKey->getContents();
        }
        // execute command
        $rs2 = $this->RemoteCommonService->reconcileCollection($this->DataStore, $cst, $cid, ['CHANGES' => 1, 'LIMIT' => 32, 'FILTER' => 0, 'BODY' => EasTypes::BODY_TYPE_TEXT]);
        // evaluate response(s)
		// return collection delta response
		if (isset($rs2->Status) && $rs2->Status->getContents() == '1') {
		    return $rs2;
		}
		// return initial response if normal response was null (work around for empty collection null responses)
		elseif (isset($rs1->Status) && $rs1->Status->getContents() == '1') {
		    return $rs1;
		}
		else {
			return null;
		}
		
    }

	/**
     * retrieve collection entity in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param string $cid			Collection ID
     * @param string $cst           Collection Signature Token
	 * @param string $eid			Entity ID
	 * 
	 * @return EventObject        EventObject on success / Null on failure
	 */
	public function fetchEntity(string $cid, string &$cst, string $eid): ?EventObject {

        // execute command
		$ro = $this->RemoteCommonService->fetchEntity($this->DataStore, $cid, $eid, ['BODY' => EasTypes::BODY_TYPE_TEXT]);
        // validate response
		if (isset($ro->Status) && $ro->Status->getContents() == '1') {
            // convert to contact object
            $eo = $this->toEventObject($ro->Properties);
            $eo->ID = ($ro->EntityId) ? $ro->EntityId->getContents() : $eid;
            $eo->CID = ($ro->CollectionId) ? $ro->CollectionId->getContents() : $cid;
            $eo->RCID = $eo->CID;
            $eo->REID = $eo->ID;
            // generate a signature for the data
            // this a crude but nessary as EAS does not transmit a harmonization signature for entities
            $eo->Signature = $this->generateSignature($eo);
            // retrieve attachment(s) from remote data store
			if (count($eo->Attachments) > 0) {
				// retrieve all attachments
				$ro = $this->RemoteCommonService->fetchAttachment($this->DataStore, array_column($eo->Attachments, 'Id'));
				// evaluate returned object
				if (count($ro) > 0) {
					foreach ($ro as $entry) {
						// evaluate status
						if (isset($entry->Status) && $entry->Status->getContents() == '1') {
							$key = array_search($entry->FileReference->getContents(), array_column($eo->Attachments, 'Id'));
							if ($key !== false) {
								$eo->Attachments[$key]->Data = base64_decode($entry->Properties->Data->getContents());
							}
						}
					}
				}
			}
            // return object
		    return $eo;
        } else {
            // return null
            return null;
        }

    }
    
	/**
     * create collection entity in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param string $cid			Collection Id
	 * @param string $cst			Collection Synchronization Token
     * @param EventObject $so     	Source Object
	 * 
	 * @return EventObject        	EventObject on success / Null on failure
	 */
	public function createEntity(string $cid, string &$cst, EventObject $so): ?EventObject {

        // convert source EventObject to EasObject
        $eo = $this->fromEventObject($so);
	    // execute command
	    $ro = $this->RemoteCommonService->createEntity($this->DataStore, $cid, $cst, EasTypes::ENTITY_TYPE_CALENDAR, $eo);
        // evaluate response
        if (isset($ro->Status) && $ro->Status->getContents() == '1') {
            // extract signature token
            $cst = $ro->SyncKey->getContents();
            //
			$eo = clone $so;
            $eo->Origin = 'R';
            $eo->ID = ($ro->Responses->Add->EntityId) ? $ro->Responses->Add->EntityId->getContents() : $eid;
            $eo->CID = ($ro->CollectionId) ? $ro->CollectionId->getContents() : $cid;
            $eo->RCID = $eo->CID;
            $eo->REID = $eo->ID;
			// deposit attachment(s)
			if (count($eo->Attachments) > 0) {
				// create attachments in remote data store
				$eo->Attachments = $this->createCollectionItemAttachment($eo->ID, $eo->Attachments);
				$eo->Signature = $eo->Attachments[0]->AffiliateState;
			}
            // generate a signature for the entity
			// this a crude but nessary as EAS does not transmit a harmonization signature for entities
			$eo->Signature = $this->generateSignature($eo);
            return $eo;
        } else {
            return null;
        }

    }

     /**
     * update collection entity in remote storage
     * 
     * @since Release 1.0.0
     * 
     * @param string $cid			Collection ID
	 * @param string $cst			Collection Signature Token
     * @param string $eid           Entity ID
     * @param EventObject $so     	Source Object
	 * 
	 * @return EventObject        	EventObject on success / Null on failure
	 */
	public function updateEntity(string $cid, string &$cst, string $eid, EventObject $so): ?EventObject {

		// convert source EventObject to EasObject
        $ro = $this->fromEventObject($so);
	    // execute command
	    $ro = $this->RemoteCommonService->updateEntity($this->DataStore, $cid, $cst, $eid, $ro);
        // evaluate response
        if (isset($ro->Status) && $ro->Status->getContents() == '1') {
            // extract signature token
            $cst = $ro->SyncKey->getContents();
            //
			$eo = clone $so;
			$eo->Origin = 'R';
            $eo->ID = ($ro->Responses->Modify->EntityId) ? $ro->Responses->Modify->EntityId->getContents() : $eid;
            $eo->CID = ($ro->CollectionId) ? $ro->CollectionId->getContents() : $cid;
			// deposit attachment(s)
			if (count($so->Attachments) > 0) {
				// create attachments in remote data store
				$eo->Attachments = $this->createCollectionItemAttachment($eo->ID, $eo->Attachments);
				$eo->Signature = $eo->Attachments[0]->AffiliateState;
			}
            // generate a signature for the entity
			// this a crude but nessary as EAS does not transmit a harmonization signature for entities
			$eo->Signature = $this->generateSignature($eo);
            return $eo;
        } else {
            return null;
        }
        
    }
    
    /**
     * delete collection entity in remote storage
     * 
     * @since Release 1.0.0
     * 
     * @param string $cid			Collection Id
	 * @param string $cst			Collection Synchronization Token
	 * @param string $eid			Entity Id
	 * 
	 * @return bool                 True on success / False on failure
	 */
    public function deleteEntity(string $cid, string $cst, string $eid): bool {
        
        // execute command
        $rs = $this->RemoteCommonService->deleteEntity($this->DataStore, $cid, $cst, $eid);
        // evaluate response
        if ($rs) {
            return true;
        } else {
            return false;
        }

    }

	/**
     * retrieve collection entity attachment from remote storage
     * 
     * @since Release 1.0.0
     * 
     * @param array $batch		Batch of Attachment ID's
	 * 
	 * @return array
	 */
	public function fetchAttachment(array $batch): array {

		// check to for entries in batch collection
        if (count($batch) == 0) {
            return array();
        }
		// retrieve attachments
		$rs = $this->RemoteCommonService->fetchAttachment($this->DataStore, $batch);
		// construct response collection place holder
		$rc = array();
		// check for response
		if (isset($rs)) {
			// process collection of objects
			foreach($rs as $entry) {
				if (!isset($entry->ContentType) || $entry->ContentType == 'application/octet-stream') {
					$type = \OCA\EAS\Utile\MIME::fromFileName($entry->Name);
				} else {
					$type = $entry->ContentType;
				}
				// insert attachment object in response collection
				$rc[] = new EventAttachmentObject(
					'D',
					$entry->AttachmentId->Id, 
					$entry->Name,
					$type,
					'B',
					$entry->Size,
					$entry->Content
				);
			}
		}
		// return response collection
		return $rc;

    }

    /**
     * create collection item attachment in local storage
     * 
     * @since Release 1.0.0
     * 
	 * @param string $aid - Affiliation ID
     * @param array $sc - Collection of EventAttachmentObject(S)
	 * 
	 * @return string
	 */
	public function createAttachment(string $aid, array $batch): array {

		// check to for entries in batch collection
        if (count($batch) == 0) {
            return array();
        }
		// construct command collection place holder
		$cc = array();
		// process batch
		foreach ($batch as $key => $entry) {
			// construct command object
			$eo = new \OCA\EAS\Utile\Eas\Type\FileAttachmentType();
			$eo->IsInline = false;
			$eo->IsContactPhoto = false;
			$eo->Name = $entry->Name;
			$eo->ContentId = $entry->Name;
			$eo->ContentType = $entry->Type;
			$eo->Size = $entry->Size;
			
			switch ($entry->Encoding) {
				case 'B':
					$eo->Content = $entry->Data;
					break;
				case 'B64':
					$eo->Content = base64_decode($entry->Data);
					break;
			}
			// insert command object in to collection
			$cc[] = $eo;
		}
		// execute command(s)
		$rs = $this->RemoteCommonService->createAttachment($this->DataStore, $aid, $cc);
		// construct results collection place holder
		$rc = array();
		// check for response
		if (isset($rs)) {
			// process collection of objects
			foreach($rs as $key => $entry) {
				$ro = clone $batch[$key];
				$ro->Id = $entry->AttachmentId->Id;
				$ro->Data = null;
				$ro->AffiliateId = $entry->AttachmentId->RootItemId;
				$ro->AffiliateState = $entry->AttachmentId->RootItemChangeKey;
				$rc[] = $ro;
			}

        }
		// return response collection
		return $rc;
    }

    /**
     * delete collection item attachment from local storage
     * 
     * @since Release 1.0.0
     * 
     * @param string $aid - Attachment ID
	 * 
	 * @return bool true - successfully delete / False - failed to delete
	 */
	public function deleteAttachment(array $batch): array {

		// check to for entries in batch collection
        if (count($batch) == 0) {
            return array();
        }
		// execute command
		$data = $this->RemoteCommonService->deleteAttachment($this->DataStore, $batch);

		return $data;

    }

	/**
     * convert remote EasObject to local ContactObject
     * 
     * @since Release 1.0.0
     * 
	 * @param EasObject $so     entity as EasObject
	 * 
	 * @return EventObject		entity as ContactObject
	 */
	public function toEventObject(EasObject $so): EventObject {
		// create object
		$eo = new EventObject();
		// Origin
		$eo->Origin = 'R';
        // Modification Date
        if (!empty($so->DtStamp)) {
            $eo->ModifiedOn = new DateTime($so->DtStamp->getContents());
        }
		// Time Zone
        if (!empty($so->Timezone)) {
        	$eo->TimeZone = $this->fromTimeZone($so->Timezone->getContents());
			if (isset($eo->TimeZone)) {
				if (!isset($eo->StartsTZ)) { $eo->StartsTZ = clone $eo->TimeZone; }
				if (!isset($eo->EndsTZ)) { $eo->EndsTZ = clone $eo->TimeZone; }
			}
        }
		// Start Date/Time
		if (!empty($so->StartTime)) {
			$eo->StartsOn = new DateTime($so->StartTime->getContents());
			if (isset($eo->StartsTZ)) { $eo->StartsOn->setTimezone($eo->StartsTZ); }
		}
		// End Date/Time
        if (!empty($so->EndTime)) {
            $eo->EndsOn = new DateTime($so->EndTime->getContents());
			if (isset($eo->EndsTZ)) { $eo->EndsOn->setTimezone($eo->EndsTZ); }
        }
		// All Day Event
		if(isset($so->AllDayEvent) && $so->AllDayEvent->getContents() == '1') {
			$eo->StartsOn->setTime(0,0,0,0);
			$eo->EndsOn->setTime(0,0,0,0);
		}
		// Label
        if (!empty($so->Subject)) {
            $eo->Label = $so->Subject->getContents();
        }
		// Notes
		if (!empty($so->Body->Data)) {
			$eo->Notes = $so->Body->Data->getContents();
		}
		// Location
		if (!empty($so->Location)) {
			$eo->Location = $so->Location->DisplayName->getContents();
		}
		// Availability
		if (!empty($so->BusyStatus)) {
			$eo->Availability = $this->fromAvailability($so->BusyStatus->getContents());
		}
		// Sensitivity
		if (!empty($so->Sensitivity)) {
			$eo->Sensitivity = $this->fromSensitivity($so->Sensitivity->getContents());
		}
		// Tag(s)
        if (isset($so->Categories)) {
            if (!is_array($so->Categories->Category)) {
                $so->Categories->Category = [$so->Categories->Category];
            }
			foreach($so->Categories->Category as $entry) {
				$eo->addTag($entry->getContents());
			}
        }
		// Organizer - Address
		if (isset($so->OrganizerEmail)) {
			$eo->Organizer->Address = $so->OrganizerEmail->getContents();
		}
		// Organizer - Name
		if (isset($so->OrganizerName)) {
			$eo->Organizer->Name = $so->OrganizerName->getContents();
		}
		// Attendee(s)
		if (isset($so->Attendees->Attendee)) {
			foreach($so->Attendees->Attendee as $entry) {
				if ($entry->Email) {
					$a = $entry->Email->getContents();
					// evaluate, if name exists
					$n = (isset($entry->Name)) ? $entry->Name->getContents() : null;
					// evaluate, if type exists
					$t = (isset($entry->AttendeeType)) ? $this->fromAttendeeType($entry->AttendeeType->getContents()) : null;
					// evaluate, if status exists
					$s = (isset($entry->AttendeeStatus)) ? $this->fromAttendeeStatus($entry->AttendeeStatus->getContents()) : null;
					// add attendee
					$eo->addAttendee($a, $n, $t, $s);
				}
			}
			unset($a, $n, $t, $s);
		}
		// Notification(s)
		if (isset($so->Reminder)) { 
			$w = new DateInterval('PT' . $so->Reminder->getContents() . 'M');
			$w->invert = 1;
			$eo->addNotification(
				'D',
				'R',
				$w
			);
		}
		// Occurrence
        if (isset($so->Recurrence)) {
			// Interval
			if (isset($so->Recurrence->Interval)) {
				$eo->Occurrence->Interval = $so->Recurrence->Interval->getContents();
			}
			// Iterations
			if (isset($so->Recurrence->Occurrences)) {
				$eo->Occurrence->Iterations = $so->Recurrence->Occurrences->getContents();
			}
			// Conclusion
			if (isset($so->Recurrence->Until)) {
				$eo->Occurrence->Concludes = new DateTime($so->Recurrence->Until->getContents());
			}
			// Daily
			if ($so->Recurrence->Type->getContents() == '0') {

				$eo->Occurrence->Pattern = 'A';
				$eo->Occurrence->Precision = 'D';

            }
			// Weekly
			if ($so->Recurrence->Type->getContents() == '1') {
				
				$eo->Occurrence->Pattern = 'A';
				$eo->Occurrence->Precision = 'W';
				
				if (isset($so->Recurrence->DayOfWeek)) {
					$eo->Occurrence->OnDayOfWeek = $this->fromDaysOfWeek((int) $so->Recurrence->DayOfWeek->getContents(), true);
				}

            }
			// Monthly Absolute
			if ($so->Recurrence->Type->getContents() == '2') {
				
				$eo->Occurrence->Pattern = 'A';
				$eo->Occurrence->Precision = 'M';
				
				if (isset($so->Recurrence->DayOfMonth)) {
					$eo->Occurrence->OnDayOfMonth = $this->fromDaysOfMonth($so->Recurrence->DayOfMonth->getContents());
				}

            }
			// Monthly Relative
			if ($so->Recurrence->Type->getContents() == '3') {
				
				$eo->Occurrence->Pattern = 'R';
				$eo->Occurrence->Precision = 'M';
				
				if (isset($so->Recurrence->DayOfWeek)) {
					$eo->Occurrence->OnDayOfWeek = $this->fromDaysOfWeek((int) $so->Recurrence->DayOfWeek->getContents(), true);
				}
				if (isset($so->Recurrence->WeekOfMonth)) {
					$eo->Occurrence->OnWeekOfMonth = $this->fromWeekOfMonth($so->Recurrence->WeekOfMonth->getContents());
				}

            }
			// Yearly Absolute
			if ($so->Recurrence->Type->getContents() == '5') {
				
				$eo->Occurrence->Pattern = 'A';
				$eo->Occurrence->Precision = 'Y';
				
				if (isset($so->Recurrence->DayOfMonth)) {
					$eo->Occurrence->OnDayOfMonth = $this->fromDaysOfMonth($so->Recurrence->DayOfMonth->getContents());
				}
				if (isset($so->Recurrence->MonthOfYear)) {
					$eo->Occurrence->OnMonthOfYear = $this->fromMonthOfYear($so->Recurrence->MonthOfYear->getContents());
				}

            }
			// Yearly Relative
			if ($so->Recurrence->Type->getContents() == '6') {
				
				$eo->Occurrence->Pattern = 'R';
				$eo->Occurrence->Precision = 'Y';
				
				if (isset($so->Recurrence->DayOfWeek)) {
					$eo->Occurrence->OnDayOfWeek = $this->fromDaysOfWeek($so->Recurrence->DayOfWeek->getContents(), true);
				}
				if (isset($so->Recurrence->WeekOfMonth)) {
					$eo->Occurrence->OnWeekOfMonth = $this->fromWeekOfMonth($so->Recurrence->WeekOfMonth->getContents());
				}
				if (isset($so->Recurrence->MonthOfYear)) {
					$eo->Occurrence->OnMonthOfYear = $this->fromMonthOfYear($so->Recurrence->MonthOfYear->getContents());
				}

            }
			// Excludes
			if (isset($so->DeletedOccurrences)) {
				foreach($so->DeletedOccurrences->DeletedOccurrence as $entry) {
					if (isset($entry->Start)) {
						$o->Occurrence->Excludes[] = new DateTime($entry->Start);
					}
				}
			}
        }
        // Attachment(s)
		if (isset($so->Attachments)) {
			// evaluate if property is a collection
			if (!is_array($so->Attachments->Attachment)) {
				$so->Attachments->Attachment = [$so->Attachments->Attachment];
			}
			foreach($so->Attachments->Attachment as $entry) {
				$type = \OCA\EAS\Utile\MIME::fromFileName($entry->DisplayName->getContents());
				$eo->addAttachment(
					'D',
					$entry->FileReference->getContents(), 
					$entry->DisplayName->getContents(),
					$type,
					'B',
					$entry->EstimatedDataSize->getContents()
				);
			}
		}

		return $eo;

    }

	/**
     * convert remote EventObject to remote EasObject
     * 
     * @since Release 1.0.0
     * 
	 * @param EventObject $so		entity as EventObject
	 * 
	 * @return EasObject            entity as EasObject
	 */
	public function fromEventObject(EventObject $so): EasObject {

		// create object
		$eo = new EasObject('AirSync');
		// Time Zone
        if (!empty($so->TimeZone)) {
        	$eo->Timezone = new EasProperty('Calendar', $this->toTimeZone($so->TimeZone, $so->StartsOn));
			//$eo->Timezone = new EasProperty('Calendar', 'LAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAsAAAABAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAACAAIAAAAAAAAAxP///w==');
        }
		elseif (!empty($so->StartsTZ)) {
			$eo->Timezone = new EasProperty('Calendar', $this->toTimeZone($so->StartsTZ, $so->StartsOn));
			//$eo->Timezone = new EasProperty('Calendar', 'LAEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAsAAAABAAIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAACAAIAAAAAAAAAxP///w==');
		}
		// Start Date/Time
		if (!empty($so->StartsOn)) {
			$dt = (clone $so->StartsOn)->setTimezone(new DateTimeZone('UTC'));
			$eo->StartTime = new EasProperty('Calendar', $dt->format('Ymd\\THisp')); // YYYYMMDDTHHMMSSZ
		}
		// End Date/Time
		if (!empty($so->EndsOn)) {
			$dt = (clone $so->EndsOn)->setTimezone(new DateTimeZone('UTC'));
			$eo->EndTime = new EasProperty('Calendar', $dt->format('Ymd\\THisp')); // YYYYMMDDTHHMMSSZ
		}
		// All Day Event
		if((fmod(($so->EndsOn->getTimestamp() - $so->StartsOn->getTimestamp()), 86400) == 0)) {
			$eo->AllDayEvent = new EasProperty('Calendar', 1);
		}
		else {
			$eo->AllDayEvent = new EasProperty('Calendar', 0);
		}
		// Label
        if (!empty($so->Label)) {
            $eo->Subject = new EasProperty('Calendar', $so->Label);
        }
		// Sensitivity
        if (!empty($so->Sensitivity)) {
            $eo->Sensitivity = new EasProperty('Calendar', $this->toSensitivity($so->Sensitivity));
        }
		else {
			$eo->Sensitivity = new EasProperty('Calendar', '2');
		}

		// Notes
        if (!empty($so->Notes)) {
            $eo->Body = new EasObject('AirSyncBase');
            $eo->Body->Type = new EasProperty('AirSyncBase', EasTypes::BODY_TYPE_TEXT);
            //$eo->Body->EstimatedDataSize = new EasProperty('AirSyncBase', strlen($so->Notes));
            $eo->Body->Data = new EasProperty('AirSyncBase', $so->Notes);
        }
		else {
			$eo->Body = new EasObject('AirSyncBase');
            $eo->Body->Type = new EasProperty('AirSyncBase', EasTypes::BODY_TYPE_TEXT);
            $eo->Body->Data = new EasProperty('AirSyncBase', ' ');
		}
		
		// Location
        if (!empty($so->Location)) {
            $eo->Location = new EasProperty('AirSyncBase', $so->Location);
        }
		// Availability
        if (!empty($so->Availability)) {
            $eo->BusyStatus = new EasProperty('Calendar', $this->toAvailability($so->Availability));
        }
		else {
			$eo->BusyStatus = new EasProperty('Calendar', 2);
		}
		// Notifications
        if (count($so->Notifications) > 0) {
			$eo->Reminder = new \OCA\EAS\Utile\Eas\EasProperty('Calendar', 10);
        }
		else {
			$eo->Reminder = new \OCA\EAS\Utile\Eas\EasProperty('Calendar', 0);
		}
		// MeetingStatus
		$eo->MeetingStatus = new \OCA\EAS\Utile\Eas\EasProperty('Calendar', 0);

		// Tag(s)
        if (count($so->Tags) > 0) {
            $eo->Categories = new EasObject('Calendar');
            $eo->Categories->Category = new EasCollection('Calendar');
            foreach($so->Tags as $entry) {
                $eo->Categories->Category[] = new EasProperty('Calendar', $entry);
            }
        }
		

		
		// Occurrence
		if (isset($so->Occurrence) && !empty($so->Occurrence->Precision)) {
			$eo->Recurrence = new EasObject('Calendar');
			// Occurrence Interval
			if (isset($so->Occurrence->Interval)) {
				$eo->Recurrence->Interval = new EasProperty('Calendar', $so->Occurrence->Interval);
			}
			// Occurrence Iterations
			if (!empty($so->Occurrence->Iterations)) {
				$eo->Recurrence->Occurrences = new EasProperty('Calendar', $so->Occurrence->Iterations);
			}
			// Occurrence Conclusion
			if (!empty($so->Occurrence->Concludes)) {
				$eo->Recurrence->Until = new EasProperty('Calendar', $so->Occurrence->Concludes->format('Ymd\\THis')); // YYYY-MM-DDTHH:MM:SS.MSSZ);
			}
			// Based on Precision
			// Occurrence Daily
			if ($so->Occurrence->Precision == 'D') {
				$eo->Recurrence->Type = new EasProperty('Calendar', 0);
			}
			// Occurrence Weekly
			elseif ($so->Occurrence->Precision == 'W') {
				$eo->Recurrence->Type = new EasProperty('Calendar', 1);
				$eo->Recurrence->DayOfWeek = new EasProperty('Calendar', $this->toDaysOfWeek($so->Occurrence->OnDayOfWeek));
			}
			// Occurrence Monthly
			elseif ($so->Occurrence->Precision == 'M') {
				if ($so->Occurrence->Pattern == 'A') {
					$eo->Recurrence->Type = new EasProperty('Calendar', 2);
					$eo->Recurrence->DayOfMonth = new EasProperty('Calendar', $this->toDaysOfMonth($so->Occurrence->OnDayOfMonth));
				}
				elseif ($so->Occurrence->Pattern == 'R') {
					$eo->Recurrence->Type = new EasProperty('Calendar', 3);
					$eo->Recurrence->DayOfWeek = new EasProperty('Calendar', $this->toDaysOfWeek($so->Occurrence->OnDayOfWeek));
					$eo->Recurrence->DayOfMonth = new EasProperty('Calendar', $this->toDaysOfMonth($so->Occurrence->OnDayOfMonth));
				}
			}
			// Occurrence Yearly
			elseif ($so->Occurrence->Precision == 'Y') {
				if ($so->Occurrence->Pattern == 'A') {
					$eo->Recurrence->Type = new EasProperty('Calendar', 5);
					$eo->Recurrence->DayOfMonth = new EasProperty('Calendar', $this->toDaysOfMonth($so->Occurrence->OnDayOfMonth));
					$eo->Recurrence->MonthOfYear = new EasProperty('Calendar', $this->toMonthOfYear($so->Occurrence->OnMonthOfYear));
				}
				elseif ($so->Occurrence->Pattern == 'R') {
					$eo->Recurrence->Type = new EasProperty('Calendar', 6);
					$eo->Recurrence->DayOfWeek = new EasProperty('Calendar', $this->toDaysOfWeek($so->Occurrence->OnDayOfWeek));
					$eo->Recurrence->WeekOfMonth = new EasProperty('Calendar', $this->toDaysOfMonth($so->Occurrence->OnWeekOfMonth));
					$eo->Recurrence->MonthOfYear = new EasProperty('Calendar', $this->toMonthOfYear($so->Occurrence->OnMonthOfYear));
				}
			}
		}
        
		return $eo;

    }

	
    public function generateSignature(EventObject $eo): string {
        
        // clone self
        $o = clone $eo;
        // remove non needed values
        unset($o->ID, $o->CID, $o->UUID, $o->RCID, $o->REID, $o->Origin, $o->Signature, $o->CreatedOn, $o->ModifiedOn);
        // generate signature
        return md5(json_encode($o));

    }
	
	/**
     * Converts EAS (Microsoft/Windows) time zone to DateTimeZone object
     * 
     * @since Release 1.0.0
     * 
     * @param string $zone			eas time zone name
     * 
     * @return DateTimeZone			valid DateTimeZone object on success, or null on failure
     */
	public function fromTimeZone(string $zone): ?DateTimeZone {

		// decode zone from bae64 format
		$zone = base64_decode($zone);
		// convert byte string to array
		$zone = unpack('lbias/a64stdname/vstdyear/vstdmonth/vstdday/vstdweek/vstdhour/vstdminute/vstdsecond/vstdmillis/lstdbias/'
					       . 'a64dstname/vdstyear/vdstmonth/vdstday/vdstweek/vdsthour/vdstminute/vdstsecond/vdstmillis/ldstbias', $zone);
		// extract zone name from array and convert to UTF8
		$name = trim(@iconv('UTF-16', 'UTF-8', $zone['stdname']));
		// convert EWS time zone name to DateTimeZone object
			return \OCA\EAS\Utile\TimeZoneEAS::toDateTimeZone($name);
		
	}

	/**
     * Converts DateTimeZone object to EWS (Microsoft/Windows) time zone name
     * 
     * @since Release 1.0.0
     * 
     * @param DateTimeZone $zone
     * 
     * @return string valid EWS time zone name on success, or null on failure
     */ 
	public function toTimeZone(DateTimeZone $zone, DateTime $date = null): string {

		// convert IANA time zone name to EAS time zone name
		$zone = \OCA\EAS\Utile\TimeZoneEAS::fromIANA($zone->getName());
		// retrieve time mutation
		$mutation = \OCA\EAS\Utile\TimeZoneEAS::findZoneMutation($zone, $date, true);

		if (isset($mutation)) {
			if ($mutation->Type == 'Static') {
				$stdName = $zone;
				$stdBias = $mutation->Alterations[0]->Bias;
				$stdMonth = 0;
				$stdWeek = 0;
				$stdDay = 0;
				$stdHour = 0;
				$stdMinute = 0;
				$dstName = $zone;
				$dstBias = 0;
				$dstMonth = 0;
				$dstWeek = 0;
				$dstDay = 0;
				$dstHour = 0;
				$dstMinute = 0;
			}
			else {
				foreach ($mutation->Alterations as $entry) {
					switch ($entry->Class) {
						case 'Daylight':
							$dstName = $zone;
							$dstBias = $entry->Bias;
							$dstMonth = $entry->Month;
							$dstWeek = $entry->Week;
							$dstDay = $entry->Day;
							$dstHour = $entry->Hour;
							$dstMinute = $entry->Minute;
							break;
						default:
							$stdName = $zone;
							$stdBias = $entry->Bias;
							$stdMonth = $entry->Month;
							$stdWeek = $entry->Week;
							$stdDay = $entry->Day;
							$stdHour = $entry->Hour;
							$stdMinute = $entry->Minute;
							break;
					}
				}
				// convert DST bias to reletive from standard
				$dstBias = ($dstBias - $stdBias) * -1;
			}

			return base64_encode(pack('la64vvvvvvvvla64vvvvvvvvl', $stdBias, $stdName, 0, $stdMonth, $stdDay, $stdWeek, $stdHour, $stdMinute, 0, 0, 0, $dstName, 0, $dstMonth, $dstDay, $dstWeek, $dstHour, $dstMinute, 0, 0, $dstBias));
		}
		else {
			return base64_encode(pack('la64vvvvvvvvla64vvvvvvvvl', 0, 'UTC', 0, 0, 0, 0, 0, 0, 0, 0, 0, 'UTC', 0, 0, 0, 0, 0, 0, 0, 0, 0));
		}

	}

	/**
     * convert remote availability status to event object availability status
	 * 
     * @since Release 1.0.0
     * 
	 * @param string $value		remote availability status value
	 * 
	 * @return string			event object availability status value
	 */
	private function fromAvailability(?string $value): string {
		
		// transposition matrix
		$_tm = array(
			'0' => 'F', // Free
			'1' => 'T',	// Tentative
			'2' => 'B',	// Busy
			'3' => 'O',	// Out of Office
			'4' => 'E'	// Working elsewhere
		);
		// evaluate if value exists
		if (isset($_tm[$value])) {
			// return transposed value
			return $_tm[$value];
		} else {
			// return default value
			return 'B';
		}
		
	}

	/**
     * convert event object availability status to remote availability status
	 * 
     * @since Release 1.0.0
     * 
	 * @param string $value		event object availability status value
	 * 
	 * @return string	 		remote availability status value
	 */
	private function toAvailability(?string $value): string {
		
		// transposition matrix
		$_tm = array(
			'F' => '0', // Free
			'T' => '1',	// Tentative
			'B' => '2',	// Busy
			'O' => '3',	// Out of Office
			'E' => '4'	// Working elsewhere
		);
		// evaluate if value exists
		if (isset($_tm[$value])) {
			// return transposed value
			return $_tm[$value];
		} else {
			// return default value
			return '2';
		}

	}

	/**
     * convert remote sensitivity status to event object sensitivity status
	 * 
     * @since Release 1.0.0
     * 
	 * @param string $value		remote sensitivity status value
	 * 
	 * @return string			event object sensitivity status value
	 */
	private function fromSensitivity(?string $value): string {
		
		// transposition matrix
		$_tm = array(
			'0' => 'N', // Normal
			'1' => 'I',	// Personal/Individual
			'2' => 'P',	// Private
			'3' => 'C',	// Confidential
		);
		// evaluate if value exists
		if (isset($_tm[$value])) {
			// return transposed value
			return $_tm[$value];
		} else {
			// return default value
			return 'N';
		}
		
	}

	/**
     * convert event object sensitivity status to remote sensitivity status
	 * 
     * @since Release 1.0.0
     * 
	 * @param string $value		event object sensitivity status value
	 * 
	 * @return string	 			remote sensitivity status value
	 */
	private function toSensitivity(?string $value): string {
		
		// transposition matrix
		$_tm = array(
			'N' => '0', // Normal
			'I' => '1',	// Personal/Individual
			'P' => '2',	// Private
			'C' => '3',	// Confidential
		);
		// evaluate if value exists
		if (isset($_tm[$value])) {
			// return transposed value
			return $_tm[$value];
		} else {
			// return default value
			return '2';
		}

	}

	/**
     * convert remote days of the week to event object days of the week
	 * 
     * @since Release 1.0.0
     * 
	 * @param int $days - remote days of the week values(s)
	 * @param bool $group - flag to check if days are grouped
	 * 
	 * @return array event object days of the week values(s)
	 */
	private function fromDaysOfWeek(int $days, bool $group = false): array {

		// evaluate if days match any group patterns
		if ($group) {
			if ($days == 65) {
				return [6,7];		// Weekend Days
			}
			elseif ($days == 62) {
				return [1,2,3,4,5];	// Week Days
			}
		}
		// convert day values
		$dow = [];
		if ($days >= 64) {
			$dow[] = 6;		// Saturday
			$days -= 64;
		}
		if ($days >= 32) {
			$dow[] = 5;		// Friday
			$days -= 32;
		}
		if ($days >= 16) {
			$dow[] = 4;		// Thursday
			$days -= 16;
		}
		if ($days >= 8) {
			$dow[] = 3;		// Wednesday
			$days -= 8;
		}
		if ($days >= 4) {
			$dow[] = 2;		// Tuesday
			$days -= 4;
		}
		if ($days >= 2) {
			$dow[] = 1;		// Monday
			$days -= 2;
		}
		if ($days >= 1) {
			$dow[] = 7;		// Sunday
			$days -= 1;
		}
		// sort days
		asort($dow);
		// return converted days
		return $dow;

	}

	/**
     * convert event object days of the week to remote days of the week
	 * 
     * @since Release 1.0.0
     * 
	 * @param array $days - event object days of the week values(s)
	 * @param bool $group - flag to check if days can be grouped 
	 * 
	 * @return string remote days of the week values(s)
	 */
	private function toDaysOfWeek(array $days, bool $group = false): int {
		
		// evaluate if days match any group patterns
		if ($group) {
			sort($days);
			if ($days == [1,2,3,4,5]) {
				return 62;		// Week	Days
			}
			elseif ($days == [6,7]) {
				return 65;		// Weekend Days
			}
		}
        // convert day values
		$dow = 0;
        foreach ($days as $key => $entry) {
			switch ($entry) {
				case 1:
					$dow += 2;	// Monday
					break;
				case 2:
					$dow += 4;	// Tuesday
					break;
				case 3:
					$dow += 8;	// Wednesday
					break;
				case 4:
					$dow += 16;	// Thursday
					break;
				case 5:
					$dow += 32;	// Friday
					break;
				case 6:
					$dow += 64;	// Saturday
					break;
				case 7:
					$dow += 1;	// Sunday
					break;
			}
        }
        // return converted days
        return $dow;

	}

	/**
     * convert remote days of the month to event object days of the month
	 * 
     * @since Release 1.0.0
     * 
	 * @param sting $days - remote days of the month values(s)
	 * 
	 * @return array event object days of the month values(s)
	 */
	private function fromDaysOfMonth(string $days): array {

		// return converted days
		return [$days];

	}

	/**
     * convert event object days of the month to remote days of the month
	 * 
     * @since Release 1.0.0
     * 
	 * @param array $days - event object days of the month values(s)
	 * 
	 * @return string remote days of the month values(s)
	 */
	private function toDaysOfMonth(array $days): string {

        // return converted days
        return $days[0];

	}

	/**
     * convert remote week of the month to event object week of the month
	 * 
     * @since Release 1.0.0
     * 
	 * @param sting $weeks - remote week of the month values(s)
	 * 
	 * @return array event object week of the month values(s)
	 */
	private function fromWeekOfMonth(string $weeks): array {

		// weeks conversion reference
		$_tm = array(
			'1' => 1,
			'2' => 2,
			'3' => 3,
			'4' => 4,
			'5' => -1
		);
		// convert week values
		foreach ($weeks as $key => $entry) {
			if (isset($_tm[$entry])) {
				$weeks[$key] = $_tm[$entry];
			}
		}
		// return converted weeks
		return $weeks;

	}

	/**
     * convert event object week of the month to remote week of the month
	 * 
     * @since Release 1.0.0
     * 
	 * @param array $weeks - event object week of the month values(s)
	 * 
	 * @return string remote week of the month values(s)
	 */
	private function toWeekOfMonth(array $weeks): string {

		// weeks conversion reference
		$_tm = array(
			1 => '1',
			2 => '2',
			3 => '3',
			4 => '4',
			-1 => '5',
			-2 => '4'
		);
		// convert week values
        foreach ($weeks as $key => $entry) {
            if (isset($_tm[$entry])) {
                $weeks[$key] = $_tm[$entry];
            }
        }
        // convert weeks to string
        $weeks = implode(',', $weeks);
        // return converted weeks
        return $weeks;

	}

	/**
     * convert remote month of the year to event object month of the year
	 * 
     * @since Release 1.0.0
     * 
	 * @param sting $months - remote month of the year values(s)
	 * 
	 * @return array event object month of the year values(s)
	 */
	private function fromMonthOfYear(string $months): array {

		// return converted months
		return [$months];

	}

	/**
     * convert event object month of the year to remote month of the year
	 * 
     * @since Release 1.0.0
     * 
	 * @param array $weeks - event object month of the year values(s)
	 * 
	 * @return string remote month of the year values(s)
	 */
	private function toMonthOfYear(array $months): string {

        // return converted months
        return $months[0];

	}

	/**
     * convert remote attendee type to event object type
	 * 
     * @since Release 1.0.0
     * 
	 * @param int $value		remote attendee type value
	 * 
	 * @return string			event object attendee type value
	 */
	private function fromAttendeeType(?int $value): string {
		
		// type conversion reference
		$_type = array(
			1 => 'R', 	// Required
			2 => 'O',	// Optional
			3 => 'A'	// Asset / Resource
		);
		// evaluate if type value exists
		if (isset($_type[$value])) {
			// return converted type value
			return $_type[$value];
		} else {
			// return default type value
			return 'R';
		}
		
	}

	/**
     * convert event object attendee type to remote attendee type
	 * 
     * @since Release 1.0.0
     * 
	 * @param string $value		event object attendee type value
	 * 
	 * @return int	 			remote attendee type value
	 */
	private function toAttendeeType(?string $value): int {
		
		// type conversion reference
		$_type = array(
			'R' => 1, 	// Required
			'O' => 2,	// Optional
			'A' => 3	// Asset / Resource
		);
		// evaluate if type value exists
		if (isset($_type[$value])) {
			// return converted type value
			return $_type[$value];
		} else {
			// return default type value
			return 1;
		}

	}

	/**
     * convert remote attendee status to event object status
	 * 
     * @since Release 1.0.0
     * 
	 * @param int $value		remote attendee status value
	 * 
	 * @return string			event object attendee status value
	 */
	private function fromAttendeeStatus(?int $value): string {
		
		// status conversion reference
		$_status = array(
			0 => 'U', 	// Unknown
			2 => 'T',	// Tentative
			3 => 'A',	// Accepted
			4 => 'D',	// Declined
			5 => 'N'	// Not responded
		);
		// evaluate if status value exists
		if (isset($_status[$value])) {
			// return converted status value
			return $_status[$value];
		} else {
			// return default status value
			return 'N';
		}
		
	}

	/**
     * convert event object attendee status to remote attendee status
	 * 
     * @since Release 1.0.0
     * 
	 * @param string $value		event object attendee status value
	 * 
	 * @return int	 			remote attendee status value
	 */
	private function toAttendeeStatus(?string $value): int {
		
		// status conversion reference
		$_status = array(
			'U' => 0,
			'T' => 2,
			'A' => 3,
			'D' => 4,
			'N' => 5
		);
		// evaluate if status value exists
		if (isset($_status[$value])) {
			// return converted status value
			return $_status[$value];
		} else {
			// return default status value
			return 5;
		}

	}

}
