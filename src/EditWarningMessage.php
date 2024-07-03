<?php
namespace EditWarning;

use Exception;

/**
 * Implementation of EditWarningMessage class.
 *
 * This file is part of the MediaWiki extension EditWarning. It contains
 * the implementation of EditWarningMessage class responsible for loading
 * the templates, inserting values and outputting the HTML code.
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

abstract class EditWarningMessage {

	/**
	 * Description of the $_content property.
	 *
	 * @var mixed Description of what $_content represents or contains.
	 */
	private $_content;

	/**
	 * Description of the $_labels property.
	 *
	 * @var array Array of labels associated with the object.
	 */
	private $_labels = [];

	/**
	 * Sets the content.
	 *
	 * This function assigns the provided content to the internal _content property.
	 *
	 * @param mixed $content The content to be set.
	 */
	public function setContent( $content ) {
		$this->_content = $content;
	}

	/**
	 * Gets the content.
	 *
	 * This function returns the internal _content property.
	 *
	 * @return mixed The content.
	 */
	public function getContent() {
		return $this->_content;
	}

	/**
	 * Adds a label message.
	 *
	 * This function sets a label with the message text corresponding to the provided message key.
	 *
	 * @param string $label The label to be set.
	 * @param string $msgkey The key for the message text.
	 */
	public function addLabelMsg( $label, $msgkey ) {
		$this->_labels[$label] = wfMessage( $msgkey )->text();
	}

	/**
	 * Adds a label.
	 *
	 * This function sets a label.
	 *
	 * @param string $label The label to be set.
	 * @param string $value The value for label.
	 */
	public function addLabel( $label, $value ) {
		$this->_labels[$label] = $value;
	}

	/**
	 * Sets a message with parameters.
	 *
	 * This function sets the 'MSG' label with the message text corresponding to the provided message key,
	 * formatted with the provided parameters.
	 *
	 * @param string $msg The key for the message text.
	 * @param array $params The parameters to be formatted into the message.
	 */
	public function setMsg( $msg, $params ) {
		$this->_labels['MSG'] = wfMessage( $msg )->rawParams( $params )->plain();
	}

	/**
	 * Gets the label.
	 *
	 * This function returns the internal _labels property.
	 *
	 * @return mixed The label.
	 */
	public function getLabels() {
		return $this->_labels;
	}

	/**
	 * Loads a template file and sets its content.
	 *
	 * This function opens the specified template file, reads its content, and sets it as the
	 * content of the object.
	 *
	 * @param string $file_name The path to the template file to load.
	 * @throws Exception If there is an error while loading the template file.
	 */
	public function loadTemplate( $file_name ) {
		try {
			$file = fopen( $file_name, "r" );
			$this->setContent( fread( $file, filesize( $file_name ) ) );
		} catch ( Exception $e ) {
			throw new Exception( $e );
		}
		fclose( $file );
	}

	/**
	 * Replaces labels in template content with associated values.
	 *
	 * @throws Exception If no template content is found.
	 * @return string The processed template content with labels replaced by values.
	 */
	public function processTemplate() {
		$content = $this->getContent();

		if ( $content == null ) {
			throw new Exception( "No template content found. You should load a template first." );
		}

		foreach ( $this->getLabels() as $label => $value ) {
			$content = preg_replace(
					"/{{{" . $label . "}}}/",
					$value,
					$content
			);
		}

		return $content;
	}

	/**
	 * Output the HTML code.
	 *
	 * @param string $type The type of HTML content to output
	 */
	public function show( $type ) {
		global $wgOut;

		if ( $type === "ArticleWarning" || $type === "ArticleSectionWarning" || $type === "SectionWarning" ) {

			// Add HTML for overlay
			$wgOut->addHTML( '<div id="edit-warning-overlay"></div>' );
			$wgOut->addModules( [ 'ext.editwarning.overlay' ] );
		}

		$content = $this->processTemplate();
		$wgOut->prependHTML( $content );
	}

}
