<?php
namespace EditWarning;

/**
 * Implementation of EditWarningCancelMsg class.
 *
 * This file is part of the MediaWiki extension EditWarning. It contains
 * the EditWarningMessage subclass EditWarningCancelMessage representing
 * the cancel message.
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

class EditWarningCancelMsg extends EditWarningMessage {

	/**
	 * Constructor for initializing the object with the provided path and URL.
	 *
	 * This constructor loads a template and adds URL and cancel button labels.
	 *
	 * @param string $path The file path to the template directory.
	 */
	public function __construct( $path ) {
		$this->loadTemplate( $path . "/canceled.html" );
		$this->addLabelMsg( 'CANCELED', 'ew-canceled' );
	}
}
