<?php

namespace OCA\EAS\Providers\Events;

use OCA\DAV\CalDAV\Integration\ExternalCalendar;
use OCA\DAV\CalDAV\Integration\ICalendarProvider;

use OCA\EAS\AppInfo\Application;
use OCA\EAS\Db\EventStore;

class Provider implements ICalendarProvider {

	private EventStore $_store;

	public function __construct(EventStore $Store) {
		$this->_store = $Store;
	}

	/**
	 * @inheritDoc
	 */
	public function getAppId(): string {
		return Application::APP_ID;
	}

	/**
	 * @inheritDoc
	 */
	public function fetchAllForCalendarHome(string $principalUri): array {

		// retrieve collection(s)
		$collections = $this->_store->listCollectionsByUser(substr($principalUri, 17), 'EC');
		// construct collection objects list
		$list = [];
		foreach ($collections as $entry) {
			$list[] = new Collection($this->_store, $entry['id'], $entry['uid'], $entry['uri'], $entry['label'], $entry['color']);
		}
		// return collection objects list
		return $list;

	}

	/**
	 * @inheritDoc
	 */
	public function hasCalendarInCalendarHome(string $principalUri, string $calendarUri): bool {

		return $this->_store->confirmCollectionByURI(substr($principalUri, 17), $calendarUri);

	}

	/**
	 * @inheritDoc
	 */
	public function getCalendarInCalendarHome(string $principalUri, string $calendarUri): ?ExternalCalendar {

		$entry = $this->_store->fetchCollectionByURI(substr($principalUri, 17), $calendarUri);

		if (isset($entry)) {
			return new Collection($this->_store, $entry['id'], $entry['uid'], $entry['uri'], $entry['label'], $entry['color']);
		}
		else {
			return null;
		}

	}

}
