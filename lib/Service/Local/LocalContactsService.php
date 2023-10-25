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

namespace OCA\EAS\Service\Local;

use Datetime;
use DateTimeZone;

use OCA\EAS\Db\ContactStore;
use OCA\EAS\Objects\ContactCollectionObject;
use OCA\EAS\Objects\ContactObject;

use Sabre\VObject\Reader;
use Sabre\VObject\Component\VCard;

class LocalContactsService {
	
	private ContactStore $_Store;

	public function __construct () {

	}

    public function initialize(ContactStore $Store) {

		$this->_Store = $Store;

	}

	/**
     * retrieve collection object from local storage
     * 
	 * @param string $id            Collection ID
	 * 
	 * @return ContactCollectionObject  ContactCollectionObject on success / null on fail
	 */
	public function fetchCollection(string $id): ?ContactCollectionObject {

        // retrieve object properties
        $lo = $this->_Store->fetchCollection($id);
        // evaluate if object properties where retrieved
        if (is_array($lo) && count($lo) > 0) {
            // construct object and return
            return new ContactCollectionObject(
                $lo['id'],
                $lo['label'],
                $lo['token']
            );
        }
        else {
            // return nothing
            return null;
        }

    }

	/**
     * retrieve changes for specific collection from local storage
     * 
	 * @param string $cid - Collection Id
     * @param string $state - Collection Id
	 * 
	 * @return array of collection changes
	 */
	public function fetchCollectionChanges(string $cid, string $state): array {

        // retrieve collection chamges
        $lcc = $this->DataStore->getChangesForAddressBook($cid, $state, null, null);
        // return collection chamges
		return $lcc;
        
    }

	/**
     * retrieve entity object from local storage
     * 
     * @param string $id            Entity ID
	 * 
	 * @return ContactObject        ContactObject on success / null on fail
	 */
	public function fetchEntity(string $id): ?ContactObject {

        // retrieve object properties
        $lo = $this->_Store->fetchEntity($id);
		// evaluate if object properties where retrieved
        if (is_array($lo) && count($lo) > 0) {
            // convert to contact object
            $co = $this->toContactObject(Reader::read($lo['data']));
            $co->ID = $lo['id'];
            $co->UUID = $lo['uuid'];
            $co->CID = $lo['cid'];
            $co->State = trim($lo['state'],'"');
            $co->RCID = $lo['rcid'];
            $co->REID = $lo['reid'];
            $co->RState = $lo['rcid'];
            // return contact object
            return $co;
        } else {
            // return null
            return null;
        }

    }

    /**
     * retrieve entity object by remote id from local storage
     * 
     * @param string $uid           User Id
	 * @param string $rcid          Remote Collection ID
     * @param string $reid          Remote Entity ID
	 * 
	 * @return ContactObject        ContactObject on success / null on fail
	 */
	public function fetchEntityByRID(string $uid, string $rcid, string $reid): ?ContactObject {

        // retrieve object properties
        $lo = $this->_Store->fetchEntityByRID($uid, $rcid, $reid);
		// evaluate if object properties where retrieved
        if (is_array($lo) && count($lo) > 0) {
            // convert to contact object
            $co = $this->toContactObject(Reader::read($lo['data']));
            $co->ID = $lo['id'];
            $co->UUID = $lo['uuid'];
            $co->CID = $lo['cid'];
            $co->State = trim($lo['state'],'"');
            $co->RCID = $lo['rcid'];
            $co->REID = $lo['reid'];
            $co->RState = $lo['rcid'];
            // return contact object
            return $co;
        } else {
            // return null
            return null;
        }

    }

    /**
     * create entity in local storage
     * 
	 * @param string $uid           User Id
	 * @param string $cid           Collection ID
     * @param ContactObject $so     Source Object
	 * 
	 * @return object               Status Object - item id, item uuid, item state token / Null - failed to create
	 */
	public function createEntity(string $uid, string $cid, ContactObject $so): ?object {

        // initilize data place holder
        $lo = [];
        // convert contact object to vcard object
        $lo['data'] = $this->fromContactObject($so)->serialize();
        $lo['uuid'] = (!empty($so->UUID)) ? $so->UUID : \OCA\EAS\Utile\UUID::v4();
        $lo['uid'] = $uid;
        $lo['cid'] = $cid;
        $lo['rcid'] = $so->RCID;
        $lo['reid'] = $so->REID;
        $lo['rstate'] = $so->RState;
        $lo['label'] = $so->Label;
        $lo['size'] = strlen($lo['data']);
        $lo['state'] = md5($lo['data']);
        // create entry in data store
        $id = $this->_Store->createEntity($lo);
        // return status object or null
        if ($id) {
            return (object) array('ID' => $id, 'UUID' => $lo['uuid'], 'State' => $lo['state']);
        } else {
            return null;
        }

    }
    
    /**
     * update entity in local storage
     * 
	 * @param string $uid           User ID
	 * @param string $cid           Collection ID
	 * @param string $eid           Entity ID
     * @param ContactObject $so     Source Object
	 * 
	 * @return object               Status Object - item id, item uuid, item state token / Null - failed to create
	 */
	public function updateEntity(string $uid, string $cid, string $eid, ContactObject $so): ?object {

        // evaluate if collection or entity id is missing - must contain id to update
        if (empty($uid) || empty($cid) || empty($eid)) {
            return null;
        }
        // initilize data place holder
        $lo = [];
        // convert contact object to vcard object
        $lo['data'] = $this->fromContactObject($so)->serialize();
        $lo['uuid'] = (!empty($so->UUID)) ? $so->UUID : \OCA\EAS\Utile\UUID::v4();
        $lo['uid'] = $uid;
        $lo['cid'] = $cid;
        $lo['rcid'] = $so->RCID;
        $lo['reid'] = $so->REID;
        $lo['rstate'] = $so->RState;
        $lo['label'] = $so->Label;
        $lo['size'] = strlen($lo['data']);
        $lo['state'] = md5($lo['data']);
        // modify entry in data store
        $rs = $this->_Store->modifyEntity($eid, $lo);
        // return status object or null
        if ($rs) {
            return (object) array('ID' => $eid, 'UUID' => $lo['uuid'], 'State' => $lo['state']);
        } else {
            return null;
        }

    }
    
    /**
     * delete entity from local storage
     * 
	 * @param string $uid           User ID
	 * @param string $cid           Collection ID
	 * @param string $eid           Entity ID
	 * 
	 * @return bool                 true - successfully delete / false - failed to delete
	 */
	public function deleteEntity(string $uid, string $cid, string $eid): bool {

        // evaluate if collection or entity id is missing - must contain id to delete
        if (empty($uid) || empty($cid) || empty($eid)) {
            return null;
        }
        // delete entry from data store
        $rs = $this->_Store->deleteEntity($eid);
        // return result
        if ($rs) {
            return true;
        } else {
            return false;
        }

    }
    
    /**
     * convert vcard object to contact object
     * 
	 * @param VCard $vo - source object
	 * 
	 * @return ContactObject converted object
	 */
	public function toContactObject(VCard $vo): ContactObject {

		// construct contact object
		$co = new ContactObject();
        // UUID
        if (isset($vo->UID)) {
            $co->UID = trim($vo->UID->getValue());
        }
        // Label
        if (isset($vo->FN)) {
            $co->Label = trim($vo->FN->getValue());
        }
		// Name
        if (isset($vo->N)) {
            $p = $vo->N->getParts();
            $co->Name->Last = trim($p[0]);
            $co->Name->First = trim($p[1]);
            $co->Name->Other = trim($p[2]);
            $co->Name->Prefix = trim($p[3]);
            $co->Name->Suffix = trim($p[4]);
            $co->Name->PhoneticLast = trim($p[6]);
            $co->Name->PhoneticFirst = trim($p[7]);
            $co->Name->Aliases = trim($p[5]);
            unset($p);
        }
        // Aliases
        if (isset($vo->NICKNAME)) {
            if (empty($co->Name->Aliases)) {
                $co->Name->Aliases .= trim($vo->NICKNAME->getValue());
            }
            else {
                $co->Name->Aliases .= ' ' . trim($vo->NICKNAME->getValue());
            }
        }
        // Photo
        if (isset($vo->PHOTO)) {
            $p = $vo->PHOTO->getValue();
            if (str_starts_with($p, 'data:')) {
                $p = explode(';', $p);
                if (count($p) == 2) {
                    $p[0] = explode(':', $p[0]);
                    $p[1] = explode(',', $p[1]);
                    $co->Photo->Type = 'data';
                    $co->Photo->Data = $vo->UID;
                    $co->addAttachment(
                        $vo->UID,
                        $vo->UID . '.' . \OCA\EAS\Utile\MIME::toExtension($p[0][1]),
                        $p[0][1],
                        'B64',
                        'CP',
                        null,
                        $p[1][1]
                    );
                }
            } elseif (str_starts_with($p, 'uri:')) {
                $co->Photo->Type = 'uri';
                $co->Photo->Data = trim(substr($p,4));
            }
            unset($p);
        }
        // Gender
        if (isset($vo->GENDER)) {
            $co->Gender = trim($vo->GENDER->getValue());
        }
        // Birth Day
        if (isset($vo->BDAY)) {
            $co->BirthDay =  new DateTime($vo->BDAY->getValue());
        }
        // Anniversary Day
        if (isset($vo->ANNIVERSARY)) {
            $co->AnniversaryDay =  new DateTime($vo->ANNIVERSARY->getValue());
        }
        // Address(es)
        if (isset($vo->ADR)) {
            foreach($vo->ADR as $entry) {
                $p = $entry->getParts();
                $co->addAddress(
                    strtoupper($entry->parameters()['TYPE']->getValue()),
                    trim($p[2]),
                    trim($p[3]),
                    trim($p[4]),
                    trim($p[5]),
                    trim($p[6])
                );
                unset($p);
            }
        }
        // Phone(s)
        if (isset($vo->TEL)) {
            foreach($vo->TEL as $entry) {
                $co->addPhone(
                    strtoupper(trim($entry->parameters()['TYPE']->getValue())),
                    null, 
                    trim($entry->getValue())
                );
            }
        }
        // Email(s)
        if (isset($vo->EMAIL)) {
            foreach($vo->EMAIL as $entry) {
                $co->addEmail(
                    strtoupper(trim($entry->parameters()['TYPE']->getValue())), 
                    trim($entry->getValue())
                );
            }
        }
        // IMPP(s)
        if (isset($vo->IMPP)) {
            foreach($vo->IMPP as $entry) {
                $co->addIMPP(
                    strtoupper(trim($entry->parameters()['TYPE']->getValue())), 
                    trim($entry->getValue())
                );
            }
        }
        // Time Zone
        if (isset($vo->TZ)) {
            $co->TimeZone = trim($vo->TZ->getValue());
        }
        // Geolocation
        if (isset($vo->GEO)) {
            $co->Geolocation = trim($vo->GEO->getValue());
        }
        // Manager
		if (isset($vo->{'X-MANAGERSNAME'})) {
			$co->Manager = trim($vo->{'X-MANAGERSNAME'}->getValue());
		}
        // Assistant
		if (isset($vo->{'X-ASSISTANTNAME'})) {
			$co->Assistant = trim($vo->{'X-ASSISTANTNAME'}->getValue());
		}
        // Occupation Organization
        if (isset($vo->ORG)) {
			$co->Occupation->Organization = trim($vo->ORG->getValue());
		}
		// Occupation Title
        if (isset($vo->TITLE)) { 
			$co->Occupation->Title = trim($vo->TITLE->getValue()); 
		}
		// Occupation Role
		if (isset($vo->ROLE)) {
			$co->Occupation->Role = trim($vo->ROLE->getValue());
		}
		// Occupation Logo
		if (isset($vo->LOGO)) {
			$co->Occupation->Logo = trim($vo->LOGO->getValue());
		}
                
        // Relation
        if (isset($vo->RELATED)) {
            $co->addRelation(
				strtoupper(trim($vo->RELATED->parameters()['TYPE']->getValue())),
				trim($vo->RELATED->getValue())
			);
        }
        // Tag(s)
        if (isset($vo->CATEGORIES)) {
            foreach($vo->CATEGORIES->getParts() as $entry) {
                $co->addTag(
                    trim($entry)
                );
            }
        }
        // Notes
        if (isset($vo->NOTE)) {
            if (!empty(trim($vo->NOTE->getValue()))) {
                $co->Notes = trim($vo->NOTE->getValue());
            }
        }
        // Sound
        if (isset($vo->SOUND)) {
            $co->Sound = trim($vo->SOUND->getValue());
        }
        // URL / Website
        if (isset($vo->URL)) {
            $co->URI = trim($vo->URL->getValue());
        }

        // return contact object
		return $co;

    }

    /**
     * Convert contact object to vcard object
     * 
	 * @param ContactObject $co - source object
	 * 
	 * @return VCard converted object
	 */
    public function fromContactObject(ContactObject $co): VCard {

        // construct vcard object
        $vo = new VCard();
        // UID
        if (isset($co->UID)) {
            $vo->UID->setValue($co->UID);
        } else {
            $vo->UID->setValue(\OCA\EAS\Utile\UUID::v4());
        }
        // Label
        if (isset($co->Label)) {
            $vo->add('FN', $co->Label);
        }
        // Name
        if (isset($co->Name)) {
            $vo->add(
                'N',
                array(
                    $co->Name->Last,
                    $co->Name->First,
                    $co->Name->Other,
                    $co->Name->Prefix,
                    $co->Name->Suffix,
                    $co->Name->PhoneticLast,
                    $co->Name->PhoneticFirst,
                    $co->Name->Aliases
            ));
        }
        // Photo
        if (isset($co->Photo)) {
            if ($co->Photo->Type == 'uri') {
                $vo->add(
                    'PHOTO',
                    'uri:' . $co->Photo->Data
                );
            } elseif ($co->Photo->Type == 'data') {
                $k = array_search($co->Photo->Data, array_column($co->Attachments, 'Id'));
                if ($k !== false) {
                    switch ($co->Attachments[$k]->Encoding) {
                        case 'B':
                            $vo->add(
                                'PHOTO',
                                'data:' . $co->Attachments[$k]->Type . ';base64,' . base64_encode($co->Attachments[$k]->Data)
                            );
                            break;
                        case 'B64':
                            $vo->add(
                                'PHOTO',
                                'data:' . $co->Attachments[$k]->Type . ';base64,' . $co->Attachments[$k]->Data
                            );
                            break;
                    }
                }
            }
        }
        // Gender
        if (isset($co->Gender)) {
            $vo->add(
                'GENDER',
                $co->Gender
            );
        }
        // Birth Day
        if (isset($co->BirthDay)) {
            $vo->add(
                'BDAY',
                $co->BirthDay->format('Y-m-d\TH:i:s\Z')
            );
        }
        // Anniversary Day
        if (isset($co->AnniversaryDay)) {
            $vo->add(
                'ANNIVERSARY',
                $co->AnniversaryDay->format('Y-m-d\TH:i:s\Z')
            );
        }
        // Address(es)
        if (count($co->Address) > 0) {
            foreach ($co->Address as $entry) {
                $vo->add('ADR',
                    array(
                        '',
                        '',
                        $entry->Street,
                        $entry->Locality,
                        $entry->Region,
                        $entry->Code,
                        $entry->Country,
                    ),
                    array (
                        'TYPE'=>$entry->Type
                    )
                );
            }
        }
        // Phone(s)
        if (count($co->Phone) > 0) {
            foreach ($co->Phone as $entry) {
                $vo->add(
                    'TEL', 
                    $entry->Number,
                    array (
                        'TYPE'=>$entry->Type
                    )
                );
            }
        }
        // Email(s)
        if (count($co->Email) > 0) {
            foreach ($co->Email as $entry) {
                $vo->add(
                    'EMAIL', 
                    $entry->Address,
                    array (
                        'TYPE'=>$entry->Type
                    )
                );
            }
        }
        // IMPP(s)
        if (count($co->IMPP) > 0) {
            foreach ($co->IMPP as $entry) {
                $vo->add(
                    'IMPP', 
                    $entry->Address,
                    array (
                        'TYPE'=>$entry->Type
                    )
                );
            }
        }
        // Time Zone
        if (isset($co->TimeZone)) {
            $vo->add(
                'TZ',
                $co->TimeZone
            );
        }
        // Geolocation
        if (isset($co->Geolocation)) {
            $vo->add(
                'GEO',
                $co->Geolocation
            );
        }
        // Manager Name
		if (!empty($co->Manager)) {
            $vo->add(
                'X-MANAGERSNAME',
                $co->Manager
            );
		}
        // Assistant Name
		if (!empty($co->Assistant)) {
            $vo->add(
                'X-ASSISTANTNAME',
                $co->Assistant
            );
		}
        // Occupation Organization
        if (isset($co->Occupation->Organization)) {
            $vo->add(
                'ORG',
                $co->Occupation->Organization
            );
        }
        // Occupation Title
        if (isset($co->Occupation->Title)) {
            $vo->add(
                'TITLE',
                $co->Occupation->Title
            );
        }
        // Occupation Role
        if (isset($co->Occupation->Role)) {
            $vo->add(
                'ROLE',
                $co->Occupation->Role
            );
        }
        // Occupation Logo
        if (isset($co->Occupation->Logo)) {
            $vo->add(
                'LOGO',
                $co->Occupation->Logo
            );
        }
        // Relation(s)
        if (count($co->Relation) > 0) {
            foreach ($co->Relation as $entry) {
                $vo->add(
                    'RELATED', 
                    $entry->Value,
                    array (
                        'TYPE'=>$entry->Type
                    )
                );
            }
        }
        // Tag(s)
        if (count($co->Tags) > 0) {
            $vo->add('CATEGORIES', $co->Tags);
        }
        // Notes
        if (isset($co->Notes)) {
            $vo->add(
                'NOTE',
                $co->Notes
            );
        }
        // Sound
        if (isset($co->Sound)) {
            $vo->add(
                'SOUND',
                $co->Sound
            );
        }
        // URL / Website
        if (isset($co->URI)) {
            $vo->add(
                'URL',
                $co->URI
            );
        }

        // return vcard object
        return $vo;

    }
    
}
