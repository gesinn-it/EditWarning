<?php
namespace EditWarning;

/**
 * Implementation of EditWarningLock class.
 *
 * This file is part of the MediaWiki extension EditWarning. It contains
 * the implementation of EditWarningLock class.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Thomas David <nemphis@code-geek.de>
 * @copyright   2007-2011 Thomas David <nemphis@code-geek.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @version     0.4-beta
 * @category    Extensions
 * @package     EditWarning
 */

class EditWarningLock {
	/**
	 *
	 * @var mixed Contains the reference to parent class.
	 */
	private $_parent;

	/**
	 *
	 * @var int Contains the Id of the user of the lock.
	 */
	private $_user_id;

	/**
	 *
	 * @var string Contains the name of the user of the lock.
	 */
	private $_user_name;

	/**
	 *
	 * @var int Contains the Id of the section of the lock.
	 */
	private $_section = 0;

	/**
	 *
	 * @var int Contains the unix timestamp of the lock.
	 */
	private $timestamp;

	/**
	 * Constructor for initializing the object with the provided parent and database row.
	 *
	 * This constructor sets up the parent, user ID, user name, section, and timestamp
	 * based on the values from the provided database row object.
	 *
	 * @param mixed $parent The parent object.
	 * @param object $db_row The database row object containing user data.
	 */
	public function __construct( $parent, $db_row ) {
		$this->setParent( $parent );
		$this->setUserID( $db_row->user_id );
		$this->setUserName( $db_row->user_name );
		$this->setSection( $db_row->section );
		$this->setTimestamp( $db_row->lock_timestamp );
	}

	/**
	 *
	 * @return mixed Reference to parent class.
	 */
	public function getParent() {
		return $this->_parent;
	}

	/**
	 *
	 * @param mixed $parent Reference to parent class.
	 */
	public function setParent( $parent ) {
		$this->_parent = $parent;
	}

	/**
	 *
	 * @return int Id of the user of the lock.
	 */
	public function getUserID() {
		return $this->_user_id;
	}

	/**
	 *
	 * @param int $user_id Id of the user of the lock.
	 */
	public function setUserID( $user_id ) {
		$this->_user_id = $user_id;
	}

	/**
	 *
	 * @return string Name of the user of the lock.
	 */
	public function getUserName() {
		return $this->_user_name;
	}

	/**
	 *
	 * @param string $user_name Name of the user of the lock.
	 */
	public function setUserName( $user_name ) {
		$this->_user_name = $user_name;
	}

	/**
	 *
	 * @return int Id of the section of the lock.
	 */
	public function getSection() {
		return $this->_section;
	}

	/**
	 *
	 * @param int $section Id of the section of the lock.
	 */
	public function setSection( $section ) {
		$this->_section = $section;
	}

	/**
	 *
	 * @return int Unix timestamp of the lock.
	 */
	public function getTimestamp() {
		return $this->_timestamp;
	}

	/**
	 *
	 * @param int $timestamp Unix timestamp of the lock.
	 */
	public function setTimestamp( $timestamp ) {
		$this->_timestamp = $timestamp;
	}
}
