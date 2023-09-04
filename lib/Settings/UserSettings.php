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

namespace OCA\EAS\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Settings\ISettings;

use OCA\EAS\AppInfo\Application;
use OCA\EAS\Service\ConfigurationService;

class UserSettings implements ISettings {

	/**
	 * @var IInitialState
	 */
	private $initialStateService;
	/**
	 * @var string|null
	 */
	private $userId;

	public function __construct(IInitialState $initialStateService, ConfigurationService $ConfigurationService, string $userId) {
		$this->initialStateService = $initialStateService;
		$this->ConfigurationService = $ConfigurationService;
		$this->userId = $userId;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		
		// retrieve user configuration
		$configuration = $this->ConfigurationService->retrieveUser($this->userId);
		$configuration['system_ms365_authrization_uri'] = \OCA\EAS\Integration\Microsoft365::constructAuthorizationUrl();
		
		$this->initialStateService->provideInitialState('user-configuration', $configuration);

		return new TemplateResponse(Application::APP_ID, 'userSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 20;
	}
}
