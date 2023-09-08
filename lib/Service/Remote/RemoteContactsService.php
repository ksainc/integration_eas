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
	 * @param string $cid - Collection ID
	 * 
	 * @return ContactCollectionObject
	 */
	public function fetchCollection(string $cid): ?ContactCollectionObject {

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
	 * @param string $cid - Collection Item ID
	 * 
	 * @return ContactCollectionObject
	 */
	public function createCollection(string $cid, string $name): ?ContactCollectionObject {
        
		// execute command
		$rs = $RemoteCommonService->createFolder($EasClient, $cid, $name, '14');
        // process response
		if (isset($rs) && isset($rs->FolderCreate) && $rs->FolderDelete->Status == '1') {
		    return new ContactCollectionObject(
				$rs->FolderCreate->Id->getContents(),
				$name,
				$rs->FolderCreate->SyncKey->getContents()
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
     * @param string $cid - Collection ID
	 * 
	 * @return bool Ture - successfully destroyed / False - failed to destory
	 */
    public function deleteCollection(string $cid): bool {
        
		// execute command
        $rs = $this->RemoteCommonService->deleteFolder($this->DataStore, $cid);
		// process response
        if (isset($rs) && isset($rs->FolderDelete->Status) && $rs->FolderDelete->Status == '1') {
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
	 * @param string $cid - Collection Id
	 * @param string $state - Collection State (Initial/Last)
	 * 
	 * @return object
	 */
	public function fetchCollectionChanges(string $cid, string $state, string $scheme = 'I'): ?object {

        // execute command
        if ($state == '0') {
            $rs = $this->RemoteCommonService->fetchFolderChanges($EasClient, $cid, $state, ['MOVED' => 1]);
            $state = $rs->Sync->Collections->Collection->SyncKey->getContents();
        }
        $rs = $this->RemoteCommonService->fetchFolderChanges($EasClient, $cid, $token, ['MOVED' => 1, 'CHANGES' => 1, 'FILTER' => 0]);

		// return response
		return $rs;

    }

    /**
     * retrieve all collection items uuids from remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param string $cid - Collection ID
	 * 
	 * @return array
	 */
	public function fetchCollectionItemsUUID(string $cid, bool $ctype = false): array {

        // define place holders
        $data = array();
        $offset = 0;

        do {
            // execute command
            $ro = $this->RemoteCommonService->fetchItemsIds($this->DataStore, $cid, $ctype, $offset);
            // validate response object
            if (isset($ro) && count($ro->Contact) > 0) {
                foreach ($ro->Contact as $entry) {
                    if ($entry->ExtendedProperty) {
                        $data[] = array('ID'=>$entry->ItemId->Id, 'UUID'=>$entry->ExtendedProperty[0]->Value);
                    }
                }
                $offset += count($ro->Contact);
            }
        }
        while (isset($ro) && count($ro->Contact) > 0);
        // return
		return $data;
    }

	/**
     * retrieve collection item in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param string $iid - Collection Item ID
	 * 
	 * @return ContactObject
	 */
	public function fetchCollectionItem(string $iid): ?ContactObject {

        // construct identification object
        $io = new \OCA\EAS\Utile\Eas\Type\ItemIdType($iid);
        // execute command
		$ro = $this->RemoteCommonService->fetchItem($this->DataStore, array($io), 'D', $this->constructDefaultItemProperties());
        // validate response
		if (isset($ro->Contact)) {
            // convert to contact object
            $co = $this->toContactObject($ro->Contact[0]);
            // retrieve attachment(s) from remote data store
			if (count($co->Attachments) > 0) {
				$co->Attachments = $this->fetchCollectionItemAttachment(array_column($co->Attachments, 'Id'));
			}
            // return object
		    return $co;
        } else {
            // return null
            return null;
        }

    }

	/**
     * find collection item by uuid in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param string $cid - Collection ID
     * @param string $uuid -Collection Item UUID
	 * 
	 * @return ContactObject
	 */
	public function fetchCollectionItemByUUID(string $cid, string $uuid): ?ContactObject {

        // retrieve properties for a specific collection item
		$ro = $this->RemoteCommonService->findItemByUUID($this->DataStore, $cid, $uuid, false, 'D', $this->constructDefaultItemProperties());
        // validate response
		if (isset($ro->Contact)) {
            // convert to contact object
            $co = $this->toContactObject($ro->Contact[0]);
            // retrieve attachment(s) from remote data store
			if (count($co->Attachments) > 0) {
				$co->Attachments = $this->fetchCollectionItemAttachment(array_column($co->Attachments, 'Id'));
			}
            // return object
		    return $co;
        } else {
            // return null
            return null;
        }

    }
    
	/**
     * create collection item in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param string $cid - Collection ID
     * @param ContactObject $so - Source Data
	 * 
	 * @return ContactObject
	 */
	public function createCollectionItem(string $cid, ContactObject $so): ?ContactObject {

        // construct request object
        $ro = new ContactItemType();
        // Label
        if (!empty($so->Label)) {
            $ro->DisplayName = $so->Label;
        }
        // Names
        if (isset($so->Name)) {
            // Last Name
            if (!empty($so->Name->Last)) {
                $ro->Surname = $so->Name->Last;
            }
            // First Name
            if (!empty($so->Name->First)) {
                $ro->GivenName = $so->Name->First;
            }
            // Other Name
            if (!empty($so->Name->Other)) {
                $ro->MiddleName = $so->Name->Other;
            }
            // Prefix
            if (!empty($so->Name->Prefix)) {
                $ro->ExtendedProperty[] = $this->createFieldExtendedByTag('14917', 'String', $so->Name->Prefix);
            }
            // Suffix
            if (!empty($so->Name->Suffix)) {
                $ro->Generation = $so->Name->Suffix;
            }
            // Phonetic Last
            if (!empty($so->Name->PhoneticLast)) {
                $ro->PhoneticLastName = $so->Name->PhoneticLast;
            }
            // Phonetic First
            if (!empty($so->Name->PhoneticFirst)) {
                $ro->PhoneticFirstName = $so->Name->PhoneticFirst;
            }
            // Aliases
            if (!empty($so->Name->Aliases)) {
                $ro->NickName = $so->Name->Aliases;
            }
        }
        // Birth Day
        if (!empty($so->BirthDay)) {
            $ro->Birthday = $so->BirthDay->format('Y-m-d\TH:i:s\Z');
        }
        // Gender
        if (!empty($so->Gender)) {
            $ro->ExtendedProperty[] = $this->createFieldExtendedByTag('14925', 'String', $so->Gender);
        }
        // Partner
        if (!empty($so->Partner)) {
            $ro->SpouseName = $so->Partner;
        }
        // Anniversary Day
        if (!empty($so->AnniversaryDay)) {
            $ro->WeddingAnniversary = $so->AnniversaryDay->format('Y-m-d\TH:i:s\Z');
        }
        // Address(es)
        if (count($so->Address) > 0) {
            $types = array(
                'WORK' => true,
                'HOME' => true,
                'OTHER' => true
            );
            foreach ($so->Address as $entry) {
                if (isset($types[$entry->Type]) && $types[$entry->Type] == true) {
                    if (!isset($ro->PhysicalAddresses->Entry)) { $ro->PhysicalAddresses = new \OCA\EAS\Utile\Eas\Type\PhysicalAddressDictionaryType(); }
                    $ro->PhysicalAddresses->Entry[] = new \OCA\EAS\Utile\Eas\Type\PhysicalAddressDictionaryEntryType(
                        $this->toAddressType($entry->Type),
                        $entry->Street,
                        $entry->Locality,
                        $entry->Region,
                        $entry->Code,
                        $entry->Country,
                    );
                    $types[$entry->Type] = false;
                }
            }
        }
        // Phone(s)
        if (count($so->Phone) > 0) {
            foreach ($so->Phone as $entry) {
                $type = $this->toTelType($entry->Type);
                if ($type && !empty($entry->Number)) {
                    if (!isset($ro->PhoneNumbers->Entry)) { $ro->PhoneNumbers = new \OCA\EAS\Utile\Eas\Type\PhoneNumberDictionaryType(); } 
                    $ro->PhoneNumbers->Entry[] = new \OCA\EAS\Utile\Eas\Type\PhoneNumberDictionaryEntryType(
                        $type, 
                        $entry->Number
                    );
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
                    if (!isset($ro->EmailAddresses->Entry)) { $ro->EmailAddresses = new \OCA\EAS\Utile\Eas\Type\EmailAddressDictionaryType(); }
                    $ro->EmailAddresses->Entry[] = new \OCA\EAS\Utile\Eas\Type\EmailAddressDictionaryEntryType(
                        $this->toEmailType($entry->Type),
                        $entry->Address
                    );
                    $types[$entry->Type] = false;
                }
            }
        }
        // IMPP(s)
        if (count($so->IMPP) > 0) {
            // TODO: Add IMPP Code
        }
        // TimeZone
        if (!empty($so->TimeZone)) {
            // TODO: Add TimeZone Code
        }
        // Geolocation
        if (!empty($so->Geolocation)) {
            // TODO: Add Geolocation Code
        }
        // Manager Name
        if (!empty($so->Manager)) {
            $ro->Manager = $so->Manager;
        }
        // Assistant Name
        if (!empty($so->Assistant)) {
            $ro->AssistantName = $so->Assistant;
        }
        // Occupation
        if (isset($so->Occupation)) {
            if (!empty($so->Occupation->Organization)) {
                $ro->CompanyName = $so->Occupation->Organization;
            }
            if (!empty($so->Occupation->Department)) {
                $ro->Department = $so->Occupation->Department;
            }
            if (!empty($so->Occupation->Title)) {
                $ro->JobTitle = $so->Occupation->Title;
            }
            if (!empty($so->Occupation->Role)) {
                $ro->Profession = $so->Occupation->Role;
            }
        }
        // Tag(s)
        if (count($so->Tags) > 0) {
            $ro->Categories = new \OCA\EAS\Utile\Eas\ArrayType\ArrayOfStringsType;
            foreach ($so->Tags as $entry) {
                $ro->Categories->String[] = $entry;
            }
        }
        // Notes
        if (!empty($so->Notes)) {
            $ro->Body = new \OCA\EAS\Utile\Eas\Type\BodyType(
                'Text',
                $so->Notes
            );
        }
        // UID
        if (!empty($so->UID)) {
            $ro->ExtendedProperty[] = $this->createFieldExtendedByName('PublicStrings', 'DAV:uid', 'String', $so->UID);
        }
        // set the "file as" mapping to "LastFirstCompany"
        //$ro->FileAsMapping = 'LastFirstCompany';
        // execute command
        $rs = $this->RemoteCommonService->createItem($this->DataStore, $cid, $ro);

        // process response
        if ($rs->ItemId) {
			$co = clone $so;
			$co->ID = $rs->ItemId->Id;
            $co->CID = $cid;
			$co->State = $rs->ItemId->ChangeKey;
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
     * update collection item in remote storage
     * 
     * @since Release 1.0.0
     * 
     * @param string $cid - Collection ID
     * @param string $iid - Collection Item ID
     * @param ContactObject $so - Source Data
	 * 
	 * @return ContactObject
	 */
	public function updateCollectionItem(string $cid, string $iid, ContactObject $so): ?ContactObject {

        // request modifications array
        $rm = array();
        // request deletions array
        $rd = array();
        // Label
        if (!empty($so->Label)) {
            $rm[] = $this->updateFieldUnindexed('contacts:DisplayName', 'DisplayName', $so->Label);
        }
        else {
            $rd[] = $this->deleteFieldUnindexed('contacts:DisplayName');
        }
        // Names
        if (isset($so->Name)) {
            // Last Name
            if (!empty($so->Name->Last)) {
                $rm[] = $this->updateFieldUnindexed('contacts:Surname', 'Surname', $so->Name->Last);
            }
            else {
                $rd[] = $this->deleteFieldUnindexed('contacts:Surname');
            }
            // First Name
            if (!empty($so->Name->First)) {
                $rm[] = $this->updateFieldUnindexed('contacts:GivenName', 'GivenName', $so->Name->First);
            }
            else {
                $rd[] = $this->deleteFieldUnindexed('contacts:GivenName');
            }
            // Other Name
            if (!empty($so->Name->Other)) {
                $rm[] = $this->updateFieldUnindexed('contacts:MiddleName', 'MiddleName', $so->Name->Other);
            }
            else {
                $rd[] = $this->deleteFieldUnindexed('contacts:MiddleName');
            }
            // Prefix
            if (!empty($so->Name->Prefix)) {
                $rm[] = $this->updateFieldExtendedByTag('14917', 'String', $so->Name->Prefix);
            }
            else {
                $rd[] = $this->deleteFieldExtendedByTag('14917', 'String');
            }
            // Suffix
            if (!empty($so->Name->Suffix)) {
                $rm[] = $this->updateFieldUnindexed('contacts:Generation', 'Generation', $so->Name->Suffix);
            }
            else {
                $rd[] = $this->deleteFieldUnindexed('contacts:Generation');
            }
            /*
            // Phonetic Last
            if (!empty($so->Name->PhoneticLast)) {
                $rm[] = $this->updateFieldExtendedByTag('32813', 'String', $so->Name->PhoneticLast);
            }
            else {
                $rd[] = $this->deleteFieldExtendedByTag('32813', 'String');
            }
            // Phonetic First
            if (!empty($so->Name->PhoneticFirst)) {
                $rm[] = $this->updateFieldExtendedByTag('32812', 'String', $so->Name->PhoneticFirst);
            }
            else {
                $rd[] = $this->deleteFieldExtendedByTag('32812', 'String');
            }
            */
            // Aliases
            if (!empty($so->Name->Aliases)) {
                $rm[] = $this->updateFieldUnindexed('contacts:Nickname', 'Nickname', $so->Name->Aliases);
            }
            else {
                $rd[] = $this->deleteFieldUnindexed('contacts:Nickname');
            }
        }
        // Birth Day
        if (!empty($so->BirthDay)) {
            $rm[] = $this->updateFieldUnindexed('contacts:Birthday', 'Birthday', $so->BirthDay->format('Y-m-d\TH:i:s\Z'));
        }
        else {
            $rd[] = $this->deleteFieldUnindexed('contacts:Birthday');
        }
        // Gender
        if (!empty($so->Gender)) {
            $rm[] = $this->updateFieldExtendedByTag('14925', 'String', $so->Gender);
        }
        else {
            $rd[] = $this->deleteFieldExtendedByTag('14925', 'String');
        }
        // Partner
        if (!empty($so->Partner)) {
            $rm[] = $this->updateFieldUnindexed('contacts:SpouseName', 'SpouseName', $so->Partner);
        }
        else {
            $rd[] = $this->deleteFieldUnindexed('contacts:SpouseName');
        }
        // Anniversary Day
        if (!empty($so->AnniversaryDay)) {
            $rm[] = $this->updateFieldUnindexed('contacts:WeddingAnniversary', 'WeddingAnniversary', $so->AnniversaryDay->format('Y-m-d\TH:i:s\Z'));
        }
        else {
            $rd[] = $this->deleteFieldUnindexed('contacts:WeddingAnniversary');
        }
        // Address(es)
        $types = array(
            'Business' => true,
            'Home' => true,
            'Other' => true
        );
        // update address
        if (count($so->Address) > 0) {
            foreach ($so->Address as $entry) {
                // convert address type
                $type = $this->toAddressType($entry->Type);
                // process if index not used already
                if (isset($types[$type]) && $types[$type] == true) {
                    // street
                    if (!empty($entry->Street)) {
                        $rm[] = $this->updateFieldIndexed(
                            'contacts:PhysicalAddress:Street',
                            $type,
                            'PhysicalAddresses',
                            new \OCA\EAS\Utile\Eas\Type\PhysicalAddressDictionaryType(),
                            new \OCA\EAS\Utile\Eas\Type\PhysicalAddressDictionaryEntryType(
                                $type,
                                $entry->Street,
                                null,
                                null,
                                null,
                                null
                            )
                        );
                    }
                    else {
                        $rd[] = $this->deleteFieldIndexed(
                            'contacts:PhysicalAddress:Street',
                            $type
                        );
                    }
                    // locality
                    if (!empty($entry->Locality)) {    
                        $rm[] = $this->updateFieldIndexed(
                            'contacts:PhysicalAddress:City',
                            $type,
                            'PhysicalAddresses',
                            new \OCA\EAS\Utile\Eas\Type\PhysicalAddressDictionaryType(),
                            new \OCA\EAS\Utile\Eas\Type\PhysicalAddressDictionaryEntryType(
                                $type,
                                null,
                                $entry->Locality,
                                null,
                                null,
                                null
                            )
                        );
                    }
                    else {
                        $rd[] = $this->deleteFieldIndexed(
                            'contacts:PhysicalAddress:City',
                            $type
                        );
                    }
                    // region
                    if (!empty($entry->Region)) {  
                        $rm[] = $this->updateFieldIndexed(
                            'contacts:PhysicalAddress:State',
                            $type,
                            'PhysicalAddresses',
                            new \OCA\EAS\Utile\Eas\Type\PhysicalAddressDictionaryType(),
                            new \OCA\EAS\Utile\Eas\Type\PhysicalAddressDictionaryEntryType(
                                $type,
                                null,
                                null,
                                $entry->Region,
                                null,
                                null
                            )
                        );
                    }
                    else {
                        $rd[] = $this->deleteFieldIndexed(
                            'contacts:PhysicalAddress:State',
                            $type
                        );
                    }
                    // code
                    if (!empty($entry->Code)) {
                        $rm[] = $this->updateFieldIndexed(
                            'contacts:PhysicalAddress:PostalCode',
                            $type,
                            'PhysicalAddresses',
                            new \OCA\EAS\Utile\Eas\Type\PhysicalAddressDictionaryType(),
                            new \OCA\EAS\Utile\Eas\Type\PhysicalAddressDictionaryEntryType(
                                $type,
                                null,
                                null,
                                null,
                                $entry->Code,
                                null
                            )
                        );
                    }
                    else {
                        $rd[] = $this->deleteFieldIndexed(
                            'contacts:PhysicalAddress:PostalCode',
                            $type
                        );
                    }
                    // country
                    if (!empty($entry->Country)) {
                        $rm[] = $this->updateFieldIndexed(
                            'contacts:PhysicalAddress:CountryOrRegion',
                            $type,
                            'PhysicalAddresses',
                            new \OCA\EAS\Utile\Eas\Type\PhysicalAddressDictionaryType(),
                            new \OCA\EAS\Utile\Eas\Type\PhysicalAddressDictionaryEntryType(
                                $type,
                                null,
                                null,
                                null,
                                null,
                                $entry->Country
                            )
                        );
                    }
                    else {
                        $rd[] = $this->deleteFieldIndexed(
                            'contacts:PhysicalAddress:CountryOrRegion',
                            $type
                        );
                    }
                    $types[$type] = false;
                }
            }
        }
        // delete address
        foreach ($types as $type => $status) {
            if ($status) {
                $rd[] = $this->deleteFieldIndexed(
                    'contacts:PhysicalAddress:Street',
                    $type
                );
                $rd[] = $this->deleteFieldIndexed(
                    'contacts:PhysicalAddress:City',
                    $type
                );
                $rd[] = $this->deleteFieldIndexed(
                    'contacts:PhysicalAddress:State',
                    $type
                );
                $rd[] = $this->deleteFieldIndexed(
                    'contacts:PhysicalAddress:PostalCode',
                    $type
                );
                $rd[] = $this->deleteFieldIndexed(
                    'contacts:PhysicalAddress:CountryOrRegion',
                    $type
                );
            }
        }
        // Phone(s)
        $types = array(
            'BusinessPhone' => true,
            'BusinessPhone2' => true,
            'BusinessFax' => true,
            'HomePhone' => true,
            'HomePhone2' => true,
            'HomeFax' => true,
            'CarPhone' => true,
            'Isdn' => true,
            'MobilePhone' => true,
            'Pager' => true,
            'OtherTelephone' => true,
            'OtherFax' => true,
        );
        // update phone
        if (count($so->Phone) > 0) {
            foreach ($so->Phone as $entry) {
                // convert email type
                $type = $this->toTelType($entry->Type);
                // process if index not used already
                if (isset($types[$type]) && $types[$type] == true && !empty($entry->Number)) {
                    $rm[] = $this->updateFieldIndexed(
                        'contacts:PhoneNumber',
                        $type,
                        'PhoneNumbers',
                        new \OCA\EAS\Utile\Eas\Type\PhoneNumberDictionaryType(),
                        new \OCA\EAS\Utile\Eas\Type\PhoneNumberDictionaryEntryType(
                            $type, 
                            $entry->Number
                        )
                    );
                    $types[$type] = false;
                }
            }
        }
        // delete phone
        foreach ($types as $type => $status) {
            if ($status) {
                $rd[] = $this->deleteFieldIndexed(
                    'contacts:PhoneNumber',
                    $type
                );
            }
        }
        // Email(s)
        $types = array(
            'EmailAddress1' => true,
            'EmailAddress2' => true,
            'EmailAddress3' => true
        );
        // update email
        if (count($so->Email) > 0) {
            foreach ($so->Email as $entry) {
                // convert email type
                $type = $this->toEmailType($entry->Type);
                // process if index not used already
                if (isset($types[$type]) && $types[$type] == true && !empty($entry->Address)) {
                    $rm[] = $this->updateFieldIndexed(
                        'contacts:EmailAddress',
                        $type,
                        'EmailAddresses',
                        new \OCA\EAS\Utile\Eas\Type\EmailAddressDictionaryType(),
                        new \OCA\EAS\Utile\Eas\Type\EmailAddressDictionaryEntryType(
                            $type,
                            $entry->Address
                        )
                    );
                    $types[$type] = false;
                }
            }
        }
        // delete email
        foreach ($types as $type => $status) {
            if ($status) {
                $rd[] = $this->deleteFieldIndexed(
                    'contacts:EmailAddress',
                    $type
                );
            }
        }
        // TimeZone
        if (!empty($so->TimeZone)) {
            // TODO: Add TimeZone Code
        }
        // Geolocation
        if (!empty($so->Geolocation)) {
            // TODO: Add Geolocation Code
        }
        // Manager Name
        if (!empty($so->Manager)) {
            $rm[] = $this->updateFieldUnindexed('contacts:Manager', 'Manager', $so->Manager);
        }
        else {
            $rd[] = $this->deleteFieldUnindexed('contacts:Manager');
        }
        // Assistant Name
        if (!empty($so->Assistant)) {
            $rm[] = $this->updateFieldUnindexed('contacts:AssistantName', 'AssistantName', $so->Assistant);
        }
        else {
            $rd[] = $this->deleteFieldUnindexed('contacts:AssistantName');
        }
        // Occupation
        if (isset($so->Occupation)) {
            // Occupation - Name
            if (!empty($so->Occupation->Organization)) {
                $rm[] = $this->updateFieldUnindexed('contacts:CompanyName', 'CompanyName', $so->Occupation->Organization);
            }
            else {
                $rd[] = $this->deleteFieldUnindexed('contacts:CompanyName');
            }
            // Occupation - Department
            if (!empty($so->Occupation->Department)) {
                $rm[] = $this->updateFieldUnindexed('contacts:Department', 'Department', $so->Occupation->Department);
            }
            else {
                $rd[] = $this->deleteFieldUnindexed('contacts:Department');
            }
            // Occupation - Title
            if (!empty($so->Occupation->Title)) {
                $rm[] = $this->updateFieldUnindexed('contacts:JobTitle', 'JobTitle', $so->Occupation->Title);
            }
            else {
                $rd[] = $this->deleteFieldUnindexed('contacts:JobTitle');
            }
            // Occupation - Role
            if (!empty($so->Occupation->Role)) {
                $rm[] = $this->updateFieldUnindexed('contacts:Profession', 'Profession', $so->Occupation->Role);
            }
            else {
                $rd[] = $this->deleteFieldUnindexed('contacts:Profession');
            }
        }
		// Tag(s)
		if (count($so->Tags) > 0) {
			$f = new \OCA\EAS\Utile\Eas\ArrayType\ArrayOfStringsType;
			foreach ($so->Tags as $entry) {
				$f->String[] = $entry;
			}
			$rm[] = $this->updateFieldUnindexed('item:Categories', 'Categories', $f);
		}
		else {
			$rd[] = $this->deleteFieldUnindexed('item:Categories');
		}
        // Notes
        if (!empty($so->Notes)) {
            $rm[] = $this->updateFieldUnindexed(
                'item:Body',
                'Body', 
                new \OCA\EAS\Utile\Eas\Type\BodyType(
                    'Text',
                    $so->Notes
            ));
        }
        else {
            $rd[] = $this->deleteFieldUnindexed('item:Body');
        }
        // UID
        if (!empty($so->UID)) {
            $rm[] = $this->updateFieldExtendedByName('PublicStrings', 'DAV:uid', 'String', $so->UID);
        }
        else {
            $rd[] = $this->deleteFieldExtendedByName('PublicStrings', 'DAV:uid', 'String');
        }
        // execute command
        $rs = $this->RemoteCommonService->updateItem($this->DataStore, $cid, $iid, null, $rm, $rd);
        // process response
        if ($rs->ItemId) {
			$co = clone $so;
			$co->ID = $rs->ItemId->Id;
            $co->CID = $cid;
			$co->State = $rs->ItemId->ChangeKey;
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
     * update collection item with uuid in remote storage
     * 
     * @since Release 1.0.0
     * 
	 * @param string $cid - Collection ID
     * @param string $iid - Collection Item ID
     * @param string $cid - Collection Item UUID
	 * 
	 * @return object Status Object - item id, item uuid, item state token / Null - failed to create
	 */
	public function updateCollectionItemUUID(string $cid, string $iid, string $uuid): ?object {
		// request modifications array
        $rm = array();
        // construct update command object
        $rm[] = $this->updateFieldExtendedByName('PublicStrings', 'DAV:uid', 'String', $uuid);
        // execute request
        $rs = $this->RemoteCommonService->updateItem($this->DataStore, $cid, $iid, null, $rm, null);
        // return response
        if ($rs->ItemId) {
            return (object) array('ID' => $rs->ItemId->Id, 'UID' => $uuid, 'State' => $rs->ItemId->ChangeKey);
        } else {
            return null;
        }
    }
    
    /**
     * delete collection item in remote storage
     * 
     * @since Release 1.0.0
     * 
     * @param string $iid - Item ID
	 * 
	 * @return bool Ture - successfully destroyed / False - failed to destory
	 */
    public function deleteCollectionItem(string $iid): bool {
        // create object
        $o = new \OCA\EAS\Utile\Eas\Type\ItemIdType($iid);

        $rs = $this->RemoteCommonService->deleteItem($this->DataStore, array($o));

        if ($rs) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * retrieve collection item attachment from remote storage
     * 
     * @since Release 1.0.0
     * 
     * @param string $aid - Attachment ID
	 * 
	 * @return array
	 */
	public function fetchCollectionItemAttachment(array $batch): array {

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
                if ($entry->IsContactPhoto || str_contains($entry->Name, 'ContactPicture')) {
                    $flag = 'CP';
                }
                else {
                    $flag = null;
                }
				// insert attachment object in response collection
				$rc[] = new ContactAttachmentObject(
					$entry->AttachmentId->Id, 
					$entry->Name,
					$type,
					'B',
                    $flag,
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
	public function createCollectionItemAttachment(string $aid, array $batch): array {

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
	public function deleteCollectionItemAttachment(array $batch): array {

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
        // Phone - Business 1
        if (!empty($so->BusinessPhoneNumber)) {
            $co->addPhone('WORK', null, $so->BusinessPhoneNumber->getContents());
        }
        // Phone - Business 2
        if (!empty($so->Business2PhoneNumber)) {
            $co->addPhone('WORK', null, $so->Business2PhoneNumber->getContents());
        }
        // Phone - Home 1
        if (!empty($so->HomePhoneNumber)) {
            $co->addPhone('HOME', null, $so->HomePhoneNumber->getContents());
        }
        // Phone - Home 2
        if (!empty($so->Home2PhoneNumber)) {
            $co->addPhone('HOME', null, $so->Home2PhoneNumber->getContents());
        }
        // Phone - Mobile
        if (!empty($so->MobilePhoneNumber)) {
            $co->addPhone('CELL', null, $so->HomePhoneNumber->getContents());
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
            if (is_array($so->Categories->Category)) {
                foreach($so->Categories->Category as $entry) {
                    $co->addTag($entry->getContents());
                }
            }
            else {
                $co->addTag($so->Categories->Category->getContents());
            }
        }
        // Notes
        if (!empty($so->Body)) {
            $co->Notes = $so->Body->Data->getContents();
        }
        // URL / Website
        if (isset($so->WebPage)) {
            $this->URI = $so->WebPage->getContents();
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
	public function fromContactObject(EasObject $so): ContactObject {

		// create object
		$eo = new EasObject();
        // Label
        if (!empty($so->Label)) {
            $eo->FileAs = new EasPropert('Contacts', $so->Label);
        }
		// Name - Last
        if (!empty($so->Name->Last)) {
            $eo->LastName = new EasPropert('Contacts', $so->Name->Last);
        }
        // Name - First
        if (!empty($so->Name->First)) {
            $eo->FirstName = new EasPropert('Contacts', $so->Name->First);
        }
        // Name - Other
        if (!empty($so->Name->Other)) {
            $eo->MiddleName = new EasPropert('Contacts', $so->Name->Other);
        }
        // Name - Prefix
        if (!empty($so->Name->Prefix)) {
            $eo->Title = new EasPropert('Contacts', $so->Name->Prefix);
        }
        // Name - Suffix
        if (!empty($so->Name->Suffix)) {
            $eo->Suffix = new EasPropert('Contacts', $so->Name->Suffix);
        }
        // Name - Phonetic - Last
        if (!empty($so->Name->PhoneticLast)) {
            $eo->YomiLastName = new EasPropert('Contacts', $so->Name->PhoneticLast);
        }
        // Name - Phonetic - First
        if (!empty($so->Name->PhoneticFirst)) {
            $eo->YomiFirstName = new EasPropert('Contacts', $so->Name->PhoneticFirst);
        }
        // Name - Aliases
        if (!empty($so->Name->Aliases)) {
            $eo->NickName = new EasPropert('Contacts', $so->Name->Aliases);
        }
        // Birth Day
        if (!empty($so->BirthDay)) {
            $eo->BirthDay = new EasPropert('Contacts', $so->Birthday->format('Y-m-d\TH:i:s\Z'));
        }
        // Partner
        if (!empty($so->Partner)) {
            $eo->Spouse = new EasPropert('Contacts', $so->Partner);
        }
        // Anniversary Day
        if (!empty($so->AnniversaryDay)) {
            $eo->Anniversary = new EasPropert('Contacts', $so->Anniversary->format('Y-m-d\TH:i:s\Z'));
        }
        // Address(es)
        if (isset($so->PhysicalAddresses)) {
            foreach($so->PhysicalAddresses->Entry as $entry) {
                $co->addAddress(
                    $entry->Key,
                    $entry->Street,
                    $entry->City,
                    $entry->State,
                    $entry->PostalCode,
                    $entry->CountryOrRegion
                );
            }
        }
        // Phone - Business 1
        if (!empty($so->BusinessPhoneNumber)) {
            $co->addPhone('WORK', null, $so->BusinessPhoneNumber->getContents());
        }
        // Phone - Business 2
        if (!empty($so->Business2PhoneNumber)) {
            $co->addPhone('WORK', null, $so->Business2PhoneNumber->getContents());
        }
        // Phone - Home 1
        if (!empty($so->HomePhoneNumber)) {
            $co->addPhone('HOME', null, $so->HomePhoneNumber->getContents());
        }
        // Phone - Home 2
        if (!empty($so->Home2PhoneNumber)) {
            $co->addPhone('HOME', null, $so->Home2PhoneNumber->getContents());
        }
        // Phone - Mobile
        if (!empty($so->MobilePhoneNumber)) {
            $co->addPhone('HOME', null, $so->HomePhoneNumber->getContents());
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
            if (is_array($so->Categories->Category)) {
                foreach($so->Categories->Category as $entry) {
                    $co->addTag($entry->getContents());
                }
            }
            else {
                $co->addTag($so->Categories->Category->getContents());
            }
        }
        // Notes
        if (!empty($so->Body)) {
            $co->Notes = $so->Body->Data->getContents();
        }
        // URL / Website
        if (isset($so->WebPage)) {
            $this->URI = $so->WebPage->getContents();
        }
        // Attachment(s)
        if (isset($so->Attachments)) {
            foreach($so->Attachments->FileAttachment as $entry) {
                // evaluate mime type
                if ($entry->ContentType == 'application/octet-stream') {
                    $type = \OCA\EAS\Utile\MIME::fromFileName($entry->Name);
                } else {
                    $type = $entry->ContentType;
                }
                // evaluate attachemnt type
                if ($entry->IsContactPhoto || str_contains($entry->Name, 'ContactPicture')) {
                    $flag = 'CP';
                    $co->Photo->Type = 'data';
                    $co->Photo->Data = $entry->AttachmentId->Id;
                }
                else {
                    $flag = null;
                }
                $co->addAttachment(
					$entry->AttachmentId->Id, 
					$entry->Name,
					$type,
					'B',
                    $flag,
					$entry->Size,
					$entry->Content
				);
            }
        }

		return $co;

    }

    /**
     * convert remote address type to contact object type
     * 
     * @since Release 1.0.0
     * 
	 * @param sting $type - remote address type
	 * 
	 * @return string|null contact object address type
	 */
    private function fromAddressType(string $type): ?string {

        // type conversion reference
        $_tm = array(
			'Business' => 'WORK',
			'Home' => 'HOME',
			'Other' => 'OTHER'
		);
        // evaluate if type value exists
		if (isset($_tm[$type])) {
			// return converted type value
			return $_tm[$type];
		} else {
            // return default type value
			return null;
		}

    }

    /**
     * convert local address type to remote type
     * 
     * @since Release 1.0.0
     * 
	 * @param sting $type - contact object address type
	 * 
	 * @return string|null remote address type
	 */
    private function toAddressType(string $type): string {

        // type conversion reference
        $_tm = array(
			'WORK' => 'Business',
			'HOME' => 'Home',
			'OTHER' => 'Other'
		);
        // evaluate if type value exists
		if (isset($_tm[$type])) {
			// return converted type value
			return $_tm[$type];
		} else {
            // return default type value
			return '';
		}

    }

}
