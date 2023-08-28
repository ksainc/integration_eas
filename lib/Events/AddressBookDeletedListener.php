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
use OCA\DAV\Events\AddressBookDeletedEvent;

use OCA\EAS\Service\CorrelationsService;

class AddressBookDeletedListener implements IEventListener {
    /**
	 * @var LoggerInterface
	 */
	private $logger;
	/**
	 * @var CorrelationsService
	 */
	private $CorrelationsService;

	public function __construct(LoggerInterface $logger, CorrelationsService $CorrelationsService) {
		$this->logger = $logger;
		$this->CorrelationsService = $CorrelationsService;
	}

    public function handle(Event $event): void {

        if ($event instanceof AddressBookDeletedEvent) {
			try {
				// retrieve collection and object attributes
				$ec = $event->getAddressBookData();
				// determine ids and state  
				$uid = str_replace('principals/users/', '', $ec['principaluri']);
				$cid = (string) $ec['id'];
				// retrieve collection correlation
				$cc = $this->CorrelationsService->findByLocalId($uid, 'CC', $cid);
				// evaluate correlation, if correlation exists for the local collection
				if ($cc instanceof \OCA\EAS\Db\Correlation) {
					// delete all affiliated correlation and collection correlation
					$this->CorrelationsService->deleteByAffiliationId($cc->getuid(),(string) $cc->getid());
					$this->CorrelationsService->delete($cc);
				}
			} catch (Exception $e) {
				$this->logger->warning($e->getMessage(), ['uid' => $event->getUser()->getUID()]);
			}
		}
		
    }
}