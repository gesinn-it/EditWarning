<?php
namespace EditWarning;

/**
 * Implementation of EditWarningMsg class.
 *
 * This file is part of the MediaWiki extension EditWarning. It contains
 * the implementation of EditWarningMsg class responsible for creating
 * EditWarningMessage subclasses.
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

/**
 * Singleton factory for EditWarningMessage subclasses.
 */
class EditWarningMsg implements EditWarningMsgFactory {

	/**
	 * Singleton instance of the class.
	 *
	 * @var array The singleton instance stored as an array.
	 */
	private static $instance = [];

	private function __construct() {
	}

	private function __clone() {
	}

	/**
	 * Returns an instance of the class based on the provided type.
	 *
	 * This static function creates and returns an instance of the class. Optionally, it can take a URL
	 * and additional parameters to customize the instance.
	 *
	 * @param string $type The type of instance to create.
	 * @param string|null $url Optional. The URL to be associated with the instance.
	 * @param array|null $params Optional. Additional parameters for the instance.
	 * @return self An instance of the class.
	 */
	public static function getInstance( $type, $url = null, $params = null ) {
		global $IP;

		$path = $IP . "/extensions/EditWarning/templates";

		if ( !isset( self::$instance[$type] ) ) {
			switch ( $type ) {
				case "ArticleNotice":
					$params[] = wfMessage( 'ew-leave' )->text();
					self::$instance[$type] = new EditWarningInfoMsg( $path, $url );
					self::$instance[$type]->setMsg( 'ew-notice-article', $params );
					break;
				case "ArticleWarning":
					$params[] = wfMessage( 'ew-leave' )->text();
					self::$instance[$type] = new EditWarningWarnMsg( $path, $url );
					self::$instance[$type]->setMsg( 'ew-warning-article', $params );
					break;
				case "ArticleSectionWarning":
					$params[] = wfMessage( 'ew-leave' )->text();
					self::$instance[$type] = new EditWarningWarnMsg( $path, $url );
					self::$instance[$type]->setMsg( 'ew-warning-sectionedit', $params );
					break;
				case "SectionNotice":
					$params[] = wfMessage( 'ew-leave' )->text();
					self::$instance[$type] = new EditWarningInfoMsg( $path, $url );
					self::$instance[$type]->setMsg( 'ew-notice-section', $params );
					break;
				case "SectionWarning":
					$params[] = wfMessage( 'ew-leave' )->text();
					self::$instance[$type] = new EditWarningWarnMsg( $path, $url );
					self::$instance[$type]->setMsg( 'ew-warning-section', $params );
					break;
				case "Cancel":
					self::$instance[$type] = new EditWarningCancelMsg( $path );
					break;
				default:
					throw new \InvalidArgumentException( "Unknown message type." );
			}
		}

		return self::$instance[$type];
	}
}
