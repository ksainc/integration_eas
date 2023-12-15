<?php

namespace OCA\EAS\Providers\Calendar;

use OCA\DAV\CalDAV\Integration\ExternalCalendar;
use OCA\DAV\CalDAV\Plugin;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\PropPatch;

use OCA\EAS\AppInfo\Application;
use OCA\EAS\Store\TaskStore;

class TaskCollection extends ExternalCalendar implements \Sabre\DAV\IMultiGet {

	private TaskStore $_store;
	private int $_id;
	private string $_uuid;
	private string $_uid;
	private string $_label;
	private string $_color;

	/**
	 * Collection constructor.
	 *
	 * @param string $id
	 * @param string $uid
	 * @param string $uuid
	 * @param string $label
	 * @param string $color
	 */
	public function __construct(TaskStore &$store, string $id, string $uid, string $uuid, string $label, string $color) {
		
		parent::__construct(Application::APP_ID, $uuid);

		$this->_store = $store;
		$this->_id = $id;
		$this->_uid = $uid;
		$this->_uuid = $uuid;
		$this->_label = $label;
		$this->_color = $color;

	}

	/**
     * retrieves the owner principal.
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
	function getOwner() {

		return 'principals/users/' . $this->_uid;

	}

	/**
     * retrieves a group principal.
     *
     * This must be a url to a principal, or null if there's no group
     *
     * @return string|null
     */
	function getGroup() {

		return null;

	}

	/**
     * retrieves a list of ACE's for this collection.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *
     * @return array
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
     * alters the ACL for this collection
	 * 
	 * @param array $acl		list of ACE's
     */
	function setACL(array $acl) {

		throw new \Sabre\DAV\Exception\Forbidden('This function is not supported yet');

	}

	/**
     * retrieves a list of supported privileges for this node.
     *
     * The returned data structure is a list of nested privileges.
     * See Sabre\DAVACL\Plugin::getDefaultSupportedPrivilegeSet for a simple
     * standard structure.
     *
     * If null is returned from this method, the default privilege set is used,
     * which is fine for most common usecases.
     *
     * @return array|null
     */
	function getSupportedPrivilegeSet() {

		return null;

	}

	/**
	 * @inheritDoc
	 */
	function calendarQuery(array $filters) {

		// construct place holder
		$limit = [];
		//
		if (is_array($filters) && is_array($filters['comp-filters'])) {
			foreach ($filters['comp-filters'] as $filter) {
				if (is_array($filter['time-range'])) {
					if (isset($filter['time-range']['start'])) {
						$limit[] = ['startson', '>=', $filter['time-range']['start']->format('U')];
					}
					if (isset($filter['time-range']['end'])) {
						$limit[] = ['dueon', '<=', $filter['time-range']['end']->format('U')];
					}
				}
			}
		}

		// retrieve entries
		$entries = $this->_store->findEntities($this->_uid, $this->_id, $limit, ['uuid']);
		// list entries
		$list = [];
		foreach ($entries as $entry) {
			$list[] = $entry['uuid'];
		}
		// return list
		return $list;

	}

	/**
     * Create a new entity in this collection
     *
     * @param string          $id		Entity ID
     * @param resource|string $data		Entity Contents
     *
     * @return string|null				state on success / Null on fail
     */
	function createFile($id, $data = null) {

		// remove extension
		$id = str_replace('.ics', '', $id);
		// evaluate if data was sent as a resource
		if (is_resource($data)) {
            $data = stream_get_contents($data);
        }
		// evauate if data is in UTF8 format and convert if needed
		if (!mb_check_encoding($data, 'UTF-8')) {
			$data = iconv(mb_detect_encoding($data), 'UTF-8', $data);
		}
		// evaluate if task is related to another task
		if (strpos($data, 'RELATED-TO:') != false) {
			throw new \Sabre\DAV\Exception\Forbidden('Related tasks are not supported');
		}
		// read the data
		$vo = \Sabre\VObject\Reader::read($data);
		$vo = $vo->VTODO;
		// data store entry
		$lo = [];
        $lo['data'] = $data;
		$lo['uuid'] = $id;
		$lo['uid'] = $this->_uid;
		$lo['cid'] = $this->_id;
		// calcualted properties
        $lo['size'] = strlen($data);
        $lo['signature'] = md5($data);
		// extracted properties from data
		$lo['label'] = (isset($vo->SUMMARY)) ? $this->extractString($vo->SUMMARY) : null;
        $lo['notes'] = (isset($vo->DESCRIPTION)) ? $this->extractString($vo->DESCRIPTION) : null;
		$lo['startson'] = (isset($vo->DTSTART)) ? $this->extractDateTime($vo->DTSTART)->setTimezone(new \DateTimeZone('UTC'))->format('U') : null;
		$lo['dueon'] = (isset($vo->DUE)) ? $this->extractDateTime($vo->DUE)->setTimezone(new \DateTimeZone('UTC'))->format('U') : null;
		// deposit entry to data store
		$this->_store->createEntity($lo);
		// return state
		return $lo['signature'];

	}

	/**
     * modify a entity in this collection
     *
     * @param string          $id		Entity ID
     * @param resource|string $data		Entity Contents
     *
     * @return string|null				state on success / Null on fail
     */
	function modifyFile($id, $uuid, $data) {

		// evaluate if data was sent as a resource
		if (is_resource($data)) {
            $data = stream_get_contents($data);
        }
		// evauate if data is in UTF8 format and convert if needed
		if (!mb_check_encoding($data, 'UTF-8')) {
			$data = iconv(mb_detect_encoding($data), 'UTF-8', $data);
		}
		// evaluate if task is related to another task
		if (strpos($data, 'RELATED-TO:') != false) {
			throw new \Sabre\DAV\Exception\Forbidden('Related tasks not suppoerted');
		}
		// read the data
		$vo = \Sabre\VObject\Reader::read($data);
		$vo = $vo->VTODO;
		// data store entry
		$lo = [];
        $lo['data'] = $data;
		$lo['uuid'] = $uuid;
		$lo['uid'] = $this->_uid;
		$lo['cid'] = $this->_id;
		// calcualted properties
        $lo['size'] = strlen($data);
        $lo['signature'] = md5($data);
		// extracted properties from data
		$lo['label'] = (isset($vo->SUMMARY)) ? $this->extractString($vo->SUMMARY) : null;
        $lo['notes'] = (isset($vo->DESCRIPTION)) ? $this->extractString($vo->DESCRIPTION) : null;
		$lo['startson'] = (isset($vo->DTSTART)) ? $this->extractDateTime($vo->DTSTART)->setTimezone(new \DateTimeZone('UTC'))->format('U') : null;
		$lo['dueon'] = (isset($vo->DUE)) ? $this->extractDateTime($vo->DUE)->setTimezone(new \DateTimeZone('UTC'))->format('U') : null;
		// deposit entry to data store
		$this->_store->modifyEntity($id, $lo);
		// return state
		return $lo['signature'];

	}

	/**
     * delete a entity in this collection
     *
     * @param string			$id		Entity ID
     *
     * @return bool				true on success / false on fail
     */
	function deleteFile($id) {

		// delete entry from data store and return result
		return $this->_store->deleteEntity($id);

	}

	/**
     * retrieves all entities in this collection
     *
     * @return Entity[]
     */
	function getChildren() {
		
		// retrieve entries
		$entries = $this->_store->listEntitiesByCollection($this->_uid, $this->_id);
		// list entries
		$list = [];
		foreach ($entries as $entry) {
			$list[] = new TaskEntity($this, $entry['id'], $entry['uuid'], $entry['label'], $entry);
		}
		// return list
		return $list;

	}

	/**
     * retrieves a specific entity in this collection
     *
     * @param string $id		Entity ID
     *
     * @return Entity
     */
	function getChild($id) {

		// remove extension
		$id = str_replace('.ics', '', $id);
		// retrieve object properties
		$entry = $this->_store->fetchEntityByUUID($this->_uid, $id);
		// evaluate if object properties where retrieved 
		if (isset($entry['uuid'])) {
			return new TaskEntity($this, $entry['id'], $entry['uuid'], $entry['label'], $entry);
		}
		else {
			return false;
		}

	}

	/**
	 * retrieves specific entities in this collection
     *
     * @param string[] $ids
     *
     * @return Entity[]
     */
    public function getMultipleChildren(array $ids) {

		// construct place holder
		$list = [];
		// retrieve entities
		foreach ($ids as $id) {
			// retrieve object properties
			$entry = $this->_store->fetchEntityByUUID($this->_uid, $id);
			// evaluate if object properties where retrieved 
			if (isset($entry['uuid'])) {
				$list[] = new TaskEntity($this, $entry['id'], $entry['uuid'], $entry['label'], $entry);
			}
		}
		
		// return list
		return $list;

	}

	/**
     * Checks if a specific entity exists in this collection
     *
     * @param string $id
     *
     * @return bool
     */
	function childExists($id) {

		// remove extension
		$id = str_replace('.ics', '', $id);
		// confim object exists
		return $this->_store->confirmEntityByUUID($this->_uid, $id);

	}

	/**
     * Deletes this collection
     */
	function delete() {

		// delete local entities
		$this->_store->deleteEntitiesByCollection($this->_uid, $this->_id);
		// delete local collection
		$this->_store->deleteCollection($this->_id);
		// initilize correlation service
		$CorrelationsService = \OC::$server->get(\OCA\EAS\Service\CorrelationsService::class);
		// retrieve correlation entry
		$cr = $CorrelationsService->findByLocalId($this->_uid, $CorrelationsService::TaskCollection, $this->_id);
		// evaluate if correlation was found
		if (isset($cr)) {
			// delete correlations
			$CorrelationsService->deleteByCollectionId($cr->getuid(), $cr->getloid(), $cr->getroid());
			$CorrelationsService->delete($cr);
		}

	}

	/**
     * Returns the last modification time, as a unix timestamp. Return null
     * if the information is not available.
     *
     * @return int|null
     */
	function getLastModified() {

		return time();

	}

	/**
     * alters properties of this collection
	 * 
	 * @param PropPatch $data
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
     * retrieves a list of properties for this collection
     *
     * The properties list is a list of propertynames the client requested,
     * encoded in clark-notation {xmlnamespace}tagname
     *
     * @param array $properties
     *
     * @return array
     */
	function getProperties($properties) {
		
		// return collection properties
		return [
			'{DAV:}displayname' => $this->_label,
			'{http://apple.com/ns/ical/}calendar-color'  => $this->_color,
			'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VTODO']),
		];
		
	}
	
	function extractString($property): string {
		return trim($property->getValue());
	}

	function extractDateTime($property): \DateTime|null {

		if (isset($property)) {
			if (isset($property->parameters['TZID'])) {
				$tz = new \DateTimeZone($property->parameters['TZID']->getValue());
			}
			else {
				$tz = new \DateTimeZone('UTC');
			}
			return new \DateTime($property->getValue(), $tz);
		}
		else {
			return null;
		}

	}

}
