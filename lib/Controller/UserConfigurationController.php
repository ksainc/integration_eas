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

namespace OCA\EAS\Controller;

use Exception;
use Throwable;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;

use OCA\EAS\AppInfo\Application;
use OCA\EAS\Service\ConfigurationService;
use OCA\EAS\Service\CoreService;
use OCA\EAS\Service\HarmonizationService;

class UserConfigurationController extends Controller {

	/**
	 * @var string|null
	 */
	private $userId;
	/**
	 * @var ConfigurationService
	 */
	private $ConfigurationService;
	/**
	 * @var CoreService
	 */
	private $CoreService;
	/**
	 * @var HarmonizationService
	 */
	private $HarmonizationService;
	
	public function __construct(string $appName,
								IRequest $request,
								ConfigurationService $ConfigurationService,
								CoreService $CoreService,
								HarmonizationService $HarmonizationService,
								string $userId) {
		parent::__construct($appName, $request);
		$this->ConfigurationService = $ConfigurationService;
		$this->CoreService = $CoreService;
		$this->HarmonizationService = $HarmonizationService;
		$this->userId = $userId;
	}

	/**
	 * handels connect click event
	 * 
	 * @NoAdminRequired
	 *
	 * @param string $server			server domain or ip
	 * @param string $account_bauth_id		users login name
	 * @param string $account_bauth_secret	users login password
	 * 
	 * @return DataResponse
	 */
	public function ConnectAlternate(string $account_bauth_id, string $account_bauth_secret, string $account_server, string $flag): DataResponse {
		
		// evaluate if user id is present
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		// assign flags
		$flags = ['VALIDATE'];
		if (filter_var($flag, FILTER_VALIDATE_BOOLEAN)) {
			$flags[] = 'CONNECT_MAIL';
		}
		// execute command
		$rs = $this->CoreService->connectAccountAlternate($this->userId, $account_bauth_id, $account_bauth_secret, $account_server, $flags);
		// return response
		if (isset($rs)) {
			return new DataResponse('success');
		} else {
			return new DataResponse($rs['error'], 401);
		}

	}

	/**
	 * handels connect click event
	 * 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $server			server domain or ip
	 * @param string $account_bauth_id		users login name
	 * @param string $account_bauth_secret	users login password
	 * 
	 * @return DataResponse
	 */
	public function ConnectMS365(string $code): TemplateResponse {
		
		// evaluate if user id is present
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		// assign flags
		$flags = ['VALIDATE'];
		// execute command
		$rs = $this->CoreService->connectAccountMS365($this->userId, $code, $flags);
		// return response
		if ($rs) {
			return new TemplateResponse(Application::APP_ID, 'popupSuccess', [], TemplateResponse::RENDER_AS_GUEST);
		} else {
			return new DataResponse($rs['error'], 401);
		}

	}

	/**
	 * handels disconnect click event
	 * 
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function Disconnect(): DataResponse {

		// evaluate if user id is present
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		// execute command
		$this->CoreService->disconnectAccount($this->userId);
		// return response
		return new DataResponse('success');

	}

	/**
	 * handels synchronize click event
	 * 
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function Harmonize(): DataResponse {

		// evaluate if user id is present
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		// execute command
		$this->HarmonizationService->performHarmonization($this->userId, 'M');
		// return response
		return new DataResponse('success');

	}

	/**
	 * handels test click event
	 * 
	 * @NoAdminRequired
	 * 
	 * @param string $action	test action to perform
	 *
	 * @return DataResponse
	 */
	public function Test($action): DataResponse {

		// evaluate if user id is present
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		// execute command
		$this->CoreService->performTest($this->userId, $action);
		// return response
		return new DataResponse('success');

	}

	/**
	 * handles remote collections fetch requests
	 * 
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function fetchRemoteCollections(): DataResponse {
		
		// evaluate if user id is present
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		// retrieve collections
		$rs = $this->CoreService->fetchRemoteCollections($this->userId);
		// return response
		if (isset($rs)) {
			return new DataResponse($rs);
		} else {
			
			return new DataResponse($rs['error'], 401);
		}

	}

	/**
	 * handels local collections fetch requests
	 * 
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function fetchLocalCollections(): DataResponse {

		// evaluate if user id is present
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		// retrieve collections
		$rs = $this->CoreService->fetchLocalCollections($this->userId);
		// return response
		if (isset($rs)) {
			return new DataResponse($rs);
		} else {
			return new DataResponse($rs['error'], 401);
		}

	}

	/**
	 * handels correlations fetch requests
	 * 
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function fetchCorrelations(): DataResponse {

		// evaluate if user id is present
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		// retrieve correlations
		$rs = $this->CoreService->fetchCorrelations($this->userId);
		// return response
		if (isset($rs)) {
			return new DataResponse($rs);
		} else {
			return new DataResponse($rs['error'], 401);
		}

	}

	/**
	 * handels save correlations requests
	 * 
	 * @NoAdminRequired
	 *
	 * @param array $values key/value pairs to save
	 * 
	 * @return DataResponse
	 */
	public function depositCorrelations(array $ContactCorrelations, array $EventCorrelations, array $TaskCorrelations): DataResponse {
		
		// evaluate if user id is present
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		// execute command
		$rs = $this->CoreService->depositCorrelations($this->userId, $ContactCorrelations, $EventCorrelations, $TaskCorrelations);
		// return response
		return $this->fetchCorrelations();

	}

	/**
	 * handles save preferences requests
	 * 
	 * @NoAdminRequired
	 *
	 * @param array $values key/value pairs to save
	 * 
	 * @return DataResponse
	 */
	public function fetchPreferences(): DataResponse {
		
		// evaluate if user id is present
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		// retrieve user configuration
		$rs = $this->ConfigurationService->retrieveUser($this->userId);
		// return response
		if (is_array($rs)) {
			return new DataResponse($rs);
		} else {
			return new DataResponse($rs['error'], 401);
		}

	}

	/**
	 * handles save preferences requests
	 * 
	 * @NoAdminRequired
	 *
	 * @param array $values key/value pairs to save
	 * 
	 * @return DataResponse
	 */
	public function depositPreferences(array $values): DataResponse {
		
		// evaluate if user id is present
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}
		// deposit user configuration
		$this->ConfigurationService->depositUser($this->userId, $values);
		// return response
		return new DataResponse(true);

	}

}
