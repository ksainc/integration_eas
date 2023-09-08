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
/*
try {
*/

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
	
	/*
	$EasXmlEncoder = new \OCA\EAS\Utile\Eas\EasXmlEncoder();
	*/
	$EasXmlDecoder = new \OCA\EAS\Utile\Eas\EasXmlDecoder();
	

	// construct remote data store client
	$EasClient = $CoreService->createClient($uid);

	// perform initial connect
	$EasClient->performConnect();

	// Load From File
	//$stream = fopen(__DIR__ . '/Microsoft-Server-ActiveSync-Sync-Response', 'r');
	//$msg_ref_raw = stream_get_contents($stream);
	//$msg_ref_obj = $EasXmlDecoder->streamToObject($stream);
	//fclose($stream);
	//$msg_ref_hex = unpack('H*', $msg_ref_raw);
	//exit;


	//$stream = fopen(__DIR__ . '/Microsoft-Server-ActiveSync2', 'r');
	//$msg2_ref_raw = stream_get_contents($stream);
	//fclose($stream);
	//$msg2_ref_obj = $EasXmlDecoder->stringToObject($msg2_ref_raw);
	//$msg2_ref_hex = unpack('H*', $msg2_ref_raw)[1];

	//$msg_ref_hex = '03016a00455c4f4b03564430794d44497a4d446b774d5651794d6a55774e4456614f3155394d44744750545537545430784f444d314d546b37557a30774f773d3d000152033100015e03310001135503353000015758033500010018450331000100114546033200010101010101';
	//$msg_ref_raw = pack('H*', $msg_ref_hex);
	//$msg_ref_obj = $EasXmlDecoder->stringToObject($msg_ref_raw);

	$token = 0;
	$cid = '8'; // MS365 5

	//$rs = $RemoteCommonService->fetchFolders($EasClient);

	$rs = $RemoteCommonService->fetchFolderChanges($EasClient, $cid, $token, ['MOVED' => 1]);
	
	$token = $rs->Sync->Collections->Collection->SyncKey->getContents();

	//$rs = $RemoteCommonService->fetchFolderEstimate($EasClient, $cid, $token);

	$rs = $RemoteCommonService->fetchFolderChanges($EasClient, $cid, $token, ['MOVED' => 1, 'CHANGES' => 1, 'FILTER' => 0, 'MIME' => 1]);

	if (isset($rs->Sync->Collections->Collection->Commands)) {
		foreach ($rs->Sync->Collections->Collection->Commands->Add as $entry) {
			$contact = $RemoteContactsService->toContactObject($entry->Data);
		}
	}
	

	//$token = $rs->Sync->Collections->Collection->SyncKey->getContents();
	
	//$rs = $RemoteCommonService->fetchFolderChanges($EasClient, $cid, $token, 0, 32);

	exit;

/*
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
*/
