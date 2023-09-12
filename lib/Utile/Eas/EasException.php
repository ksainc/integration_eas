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

namespace OCA\EAS\Utile\Eas;

use Exception;
use Throwable;

class EasException extends Exception {

	private static $_messages = [
		// Autodiscovery
		'AD-2' => 'Protocol error',
		// Find
		'FN-2' => 'The request was invalid. The search failed to validate.',
		'FN-3' => 'FolderSync required. The folder hierarchy is out of date.',
		'FN-4' => 'The requested range does not begin with 0.',
		// Collection Create
		'CC-2' => 'The parent folder already contains a folder with the same name. Create the folder under a different name.',
		'CC-3' => 'The specified parent folder is a special system folder. Create the folder under a different parent.',
		'CC-5' => 'The parent folder does not exist on the server, possibly because it has been deleted or moved.',
		'CC-6' => 'An error occurred on the server.',
		'CC-9' => 'Synchronization key mismatch or invalid synchronization key.',
		'CC-10' => 'Malformed request. The request contains a semantic error, or attempted to create a default folder, such as the Inbox folder, Outbox folder, or Contacts folder.',
		// Collection Update
		'CU-2' => 'A folder with that name already exists or the specified folder is a special folder.',
		'CU-3' => 'The specified folder is a special folder. Special folders cannot be updated.',
		'CU-4' => 'The specified folder does not exist.',
		'CU-5' => 'The specified parent folder does not exist.',
		'CU-6' => 'An error occurred on the server.',
		'CU-9' => 'Synchronization key mismatch or invalid synchronization key.',
		'CU-10' => 'Incorrectly formatted request.',
		// Collection Delete
		'CD-3' => 'The specified folder is a special system folder and cannot be deleted.',
		'CD-4' => 'The specified folder does not exist.',
		'CD-6' => 'An error occurred on the server.',
		'CD-9' => 'Synchronization key mismatch or invalid synchronization key.',
		'CS-6' => 'An error occurred on the server.',
		'CS-9' => 'Synchronization key mismatch or invalid synchronization key.',
		'CS-10' => 'Incorrectly formatted request.',
		// Global
		101 => 'Invalid Content. The body of the HTTP request sent by the client is invalid.',
		102 => 'Invalid WBXML. The request contains WBXML but it could not be decoded.',
		103 => 'Invalid XML. The XML provided in the request does not follow the protocol requirements.',
		104 => 'Invalid DateTime. The request contains a timestamp that could not be parsed into a valid date and time.',
		105 => 'Invalid Combination Of IDs. The request contains a combination of parameters that is invalid.',
		106 => 'Invalid IDs. The request contains one or more IDs that could not be parsed into valid values.',
		107 => 'Invalid MIME. The request contains MIME that could not be parsed.',
		108 => 'Device Id Missing Or Invalid. The device ID is either missing or has an invalid format.',
		109 => 'Device Type Missing Or Invalid. The device type is either missing or has an invalid format.',
		110 => 'Server Error. The server encountered an unknown error, the device SHOULD NOT retry later.',
		111 => 'Server Error Retry Later. The server encountered an unknown error, the device SHOULD retry later.',
		112 => 'Active Directory Access Denied. The server does not have access to read/write to an object in the directory service.',
		113 => 'Mailbox Quota Exceeded. The mailbox has reached its size quota.',
		114 => 'Mailbox Server Offline. The mailbox server is offline.',
		115 => 'Send Quota Exceeded. The request would exceed the send quota.',
		116 => 'Message Recipient Unresolved. One of the recipients could not be resolved to an email address.',

	];

	/**
	 * @param string|int $code			[optional] The Exception code.
	 * @param string $message 			[optional] The Exception message to throw.
	 * @param null|Throwable $previous 	[optional] The previous throwable used for the exception chaining.
	 */
	public function __construct(string|int $code = 0, string $operation, string $message = null, Throwable $previous = null) {
		if (!is_numeric($code)) {
			$code = (int)$code;
		}
		if ($code < 100) {
			$mcode = $operation . '-' . $code;
		}
		else {
			$mcode = $code;
		}

		if (!isset($message)) {
			if (isset($this->_messages[$mcode])) {
				$message = $mcode . ': ' . $this->_messages[$mcode];
			}
			else {
				$message = $code . ': Unknown Error';
			}
			
		}
		parent::__construct($message, $code, $previous);
	}
}
