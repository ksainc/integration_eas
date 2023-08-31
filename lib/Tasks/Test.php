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
	$EasXmlEncoder = new \OCA\EAS\Utile\EasXml\EasXmlEncoder();
	$EasXmlDecoder = new \OCA\EAS\Utile\EasXml\EasXmlDecoder();

	$account = $ConfigurationService->retrieveAuthenticationBasic($uid);

	$uriBase = 'https://' . $account['account_server'] . '/Microsoft-Server-ActiveSync';
	$uriQuery = '?DeviceType=NextCloudEAS&DeviceId=' . $account['account_deviceid'] . '&User=' . $account['account_id'];

	$http = array(
		'User-Agent' => 'User-Agent: NextCloudEAS/1.0 (1.0; x64)', // 'User-Agent: Outlook/16.0 (16.0.16626.20086; x64)'
		'Connection' => 'Connection: Keep-Alive',
		'Content-Type' => 'Content-Type: application/vnd.ms-sync.wbxml',
		'MS-ASProtocolVersion' => 'MS-ASProtocolVersion: 16.1',
		'X-MS-PolicyKey' => 'X-MS-PolicyKey: ' . $account['account_devicekey'],
		'Authorization' => 'Authorization: Basic ' . base64_encode($account['account_id'] . ':' . $account['account_secret'])
	);
	
	// construct object
	$ch = curl_init();
	// set options
	curl_setopt($ch, CURLOPT_URL, $uriBase);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array_values($http));
	// Send Options Request
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "OPTIONS");
	$response = curl_exec($ch);

	// construct folder sync request
	/*
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
	$message1 = stream_get_contents($stream);
	fclose($stream);
	$message1_hex = unpack('H*', $message1);
	*/

	// construct folder sync request
	$o = new \stdClass();
	$o->Message = new \stdClass();
	$o->Message->FolderSync = new \OCA\EAS\Utile\EasXml\EasXmlObject('FolderHierarchy');
	$o->Message->FolderSync->SyncKey = new \OCA\EAS\Utile\EasXml\EasXmlProperty('FolderHierarchy', 0);

	$message2 = $EasXmlEncoder->stringFromObject($o->Message);
	$message2_decoded = $EasXmlDecoder->stringToObject($message2);
	$message2_hex = unpack('H*', $message2);

	/*
	if ($message1 != $message2) {
		throw new Exception("Values do not match", 1);
	}
	*/

	// send folder sync request
	curl_setopt($ch, CURLOPT_URL, $uriBase . $uriQuery . '&Cmd=FolderSync');
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, null);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $message2);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array_values($http));
	$response = curl_exec($ch);

	// Then, after your curl_exec call:
	$size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$__last_headers = substr($response, 0, $size);
	$__last_response = substr($response, $size);
	
	$message = $EasXmlDecoder->stringToObject($__last_response);

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
	$o->Message->Provision = new \OCA\EAS\Utile\EasXml\EasXmlObject('Provision');
	$o->Message->Provision->DeviceInformation = new \OCA\EAS\Utile\EasXml\EasXmlObject('Settings');
	$o->Message->Provision->DeviceInformation->Set = new \OCA\EAS\Utile\EasXml\EasXmlObject('Settings');
	$o->Message->Provision->DeviceInformation->Set->Model = new \OCA\EAS\Utile\EasXml\EasXmlProperty('Settings', 'NextCloudEAS');
	$o->Message->Provision->DeviceInformation->Set->FriendlyName = new \OCA\EAS\Utile\EasXml\EasXmlProperty('Settings', 'NextCloud EAS Connector');
	$o->Message->Provision->DeviceInformation->Set->UserAgent = new \OCA\EAS\Utile\EasXml\EasXmlProperty('Settings', 'NextCloudEAS/1.0 (1.0; x64)');
	$o->Message->Provision->Policies = new \OCA\EAS\Utile\EasXml\EasXmlObject('Provision');
	$o->Message->Provision->Policies->Policy = new \OCA\EAS\Utile\EasXml\EasXmlObject('Provision');
	$o->Message->Provision->Policies->Policy->PolicyType = new \OCA\EAS\Utile\EasXml\EasXmlProperty('Provision', 'MS-EAS-Provisioning-WBXML');

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
