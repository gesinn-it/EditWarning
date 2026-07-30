<?php

use EditWarning\EditWarningMessage;
use PHPUnit\Framework\TestCase;

class EditWarningMessageTest extends TestCase {

	/**
	 * @return EditWarningMessage A concrete instance for testing the abstract base class.
	 */
	private function newMessage() {
		return new class extends EditWarningMessage {
		};
	}

	/**
	 * @covers EditWarning\EditWarningMessage::setContent
	 * @covers EditWarning\EditWarningMessage::getContent
	 */
	public function testSetAndGetContent() {
		$msg = $this->newMessage();

		$msg->setContent( 'hello world' );

		$this->assertSame( 'hello world', $msg->getContent() );
	}

	/**
	 * @covers EditWarning\EditWarningMessage::addLabel
	 * @covers EditWarning\EditWarningMessage::getLabels
	 */
	public function testAddLabelStoresValueUnderKey() {
		$msg = $this->newMessage();

		$msg->addLabel( 'URL', 'http://example.com' );

		$this->assertSame( [ 'URL' => 'http://example.com' ], $msg->getLabels() );
	}

	/**
	 * @covers EditWarning\EditWarningMessage::addLabel
	 * @covers EditWarning\EditWarningMessage::getLabels
	 */
	public function testAddLabelOverwritesExistingKey() {
		$msg = $this->newMessage();

		$msg->addLabel( 'URL', 'first' );
		$msg->addLabel( 'URL', 'second' );

		$this->assertSame( [ 'URL' => 'second' ], $msg->getLabels() );
	}

	/**
	 * @covers EditWarning\EditWarningMessage::loadTemplate
	 * @covers EditWarning\EditWarningMessage::getContent
	 */
	public function testLoadTemplateReadsFileContentIntoContent() {
		$msg = $this->newMessage();
		$path = dirname( __DIR__, 3 ) . '/templates/canceled.html';

		$msg->loadTemplate( $path );

		$this->assertSame( file_get_contents( $path ), $msg->getContent() );
	}

	/**
	 * @covers EditWarning\EditWarningMessage::processTemplate
	 */
	public function testProcessTemplateThrowsWithoutContent() {
		$msg = $this->newMessage();

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'No template content found. You should load a template first.' );

		$msg->processTemplate();
	}

	/**
	 * @covers EditWarning\EditWarningMessage::processTemplate
	 * @covers EditWarning\EditWarningMessage::addLabel
	 */
	public function testProcessTemplateReplacesLabelPlaceholders() {
		$msg = $this->newMessage();
		$msg->setContent( 'Hello {{{NAME}}}, visit {{{URL}}}.' );
		$msg->addLabel( 'NAME', 'Alice' );
		$msg->addLabel( 'URL', 'http://example.com' );

		$result = $msg->processTemplate();

		$this->assertSame( 'Hello Alice, visit http://example.com.', $result );
	}

	/**
	 * @covers EditWarning\EditWarningMessage::processTemplate
	 */
	public function testProcessTemplateLeavesUnmatchedPlaceholdersUntouched() {
		$msg = $this->newMessage();
		$msg->setContent( 'Hello {{{NAME}}}.' );

		$result = $msg->processTemplate();

		$this->assertSame( 'Hello {{{NAME}}}.', $result );
	}
}
