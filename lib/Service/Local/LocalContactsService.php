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

use OCA\EAS\Store\ContactStore;
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
	 * @param int $id            Collection ID
	 * 
	 * @return ContactCollectionObject  ContactCollectionObject on success / null on fail
	 */
	public function fetchCollection(int $id): ?ContactCollectionObject {

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
     * retrieve the differences for specific collection from a specific point from local storage
     * 
     * @param string $uid		    User ID
	 * @param int $cid			    Collection ID
	 * @param string $stamp		    Time Stamp
	 * @param int $limit		    Results Limit
	 * @param int $offset		    Results Offset
	 * 
	 * @return array                Collection of differences
	 */
	public function reconcileCollection(string $uid, int $cid, string $stamp, ?int $limit = null, ?int $offset = null): array {

        // retrieve collection differences
        $lcc = $this->_Store->reminisce($uid, $cid, $stamp, $limit, $offset);
        // return collection differences
		return $lcc;
        
    }

	/**
     * retrieve entity object from local storage
     * 
     * @param int $id               Entity ID
	 * 
	 * @return ContactObject        ContactObject on success / null on fail
	 */
	public function fetchEntity(int $id): ?ContactObject {

        // retrieve object properties
        $lo = $this->_Store->fetchEntity($id);
		// evaluate if object properties where retrieved
        if (is_array($lo) && count($lo) > 0) {
            // convert to contact object
            $co = $this->toContactObject(Reader::read($lo['data']));
            $co->ID = $lo['id'];
            $co->UUID = $lo['uuid'];
            $co->CID = $lo['cid'];
            $co->Signature = trim($lo['signature'],'"');
            $co->RCID = $lo['rcid'];
            $co->REID = $lo['reid'];
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
     * @param string $uid           User ID
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
            $co->Signature = trim($lo['signature'],'"');
            $co->RCID = $lo['rcid'];
            $co->REID = $lo['reid'];
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
	 * @param string $uid           User ID
	 * @param int $cid              Collection ID
     * @param ContactObject $so     Source Object
	 * 
	 * @return object               Status Object - item id, item uuid, item state token / Null - failed to create
	 */
	public function createEntity(string $uid, int $cid, ContactObject $so): ?object {

        // initilize data place holder
        $lo = [];
        // convert contact object to vcard object
        $lo['data'] = $this->fromContactObject($so)->serialize();
        $lo['uuid'] = (!empty($so->UUID)) ? $so->UUID : \OCA\EAS\Utile\UUID::v4();
        $lo['uid'] = $uid;
        $lo['cid'] = $cid;
        $lo['rcid'] = $so->RCID;
        $lo['reid'] = $so->REID;
        $lo['size'] = strlen($lo['data']);
        $lo['signature'] = md5($lo['data']);
        $lo['label'] = $so->Label;
        // create entry in data store
        $id = $this->_Store->createEntity($lo);
        // return status object or null
        if ($id) {
            return (object) array('ID' => $id, 'Signature' => $lo['signature']);
        } else {
            return null;
        }

    }
    
    /**
     * update entity in local storage
     * 
	 * @param string $uid           User ID
	 * @param int $cid              Collection ID
	 * @param int $eid              Entity ID
     * @param ContactObject $so     Source Object
	 * 
	 * @return object               Status Object - item id, item uuid, item state token / Null - failed to create
	 */
	public function updateEntity(string $uid, int $cid, int $eid, ContactObject $so): ?object {

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
        $lo['size'] = strlen($lo['data']);
        $lo['signature'] = md5($lo['data']);
        $lo['label'] = $so->Label;
        // modify entry in data store
        $rs = $this->_Store->modifyEntity($eid, $lo);
        // return status object or null
        if ($rs) {
            return (object) array('ID' => $eid, 'Signature' => $lo['signature']);
        } else {
            return null;
        }

    }
    
    /**
     * delete entity from local storage
     * 
	 * @param string $uid           User ID
	 * @param int $cid              Collection ID
	 * @param int $eid              Entity ID
	 * 
	 * @return bool                 true - successfully delete / false - failed to delete
	 */
	public function deleteEntity(string $uid, int $cid, int $eid): bool {

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
            $co->UUID = $this->sanitizeString($vo->UID->getValue());
        }
        // Label
        if (isset($vo->FN)) {
            $co->Label = $this->sanitizeString($vo->FN->getValue());
        }
		// Name
        if (isset($vo->N)) {
            $p = $vo->N->getParts();
            $co->Name->Last = $this->sanitizeString($p[0]);
            $co->Name->First = $this->sanitizeString($p[1]);
            $co->Name->Other = $this->sanitizeString($p[2]);
            $co->Name->Prefix = $this->sanitizeString($p[3]);
            $co->Name->Suffix = $this->sanitizeString($p[4]);
            $co->Name->PhoneticLast = $this->sanitizeString($p[6]);
            $co->Name->PhoneticFirst = $this->sanitizeString($p[7]);
            $co->Name->Aliases = $this->sanitizeString($p[5]);
            unset($p);
        }
        // Aliases
        if (isset($vo->NICKNAME)) {
            if (empty($co->Name->Aliases)) {
                $co->Name->Aliases .= $this->sanitizeString($vo->NICKNAME->getValue());
            }
            else {
                $co->Name->Aliases .= ' ' . $this->sanitizeString($vo->NICKNAME->getValue());
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
                $co->Photo->Data = $this->sanitizeString(substr($p,4));
            }
            unset($p);
        }
        // Gender
        if (isset($vo->GENDER)) {
            $co->Gender = $this->sanitizeString($vo->GENDER->getValue());
        }
        // Birth Day
        if (isset($vo->BDAY)) {
            $co->BirthDay =  new DateTime($vo->BDAY->getValue());
        }
        // Anniversary Day
        if (isset($vo->ANNIVERSARY)) {
            $co->NuptialDay =  new DateTime($vo->ANNIVERSARY->getValue());
        }
        // Address(es)
        if (isset($vo->ADR)) {
            foreach($vo->ADR as $entry) {
                $type  = $entry->parameters()['TYPE']->getValue();
                [$pob, $unit, $street, $locality, $region, $code, $country] = $entry->getParts();
                $co->addAddress(
                    strtoupper($type),
                    $this->sanitizeString($street),
                    $this->sanitizeString($locality),
                    $this->sanitizeString($region),
                    $this->sanitizeString($code),
                    $this->sanitizeString($country)
                );
            }
            unset($type, $pob, $unit, $street, $locality, $region, $code, $country);
        }
        // Phone(s)
        if (isset($vo->TEL)) {
            foreach($vo->TEL as $entry) {
                [$primary, $secondary] = explode(',', trim($entry->parameters()['TYPE']->getValue()));
                $co->addPhone(
                    $primary,
                    $secondary, 
                    $this->sanitizeString($entry->getValue())
                );
            }
            unset($primary, $secondary);
        }
        // Email(s)
        if (isset($vo->EMAIL)) {
            foreach($vo->EMAIL as $entry) {
                $co->addEmail(
                    strtoupper(trim($entry->parameters()['TYPE']->getValue())), 
                    $this->sanitizeString($entry->getValue())
                );
            }
        }
        // IMPP(s)
        if (isset($vo->IMPP)) {
            foreach($vo->IMPP as $entry) {
                $co->addIMPP(
                    strtoupper(trim($entry->parameters()['TYPE']->getValue())), 
                    $this->sanitizeString($entry->getValue())
                );
            }
        }
        // Time Zone
        if (isset($vo->TZ)) {
            $co->TimeZone = $this->sanitizeString($vo->TZ->getValue());
        }
        // Geolocation
        if (isset($vo->GEO)) {
            $co->Geolocation = $this->sanitizeString($vo->GEO->getValue());
        }
        // Manager
		if (isset($vo->{'X-MANAGERSNAME'})) {
			$co->Manager = $this->sanitizeString($vo->{'X-MANAGERSNAME'}->getValue());
		}
        // Assistant
		if (isset($vo->{'X-ASSISTANTNAME'})) {
			$co->Assistant = $this->sanitizeString($vo->{'X-ASSISTANTNAME'}->getValue());
		}
        // Occupation Organization
        if (isset($vo->ORG)) {
			$co->Occupation->Organization = $this->sanitizeString($vo->ORG->getValue());
		}
		// Occupation Title
        if (isset($vo->TITLE)) { 
			$co->Occupation->Title = $this->sanitizeString($vo->TITLE->getValue()); 
		}
		// Occupation Role
		if (isset($vo->ROLE)) {
			$co->Occupation->Role = $this->sanitizeString($vo->ROLE->getValue());
		}
		// Occupation Logo
		if (isset($vo->LOGO)) {
			$co->Occupation->Logo = $this->sanitizeString($vo->LOGO->getValue());
		}
                
        // Relation
        if (isset($vo->RELATED)) {
            $co->addRelation(
				strtoupper(trim($vo->RELATED->parameters()['TYPE']->getValue())),
				sanitizeString($vo->RELATED->getValue())
			);
        }
        // Tag(s)
        if (isset($vo->CATEGORIES)) {
            foreach($vo->CATEGORIES->getParts() as $entry) {
                $co->addTag(
                    $this->sanitizeString($entry)
                );
            }
        }
        // Notes
        if (isset($vo->NOTE)) {
            if (!empty(trim($vo->NOTE->getValue()))) {
                $co->Notes = $this->sanitizeString($vo->NOTE->getValue());
            }
        }
        // Sound
        if (isset($vo->SOUND)) {
            $co->Sound = $this->sanitizeString($vo->SOUND->getValue());
        }
        // URL / Website
        if (isset($vo->URL)) {
            $co->URI = $this->sanitizeString($vo->URL->getValue());
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
        if (isset($co->UUID)) {
            $vo->UID->setValue($co->UUID);
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
        if (isset($co->NuptialDay)) {
            $vo->add(
                'ANNIVERSARY',
                $co->NuptialDay->format('Y-m-d\TH:i:s\Z')
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
                        'TYPE'=> (isset($entry->SubType)) ? ($entry->Type . ',' . $entry->SubType) : $entry->Type
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
    
    public function sanitizeString($value): string|null {

        // remove white space
        $value = trim($value);
        // return value or null
        return $value === '' ? null : $value;
        
    }

}
