<?php

namespace OCA\EAS\Providers\Calendar;

class TaskEntity implements \Sabre\CalDAV\ICalendarObject, \Sabre\DAVACL\IACL {

	private TaskCollection $_collection;
	private string $_id;
	private string $_uuid;
	private string $_label;
	private array $_data;

	/**
	 * Entity Constructor
	 *
	 * @param Collection $calendar
	 * @param string $name
	 */
	public function __construct(TaskCollection $collection, string $id, string $uuid, string $label, array $data) {
		$this->_collection = $collection;
		$this->_id = $id;
		$this->_uuid = $uuid;
		$this->_label = $label;
		$this->_data = $data;
	}

	/**
	 * @inheritDoc
	 */
	function getOwner() {
		return $this->_collection->getOwner();
	}

	/**
	 * @inheritDoc
	 */
	function getGroup() {
		return $this->_collection->getGroup();
	}

	/**
	 * @inheritDoc
	 */
	function getACL() {
		return [
            [
                'privilege' => '{DAV:}all',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
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
	function put($data) {
		throw new \Sabre\DAV\Exception\Forbidden('This function is not supported yet');
	}

	/**
	 * @inheritDoc
	 */
	function get() {
		return $this->_data['data'];
	}
	
	/**
	 * @inheritDoc
	 */
	function getContentType() {
		return 'text/calendar; charset=utf-8';
	}

	/**
	 * @inheritDoc
	 */
	function getETag() {
		return $this->_data['state'];
	}

	/**
	 * @inheritDoc
	 */
	function getSize() {
		return $this->_data['size'];
	}

	/**
	 * @inheritDoc
	 */
	function delete() {
		throw new \Sabre\DAV\Exception\Forbidden('This function is not supported yet');
	}

	/**
	 * @inheritDoc
	 */
	function getName() {
		return $this->_uuid;
	}

	/**
	 * @inheritDoc
	 */
	function setName($name) {
		throw new \Sabre\DAV\Exception\Forbidden('This function is not supported yet');
	}

	/**
	 * @inheritDoc
	 */
	function getLastModified() {
		return time();
	}

}
