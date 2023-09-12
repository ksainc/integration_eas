<?php
declare(strict_types=1);

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

namespace OCA\EAS\Utile\Eas;

class EasTypes {

	const COLLECTION_TYPE_SYSTEM_INBOX = 2;
	const COLLECTION_TYPE_SYSTEM_DRAFTS = 3;
	const COLLECTION_TYPE_SYSTEM_DELETED = 4;
	const COLLECTION_TYPE_SYSTEM_SENT = 5;
	const COLLECTION_TYPE_SYSTEM_OUTBOX = 6;
	const COLLECTION_TYPE_SYSTEM_TASKS = 7;
	const COLLECTION_TYPE_SYSTEM_CALENDAR = 8;
	const COLLECTION_TYPE_SYSTEM_CONTACTS = 9;
	const COLLECTION_TYPE_SYSTEM_NOTES = 10;
	const COLLECTION_TYPE_SYSTEM_JOURNAL = 11;
	const COLLECTION_TYPE_SYSTEM_CACHE = 19;
	const COLLECTION_TYPE_USER_GENERIC = 1;
	const COLLECTION_TYPE_USER_MAIL = 12;
	const COLLECTION_TYPE_USER_CALENDAR = 13;
	const COLLECTION_TYPE_USER_CONTACTS = 14;
	const COLLECTION_TYPE_USER_TASKS = 15;
	const COLLECTION_TYPE_USER_JOURNAL = 16;
	const COLLECTION_TYPE_USER_NOTES = 17;
	const COLLECTION_TYPE_USER_UNKNOWN = 19;

	const ENTITY_TYPE_MAIL = 'Email';
	const ENTITY_TYPE_CALENDAR = 'Calendar';
	const ENTITY_TYPE_CONTACT = 'Contacts';
	const ENTITY_TYPE_JOURNAL = 'Journal';
	const ENTITY_TYPE_TASK = 'Tasks';
	const ENTITY_TYPE_SMS = 'SMS';
	const ENTITY_TYPE_NOTE = 'Notes';

}