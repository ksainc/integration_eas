<?php
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

declare(strict_types=1);

namespace OCA\EAS\AppInfo;

use OCP\IConfig;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCA\DAV\Events\AddressBookDeletedEvent;
use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardUpdatedEvent;
use OCA\DAV\Events\CalendarDeletedEvent;
use OCA\DAV\Events\CalendarObjectCreatedEvent;
use OCA\DAV\Events\CalendarObjectDeletedEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCA\DAV\Events\CalendarObjectMovedEvent;
use OCA\DAV\Events\CalendarObjectMovedToTrashEvent;
use OCA\DAV\Events\CalendarObjectRestoredEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Notification\IManager as INotificationManager;
use OCP\User\Events\UserDeletedEvent;

use OCA\EAS\Events\AddressBookDeletedListener;
use OCA\EAS\Events\CardCreatedListener;
use OCA\EAS\Events\CardUpdatedListener;
use OCA\EAS\Events\CardDeletedListener;
use OCA\EAS\Events\CalendarDeletedListener;
use OCA\EAS\Events\CalendarObjectCreatedListener;
use OCA\EAS\Events\CalendarObjectUpdatedListener;
use OCA\EAS\Events\CalendarObjectDeletedListener;
use OCA\EAS\Events\CalendarObjectMovedListener;
use OCA\EAS\Events\CalendarObjectMovedToTrashListener;
use OCA\EAS\Events\CalendarObjectRestoredListener;
use OCA\EAS\Events\UserDeletedListener;
use OCA\EAS\Notification\Notifier;

/**
 * Class Application
 *
 * @package OCA\EAS\AppInfo
 */
class Application extends App implements IBootstrap {
    // assign application identification
    public const APP_ID = 'integration_eas';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);

        // retrieve harmonization mode
        $mode = \OC::$server->getConfig()->getAppValue(Application::APP_ID, 'harmonization_mode');
        $contacts = \OC::$server->getConfig()->getAppValue('contacts', 'enabled');
        $calendar = \OC::$server->getConfig()->getAppValue('calendar', 'enabled');

        // register notifications
        $manager = $this->getContainer()->get(INotificationManager::class);
        $manager->registerNotifierService(Notifier::class);
        // register event handlers
        $dispatcher = $this->getContainer()->get(IEventDispatcher::class);
        $dispatcher->addServiceListener(UserDeletedEvent::class, UserDeletedListener::class);

        if ($contacts == 'yes') {
            $dispatcher->addServiceListener(AddressBookDeletedEvent::class, AddressBookDeletedListener::class);
            // evaluate harmonization mode, and only register if set to active
            if ($mode == 'A') {
                $dispatcher->addServiceListener(CardCreatedEvent::class, CardCreatedListener::class);
                $dispatcher->addServiceListener(CardUpdatedEvent::class, CardUpdatedListener::class);
                $dispatcher->addServiceListener(CardDeletedEvent::class, CardDeletedListener::class);
            }
        }
        
        if ($calendar == 'yes') {
            $dispatcher->addServiceListener(CalendarDeletedEvent::class, CalendarDeletedListener::class);
            // evaluate harmonization mode, and only register if set to active
            if ($mode == 'A') {
                $dispatcher->addServiceListener(CalendarObjectCreatedEvent::class, CalendarObjectCreatedListener::class);
                $dispatcher->addServiceListener(CalendarObjectUpdatedEvent::class, CalendarObjectUpdatedListener::class);
                $dispatcher->addServiceListener(CalendarObjectDeletedEvent::class, CalendarObjectDeletedListener::class);
                $dispatcher->addServiceListener(CalendarObjectMovedEvent::class, CalendarObjectMovedListener::class);
                $dispatcher->addServiceListener(CalendarObjectMovedToTrashEvent::class, CalendarObjectMovedToTrashListener::class);
                $dispatcher->addServiceListener(CalendarObjectRestoredEvent::class, CalendarObjectRestoredListener::class);
            }
        }
    }

    public function register(IRegistrationContext $context): void {
    }

    public function boot(IBootContext $context): void {
    }
}
