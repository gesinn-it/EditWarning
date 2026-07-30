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
 * Factory for EditWarningMessage subclasses.
 */
class EditWarningMsg implements EditWarningMsgFactory {

	private function __construct() {
	}

	private function __clone() {
	}

	/**
	 * Returns a new instance of the class based on the provided type.
	 *
	 * This static function creates and returns a new instance of the class on every call. Optionally,
	 * it can take a URL and additional parameters to customize the instance. A new instance is required
	 * per call because the rendered message embeds request-specific data (user name, lock timestamp,
	 * cancel URL); caching a single instance per type would leak one user's data into another user's
	 * warning message for the lifetime of the PHP worker process.
	 *
	 * @param string $type The type of instance to create.
	 * @param string|null $url Optional. The URL to be associated with the instance.
	 * @param array|null $params Optional. Additional parameters for the instance.
	 * @return EditWarningMessage An instance of the class.
	 */
	public static function getInstance( $type, $url = null, $params = null ) {
		global $IP;

		$path = $IP . "/extensions/EditWarning/templates";

		switch ( $type ) {
			case "ArticleNotice":
				$params[] = wfMessage( 'ew-leave' )->text();
				$instance = new EditWarningInfoMsg( $path, $url );
				$instance->setMsg( 'ew-notice-article', $params );
				return $instance;
			case "ArticleWarning":
				$params[] = wfMessage( 'ew-leave' )->text();
				$instance = new EditWarningWarnMsg( $path, $url );
				$instance->setMsg( 'ew-warning-article', $params );
				return $instance;
			case "ArticleSectionWarning":
				$params[] = wfMessage( 'ew-leave' )->text();
				$instance = new EditWarningWarnMsg( $path, $url );
				$instance->setMsg( 'ew-warning-sectionedit', $params );
				return $instance;
			case "SectionNotice":
				$params[] = wfMessage( 'ew-leave' )->text();
				$instance = new EditWarningInfoMsg( $path, $url );
				$instance->setMsg( 'ew-notice-section', $params );
				return $instance;
			case "SectionWarning":
				$params[] = wfMessage( 'ew-leave' )->text();
				$instance = new EditWarningWarnMsg( $path, $url );
				$instance->setMsg( 'ew-warning-section', $params );
				return $instance;
			case "Cancel":
				return new EditWarningCancelMsg( $path );
			default:
				throw new \InvalidArgumentException( "Unknown message type." );
		}
	}
}
