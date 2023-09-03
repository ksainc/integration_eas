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
		$logger->info('Test running, as console script', ['app' => 'integration_ews']);
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
		$logger->info('Test ended, missing required parameters', ['app' => 'integration_ews']);
		echo 'Test ended, missing required parameters' . PHP_EOL;
		exit(0);
	}

	$logger->info('Test started for ' . $uid, ['app' => 'integration_ews']);
	echo 'Test started for ' . $uid . PHP_EOL;

	// load all apps to get all api routes properly setup
	OC_App::loadApps();
	
	// initilize required services
	$ConfigurationService = \OC::$server->get(\OCA\EAS\Service\ConfigurationService::class);
	//$CoreService = \OC::$server->get(\OCA\EAS\Service\CoreService::class);
	//$HarmonizationService = \OC::$server->get(\OCA\EAS\Service\HarmonizationService::class);

	// construct decoder
	$EasXmlEncoder = new \OCA\EAS\Utile\Eas\EasXmlEncoder();
	$EasXmlDecoder = new \OCA\EAS\Utile\Eas\EasXmlDecoder();

	$account = $ConfigurationService->retrieveAuthenticationBasic($uid);

	// construct remote data store client
	$EasClient = new \OCA\EAS\Utile\Eas\EasClient(
		$account['account_server'], 
		new \OCA\EAS\Utile\Eas\EasAuthenticationBasic($account['account_bauth_id'], $account['account_bauth_secret']),
		$account['account_deviceid'],
		$account['account_devicekey'],
		$account['account_deviceversion']
	);

	// Load From File
	//$stream = fopen(__DIR__ . '/Microsoft-Server-ActiveSync', 'r');
	//$msg_ref_raw = stream_get_contents($stream);
	//$msg_ref_obj = $EasXmlDecoder->streamToObject($stream);
	//fclose($stream);
	//$msg_ref_hex = unpack('H*', $msg_ref_raw);

	//$stream = fopen(__DIR__ . '/Microsoft-Server-ActiveSync2', 'r');
	//$msg2_ref_raw = stream_get_contents($stream);
	//fclose($stream);
	//$msg2_ref_obj = $EasXmlDecoder->stringToObject($msg2_ref_raw);
	//$msg2_ref_hex = unpack('H*', $msg2_ref_raw)[1];

	//$msg_ref_hex = '03016a00455c4f4b03564430794d44497a4d446b774d5651794d6a55774e4456614f3155394d44744750545537545430784f444d314d546b37557a30774f773d3d000152033100015e03310001135503353000015758033500010018450331000100114546033200010101010101';
	//$msg_ref_raw = pack('H*', $msg_ref_hex);
	//$msg_ref_obj = $EasXmlDecoder->stringToObject($msg_ref_raw);

	// perform initial connect
	$EasClient->performConnect();

	// construct folder sync request
	$msg_fs_rq_o = new \stdClass();
	$msg_fs_rq_o->FolderSync = new \OCA\EAS\Utile\Eas\EasObject('FolderHierarchy');
	$msg_fs_rq_o->FolderSync->SyncKey = new \OCA\EAS\Utile\Eas\EasProperty('FolderHierarchy', 0);

	$msg_fs_rq_r = $EasXmlEncoder->stringFromObject($msg_fs_rq_o);
	$msg_fs_rq_h = unpack('H*', $msg_fs_rq_r)[1];

	// perform command
	$msg_fs_rp_r = $EasClient->performFolderSync($msg_fs_rq_r);
	$msg_fs_rp_o = $EasXmlDecoder->stringToObject($msg_fs_rp_r);

	/*
	// construct folder sync request
	$stream = fopen('php://temp', 'r+');
	$WbxmlEncoder = new \OCA\EAS\Utile\Wbxml\WbxmlEncoder($stream);
	$WbxmlEncoder->StartWBXML();
	// Start Sync Command Tag
	$WbxmlEncoder->startTag('Synchronize');

	// Start Collections Tag
	$WbxmlEncoder->startTag('Folders');
	// Start Collection Tag
	$WbxmlEncoder->startTag('Folder');

	// SyncKey Property
	$WbxmlEncoder->startTag('SyncKey');
	$WbxmlEncoder->content(0);
	$WbxmlEncoder->endTag();
	// CollectionId Property
	$WbxmlEncoder->startTag('FolderId');
	$WbxmlEncoder->content(8);
	$WbxmlEncoder->endTag();
	// GetChanges Property
	$WbxmlEncoder->startTag('GetChanges');
	$WbxmlEncoder->content(0);
	$WbxmlEncoder->endTag();
	// DeletesAsMoves Property
	$WbxmlEncoder->startTag('DeletesAsMoves');
	$WbxmlEncoder->content(0);
	$WbxmlEncoder->endTag();
	// WindowSize Property
	$WbxmlEncoder->startTag('WindowSize');
	$WbxmlEncoder->content(32);
	$WbxmlEncoder->endTag();

	// Start Options Tag
	$WbxmlEncoder->startTag('Options');
	// WindowSize Property
	$WbxmlEncoder->startTag('FilterType');
	$WbxmlEncoder->content(0);
	$WbxmlEncoder->endTag();

	// Start BodyPreference Tag
	$WbxmlEncoder->startTag('AirSyncBase:BodyPreference');
	// Type Property
	$WbxmlEncoder->startTag('AirSyncBase:Type');
	$WbxmlEncoder->content(1);
	$WbxmlEncoder->endTag();
	// Type Property
	$WbxmlEncoder->startTag('AirSyncBase:AllOrNone');
	$WbxmlEncoder->content(1);
	$WbxmlEncoder->endTag();
	// End BodyPreference Tag
	$WbxmlEncoder->endTag();

	// End Options Tag
	$WbxmlEncoder->endTag();

	// End Collection Tag
	$WbxmlEncoder->endTag();
	// End Collections Tag
	$WbxmlEncoder->endTag();

	// WindowSize Propery
	$WbxmlEncoder->startTag('WindowSize');
	$WbxmlEncoder->content(32);
	$WbxmlEncoder->endTag();

	// End Sync Command Tag
	$WbxmlEncoder->endTag();

	// retrieve data from stream
	rewind($stream);
	$msg_ref_raw = stream_get_contents($stream);
	fclose($stream);
	$msg_ref_hex = unpack('H*', $msg_ref_raw)[1];
	*/

	// construct folder sync request
	$msg_sync_rq_o = new \stdClass();
	$msg_sync_rq_o->Sync = new \OCA\EAS\Utile\Eas\EasObject('AirSync');
	$msg_sync_rq_o->Sync->Collections = new \OCA\EAS\Utile\Eas\EasObject('AirSync');
	$msg_sync_rq_o->Sync->Collections->Collection = new \OCA\EAS\Utile\Eas\EasObject('AirSync');
	$msg_sync_rq_o->Sync->Collections->Collection->SyncKey = new \OCA\EAS\Utile\Eas\EasProperty('AirSync', 0);
	$msg_sync_rq_o->Sync->Collections->Collection->CollectionId = new \OCA\EAS\Utile\Eas\EasProperty('AirSync', 8);
	$msg_sync_rq_o->Sync->Collections->Collection->GetChanges = new \OCA\EAS\Utile\Eas\EasProperty('AirSync', 0);
	$msg_sync_rq_o->Sync->Collections->Collection->DeletesAsMoves = new \OCA\EAS\Utile\Eas\EasProperty('AirSync', 0);
	$msg_sync_rq_o->Sync->Collections->Collection->WindowSize = new \OCA\EAS\Utile\Eas\EasProperty('AirSync', 32);
	$msg_sync_rq_o->Sync->Collections->Collection->Options = new \OCA\EAS\Utile\Eas\EasObject('AirSync');
	$msg_sync_rq_o->Sync->Collections->Collection->Options->FilterType = new \OCA\EAS\Utile\Eas\EasProperty('AirSync', 0);
	//$msg_sync_rq_o->Sync->Collections->Collection->Options->MIMESupport = new \OCA\EAS\Utile\Eas\EasProperty('AirSync', 2);
	//$msg_sync_rq_o->Sync->Collections->Collection->Options->MIMETruncation = new \OCA\EAS\Utile\Eas\EasProperty('AirSync', 8);
	$msg_sync_rq_o->Sync->Collections->Collection->Options->BodyPreference = new \OCA\EAS\Utile\Eas\EasObject('AirSyncBase');
	$msg_sync_rq_o->Sync->Collections->Collection->Options->BodyPreference->Type = new \OCA\EAS\Utile\Eas\EasProperty('AirSyncBase', 1);
	$msg_sync_rq_o->Sync->Collections->Collection->Options->BodyPreference->AllOrNone = new \OCA\EAS\Utile\Eas\EasProperty('AirSyncBase', 1);
	$msg_sync_rq_o->Sync->WindowSize = new \OCA\EAS\Utile\Eas\EasProperty('AirSync', 32);

	$msg_sync_rq_r = $EasXmlEncoder->stringFromObject($msg_sync_rq_o);
	$msg_sync_rq_h = unpack('H*', $msg_sync_rq_r)[1];

	/*
	if ($msg_ref_raw != $msg_sync_raw) {
		throw new Exception("Messages do not match!!", 1);
	}
	*/

	// perform command
	$msg_sync_rp_r = $EasClient->performSync($msg_sync_rq_r);
	$msg_sync_rp_h = $EasXmlDecoder->stringToObject($msg_sync_rp_r);
	
	/*
	// construct folder sync request
	$stream = fopen('php://temp', 'r+');
	$WbxmlEncoder = new \OCA\EAS\Utile\Wbxml\WbxmlEncoder($stream);
	$WbxmlEncoder->StartWBXML();
	// Start Provision
	$WbxmlEncoder->startTag('Provision:Provision');

	// Start Device Information
	$WbxmlEncoder->startTag('Settings:DeviceInformation');
	// Start Settings
	$WbxmlEncoder->startTag('Settings:Set');
	// Model Property
	$WbxmlEncoder->startTag('Settings:Model');
	$WbxmlEncoder->content('NextCloudEAS');
	$WbxmlEncoder->endTag();
	// Description Property
	$WbxmlEncoder->startTag('Settings:FriendlyName');
	$WbxmlEncoder->content('NextCloud EAS Connector');
	$WbxmlEncoder->endTag();
	// User Agent Property
	$WbxmlEncoder->startTag('Settings:UserAgent');
	$WbxmlEncoder->content('NextCloudEAS/1.0 (1.0; x64)');
	$WbxmlEncoder->endTag();
	// End Settings
	$WbxmlEncoder->endTag();
	// End Device Information
	$WbxmlEncoder->endTag();

	// Start Policies
	$WbxmlEncoder->startTag('Provision:Policies');
	// Start Policy
	$WbxmlEncoder->startTag('Provision:Policy');
	// PolicyType Property
	$WbxmlEncoder->startTag('Provision:PolicyType');
	$WbxmlEncoder->content('MS-EAS-Provisioning-WBXML');
	$WbxmlEncoder->endTag();
	// End Policy
	$WbxmlEncoder->endTag();
	// End Policies
	$WbxmlEncoder->endTag();

	// End Provision
	$WbxmlEncoder->endTag();

	// retrieve data from stream
	rewind($stream);
	$message1 = stream_get_contents($stream);
	fclose($stream);
	$message1_hex = unpack('H*', $message2);
	*/

	/*
	// construct folder sync request
	$o = new \stdClass();
	$o->Message = new \stdClass();
	$o->Message->Provision = new \OCA\EAS\Utile\Eas\EasObject('Provision');
	$o->Message->Provision->DeviceInformation = new \OCA\EAS\Utile\Eas\EasObject('Settings');
	$o->Message->Provision->DeviceInformation->Set = new \OCA\EAS\Utile\Eas\EasObject('Settings');
	$o->Message->Provision->DeviceInformation->Set->Model = new \OCA\EAS\Utile\Eas\EasProperty('Settings', 'NextCloudEAS');
	$o->Message->Provision->DeviceInformation->Set->FriendlyName = new \OCA\EAS\Utile\Eas\EasProperty('Settings', 'NextCloud EAS Connector');
	$o->Message->Provision->DeviceInformation->Set->UserAgent = new \OCA\EAS\Utile\Eas\EasProperty('Settings', 'NextCloudEAS/1.0 (1.0; x64)');
	$o->Message->Provision->Policies = new \OCA\EAS\Utile\Eas\EasObject('Provision');
	$o->Message->Provision->Policies->Policy = new \OCA\EAS\Utile\Eas\EasObject('Provision');
	$o->Message->Provision->Policies->Policy->PolicyType = new \OCA\EAS\Utile\Eas\EasProperty('Provision', 'MS-EAS-Provisioning-WBXML');

	$message2 = $EasXmlEncoder->stringFromObject($o->Message);
	//$message2_decoded = $EasXmlDecoder->stringToObject($message2);
	$message2_hex = unpack('H*', $message2);

	if ($message1 != $message2) {
		throw new Exception("Values do not match", 1);
	}
	*/

/*
	// send folder sync request
	curl_setopt($ch, CURLOPT_URL, $uriBase . $uriQuery . '&Cmd=Provision');
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, null);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array_values($http));
	$response = curl_exec($ch);

	// Then, after your curl_exec call:
	$size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$__last_headers = substr($response, 0, $size);
	$__last_response = substr($response, $size);

	$message = $EasXmlDecoder->stringToObject($__last_response);

	// retrieve policy key
	if (isset($message->Message->Provision->Policies->Policy->PolicyKey)) {
		$key = $message->Message->Provision->Policies->Policy->PolicyKey->getValue();
	}

	// construct folder sync request
	$stream = fopen('php://temp', 'r+');
	$WbxmlEncoder = new \OCA\EAS\Utile\Wbxml\WbxmlEncoder($stream);
	$WbxmlEncoder->StartWBXML();
	// Start Provision
	$WbxmlEncoder->startTag('Provision:Provision');

	// Start Policies
	$WbxmlEncoder->startTag('Provision:Policies');
	// Start Policy
	$WbxmlEncoder->startTag('Provision:Policy');
	// PolicyType Property
	$WbxmlEncoder->startTag('Provision:PolicyType');
	$WbxmlEncoder->content('MS-EAS-Provisioning-WBXML');
	$WbxmlEncoder->endTag();
	// PolicyKey Property
	$WbxmlEncoder->startTag('Provision:PolicyKey');
	$WbxmlEncoder->content($key);
	$WbxmlEncoder->endTag();
	// Status Property
	$WbxmlEncoder->startTag('Provision:Status');
	$WbxmlEncoder->content('1');
	$WbxmlEncoder->endTag();
	// End Policy
	$WbxmlEncoder->endTag();
	// End Policies
	$WbxmlEncoder->endTag();

	// End Provision
	$WbxmlEncoder->endTag();

	// retrieve data from stream
	rewind($stream);
	$message = stream_get_contents($stream);
	fclose($stream);
	// send folder sync request
	$http['X-MS-PolicyKey'] = 'X-MS-PolicyKey: ' . $key;
	curl_setopt($ch, CURLOPT_URL, $uriBase . $uriQuery . '&Cmd=Provision');
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, null);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array_values($http));
	$response = curl_exec($ch);

	// Then, after your curl_exec call:
	$size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$__last_headers = substr($response, 0, $size);
	$__last_response = substr($response, $size);

	$message = $EasXmlDecoder->stringToObject($__last_response);

	// retrieve policy key
	if (isset($message->Message->Provision->Policies->Policy->PolicyKey)) {
		$key = $message->Message->Provision->Policies->Policy->PolicyKey->getValue();
	}

	// construct folder sync request
	$stream = fopen('php://temp', 'r+');
	$WbxmlEncoder = new \OCA\EAS\Utile\Wbxml\WbxmlEncoder($stream);
	$WbxmlEncoder->StartWBXML();
	$WbxmlEncoder->startTag('FolderHierarchy:FolderSync');
	$WbxmlEncoder->startTag('FolderHierarchy:SyncKey');
	$WbxmlEncoder->content(0);
	$WbxmlEncoder->endTag();
	$WbxmlEncoder->endTag();
	// retrieve data from stream
	rewind($stream);
	$message = stream_get_contents($stream);
	fclose($stream);
	// send folder sync request
	$http['X-MS-PolicyKey'] = 'X-MS-PolicyKey: ' . $key;
	curl_setopt($ch, CURLOPT_URL, $uriBase . $uriQuery . '&Cmd=FolderSync');
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, null);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array_values($http));
	$response = curl_exec($ch);

	// Then, after your curl_exec call:
	$size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$__last_headers = substr($response, 0, $size);
	$__last_response = substr($response, $size);

	//$__last_response = pack('H*', '03016A00455C4F5003436F6E746163747300014B0332000152033200014E0331000156474D03323A3100015D00114A46033100014C033000014D033100010100015E0346756E6B2C20446F6E00015F03446F6E0001690346756E6B000100115603310001010101010101');
	// decode Wbxml
	
	$message = $EasXmlDecoder->stringToObject($__last_response);

	*/

	$logger->info('Test ended for ' . $uid, ['app' => 'integration_ews']);
	echo 'Test ended for ' . $uid . PHP_EOL;

	exit();
/*
} catch (Exception $ex) {
	$logger->logException($ex, ['app' => 'integration_ews']);
	$logger->info('Test ended unexpectedly', ['app' => 'integration_ews']);
	echo $ex . PHP_EOL;
	exit(1);
} catch (Error $ex) {
	$logger->logException($ex, ['app' => 'integration_ews']);
	$logger->info('Test ended unexpectedly', ['app' => 'integration_ews']);
	echo $ex . PHP_EOL;
	exit(1);
}
*/
