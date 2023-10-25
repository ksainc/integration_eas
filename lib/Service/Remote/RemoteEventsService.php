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
     * @param string $cid		Collection Id
	 * @param string $cst		Collections Synchronization Token
	 * 
	 * @return object
	 */
	public function syncEntities(string $cid, string $cst): ?object {

        // evaluate synchronization token, if empty or 0 retrieve initial synchronization token
        if (empty($cst) || $cst == '0') {
            // execute command
            $rs = $this->RemoteCommonService->syncEntities($this->DataStore, '0', $cid, []);
            // extract synchronization token
            $cst = $rs->SyncKey->getContents();
        }
        // execute command
        $rs = $this->RemoteCommonService->syncEntities($this->DataStore, $cst, $cid, ['CHANGES' => 1, 'LIMIT' => 32, 'FILTER' => 0, 'BODY' => EasTypes::BODY_TYPE_TEXT]);
        // evaluate response
		if (isset($rs->Status) && $rs->Status->getContents() == '1') {
		    return $rs;
		} else {
			return null;
		}


    }

	/**
     * retrieve collection entity in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param string $cid			Collection Id
	 * @param string $eid			Entity Id
	 * 
	 * @return EventObject       	EventObject on success / Null on failure
	 */
	public function fetchEntity(string $cid, string $eid): ?EventObject {

        // execute command
		$ro = $this->RemoteCommonService->fetchEntity($this->DataStore, $cid, $eid, ['BODY' => EasTypes::BODY_TYPE_TEXT]);
        // validate response
		if (isset($ro->Status) && $ro->Status->getContents() == '1') {
            // convert to contact object
            $eo = $this->toEventObject($ro->Properties);
            $eo->ID = $ro->EntityId->getContents();
            $eo->CID = $ro->CollectionId->getContents();
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
	public function createEntity(string $cid, string $cst, EventObject $so): ?EventObject {

        // convert source EventObject to EasObject
        $eo = $this->fromEventObject($so);
	    // execute command
	    $ro = $this->RemoteCommonService->createEntity($this->DataStore, $cid, $cst, EasTypes::ENTITY_TYPE_EVENT, $eo);
        // evaluate response
        if (isset($ro->Status) && $ro->Status->getContents() == '1') {
			$eo = clone $so;
			$eo->ID = $ro->Responses->Add->EntityId->getContents();
            $eo->CID = $ro->CollectionId->getContents();
			// deposit attachment(s)
			if (count($eo->Attachments) > 0) {
				// create attachments in remote data store
				$eo->Attachments = $this->createCollectionItemAttachment($eo->ID, $eo->Attachments);
				$eo->State = $eo->Attachments[0]->AffiliateState;
			}
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
     * @param string $cid			Collection Id
	 * @param string $cst			Collection Synchronization Token
     * @param EventObject $so     	Source Object
	 * 
	 * @return EventObject        	EventObject on success / Null on failure
	 */
	public function updateEntity(string $cid, string $cst, EventObject $so): ?EventObject {

        // extract source object id
        $eid = $eo->ID;
        // convert source EventObject to EasObject
        $eo = $this->fromEventObject($so);
	    // execute command
	    $ro = $this->RemoteCommonService->updateEntity($this->DataStore, $cid, $cst, $eid, $eo);
        // evaluate response
        if (isset($ro->Status) && $ro->Status->getContents() == '1') {
			$eo = clone $so;
			$eo->ID = $ro->Responses->Modify->EntityId;
            $eo->CID = $cid;
			// deposit attachment(s)
			if (count($so->Attachments) > 0) {
				// create attachments in remote data store
				$eo->Attachments = $this->createCollectionItemAttachment($eo->ID, $eo->Attachments);
				$eo->State = $eo->Attachments[0]->AffiliateState;
			}
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
		// Start Time Zone
        if (!empty($so->StartTimeZone)) {
			$eo->StartsTZ = $this->fromTimeZone($so->StartTimeZone->getContents());
        }
		// End Time Zone
		if (!empty($so->EndTimeZone)) {
			$eo->EndsTZ = $this->fromTimeZone($so->EndTimeZone->getContents());
		}
		// Time Zone
        if (!empty($so->Timezone)) {
        	$eo->TimeZone = $this->fromTimeZone($so->Timezone->getContents());
			if (isset($eo->TimeZone)) {
				if (!isset($eo->StartsTZ)) { $eo->StartsTZ = clone $eo->TimeZone; }
				//if (!isset($eo->EndsTZ)) { $eo->EndsTZ = clone $eo->TimeZone; }
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
			$v = (int) $so->BusyStatus->getContents();
			$eo->Availability = ($v > -1 && $v < 5) ? $v : 2;
		}
		// Sensitivity
		if (!empty($so->Sensitivity)) {
			$v = (int) $so->Sensitivity->getContents();
			$eo->Sensitivity = ($v > -1 && $v < 4) ? $v : 0;
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
        // Label
        if (!empty($so->Label)) {
            $eo->FileAs = new EasProperty('Contacts', $so->Label);
        }
		// Name - Last
        if (!empty($so->Name->Last)) {
            $eo->LastName = new EasProperty('Contacts', $so->Name->Last);
        }
        // Name - First
        if (!empty($so->Name->First)) {
            $eo->FirstName = new EasProperty('Contacts', $so->Name->First);
        }
        // Name - Other
        if (!empty($so->Name->Other)) {
            $eo->MiddleName = new EasProperty('Contacts', $so->Name->Other);
        }
        // Name - Prefix
        if (!empty($so->Name->Prefix)) {
            $eo->Title = new EasProperty('Contacts', $so->Name->Prefix);
        }
        // Name - Suffix
        if (!empty($so->Name->Suffix)) {
            $eo->Suffix = new EasProperty('Contacts', $so->Name->Suffix);
        }
        // Name - Phonetic - Last
        if (!empty($so->Name->PhoneticLast)) {
            $eo->YomiLastName = new EasProperty('Contacts', $so->Name->PhoneticLast);
        }
        // Name - Phonetic - First
        if (!empty($so->Name->PhoneticFirst)) {
            $eo->YomiFirstName = new EasProperty('Contacts', $so->Name->PhoneticFirst);
        }
        // Name - Aliases
        if (!empty($so->Name->Aliases)) {
            $eo->NickName = new EasProperty('Contacts', $so->Name->Aliases);
        }
        // Birth Day
        if (!empty($so->BirthDay)) {
            $eo->Birthday = new EasProperty('Contacts', $so->BirthDay->format('Y-m-d\\T11:59:00.000\\Z')); //2018-01-01T11:59:00.000Z
        }
        // Partner
        if (!empty($so->Partner)) {
            $eo->Spouse = new EasProperty('Contacts', $so->Partner);
        }
        // Anniversary Day
        if (!empty($so->AnniversaryDay)) {
            $eo->Anniversary = new EasProperty('Contacts', $so->AnniversaryDay->format('Y-m-d\\T11:59:00.000\\Z')); //2018-01-01T11:59:00.000Z
        }
        // Address(es)
        if (count($so->Address) > 0) {
            $types = [
                'WORK' => true,
                'HOME' => true,
                'OTHER' => true
            ];
            foreach ($so->Address as $entry) {
                // Address - Work
                if ($entry->Type == 'WORK' && $types[$entry->Type]) {
                    // Street
                    if (!empty($entry->Street)) {
                        $eo->BusinessAddressStreet = new EasProperty('Contacts', $entry->Street);
                    }
                    // Locality
                    if (!empty($entry->Locality)) {
                        $eo->BusinessAddressCity = new EasProperty('Contacts', $entry->Locality);
                    }
                    // Region
                    if (!empty($entry->Region)) {
                        $eo->BusinessAddressState = new EasProperty('Contacts', $entry->Region);
                    }
                    // Code
                    if (!empty($entry->Code)) {
                        $eo->BusinessAddressPostalCode = new EasProperty('Contacts', $entry->Code);
                    }
                    // Country
                    if (!empty($entry->Country)) {
                        $eo->BusinessAddressCountry = new EasProperty('Contacts', $entry->Country);
                    }
                    // disable type
                    $types[$entry->Type] = false;
                }
                // Address - Home
                if ($entry->Type == 'HOME' && $types[$entry->Type]) {
                    // Street
                    if (!empty($entry->Street)) {
                        $eo->HomeAddressStreet = new EasProperty('Contacts', $entry->Street);
                    }
                    // Locality
                    if (!empty($entry->Locality)) {
                        $eo->HomeAddressCity = new EasProperty('Contacts', $entry->Locality);
                    }
                    // Region
                    if (!empty($entry->Region)) {
                        $eo->HomeAddressState = new EasProperty('Contacts', $entry->Region);
                    }
                    // Code
                    if (!empty($entry->Code)) {
                        $eo->HomeAddressPostalCode = new EasProperty('Contacts', $entry->Code);
                    }
                    // Country
                    if (!empty($entry->Country)) {
                        $eo->HomeAddressCountry = new EasProperty('Contacts', $entry->Country);
                    }
                    // disable type
                    $types[$entry->Type] = false;
                }
                // Address - Other
                if ($entry->Type == 'OTHER' && $types[$entry->Type]) {
                    // Street
                    if (!empty($entry->Street)) {
                        $eo->OtherAddressStreet = new EasProperty('Contacts', $entry->Street);
                    }
                    // Locality
                    if (!empty($entry->Locality)) {
                        $eo->OtherAddressCity = new EasProperty('Contacts', $entry->Locality);
                    }
                    // Region
                    if (!empty($entry->Region)) {
                        $eo->OtherAddressState = new EasProperty('Contacts', $entry->Region);
                    }
                    // Code
                    if (!empty($entry->Code)) {
                        $eo->OtherAddressPostalCode = new EasProperty('Contacts', $entry->Code);
                    }
                    // Country
                    if (!empty($entry->Country)) {
                        $eo->OtherAddressCountry = new EasProperty('Contacts', $entry->Country);
                    }
                    // disable type
                    $types[$entry->Type] = false;
                }
            }
        }
        // Phone(s)
        if (count($so->Phone) > 0) {
            $types = array(
                'WorkVoice1' => true,
                'WorkVoice2' => true,
                'WorkFax' => true,
                'HomeVoice1' => true,
                'HomeVoice2' => true,
                'HomeFax' => true,
                'Cell' => true,
            );
            foreach ($so->Phone as $entry) {
                if ($entry->Type == 'WORK' && $entry->SubType == 'VOICE' && $types['WorkVoice1']) {
                    $eo->BusinessPhoneNumber = new EasProperty('Contacts', $entry->Number);
                    $types['WorkVoice1'] = false;
                }
                elseif ($entry->Type == 'WORK' && $entry->SubType == 'VOICE' && $types['WorkVoice2']) {
                    $eo->Business2PhoneNumber = new EasProperty('Contacts', $entry->Number);
                    $types['WorkVoice2'] = false;
                }
                elseif ($entry->Type == 'WORK' && $entry->SubType == 'FAX' && $types['WorkFax']) {
                    $eo->BusinessFaxNumber = new EasProperty('Contacts', $entry->Number);
                    $types['WorkFax'] = false;
                }
                elseif ($entry->Type == 'HOME' && $entry->SubType == 'VOICE' && $types['HomeVoice1']) {
                    $eo->HomePhoneNumber = new EasProperty('Contacts', $entry->Number);
                    $types['HomeVoice1'] = false;
                }
                elseif ($entry->Type == 'HOME' && $entry->SubType == 'VOICE' && $types['HomeVoice2']) {
                    $eo->Home2PhoneNumber = new EasProperty('Contacts', $entry->Number);
                    $types['HomeVoice2'] = false;
                }
                elseif ($entry->Type == 'WORK' && $entry->SubType == 'FAX' && $types['HomeFax']) {
                    $eo->HomeFaxNumber = new EasProperty('Contacts', $entry->Number);
                    $types['HomeFax'] = false;
                }
                elseif ($entry->Type == 'CELL' && $types['Cell'] != true) {
                    $eo->MobilePhoneNumber = new EasProperty('Contacts', $entry->Number);
                    $types['Cell'] = false;
                }
            }
        }
        // Email(s)
        if (count($so->Email) > 0) {
            $types = array(
                'WORK' => true,
                'HOME' => true,
                'OTHER' => true
            );
            foreach ($so->Email as $entry) {
                if (isset($types[$entry->Type]) && $types[$entry->Type] == true && !empty($entry->Address)) {
                    switch ($entry->Type) {
                        case 'WORK':
                            $eo->Email1Address = new EasProperty('Contacts', $entry->Address);
                            break;
                        case 'HOME':
                            $eo->Email2Address = new EasProperty('Contacts', $entry->Address);
                            break;
                        case 'OTHER':
                            $eo->Email3Address = new EasProperty('Contacts', $entry->Address);
                            break;
                    }
                    $types[$entry->Type] = false;
                }
            }
        }
        // Manager Name
        if (!empty($so->Name->Manager)) {
            $eo->ManagerName = new EasProperty('Contacts', $so->Name->Manager);
        }
        // Assistant Name
        if (!empty($so->Name->Assistant)) {
            $eo->AssistantName = new EasProperty('Contacts', $so->Name->Assistant);
        }
        // Occupation Organization
        if (!empty($so->Occupation->Organization)) {
            $eo->CompanyName = new EasProperty('Contacts', $so->Occupation->Organization);
        }
        // Occupation Department
        if (!empty($so->Occupation->Department)) {
            $eo->Department = new EasProperty('Contacts', $so->Occupation->Department);
        }
        // Occupation Title
        if (!empty($so->Occupation->Title)) {
            $eo->JobTitle = new EasProperty('Contacts', $so->Occupation->Title);
        }
        // Occupation Location
        if (!empty($so->Occupation->Location)) {
            $eo->OfficeLocation = new EasProperty('Contacts', $so->Occupation->Location);
        }
        // URL / Website
        if (!empty($so->URI)) {
            $eo->WebPage = new EasProperty('Contacts', $so->URI);
        }
        
		return $eo;

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
		$zone = unpack("lbias/a64tzname/vdstendyear/vdstendmonth/vdstendday/vdstendweek/vdstendhour/"
                        ."vdstendminute/vdstendsecond/vdstendmillis/lstdbias/a64tznamedst/vdststartyear/"
                        ."vdststartmonth/vdststartday/vdststartweek/vdststarthour/vdststartminute/"
                        ."vdststartsecond/vdststartmillis/ldstbias", $zone);
		// extract zone name from array and convert to UTF8
		$name = trim(@iconv('UTF-16', 'UTF-8', $zone['tzname']));
		// convert EWS time zone name to DateTimeZone object
		return \OCA\EAS\Utile\TimeZoneEWS::toDateTimeZone($name);
		
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
	public function toTimeZone(DateTimeZone $zone): ?string {

		// convert DateTimeZone object to EWS time zone name
		return \OCA\EAS\Utile\TimeZoneEWS::fromDateTimeZone($zone);

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
