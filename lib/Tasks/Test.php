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
	//$EasXmlEncoder = new \OCA\EAS\Utile\Eas\EasXmlEncoder();
	//$EasXmlDecoder = new \OCA\EAS\Utile\Eas\EasXmlDecoder();
	
	// construct remote data store client
	$EasClient = $CoreService->createClient($uid);
	// assign remote data store to module
	$RemoteContactsService->DataStore = $EasClient;

	// perform initial connect
	$EasClient->performConnect();

	// Load From File
	//$stream = fopen(__DIR__ . '/EAS-Calendar-Create', 'r');
	//$msg_ref_raw = stream_get_contents($stream);
	//$msg_ref_obj = $EasXmlDecoder->streamToObject($stream);
	//fclose($stream);
	//exit;

	$token = 0;

	// Fetch collections
	$rs = $RemoteCommonService->syncCollections($EasClient);
	
	// find contacts collection
	foreach ($rs->Changes->Add as $entry) {
		if ($entry->Name->getContents() == 'Contacts') {
			$cid = $entry->Id->getContents();
			break;
		}
	}

	// sync collection
	$rs = $RemoteContactsService->syncEntities($cid, $token);
	
	$token = $rs->SyncKey->getContents();

	if (isset($rs->Commands)) {

		if (!is_array($rs->Commands->Add)) {
			$rs->Commands->Add = [$rs->Commands->Add];
		}

		foreach ($rs->Commands->Add as $entry) {
			$testid = $entry->EntityId->getContents();
		}
	
	}

	// retrieve entity
	$rs = $RemoteContactsService->fetchEntity($cid, $testid);

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

