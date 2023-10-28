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

namespace OCA\EAS\Service;

use stdClass;
use DateTime;
use Exception;
use Throwable;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use OCP\Notification\IManager as INotificationManager;
use OCP\BackgroundJob\IJobList;

use OCA\EAS\AppInfo\Application;
use OCA\EAS\Utile\Eas\EasClient;
use OCA\EAS\Utile\Eas\EasTypes;
use OCA\EAS\Service\ConfigurationService;
use OCA\EAS\Service\CorrelationsService;
/*
use OCA\EAS\Service\ContactsService;
use OCA\EAS\Service\EventsService;
use OCA\EAS\Service\TasksService;
*/
use OCA\EAS\Service\HarmonizationThreadService;
use OCA\EAS\Service\Local\LocalContactsService;
use OCA\EAS\Service\Local\LocalEventsService;
use OCA\EAS\Service\Local\LocalTasksService;
/*
use OCA\EAS\Service\Remote\RemoteContactsService;
use OCA\EAS\Service\Remote\RemoteEventsService;
use OCA\EAS\Service\Remote\RemoteTasksService;
*/
use OCA\EAS\Service\Remote\RemoteCommonService;
/*
use OCA\EAS\Tasks\HarmonizationLauncher;
*/


class CoreService {

	/**
	 * @var LoggerInterface
	 */
	private $logger;
	/**
	 * @var IJobList
	 */
	private IJobList $TaskService;
	/**
	 * @var INotificationManager
	 */
	private $notificationManager;
	/**
	 * @var string|null
	 */
	private $userId;
	/**
	 * @var ConfigurationService
	 */
	private $ConfigurationService;
	/**
	 * @var CorrelationsService
	 */
	private $CorrelationsService;
	/**
	 * @var LocalContactsService
	 */
	private $LocalContactsService;
	/**
	 * @var LocalEventsService
	 */
	private $LocalEventsService;
	/**
	 * @var LocalTasksService
	 */
	private $LocalTasksService;
	/**
	 * @var RemoteContactsService
	 */
	private $RemoteContactsService;
	/**
	 * @var RemoteEventsService
	 */
	private $RemoteEventsService;
	/**
	 * @var RemoteTasksService
	 */
	private $RemoteTasksService;
	/**
	 * @var CardDavBackend
	 */
	private $LocalContactsStore;
	/**
	 * @var CalDavBackend
	 */
	private $LocalEventsStore;
	/**
	 * @var CalDavBackend
	 */
	private $LocalTasksStore;
	/**
	 * @var EasClient
	 */
	private $RemoteStore;

	public function __construct (LoggerInterface $logger,
								IJobList $TaskService,
								INotificationManager $notificationManager,
								ConfigurationService $ConfigurationService,
								CorrelationsService $CorrelationsService,
								HarmonizationThreadService $HarmonizationThreadService,
								LocalContactsService $LocalContactsService,
								LocalEventsService $LocalEventsService,
								LocalTasksService $LocalTasksService,
								/*
								RemoteContactsService $RemoteContactsService,
								RemoteEventsService $RemoteEventsService,
								RemoteTasksService $RemoteTasksService,
								*/
								RemoteCommonService $RemoteCommonService,
								/*
								ContactsService $ContactsService,
								EventsService $EventsService,
								TasksService $TasksService,
								*/) {
		$this->logger = $logger;
		$this->TaskService = $TaskService;
		$this->notificationManager = $notificationManager;
		$this->ConfigurationService = $ConfigurationService;
		$this->CorrelationsService = $CorrelationsService;
		$this->HarmonizationThreadService = $HarmonizationThreadService;
		$this->LocalContactsService = $LocalContactsService;
		$this->LocalEventsService = $LocalEventsService;
		$this->LocalTasksService = $LocalTasksService;
		/*
		$this->RemoteContactsService = $RemoteContactsService;
		$this->RemoteEventsService = $RemoteEventsService;
		$this->RemoteTasksService = $RemoteTasksService;
		*/
		$this->RemoteCommonService = $RemoteCommonService;
		/*
		$this->ContactsService = $ContactsService;
		$this->EventsService = $EventsService;
		$this->TasksService = $TasksService;
		*/

	}

	/**
	 * Connects to account to verify details, on success saves details to user settings
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid					nextcloud user id
	 * @param string $account_bauth_id		account username
	 * @param string $account_bauth_secret	account secret
	 * 
	 * @return object
	 */
	public function locateAccount(string $account_bauth_id, string $account_bauth_secret): ?object {

		// construct locator
		$locator = new \OCA\EAS\Utile\Eas\EasLocator($account_bauth_id, $account_bauth_secret);
		// find configuration
		$result = $locator->locate();

		if ($result > 0) {
			$data = $locator->discovered;

			$o = new \stdClass();
			$o->UserDisplayName = $data['User']['DisplayName'];
			$o->UserEMailAddress = $data['User']['EMailAddress'];
			$o->UserSMTPAddress = $data['User']['AutoDiscoverSMTPAddress'];
			$o->UserSecret = $account_bauth_secret;

			foreach ($data['Account']['Protocol'] as $entry) {
				// evaluate if type is EXCH
				if ($entry['Type'] == 'EXCH') {
					$o->EXCH = new \stdClass();
					$o->EXCH->Server = $entry['Server'];
					$o->EXCH->AD = $entry['AD'];
					$o->EXCH->ASUrl = $entry['ASUrl'];
					$o->EXCH->EwsUrl = $entry['EwsUrl'];
					$o->EXCH->OOFUrl = $entry['OOFUrl'];
				}
				// evaluate if type is IMAP
				elseif ($entry['Type'] == 'IMAP') {
					if ($entry['SSL'] == 'on') {
						$o->IMAPS = new \stdClass();
						$o->IMAPS->Server = $entry['Server'];
						$o->IMAPS->Port = (int) $entry['Port'];
						$o->IMAPS->AuthMode = 'ssl';
						$o->IMAPS->AuthId = $entry['LoginName'];
					} else {
						$o->IMAP = new \stdClass();
						$o->IMAP->Server = $entry['Server'];
						$o->IMAP->Port = (int) $entry['Port'];
						$o->IMAP->AuthMode = 'tls';
						$o->IMAP->AuthId = $entry['LoginName'];
					}
				}
				// evaluate if type is SMTP
				elseif ($entry['Type'] == 'SMTP') {
					if ($entry['SSL'] == 'on') {
						$o->SMTPS = new \stdClass();
						$o->SMTPS->Server = $entry['Server'];
						$o->SMTPS->Port = (int) $entry['Port'];
						$o->SMTPS->AuthMode = 'ssl';
						$o->SMTPS->AuthId = $entry['LoginName'];
					}
					else {
						$o->SMTP = new \stdClass();
						$o->SMTP->Server = $entry['Server'];
						$o->SMTP->Port = (int) $entry['Port'];
						$o->SMTP->AuthMode = 'tls';
						$o->SMTP->AuthId = $entry['LoginName'];
					}
				}
			}

			return $o;

		} else {
			return null;
		}

	}

	/**
	 * Connects to account, verifies details, on success saves details to user settings
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid					nextcloud user id
	 * @param string $account_bauth_id		account username
	 * @param string $account_bauth_secret	account secret
	 * @param string $account_server		FQDN or IP
	 * @param array $flags
	 * 
	 * @return bool
	 */
	public function connectAccountAlternate(string $uid, string $account_bauth_id, string $account_bauth_secret, string $account_server = '', array $flags = []): bool {

		// define place holders
		$configuration = null;
		$account_id = '';
		$account_name = '';
		$account_device_id = '';
		$account_device_key = '';
		$account_device_version = '';

		// evaluate if provider is empty
		if (empty($account_server) || in_array('CONNECT_MAIL', $flags)) {
			// locate provider
			$configuration = $this->locateAccount($account_bauth_id, $account_bauth_secret);
			//
			if (isset($configuration->EXCH->Server)) {
				$account_server = $configuration->EXCH->Server;
			}
		}

		// validate server
		if (!\OCA\EAS\Utile\Validator::host($account_server)) {
			return false;
		}

		// validate id
		if (!\OCA\EAS\Utile\Validator::username($account_bauth_id)) {
			return false;
		}

		// validate secret
		if (empty($account_bauth_secret)) {
			return false;
		}

		// Generate Device Information
		$account_device_id = str_replace('-', '', \OCA\EAS\Utile\UUID::v4());
		$account_device_key = '0';
		$account_device_version = EasClient::SERVICE_VERSION_161;

		// construct remote data store client
		$RemoteStore =  new \OCA\EAS\Utile\Eas\EasClient(
			$account_server, 
			new \OCA\EAS\Utile\Eas\EasAuthenticationBasic($account_bauth_id, $account_bauth_secret),
			$account_device_id,
			$account_device_key,
			$account_device_version
		);

		// perform folder fetch
		$rs = $this->RemoteCommonService->syncCollections($RemoteStore, '0');
		// evaluate, response status
		if ($rs->Status->getContents() == '142') {
			// Step 1
			// initilize provisioning
			$rs = $this->RemoteCommonService->provisionInit($RemoteStore, 'NextcloudEAS', 'Nextcloud EAS Connector', $RemoteStore->getTransportAgent());
			// evaluate response status
			if (isset($rs->Status) && $rs->Status->getContents() != '1') {
				throw new Exception("Failed to provision account. Unexpected error occured", $rs->Status);
			}
			// step 2
			// retrieve device policy token
			$account_device_key = $rs->Policies->Policy->PolicyKey->getContents();
			// assign device policy token
			$RemoteStore->setDeviceKey($account_device_key);
			// accept provisioning
			$rs = $this->RemoteCommonService->provisionAccept($RemoteStore, $account_device_key);
			// evaluate response status
			if (isset($rs->Policies->Policy->Status) && $rs->Policies->Policy->Status->getContents() != '1') {
				throw new Exception("Failed to provision account. Unexpected error occured", $rs->Policies->Policy->Status);
			}
			// step 3
			// retrieve device policy token
			$account_device_key = $rs->Policies->Policy->PolicyKey->getContents();
			// assign device policy token
			$RemoteStore->setDeviceKey($account_device_key);
			// perform folder fetch
			$rs = $this->RemoteCommonService->syncCollections($RemoteStore, '0');
			// evaluate response status
			if ($rs->Status->getContents() != '1') {
				throw new Exception("Failed to provision account.");
			}
		}

		// deposit authentication to datastore
		$this->ConfigurationService->depositProvider($uid, ConfigurationService::ProviderAlternate);
		$this->ConfigurationService->depositUserValue($uid, 'account_id', $account_bauth_id);
		$this->ConfigurationService->depositUserValue($uid, 'account_name', $account_name);
		$this->ConfigurationService->depositUserValue($uid, 'account_server', $account_server);
		$this->ConfigurationService->depositUserValue($uid, 'account_bauth_id', $account_bauth_id);
		$this->ConfigurationService->depositUserValue($uid, 'account_bauth_secret', $account_bauth_secret);
		$this->ConfigurationService->depositUserValue($uid, 'account_device_id', $account_device_id);
		$this->ConfigurationService->depositUserValue($uid, 'account_device_key', $account_device_key);
		$this->ConfigurationService->depositUserValue($uid, 'account_device_version', $account_device_version);
		$this->ConfigurationService->depositUserValue($uid, 'account_connected', 1);
		// register harmonization task
		$this->TaskService->add(\OCA\EAS\Tasks\HarmonizationLauncher::class, ['uid' => $uid]);

		// evaluate validate flag
		if (in_array("CONNECT_MAIL", $flags)) {
			$this->connectMail($uid, $configuration);
		}
		
		return true;

	}

	/**
	 * Connects to account, verifies details, on success saves details to user settings
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid				nextcloud user id
	 * @param string $code				authentication code
	 * @param array $flags
	 * 
	 * @return bool
	 */
	public function connectAccountMS365(string $uid, string $code, array $flags): bool {

		$code = rtrim($code,'#');

		try {
			$data = \OCA\EAS\Integration\Microsoft365::createAccess($code);
		} catch (Exception $e) {
			$this->logger->error('Could not link Microsoft account: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return false;
		}

		if (is_array($data)) {

			// Generate Device Information
			$account_id = $data['email'];
			$account_server = $data['service_server'];
			$account_oauth_access = $data['access'];
			$account_oauth_expiry = (int) $data['expiry'];
			$account_oauth_refresh = $data['refresh'];
			$account_device_id = str_replace('-', '', \OCA\EAS\Utile\UUID::v4());
			$account_device_key = '0';
			$account_device_version = EasClient::SERVICE_VERSION_161;

			// construct remote data store client
			$RemoteStore =  new \OCA\EAS\Utile\Eas\EasClient(
				$account_server, 
				new \OCA\EAS\Utile\Eas\EasAuthenticationBearer($account_id, $account_oauth_access, $account_oauth_expiry),
				$account_device_id,
				$account_device_key,
				$account_device_version
			);

			// perform folder fetch
			$rs = $this->RemoteCommonService->syncCollections($RemoteStore, '0');
			// evaluate, response status
			if ($rs->Status->getContents() == '142') {
				// Step 1
				// initilize provisioning
				$rs = $this->RemoteCommonService->provisionInit($RemoteStore, 'NextcloudEAS', 'Nextcloud EAS Connector', $RemoteStore->getTransportAgent());
				// evaluate response status
				if (isset($rs->Status) && $rs->Status->getContents() != '1') {
					throw new Exception("Failed to provision account. Unexpected error occured", $rs->Status);
				}
				// step 2
				// retrieve device policy token
				$account_device_key = $rs->Policies->Policy->PolicyKey->getContents();
				// assign device policy token
				$RemoteStore->setDeviceKey($account_device_key);
				// accept provisioning
				$rs = $this->RemoteCommonService->provisionAccept($RemoteStore, $account_device_key);
				// evaluate response status
				if (isset($rs->Policies->Policy->Status) && $rs->Policies->Policy->Status->getContents() != '1') {
					throw new Exception("Failed to provision account. Unexpected error occured", $rs->Policies->Policy->Status);
				}
				// step 3
				// retrieve device policy token
				$account_device_key = $rs->Policies->Policy->PolicyKey->getContents();
				// assign device policy token
				$RemoteStore->setDeviceKey($account_device_key);
				// perform folder fetch
				$rs = $this->RemoteCommonService->syncCollections($RemoteStore, '0');
				// evaluate response status
				if ($rs->Status->getContents() != '1') {
					throw new Exception("Failed to provision account.");
				}
			}
			
			// deposit authentication to datastore
			$this->ConfigurationService->depositProvider($uid, ConfigurationService::ProviderMS365);
			$this->ConfigurationService->depositUserValue($uid, 'account_id', (string) $account_id);
			$this->ConfigurationService->depositUserValue($uid, 'account_name', (string) $account_name);
			$this->ConfigurationService->depositUserValue($uid, 'account_server', $account_server);
			$this->ConfigurationService->depositUserValue($uid, 'account_oauth_access', $account_oauth_access);
			$this->ConfigurationService->depositUserValue($uid, 'account_oauth_expiry', $account_oauth_expiry);
			$this->ConfigurationService->depositUserValue($uid, 'account_oauth_refresh', $account_oauth_refresh);
			$this->ConfigurationService->depositUserValue($uid, 'account_device_id', $account_device_id);
			$this->ConfigurationService->depositUserValue($uid, 'account_device_key', $account_device_key);
			$this->ConfigurationService->depositUserValue($uid, 'account_device_version', $account_device_version);
			$this->ConfigurationService->depositUserValue($uid, 'account_connected', '1');
			// register harmonization task
			$this->TaskService->add(\OCA\EAS\Tasks\HarmonizationLauncher::class, ['uid' => $uid]);

			return true;
		} else {
			return false;
		}

	}

	/**
	 * Reauthorize to account, verifies details, on success saves details to user settings
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid				nextcloud user id
	 * @param string $code				authentication refresh code
	 * 
	 * @return bool
	 */
	public function refreshAccountMS365(string $uid, string $code): bool {

		try {
			$data = \OCA\EAS\Integration\Microsoft365::refreshAccess($code);
		} catch (Exception $e) {
			$this->logger->error('Could not refresh Microsoft account access token: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return false;
		}

		if (is_array($data)) {
			// deposit authentication to datastore
			$this->ConfigurationService->depositProvider($uid, ConfigurationService::ProviderMS365);
			$this->ConfigurationService->depositUserValue($uid, 'account_id', (string) $data['email']);
			$this->ConfigurationService->depositUserValue($uid, 'account_name', (string) $data['name']);
			$this->ConfigurationService->depositUserValue($uid, 'account_server', (string) $data['service_server']);
			$this->ConfigurationService->depositUserValue($uid, 'account_oauth_access', (string) $data['access']);
			$this->ConfigurationService->depositUserValue($uid, 'account_oauth_expiry', (string) $data['expiry']);
			$this->ConfigurationService->depositUserValue($uid, 'account_oauth_refresh', (string) $data['refresh']);
			$this->ConfigurationService->depositUserValue($uid, 'account_connected', '1');

			return true;
		} else {
			return false;
		}

	}

	/**
	 * Removes all users settings, correlations, etc for specific user
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid	nextcloud user id
	 * 
	 * @return void
	 */
	public function disconnectAccount(string $uid): void {
		
		// deregister task
		$this->TaskService->remove(\OCA\EAS\Tasks\HarmonizationLauncher::class, ['uid' => $uid]);
		// terminate harmonization thread
		$this->HarmonizationThreadService->terminate($uid);
		// delete correlations
		$this->CorrelationsService->deleteByUserId($uid);
		// delete configuration
		$this->ConfigurationService->destroyUser($uid);

	}

	/**
	 * Connects Mail App
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid	nextcloud user id
	 * 
	 * @return void
	 */
	public function connectMail(string $uid, object $configuration): void {

		// evaluate if mail app exists
		if (!$this->ConfigurationService->isMailAppAvailable()) {
			return;
		}
		// evaluate if configuration contains the accounts email address
		if (empty($configuration->UserEMailAddress) && empty($configuration->UserSMTPAddress)) {
			return;
		}
		// evaluate if configuration contains IMAP parameters
		if (!isset($configuration->IMAP) && !isset($configuration->IMAPS)) {
			return;
		}
		// evaluate if configuration contains SMTP parameters
		if (!isset($configuration->SMTP) && !isset($configuration->SMTPS)) {
			return;
		}
		//construct mail account manager 
		$mam = \OC::$server->get(\OCA\Mail\Service\AccountService::class);
		// retrieve configured mail account
		$accounts = $mam->findByUserId($uid);
		// search for existing account that matches
		foreach ($accounts as $entry) {
			if ($configuration->UserEMailAddress == $entry->getEmail() || 
			    $configuration->UserSMTPAddress == $entry->getEmail()) {
				return;
			}
		}

		$account = \OC::$server->get(\OCA\Mail\Db\MailAccount::class);
		$account->setUserId($uid);
		$account->setName($configuration->UserDisplayName);
		$account->setEmail($configuration->UserEMailAddress);
		$account->setAuthMethod('password');

		// evaluate if type is IMAPS is present
		if (isset($configuration->IMAPS)) {
			$imap = $configuration->IMAPS;
		} else{
			$imap = $configuration->IMAP;
		}

		$account->setInboundHost($imap->Server);
		$account->setInboundPort($imap->Port);
		$account->setInboundSslMode($imap->AuthMode);
		$account->setInboundUser($imap->AuthId);
		$account->setInboundPassword($this->ConfigurationService->encrypt($configuration->UserSecret));
		
		// evaluate if type is SMTPS is present
		if (isset($configuration->SMTPS)) {
			$smtp = $configuration->SMTPS;
		} else{
			$smtp = $configuration->SMTP;
		}

		$account->setOutboundHost($smtp->Server);
		$account->setOutboundPort($smtp->Port);
		$account->setOutboundSslMode($smtp->AuthMode);
		$account->setOutboundUser($smtp->AuthId);
		$account->setOutboundPassword($this->ConfigurationService->encrypt($configuration->UserSecret));

		$account = $mam->save($account);

	}
	
	/**
	 * Retrieves local collections for all modules
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid	nextcloud user id
	 * 
	 * @return array of local collection(s) and attributes
	 */
	public function fetchLocalCollections(string $uid): array {

		// assign local data store
		$this->LocalContactsService->DataStore = $this->LocalContactsStore;
		$this->LocalEventsService->DataStore = $this->LocalEventsStore;
		$this->LocalTasksService->DataStore = $this->LocalTasksStore;

		// construct response object
		$response = ['ContactCollections' => [], 'EventCollections' => [], 'TaskCollections' => []];
		// retrieve local collections
		if ($this->ConfigurationService->isContactsAppAvailable()) {
			$response['ContactCollections'] = $this->LocalContactsService->listCollections($uid);;
		}
		if ($this->ConfigurationService->isCalendarAppAvailable()) {
			$response['EventCollections'] = $this->LocalEventsService->listCollections($uid);
		}
		if ($this->ConfigurationService->isTasksAppAvailable()) {
			$response['TaskCollections'] = $this->LocalTasksService->listCollections($uid);
		}
		// return response
		return $response;

	}

	/**
	 * Retrieves remote collections for all modules
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid	nextcloud user id
	 * 
	 * @return array of remote collection(s) and attributes
	 */
	public function fetchRemoteCollections(string $uid): ?array {
		
		// create remote store client
		$RemoteStore = $this->createClient($uid);
		// retrieve remote collections
		$ro = $this->RemoteCommonService->syncCollections($RemoteStore);
		// construct response object
		$data = ['ContactCollections' => [], 'EventCollections' => [], 'TaskCollections' => []];
		// evaluate response status and structure
		if ($ro->Status->getContents() == '1' && isset($ro->Changes->Add)) {
			// iterate throught collections 
			foreach ($ro->Changes->Add as $Collection) {
				switch ($Collection->Type->getContents()) {
					case EasTypes::COLLECTION_TYPE_SYSTEM_CONTACTS:
					case EasTypes::COLLECTION_TYPE_USER_CONTACTS:
						$data['ContactCollections'][] = ['id'=>$Collection->Id->getContents(), 'name'=>'Personal - '.$Collection->Name->getContents(),'count'=>''];
						break;
					case EasTypes::COLLECTION_TYPE_SYSTEM_CALENDAR:
					case EasTypes::COLLECTION_TYPE_USER_CALENDAR:
						$data['EventCollections'][] = ['id'=>$Collection->Id->getContents(), 'name'=>'Personal - '.$Collection->Name->getContents(),'count'=>''];
						break;
					case EasTypes::COLLECTION_TYPE_SYSTEM_TASKS:
					case EasTypes::COLLECTION_TYPE_USER_TASKS:
						$data['TaskCollections'][] = ['id'=>$Collection->Id->getContents(), 'name'=>'Personal - '.$Collection->Name->getContents(),'count'=>''];
						break;
				}
			}
		}

		// retrieve entiry counts
		foreach ($data as $gid => $group) {
			if (count($group) > 0) {
				// extract id's
				$a = array_map(function($a) {return ['cid' => $a['id'], 'cst' => 0];}, $group);
				// retrieve initial syncronization token(s)
				$ro = $this->RemoteCommonService->syncEntitiesVarious($RemoteStore, $a, []);
				// extract id's and tokens
				$a = array_map(function($a) {
					return ['cid' => $a->CollectionId->getContents(), 'cst' => $a->SyncKey->getContents()];
				}, $ro);
				// retrieve entity counts
				$ro = $this->RemoteCommonService->estimateEntitiesVarious($RemoteStore, $a);
				// extract entity counts
				foreach ($ro as $entry) {
					$k = array_search($entry->Collection->CollectionId->getContents(), array_column($group, 'id'));
					$data[$gid][$k]['count'] = $entry->Collection->Estimate->getContents();
				}
			}
		}
		
		// return response
		return $data;

	}

	/**
	 * Retrieves collection correlations for all modules
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid	nextcloud user id
	 * 
	 * @return array of collection correlation(s) and attributes
	 */
	public function fetchCorrelations(string $uid): array {

		$CoreUtile = \OC::$server->get(\OCA\EAS\Store\CoreUtile::class);

		// construct response object
		$response = ['ContactCorrelations' => [], 'EventCorrelations' => [], 'TaskCorrelations' => []];
		// retrieve local collections
		if ($this->ConfigurationService->isContactsAppAvailable()) {
			$response['ContactCorrelations'] = $CoreUtile->listCorrelationsEstablished($uid, 'CC');
		}
		if ($this->ConfigurationService->isCalendarAppAvailable()) {
			$response['EventCorrelations'] = $CoreUtile->listCorrelationsEstablished($uid, 'EC');
		}
		if ($this->ConfigurationService->isTasksAppAvailable()) {
			$response['TaskCorrelations'] = $CoreUtile->listCorrelationsEstablished($uid, 'TC');
		}
		// return response
		return $response;

	}

	/**
	 * Deposit collection correlations for all modules
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid	nextcloud user id
	 * @param array $cc		contacts collection(s) correlations
	 * @param array $ec		events collection(s) correlations
	 * @param array $tc		tasks collection(s) correlations
	 * 
	 * @return array of collection correlation(s) and attributes
	 */
	public function depositCorrelations(string $uid, array $cc, array $ec, array $tc): void {
		
		// terminate harmonization thread, in case the user changed any correlations
		//$this->HarmonizationThreadService->terminate($uid);
		// deposit contacts correlations
		if ($this->ConfigurationService->isContactsAppAvailable()) {
			// initilize data store
			$DataStore = \OC::$server->get(\OCA\EAS\Store\ContactStore::class);
			// process entries
			foreach ($cc as $entry) {
				if (isset($entry['enabled'])) {
					try {
						switch ((bool) $entry['enabled']) {
							case false:
								if (!empty($entry['id'])) {
									// retrieve correlation entry
									$cr = $this->CorrelationsService->fetch($entry['id']);
									// evaluate if user id matches
									if ($uid == $cr->getuid()) {
										// delete local entities
										$DataStore->deleteEntitiesByCollection($uid, $cr->getloid());
										// delete local collection
										$DataStore->deleteCollection($cr->getloid());
										// delete correlations
										$this->CorrelationsService->deleteByCollectionId($cr->getuid(), $cr->getloid(), $cr->getroid());
										$this->CorrelationsService->delete($cr);
									}
								}
								break;
							case true:
								if (empty($entry['id'])) {
									// create local collection
									$cl = [];
									$cl['uid'] = $uid; // User ID
									$cl['uuid'] = \OCA\EAS\Utile\UUID::v4(); // Universal Resource ID
									$cl['label'] = 'EAS: ' . $entry['label']; // Collection Label
									$cl['color'] = $entry['color']; // Collection Color
									$cl['token'] = 0; // Collection State Token
									$cid = $DataStore->createCollection($cl);
									// create correlation
									$cr = new \OCA\EAS\Store\Correlation();
									$cr->settype('CC'); // Correlation Type
									$cr->setuid($uid); // User ID
									$cr->setloid($cid); // Local Collection ID
									$cr->setroid($entry['roid']); // Remote Collection ID
									$this->CorrelationsService->create($cr);
								}
								break;
						}
					}
					catch (Exception $e) {
						
					}
				}
			}
		}
		// deposit events correlations
		if ($this->ConfigurationService->isCalendarAppAvailable()) {
			// initilize data store
			$DataStore = \OC::$server->get(\OCA\EAS\Store\EventStore::class);
			// process entries
			foreach ($ec as $entry) {
				if (isset($entry['enabled'])) {
					try {
						switch ((bool) $entry['enabled']) {
							case false:
								if (!empty($entry['id'])) {
									// retrieve correlation entry
									$cr = $this->CorrelationsService->fetch($entry['id']);
									// evaluate if user id matches
									if ($uid == $cr->getuid()) {
										// delete local entities
										$DataStore->deleteEntitiesByCollection($uid, $cr->getloid());
										// delete local collection
										$DataStore->deleteCollection($cr->getloid());
										// delete correlations
										$this->CorrelationsService->deleteByCollectionId($cr->getuid(), $cr->getloid(), $cr->getroid());
										$this->CorrelationsService->delete($cr);
									}
								}
								break;
							case true:
								if (empty($entry['id'])) {
									// create local collection
									$cl = [];
									$cl['uid'] = $uid; // User ID
									$cl['uuid'] = \OCA\EAS\Utile\UUID::v4(); // Universal Resource ID
									$cl['label'] = 'EAS: ' . $entry['label']; // Collection Label
									$cl['color'] = $entry['color']; // Collection Color
									$cl['token'] = 0; // Collection State Token
									$cid = $DataStore->createCollection($cl);
									// create correlation
									$cr = new \OCA\EAS\Store\Correlation();
									$cr->settype('EC'); // Correlation Type
									$cr->setuid($uid); // User ID
									$cr->setloid($cid); // Local Collection ID
									$cr->setroid($entry['roid']); // Remote Collection ID
									$this->CorrelationsService->create($cr);
								}
								break;
						}
					}
					catch (Exception $e) {
						
					}
				}
			}
		}
		// deposit tasks correlations
		if ($this->ConfigurationService->isTasksAppAvailable()) {
			// initilize data store
			$DataStore = \OC::$server->get(\OCA\EAS\Store\TaskStore::class);
			// process entries
			foreach ($tc as $entry) {
				if (isset($entry['enabled'])) {
					try {
						switch ((bool) $entry['enabled']) {
							case false:
								if (!empty($entry['id'])) {
									// retrieve correlation entry
									$cr = $this->CorrelationsService->fetch($entry['id']);
									// evaluate if user id matches
									if ($uid == $cr->getuid()) {
										// delete local entities
										$DataStore->deleteEntitiesByCollection($uid, $cr->getloid());
										// delete local collection
										$DataStore->deleteCollection($cr->getloid());
										// delete correlations
										$this->CorrelationsService->deleteByCollectionId($cr->getuid(), $cr->getloid(), $cr->getroid());
										$this->CorrelationsService->delete($cr);
									}
								}
								break;
							case true:
								if (empty($entry['id'])) {
									// create local collection
									$cl = [];
									$cl['uid'] = $uid; // User ID
									$cl['uuid'] = \OCA\EAS\Utile\UUID::v4(); // Universal Resource ID
									$cl['label'] = 'EAS: ' . $entry['label']; // Collection Label
									$cl['color'] = $entry['color']; // Collection Color
									$cl['token'] = 0; // Collection State Token
									$cid = $DataStore->createCollection($cl);
									// create correlation
									$cr = new \OCA\EAS\Store\Correlation();
									$cr->settype('TC'); // Correlation Type
									$cr->setuid($uid); // User ID
									$cr->setloid($cid); // Local Collection ID
									$cr->setroid($entry['roid']); // Remote Collection ID
									$this->CorrelationsService->create($cr);
								}
								break;
						}
					}
					catch (Exception $e) {
						
					}
				}
			}
		}
	}

	/**
	 * Create remote data store client (EWS Client)
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid	nextcloud user id
	 * 
	 * @return EasClient
	 */
	public function createClient(string $uid): EasClient {

		if (!$this->RemoteStore instanceof EasClient) {
			switch ($this->ConfigurationService->retrieveProvider($uid)) {
				case ConfigurationService::ProviderMS365:
					// retrieve oauth expiry information
					$account_oauth_expiry = (int) $this->ConfigurationService->retrieveUserValue($uid, 'account_oauth_expiry');
					//evaluate if token expired
					if ($account_oauth_expiry < time()) {
						// retrieve refresh token information
						$account_oauth_refresh = $this->ConfigurationService->retrieveUserValue($uid, 'account_oauth_refresh');
						// refresh access token
						$this->refreshAccountMS365($uid, $account_oauth_refresh);
					}
					// retrieve connection information
					$account_id = $this->ConfigurationService->retrieveUserValue($uid, 'account_id');
					$account_server = $this->ConfigurationService->retrieveUserValue($uid, 'account_server');
					$account_oauth_access = $this->ConfigurationService->retrieveUserValue($uid, 'account_oauth_access');
					$account_oauth_expiry = $this->ConfigurationService->retrieveUserValue($uid, 'account_oauth_expiry');
					$account_device_id = $this->ConfigurationService->retrieveUserValue($uid, 'account_device_id');
					$account_device_key = $this->ConfigurationService->retrieveUserValue($uid, 'account_device_key');
					$account_device_version = $this->ConfigurationService->retrieveUserValue($uid, 'account_device_version');
					// construct remote data store client
					$this->RemoteStore = new EasClient(
						$account_server, 
						new \OCA\EAS\Utile\Eas\EasAuthenticationBearer($account_id, $account_oauth_access, $account_oauth_expiry), 
						$account_device_id,
						$account_device_key,
						$account_device_version
					);
					break;
				case ConfigurationService::ProviderAlternate:
					// retrieve connection information
					$account_id = $this->ConfigurationService->retrieveUserValue($uid, 'account_id');
					$account_server = $this->ConfigurationService->retrieveUserValue($uid, 'account_server');
					$account_bauth_id = $this->ConfigurationService->retrieveUserValue($uid, 'account_bauth_id');
					$account_bauth_secret = $this->ConfigurationService->retrieveUserValue($uid, 'account_bauth_secret');
					$account_device_id = $this->ConfigurationService->retrieveUserValue($uid, 'account_device_id');
					$account_device_key = $this->ConfigurationService->retrieveUserValue($uid, 'account_device_key');
					$account_device_version = $this->ConfigurationService->retrieveUserValue($uid, 'account_device_version');
					// construct remote data store client
					$this->RemoteStore = new EasClient(
						$account_server, 
						new \OCA\EAS\Utile\Eas\EasAuthenticationBasic($account_bauth_id, $account_bauth_secret),
						$account_device_id,
						$account_device_key,
						$account_device_version
					);
					break;
			}
		}

		return $this->RemoteStore;

	}

	/**
	 * Destroys remote data store client (EWS Client)
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param EasClient $Client	nextcloud user id
	 * 
	 * @return void
	 */
	public function destroyClient(EasClient $Client): void {
		
		// destory remote data store client
		$Client = null;

	}
	
	/**
	 * publish user notification
	 * 
	 * @since Release 1.0.0
	 * 
	 * @param string $uid		nextcloud user id
	 * @param array $subject	notification type
	 * @param array $params		notification paramaters to pass
	 * 
	 * @return array of collection correlation(s) and attributes
	 */
	public function publishNotice(string $uid, string $subject, array $params): void {
		// construct notification object
		$notification = $this->notificationManager->createNotification();
		// assign attributes
		$notification->setApp(Application::APP_ID)
			->setUser($uid)
			->setDateTime(new DateTime())
			->setObject('eas', 'eas')
			->setSubject($subject, $params);
		// submit notification
		$this->notificationManager->notify($notification);
	}

	public function performTest(string $uid, string $action): void {

		try {
			// retrieve Configuration
			$Configuration = $this->ConfigurationService->retrieveUser($uid);
			$Configuration = $this->ConfigurationService->toUserConfigurationObject($Configuration);
			// create remote store client
			$RemoteStore = $this->createClient($uid);
			// Test Contacts
			$this->ContactsService->RemoteStore = $RemoteStore;
			$result = $this->ContactsService->performTest($action, $Configuration);
			// Test Events
			$this->EventsService->RemoteStore = $RemoteStore;
			$result = $this->EventsService->performTest($action, $Configuration);
			// Test Tasks
			//$this->TasksService->RemoteStore = $RemoteStore;
			//$result = $this->TasksService->performTest($action, $Configuration);
			// destroy remote store client
			$this->destroyClient($RemoteStore);
		} catch (Exception $e) {
			$result = [
					'error' => 'Unknown Test failure:' . $e,
			];
		}
	}
	
}
