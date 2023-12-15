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
use DateTimeZone;

class EventObject {

    public ?string $Origin = null;                 // Source System / L - Local / R - Remote
    public ?string $ID = null;                     // Source System Id
    public ?string $UUID = null;                   // Object UUID
    public ?string $CID = null;                    // Source System Object Collection Affiliation Id
    public ?string $Signature = null;              // Source System Object State
    public ?DateTime $CreatedOn = null;            // Source System Creation Date/Time
    public ?DateTime $ModifiedOn = null;           // Source System Modification Date/Time
    public ?DateTime $StartsOn = null;             // Event Start Date/Time
    public ?DateTimeZone $StartsTZ = null;         // Event Start Time Zone
    public ?DateTime $EndsOn = null;               // Event End Date/Time
    public ?DateTimeZone $EndsTZ = null;           // Event End Time Zone
    public ?DateTimeZone $TimeZone = null;         // Event Time Zone
    public ?string $Label = null;                  // Event Title/Summary
    public ?string $Notes = null;                  // Event Notes
    public ?string $Location = null;               // Event Location
    public ?string $Availability = null;           // Event Free Busy Status / F - Free / B - Busy
    public ?string $Priority = null;               // Event Priority / 0 - Low / 1 - Normal / 2 - High
    public ?string $Sensitivity = null;            // Event Sensitivity / 0 - Normal / 1 - Personnal / 2 - Private / 3 - Confidential
    public ?string $Color = null;                  // Event Display Color
    public array $Tags = [];                       // Event Categories
    public ?EventOrganizerObject $Organizer;       // Event Organizer Name/Email
    public array $Attendee = [];                   // Event Attendees Name/Email/Attendance
    public array $Notifications = [];              // Event Reminders/Alerts
    public ?EventOccurrenceObject $Occurrence = null; // Event Recurrance Data
    public array $Attachments = [];                // Event Attachments
    public ?array $Other = [];
	
	public function __construct($data = null) {
        $this->Data = (object) array();
        $this->Data->Original = (object) array();
        $this->Data->Changed = (object) array();
        $this->Organizer = new EventOrganizerObject();
        $this->Occurrence = new EventOccurrenceObject();
	}

    public function addAttachment(string $store, string $id = null, ?string $name = null, ?string $type = null, ?string $encoding = null, ?string $size = null, ?string $data = null) {
        $this->Attachments[] = new EventAttachmentObject($store, $id, $name, $type, $encoding, $size, $data);
    }

    public function addTag(string $tag) {
        $this->Tags[] = $tag;
    }

    public function addAttendee(string $address, ?string $name, ?string $type, ?string $attendance) {
        $this->Attendee[] = new EventAttendeeObject($address, $name, $type, $attendance);
    }

    public function addNotification(string $type, string $pattern, mixed $when) {
        $this->Notifications[] = new EventNotificationObject($type, $pattern, $when);
    }
}
