<?php

namespace OCA\EAS\Providers\Calendar;

use OCA\DAV\CalDAV\Integration\ExternalCalendar;
use OCA\DAV\CalDAV\Integration\ICalendarProvider;

use OCA\EAS\AppInfo\Application;
use OCA\EAS\Store\EventStore;
use OCA\EAS\Store\TaskStore;

class Provider implements ICalendarProvider {

	private EventStore $_EventStore;
	private TaskStore $_TaskStore;

	public function __construct(EventStore $EventStore, TaskStore $TaskStore) {
		$this->_EventStore = $EventStore;
		$this->_TaskStore = $TaskStore;
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
		
		// construct collection objects list
		$list = [];
		// retrieve collection(s)
		$collections = $this->_EventStore->listCollectionsByUser(substr($principalUri, 17), 'EC');
		// add collections to list
		foreach ($collections as $entry) {
			$list[] = new EventCollection($this->_EventStore, $entry['id'], $entry['uid'], $entry['uuid'], $entry['label'], $entry['color']);
		}
		// retrieve collection(s)
		$collections = $this->_TaskStore->listCollectionsByUser(substr($principalUri, 17), 'TC');
		// add collections to list
		foreach ($collections as $entry) {
			$list[] = new TaskCollection($this->_TaskStore, $entry['id'], $entry['uid'], $entry['uuid'], $entry['label'], $entry['color']);
		}
		// return collection objects list
		return $list;

	}

	/**
	 * @inheritDoc
	 */
	public function hasCalendarInCalendarHome(string $principalUri, string $calendarUri): bool {

		return $this->_EventStore->confirmCollectionByUUID(substr($principalUri, 17), $calendarUri);

	}

	/**
	 * @inheritDoc
	 */
	public function getCalendarInCalendarHome(string $principalUri, string $calendarUri): ?ExternalCalendar {

		$entry = $this->_EventStore->fetchCollectionByUUID(substr($principalUri, 17), $calendarUri);

		if (isset($entry)) {
			if ($entry['type'] == 'EC') {
				return new EventCollection($this->_EventStore, $entry['id'], $entry['uid'], $entry['uuid'], $entry['label'], $entry['color']);
			}
			elseif ($entry['type'] == 'TC') {
				return new TaskCollection($this->_TaskStore, $entry['id'], $entry['uid'], $entry['uuid'], $entry['label'], $entry['color']);
			}
		}
		else {
			return null;
		}

	}

}
