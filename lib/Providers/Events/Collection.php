<?php

namespace OCA\EAS\Providers\Events;

use OCA\DAV\CalDAV\Integration\ExternalCalendar;
use OCA\DAV\CalDAV\Plugin;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\PropPatch;

use OCA\EAS\AppInfo\Application;
use OCA\EAS\Db\EventStore;

class Collection extends ExternalCalendar {

	/** @var EventStore */
	private EventStore $_store;
	/** @var string */
	private int $_id;
	/** @var string */
	private string $_uid;
	/** @var string */
	private string $_uri;
	/** @var string */
	private string $_label;
	/** @var string */
	private string $_color;

	/**
	 * Collection constructor.
	 *
	 * @param string $id
	 * @param string $uid
	 * @param string $uri
	 * @param string $label
	 * @param string $color
	 */
	public function __construct(EventStore $store, string $id, string $uid, string $uri, string $label, string $color) {
		parent::__construct(Application::APP_ID, $uri);

		$this->_store = $store;
		$this->_id = $id;
		$this->_uid = $uid;
		$this->_uri = $uri;
		$this->_label = $label;
		$this->_color = $color;

	}

	/**
	 * @inheritDoc
	 */
	function getOwner() {
		return 'principals/users/' . $this->_uid;
	}

	/**
	 * @inheritDoc
	 */
	function getACL() {
		return [
			
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner(),
				'protected' => true,
			],
			/*
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner() . '/calendar-proxy-write',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner() . '/calendar-proxy-read',
				'protected' => true,
			],
			*/
			[
				'privilege' => '{DAV:}write',
				'principal' => $this->getOwner(),
				'protected' => true,
			],
			/*
			[
				'privilege' => '{DAV:}write',
				'principal' => $this->getOwner() . '/calendar-proxy-write',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}write-properties',
				'principal' => $this->getOwner(),
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}write-properties',
				'principal' => $this->getOwner() . '/calendar-proxy-write',
				'protected' => true,
			]
			*/
		];
	}

	/**
	 * @inheritDoc
	 */
	function setACL(array $acl) {
		throw new \Sabre\DAV\Exception\Forbidden('Setting ACL is not supported on this node');
	}

	/**
	 * @inheritDoc
	 */
	function getSupportedPrivilegeSet() {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	function calendarQuery(array $filters) {

		// retrieve entries
		$entries = $this->_store->listEntitiesByCollection($this->_uid, $this->_id);
		// list entries
		$list = [];
		foreach ($entries as $entry) {
			$list[] = $entry['id'];
		}
		// return list
		return $list;

	}

	/**
	 * @inheritDoc
	 */
	function createFile($name, $data = null) {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	function getChild($id) {

		// retrieve entities
		$entry = $this->_store->listByCollection($this->_uid, $this->_id);
		// evaluate if entry was returned
		if (is_array($entry)) {
			// return entry
			return new Entity($this, $entry->getId(), $entry->getUuid(), $entry->getLabel(), $entry->getData());
		}
		else {
			// return nothing
			return null;
		}

	}

	/**
	 * @inheritDoc
	 */
	function getChildren() {
		
		// retrieve entries
		$entries = $this->_store->listEntitiesByCollection($this->_uid, $this->_id);
		// list entries
		$list = [];
		foreach ($entries as $entry) {
			$list[] = $entry['id'];
		}
		// return list
		return $list;

	}

	/**
	 * @inheritDoc
	 */
	function childExists($id) {
		return $this->_store->exists($id);
	}

	/**
	 * @inheritDoc
	 */
	function delete() {
		
		// delete local entities
		$this->_store->deleteEntitiesByCollection($this->_uid, $this->_id);
		// delete local collection
		$this->_store->deleteCollection($this->_id);
		// initilize correlation service
		$CorrelationsService = \OC::$server->get(\OCA\EAS\Service\CorrelationsService::class);
		// retrieve correlation entry
		$cr = $CorrelationsService->findByLocalId($this->_uid, $CorrelationsService::ContactCollection, $this->_id);
		// evaluate if correlation was found
		if (isset($cr)) {
			// delete correlations
			$CorrelationsService->deleteByCollectionId($cr->getuid(), $cr->getloid(), $cr->getroid());
			$CorrelationsService->delete($cr);
		}

		return true;

	}

	/**
	 * @inheritDoc
	 */
	function getLastModified() {
		return time();
	}

	/**
	 * @inheritDoc
	 */
	function getGroup() {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	function getLabel(): string {
		return $this->_Label;
	}

	/**
	 * @inheritDoc
	 */
	function setLabel(string $label): void {
		$this->_label = $label;
	}

	/**
	 * @inheritDoc
	 */
	function getColor(): string {
		return $this->_color;
	}

	/**
	 * @inheritDoc
	 */
	function setColor(string $color): void {
		$this->_color = $color;
	}

	/**
	 * @inheritDoc
	 */
	function propPatch(PropPatch $propPatch) {
		
		// retrieve mutations
		$mutations = $propPatch->getMutations();
		// evaluate if any mutations apply
		if (isset($mutations['{DAV:}displayname']) || isset($mutations['{http://apple.com/ns/ical/}calendar-color'])) {
			// retrieve collection
			if ($this->_store->confirmCollection($this->_id)) {
				// construct place holder
				$entry = [];
				// evaluate if name was changed
				if (isset($mutations['{DAV:}displayname'])) {
					// assign new name
					$entry['label'] = ($mutations['{DAV:}displayname']);
				}
				// evaluate if color was changed
				if (isset($mutations['{http://apple.com/ns/ical/}calendar-color'])) {
					// assign new color
					$entry['color'] = ($mutations['{http://apple.com/ns/ical/}calendar-color']);
				}
				// update collection
				if (count($entry) > 0) {
					$this->_store->modifyCollection($this->_id, $entry);
				}
			}
		}

	}

	/**
	 * @inheritDoc
	 */
	function getProperties($properties) {
		// A backend should provide at least minimum properties
		return [
			'{DAV:}displayname' => $this->_label,
			'{http://apple.com/ns/ical/}calendar-color'  => $this->_color,
			'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VEVENT']),
		];
	}

}
