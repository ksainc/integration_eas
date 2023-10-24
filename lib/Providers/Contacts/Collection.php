<?php

namespace OCA\EAS\Providers\Contacts;

use OCA\DAV\CardDAV\Integration\ExternalAddressBook;
use OCA\DAV\CardDAV\Plugin;
use Sabre\DAV\PropPatch;

use OCA\EAS\AppInfo\Application;
use OCA\EAS\Db\ContactStore;

class Collection extends ExternalAddressBook {

	/** @var ContactStore */
	private ContactStore $_store;
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
	public function __construct(ContactStore $store, string $id, string $uid, string $uri, string $label, string $color) {
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
		throw new \Sabre\DAV\Exception\Forbidden('This function is not supported yet');
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
			$list[] = new Entity($this, $entry->getId(), $entry->getUuid(), $entry->getLabel(), $entry->getData());
		}
		// return list
		return $list;

	}

	/**
	 * @inheritDoc
	 */
	function createFile($name, $data = null) {
		throw new \Sabre\DAV\Exception\Forbidden('This function is not supported yet');
	}

	/**
	 * @inheritDoc
	 */
	function getChild($id) {

		// retrieve object properties
		$entry = $this->_store->fetchEntityByUUID($this->_uid, $id);
		// evaluate if object properties where retrieved 
		if (isset($entry['uuid'])) {
			return new Entity($this, $entry['id'], $entry['uuid'], $entry['label'], $entry);
		}
		else {
			return false;
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
			$list[] = new Entity($this, $entry['id'], $entry['uuid'], $entry['label'], $entry);
		}
		// return list
		return $list;

	}

	/**
	 * @inheritDoc
	 */
	function childExists($id) {

		return $this->_store->confirmEntityByUUID($this->_uid, $id);

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
	function getColor(): string {
		return $this->_color;
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
		
		// return collection properties
		return [
			'{DAV:}displayname' => $this->_label,
		];

	}

}
