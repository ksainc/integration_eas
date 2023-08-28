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

namespace OCA\EAS\Events;

use Psr\Log\LoggerInterface;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;

use OCA\EAS\Service\CorrelationsService;
use OCA\EAS\Service\HarmonizationThreadService;

class UserDeletedListener implements IEventListener {
    /**
	 * @var LoggerInterface
	 */
	private $logger;
	/**
	 * @var CorrelationsService
	 */
	private $CorrelationsService;
	/**
	 * @var HarmonizationThreadService
	 */
	private $HarmonizationThreadService;

	public function __construct(LoggerInterface $logger, CorrelationsService $CorrelationsService, HarmonizationThreadService $HarmonizationThreadService) {
		$this->logger = $logger;
		$this->CorrelationsService = $CorrelationsService;
		$this->HarmonizationThreadService = $HarmonizationThreadService;
	}

    public function handle(Event $event): void {

        if ($event instanceof UserDeletedEvent) {
			try {
				$this->CorrelationsService->deleteByUserId($event->getUser()->getUID());
				$this->HarmonizationThreadService->terminate($event->getUser()->getUID());
			} catch (Exception $e) {
				$this->logger->warning($e->getMessage(), ['uid' => $event->getUser()->getUID()]);
			}
		}
		
    }
}