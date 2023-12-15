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

namespace OCA\EAS\Objects;

use DateTime;

class ContactObject {

    public ?string $Origin = null;                  // Source System / L - Local / R - Remote
    public ?string $ID = null;                      // Source System Id
    public ?string $UUID = null;                    // Object UUID
    public ?string $CID = null;                     // Source System Collection Id
    public ?string $Signature = null;               // Source System Object Signature
    public ?DateTime $CreatedOn = null;             // Source System Creation Date/Time
    public ?DateTime $ModifiedOn = null;            // Source System Modification Date/Time
    public ?string $Label = null;                   // Contact Display Label
    public ?string $Notes = null;                   // Contact Notes
	public ?ContactNameObject $Name = null;         // Contact Name(s)
    public ?ContactPhotoObject $Photo = null;       // Contact Photo
    public ?DateTime $BirthDay = null;              // Contact Birth Day
    public ?string $Gender = null;                  // Contact Gender
    public ?string $Partner = null;                 // Contact Partner/Spouse
	public ?DateTime $NuptialDay = null;            // Contact Matrimonial Day
	public array $Address = [];
	public array $Phone = [];
    public array $Email = [];
    public array $IMPP = [];
    public ?string $TimeZone = null;
    public ?string $Geolocation = null;
    public ?string $Manager = null;
    public ?string $Assistant = null;
    public ?ContactOccupationObject $Occupation = null;
    public ?array $Relation = [];
    public array $Tags = [];                       // Contact Categories
    public ?string $Sound = null;
    public ?string $URI = null;
    public array $Attachments = [];
    public ?array $Other = [];
	
	public function __construct() {
        $this->Name = new ContactNameObject();
        $this->Photo = new ContactPhotoObject();
        $this->Occupation = new ContactOccupationObject();
	}

    public function addEmail(string $type, string $address) {
        $this->Email[] = new ContactEmailObject($type, $address);
    }

    public function addPhone(string $type, ?string $subtype, ?string $number) {
        $this->Phone[] = new ContactPhoneObject($type, $subtype, $number);
    }

    public function addAddress($type, ?string $street = null, ?string $locality = null, ?string $region = null, ?string $code = null, ?string $country = null) {
        $this->Address[] = new ContactAddressObject($type, $street, $locality, $region, $code, $country);
    }

    public function addIMPP(string $type, string $address) {
        $this->IMPP[] = new ContactIMPPObject($type, $address);
    }

    public function addTag(string $tag) {
        $this->Tags[] = $tag;
    }

    public function addRelation(string $type, string $value) {
        $this->Phone[] = new ContactRelationObject($type, $value);
    }
    
    public function addAttachment(string $id, ?string $name = null, ?string $type = null, ?string $encoding = null, ?string $flag = null, ?string $size = null,  ?string $data = null) {
        $this->Attachments[] = new ContactAttachmentObject($id, $name, $type, $encoding, $flag, $size, $data);
    }

}
