<?php
namespace EditWarning;

/**
 * Implementation of EditWarning class.
 *
 * This file is part of the MediaWiki extension EditWarning. It contains
 * the implementation of EditWarning class with functions to add, edit,
 * delete and check for article locks.
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
 * @version     0.4-rc
 * @category    Extensions
 * @package     EditWarning
 */

class EditWarning {
	/**
	 * @access private
	 * @var int Contains the ID of the current user.
	 */
	private $_user_id;

	/**
	 * @access private
	 * @var string Contains the name of the current user.
	 */
	private $_user_name;

	/**
	 * @access private
	 * @var int Contains the ID of the current article.
	 */
	private $_article_id;

	/**
	 * @access private
	 * @var int Contains the ID of the current section (optional).
	 */
	private $_section;

	/**
	 * @access private
	 * @var mixed Contains locks and metadata about them.
	 */
	private $_locks = [
		'count' => 0,
		'article' => null,
		'section' => [
			'count' => 0,
			'user' => [
				'count' => 0,
				'obj' => []
			],
			'other' => [
				'count' => 0,
				'obj' => []
			]
		]
	];

	/**
	 *
	 * @param int|null $user_id ID of the current user.
	 * @param int|null $article_id ID of the current article (optional).
	 * @param int|null $section ID of the current section (optional).
	 */
	public function __construct( $user_id = null, $article_id = null, $section = null ) {
		$this->setUserID( $user_id );
		$this->setArticleID( $article_id );
		$this->setSection( $section );
	}

	/**
	 * Recieves data from database sets object values and creates
	 * lock objects.
	 *
	 *
	 * @param object $dbr Database connection object.
	 */
	public function load( $dbr ) {
		global $wgTS_Current;
		// Build conditions for select operation.

		$conditions = [
			'article_id' => $this->getArticleID(),
			'lock_timestamp >= ' . $dbr->addQuotes( $this->getTimestamp( $wgTS_Current ) )
		];

		$result = $dbr->select(
			"editwarning_locks",
			"*",
			$conditions
		);

		foreach ( $result as $row ) {
			$this->addLock( $this, $row );
		}
	}

	/**
	 * Creates timestamp with x minutes (depends on settings) in the future/past.
	 * Future timestamps are used for new and updated article locks, past timestamps
	 * are used to get all locks with valid timestamp.
	 *
	 *
	 * @param int $type Which type of timestamp should be created. Use TIMESTAMP_NEW
	 *                   or TIMESTAMP_EXPIRED constant.
	 * @return int Unix timestamp.
	 */
	public function getTimestamp( $type ) {
		global $wgEditWarning_Timeout, $wgTS_Timeout, $wgTS_Current;

		$timeout = $wgEditWarning_Timeout;

		switch ( $type ) {
			case $wgTS_Timeout:
				return mktime( date( "H" ), date( "i" ) + $timeout,
				date( "s" ), date( "m" ), date( "d" ), date( "Y" ) );
			case $wgTS_Current:
				return mktime( date( "H" ), date( "i" ),
				date( "s" ), date( "m" ), date( "d" ), date( "Y" ) );
			default:
				throw new \InvalidArgumentException(
					"Invalid argument for type. Use TIMESTAMP_NEW or TIMESTAMP_EXPIRED constant."
				);
		}
	}

	/**
	 *
	 * @return int Id of the current user.
	 */
	public function getUserID() {
		return $this->_user_id;
	}

	/**
	 *
	 * @param int $user_id Id of the current user.
	 */
	public function setUserID( $user_id ) {
		$this->_user_id = $user_id;
	}

	/**
	 *
	 * @return string Name of the current user.
	 */
	public function getUserName() {
		return $this->_user_name;
	}

	/**
	 *
	 * @param string $user_name Name of the current user.
	 */
	public function setUserName( $user_name ) {
		$this->_user_name = $user_name;
	}

	/**
	 *
	 * @return int Id of the current article.
	 */
	public function getArticleID() {
		return $this->_article_id;
	}

	/**
	 *
	 * @param int $article_id Id of the current article.
	 */
	public function setArticleID( $article_id ) {
		$this->_article_id = $article_id;
	}

	/**
	 *
	 * @return int Id of the current section.
	 */
	public function getSection() {
		return $this->_section;
	}

	/**
	 *
	 * @param int $section Id of the current section.
	 */
	public function setSection( $section ) {
		$this->_section = $section;
	}

	/**
	 *
	 * @param array $locks
	 */
	public function setLocks( $locks ) {
		$this->_locks = $locks;
	}

	/**
	 * Returns the article lock.
	 *
	 *
	 * @return object Returns the EditWarningLock object for the article.
	 */
	public function getArticleLock() {
		return $this->_locks['article'];
	}

	/**
	 * Returns the EditWarningLock object for a certain section.
	 *
	 *
	 * @return object Returns the EditWarningLock object for the section or false.
	 */
	public function getSectionLock() {
		if ( $this->_locks['section']['count'] == 0 ) {
			return false;
		}

		if ( $this->_locks['section']['user']['count'] == 0 ) {
			$section_locks = $this->_locks['section']['other']['obj'];
		} elseif ( $this->_locks['section']['other']['count'] == 0 ) {
			$section_locks = $this->_locks['section']['user']['obj'];
		} else {
			$section_locks = array_merge( $this->_locks['section']['user']['obj'],
										$this->_locks['section']['other']['obj'] );
		}

		foreach ( $section_locks as $lock ) {
			if ( $this->_section == $lock->getSection() ) {
				return $lock;
			}
		}
	}

	/**
	 * Returns all section locks of other users.
	 *
	 *
	 * @return mixed Returns all section locks of other users.
	 */
	public function getSectionLocksByOther() {
		return $this->_locks['section']['other']['obj'];
	}

	/**
	 * Returns the count of all section locks by other users.
	 *
	 *
	 * @return int Count of all section locks by other users.
	 */
	public function getSectionLocksByOtherCount() {
		return $this->_locks['section']['other']['count'];
	}

	/**
	 * Checks if there is any valid article lock.
	 *
	 *
	 * @return bool Returns true if there is at least one lock, else false.
	 */
	public function anyLock() {
		if ( $this->_locks['count'] == 0 ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Checks if there is a lock for the whole article.
	 *
	 *
	 * @return bool Returns true if there is an article lock.
	 */
	public function isArticleLocked() {
		if ( $this->_locks['article'] != null ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks if the article lock is by the current user.
	 *
	 *
	 * @return bool Returns true if the article lock is by the user.
	 */
	public function isArticleLockedByUser() {
		$lock = $this->_locks['article'];

		if ( $lock == false || $lock->getUserID() != $this->getUserID() ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Checks if there is a section lock for any section of the article.
	 *
	 *
	 * @return bool Returns true if there is a section lock.
	 */
	public function anySectionLocks() {
		if ( $this->_locks['section']['count'] > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks if there is a section lock for a specific section of the article.
	 *
	 *
	 * @return bool Returns true if the section is locked.
	 */
	public function isSectionLocked() {
		if ( $this->_locks['section']['count'] == 0 ) {
			return false;
		}

		if ( $this->getSectionLock( $this->_section ) == null ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Checks if there is a section lock for the article by the
	 * current user.
	 *
	 *
	 * @return bool Returns true if there is at least one section lock.
	 */
	public function anySectionLocksByUser() {
		if ( $this->_locks['section']['user']['count'] > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks if there is a section lock for the article by other users.
	 *
	 *
	 * @return bool Returns true if there is at least one section lock.
	 */
	public function anySectionLocksByOthers() {
		if ( $this->_locks['section']['other']['count'] > 0 ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks if the given section lock is by the current user.
	 *
	 * @param SectionLock $sectionLock The section lock object to check against the current user.
	 * @return bool Returns true if the section lock is owned by the current user, false otherwise.
	 */
	public function isSectionLockedByUser( $sectionLock ) {
		if ( $sectionLock->getUserID() == $this->getUserID() ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Create EditWarningLock object and add it to _locks array.
	 *
	 *
	 * @param mixed $parent Reference to EditWarning class.
	 * @param object $db_row Values of one database result row.
	 */
	private function addLock( $parent, $db_row ) {
		$lock = new EditWarningLock( $parent, $db_row );
		$this->_locks['count']++;

		if ( $lock->getSection() != 0 ) {
			$this->_locks['article'] = $lock;
		} else {
			$this->_locks['section']['count']++;

			if ( $lock->getUserID() == $this->_user_id ) {
				$this->_locks['section']['user']['count']++;
				$this->_locks['section']['user']['obj'][] = $lock;
			} else {
				$this->_locks['section']['other']['count']++;
				$this->_locks['section']['other']['obj'][] = $lock;
			}
		}
	}

	/**
	 * Saves a new lock into the database.
	 * !!! "page_props" is not an alternative as it requires the Parser that is not available in hook BeforePageDisplay
	 *
	 *
	 * @param object $dbw MediaWiki write connection object.
	 * @param int $section Id of the current section (0 for no section).
	 */
	public function saveLock( $dbw, $section = 0 ) {
		global $wgTS_Timeout;
		$values = [
			'user_id'    => $this->_user_id,
			'user_name'  => $this->_user_name,
			'article_id' => $this->_article_id,
			'lock_timestamp'  => $this->getTimestamp( $wgTS_Timeout ),
			'section'    => $section
		];
		$dbw->insert( "editwarning_locks", $values );
	}

	/**
	 * Update the timestamp of a lock.
	 *
	 *
	 * @see getTimestamp()
	 * @param object $dbw MediaWiki write connection object.
	 * @param int $section Id of the current section (0 for no section).
	 */
	public function updateLock( $dbw, $section = 0 ) {
		global $wgTS_Timeout;
		$value      = [ "lock_timestamp" => $this->getTimestamp( $wgTS_Timeout ) ];
		$conditions = [
			'user_id'    => $this->_user_id,
			'article_id' => $this->_article_id,
			'section'    => $section
		];
		$dbw->update( "editwarning_locks", $value, $conditions );
	}

	/**
	 * Removes a lock from the databse.
	 *
	 *
	 * @param object $dbw MediaWiki write connection object.
	 * @param int $section Id of the current section (0 for no section).
	 */
	public function removeLock( $dbw, $section = 0 ) {
		$conditions = [
			'user_id'    => $this->_user_id,
			'article_id' => $this->_article_id
		];
		$dbw->delete( "editwarning_locks", $conditions );
	}

	/**
	 * Remove all locks of an user from the database.
	 *
	 * @param DatabaseWrapper $dbw The database writer object used for database operations.
	 */
	public function removeUserLocks( $dbw ) {
		$condition = [ 'user_id' => $this->_user_id ];
		$dbw->delete( "editwarning_locks", $condition );
	}
}
