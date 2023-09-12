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

require_once __DIR__ . '/../../../../lib/versioncheck.php';

try {

	require_once __DIR__ . '/../../../../lib/base.php';

	// assign defaults
	$executionMode = 'S';
	$uid = null;

	$logger = \OC::$server->getLogger();
	$config = \OC::$server->getConfig();

	// evaluate if script was started from console
	if (php_sapi_name() == 'cli') {
		$executionMode = 'C';
		$logger->info('Test running, as console script', ['app' => 'integration_eas']);
		echo 'Test running, as console script' . PHP_EOL;
	}

	// evaluate running mode
	if ($executionMode == 'C') {
		// retrieve passed parameters
		$parameters = getopt("u:");
		// evaluate if user name exists
		if (isset($parameters["u"])) {
			// assign user name
			$uid = \OCA\EAS\Utile\Sanitizer::username($parameters["u"]);
		}
	}
	else {
		// evaluate if user name exists
		if (isset($_GET["u"])) {
			// assign user name
			$uid = \OCA\EAS\Utile\Sanitizer::username($_GET["u"]);
		}
	}
	
	// evaluate, if user name is present
	if (empty($uid)) {
		$logger->info('Test ended, missing required parameters', ['app' => 'integration_eas']);
		echo 'Test ended, missing required parameters' . PHP_EOL;
		exit(0);
	}

	$logger->info('Test started for ' . $uid, ['app' => 'integration_eas']);
	echo 'Test started for ' . $uid . PHP_EOL;

	// load all apps to get all api routes properly setup
	//OC_App::loadApps();
	
	// initilize required services
	$ConfigurationService = \OC::$server->get(\OCA\EAS\Service\ConfigurationService::class);
	$CoreService = \OC::$server->get(\OCA\EAS\Service\CoreService::class);
	//$HarmonizationService = \OC::$server->get(\OCA\EAS\Service\HarmonizationService::class);
	$RemoteCommonService = \OC::$server->get(\OCA\EAS\Service\Remote\RemoteCommonService::class);
	$RemoteContactsService = \OC::$server->get(\OCA\EAS\Service\Remote\RemoteContactsService::class);

	// construct decoder
	
	/**/
	$EasXmlEncoder = new \OCA\EAS\Utile\Eas\EasXmlEncoder();
	$EasXmlDecoder = new \OCA\EAS\Utile\Eas\EasXmlDecoder();
	

	// construct remote data store client
	$EasClient = $CoreService->createClient($uid);

	// perform initial connect
	$EasClient->performConnect();

	// Load From File
	//$stream = fopen(__DIR__ . '/Microsoft-Server-ActiveSync-Add2', 'r');
	//$msg_ref_raw = stream_get_contents($stream);
	//$msg_ref_obj = $EasXmlDecoder->streamToObject($stream);
	//fclose($stream);
	//$msg_ref_hex = unpack('H*', $msg_ref_raw);
	//exit;

	$token = 0;

	// Fetch collections
	$rs = $RemoteCommonService->syncCollections($EasClient);

	/* ====== Working ============
	// retrieve sync token
	$token = $rs->CollectionSync->SyncKey->getContents();
	// create collection
	$rs = $RemoteCommonService->createCollection($EasClient, $token, '0', 'Test Contacts', \OCA\EAS\Utile\Eas\EasTypes::COLLECTION_TYPE_USER_CONTACTS);
	// retrieve collection id and sync token
	$cid = $rs->CollectionCreate->Id->getContents();
	//$token = $rs->CollectionCreate->SyncKey->getContents();
	// update collection
	$rs = $RemoteCommonService->updateCollection($EasClient, $token, '0', $cid, 'Test Contacts 2');
	// retrieve sync token
	//$token = $rs->CollectionCreate->SyncKey->getContents();
	// delete collection
	$rs = $RemoteCommonService->deleteCollection($EasClient, $token, $cid);
	*/

	// select collection
	$cid = '5'; // MS365 5 - OP - 8

	// sync collection
	$rs = $RemoteCommonService->syncEntities($EasClient, $cid, $token, []); // ['SUPPORTED' => true]
	
	$token = $rs->Sync->Collections->Collection->SyncKey->getContents();

	//$rs = $RemoteCommonService->fetchFolderEstimate($EasClient, $cid, $token);

	$rs = $RemoteCommonService->syncEntities($EasClient, $cid, $token, ['CHANGES' => 1, 'LIMIT' => 32, 'FILTER' => 0, 'MIME' => 0]); // 

	$token = $rs->Sync->Collections->Collection->SyncKey->getContents();

	if (isset($rs->Sync->Collections->Collection->Commands)) {

		foreach ($rs->Sync->Collections->Collection->Commands->Add as $entry) {
			
			//$entry = $rs->Sync->Collections->Collection->Commands->Add[0];

			if ($entry->Data->FileAs->getContents() == 'NC Homer J. Simpson') {
				$rs = $RemoteCommonService->deleteEntity($EasClient, $cid, $token, $entry->EntityId->getContents());
			}

		}

		//$rs = $RemoteCommonService->fetchEntity($EasClient, $cid, $token, $entry->EntityId->getContents());

	}

	$co = new \OCA\EAS\Objects\ContactObject();
	$co->Label = 'NC Homer J. Simpson';
	$co->Name->Last = "Simpson";
	$co->Name->First = 'Homer';
	$co->Name->Other = 'J';
	$co->Name->Prefix = 'Mr';
	$co->Name->Suffix = 'Dooh';
	$co->Aliases = 'Pieman';
	$co->BirthDay = new \Datetime('May 12, 1956');
	$co->AnniversaryDay = new \Datetime('April 19, 1987');
	$co->addAddress(
		'HOME',
		'742 Evergreen Terrace',
		'Springfield',
		'Oregon',
		'97477',
		'United States'
	);
	$co->addAddress(
		'WORK',
		'1 Atomic Lane',
		'Springfield',
		'Oregon',
		'97408',
		'United States'
	);
	$co->addPhone(
		'HOME',
		'VOICE',
		'(939) 555-0113'
	);
	$co->addPhone(
		'WORK',
		'VOICE',
		'(939) 555-7334'
	);
	$co->addEmail(
		'HOME',
		'homer@simpsons.fake'
	);
	$co->addEmail(
		'WORK',
		'hsimpson@springfieldpower.fake'
	);
	$co->Occupation->Organization = 'Springfield Power Company';
	$co->Occupation->Title = 'Chief Safety Officer';
	$co->Occupation->Role = 'Safety Inspector';
	$co->addTag('Simpson Family');

	$o = $RemoteContactsService->fromContactObject($co);
	
	$rs = $RemoteCommonService->createEntity($EasClient, $cid, $token, 'Contacts', $o);

	$test = $rs;
	

	//$token = $rs->Sync->Collections->Collection->SyncKey->getContents();
	
	//$rs = $RemoteCommonService->fetchFolderChanges($EasClient, $cid, $token, 0, 32);

	exit;

} catch (Exception $ex) {
	$logger->logException($ex, ['app' => 'integration_eas']);
	$logger->info('Test ended unexpectedly', ['app' => 'integration_eas']);
	echo $ex . PHP_EOL;
	exit(1);
} catch (Error $ex) {
	$logger->logException($ex, ['app' => 'integration_eas']);
	$logger->info('Test ended unexpectedly', ['app' => 'integration_eas']);
	echo $ex . PHP_EOL;
	exit(1);
}

