<?php

namespace OCA\EAS\Providers\Tasks;

class Entity implements \Sabre\CalDAV\ICalendarObject, \Sabre\DAVACL\IACL {

	private Collection $_collection;
	private string $_id;
	private string $_uuid;
	private string $_label;
	private string $_data;

	/**
	 * Entity Constructor
	 *
	 * @param Collection $calendar
	 * @param string $name
	 */
	public function __construct(Collection $collection, string $id, string $uuid, string $label, string $data) {
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
		return $this->_collection->getACL();
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
	function put($data) {
		$this->_data = $data;
	}

	/**
	 * @inheritDoc
	 */
	function get() {
		return $this->_data;
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
		return '"' . md5($this->get()) . '"';
	}

	/**
	 * @inheritDoc
	 */
	function getSize() {
		return strlen($this->get());
	}

	/**
	 * @inheritDoc
	 */
	function delete() {
		throw new \Sabre\DAV\Exception\Forbidden('This calendar-object is read-only');
	}

	/**
	 * @inheritDoc
	 */
	function getName() {
		return $this->_name;
	}

	/**
	 * @inheritDoc
	 */
	function setName($name) {
		$this->_name = $name;
	}

	/**
	 * @inheritDoc
	 */
	function getLastModified() {
		return time();
	}
}
