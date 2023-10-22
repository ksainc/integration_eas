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

use OCA\EAS\AppInfo\Application;
use OCA\EAS\Service\Remote\RemoteCommonService;
use OCA\EAS\Objects\TaskCollectionObject;
use OCA\EAS\Objects\TaskObject;
use OCA\EAS\Objects\TaskAttachmentObject;
use OCA\EAS\Utile\Eas\EasClient;
use OCA\EAS\Utile\Eas\EasCollection;
use OCA\EAS\Utile\Eas\EasObject;
use OCA\EAS\Utile\Eas\EasProperty;
use OCA\EAS\Utile\Eas\EasTypes;

class RemoteTasksService {
	/**
	 * @var LoggerInterface
	 */
	private $logger;
	/**
	 * @var RemoteCommonService
	 */
	private $RemoteCommonService;
	/**
	 * @var DateTimeZone
	 */
	public ?DateTimeZone $SystemTimeZone = null;
    /**
	 * @var DateTimeZone
	 */
	public ?DateTimeZone $UserTimeZone = null;
	/**
	 * @var EasClient
	 */
	public ?EasClient $DataStore = null;

	public function __construct (string $appName,
								LoggerInterface $logger,
								RemoteCommonService $RemoteCommonService) {
		$this->logger = $logger;
		$this->RemoteCommonService = $RemoteCommonService;
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
	 * @return TaskCollectionObject  	TaskCollectionObject on success / Null on failure
	 */
	public function fetchCollection(string $cht, string $chl, string $cid): ?TaskCollectionObject {

        // execute command
		$cr = $this->RemoteCommonService->fetchFolder($this->DataStore, $cid, false, 'I', $this->constructDefaultCollectionProperties());
        // process response
		if (isset($cr) && (count($cr->TasksFolder) > 0)) {
		    $ec = new TaskCollectionObject(
				$cr->TasksFolder[0]->FolderId->Id,
				$cr->TasksFolder[0]->DisplayName,
				$cr->TasksFolder[0]->FolderId->ChangeKey,
				$cr->TasksFolder[0]->TotalCount
			);
			if (isset($cr->TasksFolder[0]->ParentFolderId->Id)) {
				$ec->AffiliationId = $cr->TasksFolder[0]->ParentFolderId->Id;
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
	 * @return TaskCollectionObject  	TaskCollectionObject on success / Null on failure
	 */
	public function createCollection(string $cht, string $chl, string $name): ?TaskCollectionObject {
        
		// execute command
		$rs = $RemoteCommonService->createCollection($this->DataStore, $cht, $chl, $name, EasTypes::COLLECTION_TYPE_USER_TASKS);
        // process response
		if (isset($rs->Status) && $rs->Status->getContents() == '1') {
		    return new TaskCollectionObject(
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
	 * @return TaskCollectionObject  	TaskCollectionObject on success / Null on failure
	 */
	public function updateCollection(string $cht, string $chl, string $cid, string $name): ?TaskCollectionObject {
        
		// execute command
		$rs = $RemoteCommonService->updateCollection($this->DataStore, $cht, $chl, $cid, $name);
        // process response
		if (isset($rs->Status) && $rs->Status->getContents() == '1') {
		    return new TaskCollectionObject(
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

        // evaluate synchronization token, if 0 retrieve initial synchronization token
        if ($cst == '0') {
            // execute command
            $rs = $this->RemoteCommonService->syncEntities($this->DataStore, $cst, $cid, []);
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
	 * @return TaskObject        	TaskObject on success / Null on failure
	 */
	public function fetchEntity(string $cid, string $eid): ?TaskObject {

        // execute command
		$ro = $this->RemoteCommonService->fetchEntity($this->DataStore, $cid, $eid, ['BODY' => EasTypes::BODY_TYPE_TEXT]);
        // validate response
		if (isset($ro->Status) && $ro->Status->getContents() == '1') {
            // convert to contact object
            $to = $this->toTaskObject($ro->Properties);
            $to->ID = $ro->EntityId->getContents();
            $to->CID = $ro->CollectionId->getContents();
            // retrieve attachment(s) from remote data store
			if (count($to->Attachments) > 0) {
				$to->Attachments = $this->fetchCollectionItemAttachment(array_column($to->Attachments, 'Id'));
			}
            // return object
		    return $to;
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
     * @param TaskObject $so     Source Object
	 * 
	 * @return TaskObject        TaskObject on success / Null on failure
	 */
	public function createEntity(string $cid, string $cst, TaskObject $so): ?TaskObject {

        // convert source TaskObject to EasObject
        $eo = $this->fromTaskObject($so);
	    // execute command
	    $ro = $this->RemoteCommonService->createEntity($this->DataStore, $cid, $cst, EasTypes::ENTITY_TYPE_TASK, $eo);
        // evaluate response
        if (isset($ro->Status) && $ro->Status->getContents() == '1') {
			$to = clone $so;
			$to->ID = $ro->Responses->Add->EntityId->getContents();
            $to->CID = $ro->CollectionId->getContents();
			// deposit attachment(s)
			if (count($to->Attachments) > 0) {
				// create attachments in remote data store
				$to->Attachments = $this->createCollectionItemAttachment($to->ID, $to->Attachments);
				$to->State = $to->Attachments[0]->AffiliateState;
			}
            return $to;
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
     * @param TaskObject $so     Source Object
	 * 
	 * @return TaskObject        TaskObject on success / Null on failure
	 */
	public function updateEntity(string $cid, string $cst, TaskObject $so): ?TaskObject {

        // extract source object id
        $eid = $to->ID;
        // convert source TaskObject to EasObject
        $eo = $this->fromTaskObject($so);
	    // execute command
	    $ro = $this->RemoteCommonService->updateEntity($this->DataStore, $cid, $cst, $eid, $eo);
        // evaluate response
        if (isset($ro->Status) && $ro->Status->getContents() == '1') {
			$to = clone $so;
			$to->ID = $ro->Responses->Modify->EntityId;
            $to->CID = $cid;
			// deposit attachment(s)
			if (count($so->Attachments) > 0) {
				// create attachments in remote data store
				$to->Attachments = $this->createCollectionItemAttachment($to->ID, $to->Attachments);
				$to->State = $to->Attachments[0]->AffiliateState;
			}
            return $to;
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
     * @param array $sc - Collection of TaskAttachmentObject(S)
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
			$to = new \OCA\EAS\Utile\Eas\Type\FileAttachmentType();
			$to->IsInline = false;
			$to->IsContactPhoto = false;
			$to->Name = $entry->Name;
			$to->ContentId = $entry->Name;
			$to->ContentType = $entry->Type;
			$to->Size = $entry->Size;
			
			switch ($entry->Encoding) {
				case 'B':
					$to->Content = $entry->Data;
					break;
				case 'B64':
					$to->Content = base64_decode($entry->Data);
					break;
			}
			// insert command object in to collection
			$cc[] = $to;
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
	 * @return TaskObject		entity as TaskObject
	 */
	public function toTaskObject(EasObject $so): TaskObject {
		// create object
		$to = new TaskObject();
		// Origin
		$to->Origin = 'R';
		// Start Date/Time
		if (!empty($so->UtcStartDate)) {
			$to->StartsOn = new DateTime($so->UtcStartDate->getContents());
		}
		// Due Date/Time
        if (!empty($so->UtcDueDate)) {
            $to->DueOn = new DateTime($so->UtcDueDate->getContents());
        }
		// Completed Date/Time
        if (!empty($so->DateCompleted)) {
            $to->CompletedOn = new DateTime($so->DateCompleted->getContents());
        }
		// Label
        if (!empty($so->Subject)) {
            $to->Label = $so->Subject->getContents();
        }
		// Notes
		if (!empty($so->Body->Data)) {
			$to->Notes = $so->Body->Data->getContents();
		}
		// Progress
        if (!empty($so->Complete)) {
            $to->Progress = $so->Complete->getContents();
        }
		// Priority
		if (!empty($so->Importance)) {
			$to->Priority = $this->fromImportance((int) $so->Importance->getContents());
		}
		// Sensitivity
		if (!empty($so->Sensitivity)) {
			$v = (int) $so->Sensitivity->getContents();
			$to->Sensitivity = ($v > -1 && $v < 4) ? $v : 0;
		}
		// Tag(s)
        if (isset($so->Categories)) {
            if (!is_array($so->Categories->Category)) {
                $so->Categories->Category = [$so->Categories->Category];
            }
			foreach($so->Categories->Category as $entry) {
				$to->addTag($entry->getContents());
			}
        }
		// Notification(s)
		if (isset($so->Reminder)) { 
			$w = new DateInterval('PT' . $so->Reminder->getContents() . 'M');
			$w->invert = 1;
			$to->addNotification(
				'D',
				'R',
				$w
			);
		}
		// Occurrence
        if (isset($so->Recurrence)) {
			// Interval
			if (isset($so->Recurrence->Interval)) {
				$to->Occurrence->Interval = $so->Recurrence->Interval->getContents();
			}
			// Iterations
			if (isset($so->Recurrence->Occurrences)) {
				$to->Occurrence->Iterations = $so->Recurrence->Occurrences->getContents();
			}
			// Conclusion
			if (isset($so->Recurrence->Until)) {
				$to->Occurrence->Concludes = new DateTime($so->Recurrence->Until->getContents());
			}
			// Daily
			if ($so->Recurrence->Type->getContents() == '0') {

				$to->Occurrence->Pattern = 'A';
				$to->Occurrence->Precision = 'D';

            }
			// Weekly
			if ($so->Recurrence->Type->getContents() == '1') {
				
				$to->Occurrence->Pattern = 'A';
				$to->Occurrence->Precision = 'W';
				
				if (isset($so->Recurrence->DayOfWeek)) {
					$to->Occurrence->OnDayOfWeek = $this->fromDaysOfWeek($so->Recurrence->DayOfWeek->getContents());
				}

            }
			// Monthly Absolute
			if ($so->Recurrence->Type->getContents() == '2') {
				
				$to->Occurrence->Pattern = 'A';
				$to->Occurrence->Precision = 'M';
				
				if (isset($so->Recurrence->DayOfMonth)) {
					$to->Occurrence->OnDayOfMonth = $this->fromDaysOfMonth($so->Recurrence->DayOfMonth->getContents());
				}

            }
			// Monthly Relative
			if ($so->Recurrence->Type->getContents() == '3') {
				
				$to->Occurrence->Pattern = 'R';
				$to->Occurrence->Precision = 'M';
				
				if (isset($so->Recurrence->DaysOfWeek)) {
					$to->Occurrence->OnDayOfWeek = $this->fromDaysOfWeek($so->Recurrence->DaysOfWeek, true);
				}
				if (isset($so->Recurrence->DayOfWeekIndex)) {
					$to->Occurrence->OnWeekOfMonth = $this->fromWeekOfMonth($so->Recurrence->WeekOfMonth->getContents());
				}

            }
			// Yearly Absolute
			if ($so->Recurrence->Type->getContents() == '5') {
				
				$to->Occurrence->Pattern = 'A';
				$to->Occurrence->Precision = 'Y';
				
				if (isset($so->Recurrence->Month)) {
					$to->Occurrence->OnMonthOfYear = $this->fromMonthOfYear($so->Recurrence->MonthOfYear->getContents());
				}
				if (isset($so->Recurrence->DayOfMonth)) {
					$to->Occurrence->OnDayOfMonth = $this->fromDaysOfMonth($so->Recurrence->DayOfMonth->getContents());
				}

            }
			// Yearly Relative
			if ($so->Recurrence->Type->getContents() == '6') {
				
				$to->Occurrence->Pattern = 'R';
				$to->Occurrence->Precision = 'Y';
				
				if (isset($so->Recurrence->DaysOfWeek)) {
					$to->Occurrence->OnDayOfWeek = $this->fromDaysOfWeek($so->Recurrence->DayOfWeek->getContents(), true);
				}
				if (isset($so->Recurrence->DayOfWeekIndex)) {
					$to->Occurrence->OnWeekOfMonth = $this->fromWeekOfMonth($so->Recurrence->WeekOfMonth->getContents());
				}
				if (isset($so->Recurrence->Month)) {
					$to->Occurrence->OnMonthOfYear = $this->fromMonthOfYear($so->Recurrence->MonthOfYear->getContents());
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
			if (!is_array($so->Attachments->Attachment)) {
				$so->Attachments->Attachment = [$so->Attachments->Attachment];
			}
			foreach($so->Attachments->Attachment as $entry) {
				$type = \OCA\EAS\Utile\MIME::fromFileName($entry->DisplayName->getContents());
				$to->addAttachment(
					'D',
					$entry->FileReference->getContents(), 
					$entry->DisplayName->getContents(),
					$type,
					'B',
					$entry->EstimatedDataSize->getContents()
				);
			}
		}
        

		return $to;

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
     * convert remote importance value to task object priority value
	 * 
     * @since Release 1.0.0
     * 
	 * @param int $value		remote importance value
	 * 
	 * @return int 				task object priority value
	 */
	private function fromImportance(?int $value): int {
		
		// EAS: 0 = low, 1 = normal (default), 2 = high
		// VTODO: 0 = undefined, 1-3 = high, 4-6 = normal, 7-9 = low

		// evaluate remote level and return local equvialent
		if ($value == 2) {
			return 2;		// high priority
		}
		elseif ($value == 0) {
			return 8;		// low priority
		}
		else {
			return 5;		// normal priority
		}
		
	}

	/**
     * convert task object priority value to remote importance value
	 * 
     * @since Release 1.0.0
     * 
	 * @param int $value		task object priority value
	 * 
	 * @return int				remote importance value
	 */
	private function toImportance(?int $value): int {

		// EAS: 0 = low, 1 = normal (default), 2 = high
		// VTODO: 0 = undefined, 1-3 = high, 4-6 = normal, 7-9 = low

		// evaluate local level and return remote equvialent
		if ($value > 0 && $value < 4) {
			return 2;		// high priority
		}
		elseif ($value > 6 && $value < 10) {
			return 0;		// low priority
		}
		else {
			return 1;		// normal priority
		}

	}

}
