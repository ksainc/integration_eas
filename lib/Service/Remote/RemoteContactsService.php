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
use DateTimeInterface;
use finfo;
use Psr\Log\LoggerInterface;

use OCA\EAS\AppInfo\Application;
use OCA\EAS\Service\Remote\RemoteCommonService;
use OCA\EAS\Objects\ContactCollectionObject;
use OCA\EAS\Objects\ContactObject;
use OCA\EAS\Objects\ContactAttachmentObject;
use OCA\EAS\Utile\Eas\EasClient;
use OCA\EAS\Utile\Eas\EasCollection;
use OCA\EAS\Utile\Eas\EasObject;
use OCA\EAS\Utile\Eas\EasProperty;
use OCA\EAS\Utile\Eas\EasTypes;

class RemoteContactsService {
	/**
	 * @var LoggerInterface
	 */
	private $logger;
	/**
	 * @var RemoteCommonService
	 */
	private $RemoteCommonService;
	/**
	 * @var EasClient
	 */
	public ?EasClient $DataStore = null;
    /**
	 * @var Object
	 */
	private ?object $DefaultCollectionProperties = null;
	/**
	 * @var Object
	 */
	private ?object $DefaultItemProperties = null;

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
	 * @return ContactCollectionObject  ContactCollectionObject on success / Null on failure
	 */
	public function fetchCollection(string $cht, string $chl, string $cid): ?ContactCollectionObject {

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
	 * @return ContactCollectionObject  ContactCollectionObject on success / Null on failure
	 */
	public function createCollection(string $cht, string $chl, string $name): ?ContactCollectionObject {
        
		// execute command
		$rs = $RemoteCommonService->createCollection($this->DataStore, $cht, $chl, $name, EasTypes::COLLECTION_TYPE_USER_CONTACTS);
        // process response
		if (isset($rs->Status) && $rs->Status->getContents() == '1') {
		    return new ContactCollectionObject(
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
	 * @return ContactCollectionObject  ContactCollectionObject on success / Null on failure
	 */
	public function updateCollection(string $cht, string $chl, string $cid, string $name): ?ContactCollectionObject {
        
		// execute command
		$rs = $RemoteCommonService->updateCollection($this->DataStore, $cht, $chl, $cid, $name);
        // process response
		if (isset($rs->Status) && $rs->Status->getContents() == '1') {
		    return new ContactCollectionObject(
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
	 * @return ContactObject        ContactObject on success / Null on failure
	 */
	public function fetchEntity(string $cid, string $eid): ?ContactObject {

        // execute command
		$ro = $this->RemoteCommonService->fetchEntity($this->DataStore, $cid, $eid, ['BODY' => EasTypes::BODY_TYPE_TEXT]);
        // validate response
		if (isset($ro->Status) && $ro->Status->getContents() == '1') {
            // convert to contact object
            $co = $this->toContactObject($ro->Properties);
            $co->ID = $ro->EntityId->getContents();
            $co->CID = $ro->CollectionId->getContents();
            // retrieve attachment(s) from remote data store
			if (count($co->Attachments) > 0) {
				// retrieve all attachments
				$ro = $this->RemoteCommonService->fetchAttachment($this->DataStore, array_column($co->Attachments, 'Id'));
				// evaluate returned object
				if (count($ro) > 0) {
					foreach ($ro as $entry) {
						// evaluate status
						if (isset($entry->Status) && $entry->Status->getContents() == '1') {
							$key = array_search($entry->FileReference->getContents(), array_column($co->Attachments, 'Id'));
							if ($key !== false) {
								$co->Attachments[$key]->Data = base64_decode($entry->Properties->Data->getContents());
							}
						}
					}
				}
			}
            // return object
		    return $co;
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
     * @param ContactObject $so     Source Object
	 * 
	 * @return ContactObject        ContactObject on success / Null on failure
	 */
	public function createEntity(string $cid, string $cst, ContactObject $so): ?ContactObject {

        // convert source ContactObject to EasObject
        $eo = $this->fromContactObject($so);
	    // execute command
	    $ro = $this->RemoteCommonService->createEntity($this->DataStore, $cid, $cst, EasTypes::ENTITY_TYPE_CONTACT, $eo);
        // evaluate response
        if (isset($ro->Status) && $ro->Status->getContents() == '1') {
			$co = clone $so;
			$co->ID = $ro->Responses->Add->EntityId->getContents();
            $co->CID = $ro->CollectionId->getContents();
			// deposit attachment(s)
			if (count($co->Attachments) > 0) {
				// create attachments in remote data store
				$co->Attachments = $this->createCollectionItemAttachment($co->ID, $co->Attachments);
				$co->State = $co->Attachments[0]->AffiliateState;
			}
            return $co;
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
     * @param ContactObject $so     Source Object
	 * 
	 * @return ContactObject        ContactObject on success / Null on failure
	 */
	public function updateEntity(string $cid, string $cst, ContactObject $so): ?ContactObject {

        // extract source object id
        $eid = $co->ID;
        // convert source ContactObject to EasObject
        $eo = $this->fromContactObject($so);
	    // execute command
	    $ro = $this->RemoteCommonService->updateEntity($this->DataStore, $cid, $cst, $eid, $eo);
        // evaluate response
        if (isset($ro->Status) && $ro->Status->getContents() == '1') {
			$co = clone $so;
			$co->ID = $ro->Responses->Modify->EntityId;
            $co->CID = $cid;
			// deposit attachment(s)
			if (count($so->Attachments) > 0) {
				// create attachments in remote data store
				$co->Attachments = $this->createCollectionItemAttachment($co->ID, $co->Attachments);
				$co->State = $co->Attachments[0]->AffiliateState;
			}
            return $co;
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
     * create collection item attachment in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param string $aid - Affiliation ID
     * @param array $sc - Collection of ContactAttachmentObject(S)
	 * 
	 * @return array
	 */
	public function createEntityAttachment(string $aid, array $batch): array {

		// check to for entries in batch collection
        if (count($batch) == 0) {
            return array();
        }
		// construct command collection place holder
		$cc = array();
		// process batch
		foreach ($batch as $key => $entry) {
			// construct command object
			$co = new \OCA\EAS\Utile\Eas\Type\FileAttachmentType();
			$co->IsInline = false;
			$co->ContentId = $entry->Name;
			$co->ContentType = $entry->Type;
            $co->Name = $entry->Name;
			$co->Size = $entry->Size;

            if ($entry->Flag == 'CP') {
                $co->IsContactPhoto = true;
            }
            else {
                $co->IsContactPhoto = false;
            }
            
			switch ($entry->Encoding) {
				case 'B':
					$co->Content = $entry->Data;
					break;
				case 'B64':
					$co->Content = base64_decode($entry->Data);
					break;
			}
			// insert command object in to collection
			$cc[] = $co;
		}
		// execute command(s)
		$rs = $this->RemoteCommonService->createAttachment($this->DataStore, $aid, $cc);
		// construct results collection place holder
		$rc = array();
		// check for response
		if (isset($rs)) {
			// process collection of objects
			foreach($rs as $key => $entry) {
				$ro = $batch[$key];
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
     * delete collection item attachment from remote storage
     * 
     * @since Release 1.0.0
     * 
     * @param string $aid - Attachment ID
	 * 
	 * @return array
	 */
	public function deleteEntityAttachment(array $batch): array {

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
	 * @return ContactObject    entity as ContactObject
	 */
	public function toContactObject(EasObject $so): ContactObject {

		// create object
		$co = new ContactObject();
        // Origin
		$o->Origin = 'R';
        // Label
        if (!empty($so->FileAs)) {
            $co->Label = $so->FileAs->getContents();
        }
		// Name - Last
        if (isset($so->LastName)) {
            $co->Name->Last = $so->LastName->getContents();
        }
        // Name - First
        if (isset($so->FirstName)) {
            $co->Name->First = $so->FirstName->getContents();
        }
        // Name - Other
        if (isset($so->MiddleName)) {
            $co->Name->Other = $so->MiddleName->getContents();
        }
        // Name - Prefix
        if (isset($so->Title)) {
            $co->Name->Prefix = $so->Title->getContents();
        }
        // Name - Suffix
        if (isset($so->Suffix)) {
            $co->Name->Suffix = $so->Suffix->getContents();
        }
        // Name - Phonetic - Last
        if (isset($so->YomiLastName)) {
            $co->Name->PhoneticLast = $so->YomiLastName->getContents();
        }
        // Name - Phonetic - First
        if (isset($so->YomiFirstName)) {
            $co->Name->PhoneticFirst = $so->YomiFirstName->getContents();
        }
        // Name - Aliases
        if (isset($so->NickName)) {
            $co->Name->Aliases = $so->NickName->getContents();
        }
        // Birth Day
        if (!empty($so->Birthday)) {
            $co->BirthDay =  new DateTime($so->Birthday->getContents());
        }
        // Photo
        if (isset($so->Picture)) {
            $co->Photo->Type = '';
            $co->Photo->Data= $so->Picture[0]->getContents();
        }
        // Partner
        if (!empty($so->Spouse)) {
            $co->Partner = $so->Spouse->getContents();
        }
        // Anniversary Day
        if (!empty($so->Anniversary)) {
            $co->AnniversaryDay =  new DateTime($so->Anniversary->getContents());
        }
        // Address(es)
        // Work
        if (isset($so->BusinessAddressStreet) ||
            isset($so->BusinessAddressCity) ||
            isset($so->BusinessAddressState) ||
            isset($so->BusinessAddressPostalCode) ||
            isset($so->BusinessAddressCountry)
        ) {
            $address = new \OCA\EAS\Objects\ContactAddressObject();
            $address->Type = 'WORK';
            // Street
            if (isset($so->BusinessAddressStreet)) {
                $address->Street = $so->BusinessAddressStreet->getContents();
            }
            // Locality
            if (isset($so->BusinessAddressCity)) {
                $address->Locality = $so->BusinessAddressCity->getContents();
            }
            // Region
            if (isset($so->BusinessAddressState)) {
                $address->Region = $so->BusinessAddressState->getContents();
            }
            // Code
            if (isset($so->BusinessAddressPostalCode)) {
                $address->Code = $so->BusinessAddressPostalCode->getContents();
            }
            // Country
            if (isset($so->BusinessAddressCountry)) {
                $address->Country = $so->BusinessAddressCountry->getContents();
            }
            // add address to collection
            $co->Address[] = $address;
        }
        // Home
        if (isset($so->HomeAddressStreet) ||
            isset($so->HomeAddressCity) ||
            isset($so->HomeAddressState) ||
            isset($so->HomeAddressPostalCode) ||
            isset($so->HomeAddressCountry)
        ) {
            $address = new \OCA\EAS\Objects\ContactAddressObject();
            $address->Type = 'HOME';
            // Street
            if (isset($so->HomeAddressStreet)) {
                $address->Street = $so->HomeAddressStreet->getContents();
            }
            // Locality
            if (isset($so->HomeAddressCity)) {
                $address->Locality = $so->HomeAddressCity->getContents();
            }
            // Region
            if (isset($so->HomeAddressState)) {
                $address->Region = $so->HomeAddressState->getContents();
            }
            // Code
            if (isset($so->HomeAddressPostalCode)) {
                $address->Code = $so->HomeAddressPostalCode->getContents();
            }
            // Country
            if (isset($so->HomeAddressCountry)) {
                $address->Country = $so->HomeAddressCountry->getContents();
            }
            // add address to collection
            $co->Address[] = $address;
        }
        // Other
        if (isset($so->OtherAddressStreet) ||
            isset($so->OtherAddressCity) ||
            isset($so->OtherAddressState) ||
            isset($so->OtherAddressPostalCode) ||
            isset($so->OtherAddressCountry)
        ) {
            $address = new \OCA\EAS\Objects\ContactAddressObject();
            $address->Type = 'OTHER';
            // Street
            if (isset($so->OtherAddressStreet)) {
                $address->Street = $so->OtherAddressStreet->getContents();
            }
            // Locality
            if (isset($so->OtherAddressCity)) {
                $address->Locality = $so->OtherAddressCity->getContents();
            }
            // Region
            if (isset($so->OtherAddressState)) {
                $address->Region = $so->OtherAddressState->getContents();
            }
            // Code
            if (isset($so->OtherAddressPostalCode)) {
                $address->Code = $so->OtherAddressPostalCode->getContents();
            }
            // Country
            if (isset($so->OtherAddressCountry)) {
                $address->Country = $so->OtherAddressCountry->getContents();
            }
            // add address to collection
            $co->Address[] = $address;
        }
        // Phone - Business Phone 1
        if (!empty($so->BusinessPhoneNumber)) {
            $co->addPhone('WORK', 'VOICE', $so->BusinessPhoneNumber->getContents());
        }
        // Phone - Business Phone 2
        if (!empty($so->Business2PhoneNumber)) {
            $co->addPhone('WORK', 'VOICE', $so->Business2PhoneNumber->getContents());
        }
        // Phone - Business Fax
        if (!empty($so->BusinessFaxNumber)) {
            $co->addPhone('WORK', 'FAX', $so->BusinessFaxNumber->getContents());
        }
        // Phone - Home Phone 1
        if (!empty($so->HomePhoneNumber)) {
            $co->addPhone('HOME', 'VOICE', $so->HomePhoneNumber->getContents());
        }
        // Phone - Home Phone 2
        if (!empty($so->Home2PhoneNumber)) {
            $co->addPhone('HOME', 'VOICE', $so->Home2PhoneNumber->getContents());
        }
        // Phone - Home Fax
        if (!empty($so->HomeFaxNumber)) {
            $co->addPhone('HOME', 'FAX', $so->HomeFaXNumber->getContents());
        }
        // Phone - Mobile
        if (!empty($so->MobilePhoneNumber)) {
            $co->addPhone('CELL', null, $so->MobilePhoneNumber->getContents());
        }
        // Email(s)
        if (!empty($so->Email1Address)) {
            $co->addEmail('WORK', $so->Email1Address->getContents());
        }
        if (!empty($so->Email2Address)) {
            $co->addEmail('HOME', $so->Email2Address->getContents());
        }
        if (!empty($so->Email3Address)) {
            $co->addEmail('OTHER', $so->Email3Address->getContents());
        }
        // IMPP(s)
        if (!empty($so->IMAddress)) {
            $co->addIMPP('WORK', $so->IMAddress->getContents());
        }
        if (!empty($so->IMAddress2)) {
            $co->addIMPP('HOME', $so->IMAddress2->getContents());
        }
        if (!empty($so->IMAddress3)) {
            $co->addIMPP('OTHER', $so->IMAddress3->getContents());
        }
        // Manager Name
        if (!empty($so->ManagerName)) {
            $co->Name->Manager =  $so->ManagerName->getContents();
        }
        // Assistant Name
        if (!empty($so->AssistantName)) {
            $co->Name->Assistant =  $so->AssistantName->getContents();
        }
        // Occupation Organization
        if (!empty($so->CompanyName)) {
            $co->Occupation->Organization = $so->CompanyName->getContents();
        }
        // Occupation Department
        if (!empty($so->Department)) {
            $co->Occupation->Department = $so->Department->getContents();
        }
        // Occupation Title
        if (!empty($so->JobTitle)) {
            $co->Occupation->Title = $so->JobTitle->getContents();
        }
        // Occupation Role
        if (!empty($so->Profession)) {
            $co->Occupation->Role = $so->Profession->getContents();
        }
        // Occupation Location
        if (!empty($so->OfficeLocation)) {
            $co->Occupation->Location = $so->OfficeLocation->getContents();
        }
        // Tag(s)
        if (isset($so->Categories)) {
            if (!is_array($so->Categories->Category)) {
                $so->Categories->Category = [$so->Categories->Category];
            }
			foreach($so->Categories->Category as $entry) {
				$co->addTag($entry->getContents());
			}
        }
        // Notes
        if (!empty($so->Body->Data)) {
            $co->Notes = $so->Body->Data->getContents();
        }
        // URL / Website
        if (isset($so->WebPage)) {
            $co->URL = $so->WebPage->getContents();
        }
        // Attachment(s)
		if (isset($so->Attachments)) {
			if (!is_array($so->Attachments->Attachment)) {
				$so->Attachments->Attachment = [$so->Attachments->Attachment];
			}
			foreach($so->Attachments->Attachment as $entry) {
				$type = \OCA\EAS\Utile\MIME::fromFileName($entry->DisplayName->getContents());
				$co->addAttachment(
					'D',
					$entry->FileReference->getContents(), 
					$entry->DisplayName->getContents(),
					$type,
					'B',
					$entry->EstimatedDataSize->getContents()
				);
			}
		}

		return $co;

    }

    /**
     * convert remote ContactObject to local EasObject
     * 
     * @since Release 1.0.0
     * 
	 * @param ContactObject $so     entity as ContactObject
	 * 
	 * @return EasObject            entity as EasObject
	 */
	public function fromContactObject(ContactObject $so): EasObject {

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
        // Tag(s)
        /*
        if (isset($so->Categories)) {
            if (is_array($so->Categories->Category)) {
                foreach($so->Categories->Category as $entry) {
                    $co->addTag($entry->getContents());
                }
            }
            else {
                $co->addTag($so->Categories->Category->getContents());
            }
        }
        */

        // Notes
        /*
        if (!empty($so->Body)) {
            $co->Notes = $so->Body->Data->getContents();
        }
        */
        // URL / Website
        if (!empty($so->URI)) {
            $eo->WebPage = new EasProperty('Contacts', $so->URI);
        }
        
		return $eo;

    }

}
