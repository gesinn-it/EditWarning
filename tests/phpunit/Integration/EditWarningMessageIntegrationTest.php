<?php

use EditWarning\EditWarningMessage;

/**
 * @group EditWarning
 * @group Database
 * @group medium
 *
 * @covers \EditWarning\EditWarningMessage
 */
class EditWarningMessageIntegrationTest extends MediaWikiIntegrationTestCase {

	/**
	 * @return EditWarningMessage A concrete instance for testing the abstract base class.
	 */
	private function newMessage() {
		return new class extends EditWarningMessage {
		};
	}

	/**
	 * @covers \EditWarning\EditWarningMessage::addLabelMsg
	 * @covers \EditWarning\EditWarningMessage::getLabels
	 */
	public function testAddLabelMsgResolvesMessageText() {
		$msg = $this->newMessage();

		$msg->addLabelMsg( 'BUTTON_CANCEL', 'ew-button-cancel' );

		$this->assertSame( [ 'BUTTON_CANCEL' => 'Cancel' ], $msg->getLabels() );
	}

	/**
	 * @covers \EditWarning\EditWarningMessage::setMsg
	 * @covers \EditWarning\EditWarningMessage::getLabels
	 */
	public function testSetMsgFormatsMessageWithParams() {
		$msg = $this->newMessage();

		$msg->setMsg( 'ew-notice-article', [ '2024-01-01', '10:00', 'leave text' ] );

		$labels = $msg->getLabels();
		$this->assertStringContainsString( '2024-01-01', $labels['MSG'] );
		$this->assertStringContainsString( '10:00', $labels['MSG'] );
		$this->assertStringContainsString( 'leave text', $labels['MSG'] );
	}

	/**
	 * @covers \EditWarning\EditWarningMessage::setMsg
	 * @covers \EditWarning\EditWarningMessage::getLabels
	 */
	public function testSetMsgEscapesHtmlInParams() {
		$msg = $this->newMessage();

		$msg->setMsg( 'ew-notice-article', [ '2024-01-01', '10:00', '<script>alert(1)</script>' ] );

		$labels = $msg->getLabels();
		$this->assertStringNotContainsString( '<script>', $labels['MSG'] );
		$this->assertStringContainsString( '&lt;script&gt;', $labels['MSG'] );
	}

	/**
	 * @covers \EditWarning\EditWarningMessage::show
	 * @covers \EditWarning\EditWarningMessage::processTemplate
	 */
	public function testShowPrependsProcessedContentToOutput() {
		global $wgOut;
		$previousOut = $wgOut;

		$out = RequestContext::getMain()->getOutput();
		$wgOut = $out;

		$msg = $this->newMessage();
		$msg->setContent( 'Hello {{{NAME}}}.' );
		$msg->addLabel( 'NAME', 'Alice' );

		$msg->show( 'ArticleNotice' );

		$this->assertStringContainsString( 'Hello Alice.', $out->getHTML() );

		$wgOut = $previousOut;
	}

	/**
	 * @covers \EditWarning\EditWarningMessage::show
	 */
	public function testShowAddsOverlayModuleForWarningTypes() {
		global $wgOut;
		$previousOut = $wgOut;

		$out = RequestContext::getMain()->getOutput();
		$wgOut = $out;

		$msg = $this->newMessage();
		$msg->setContent( 'Warning content' );

		$msg->show( 'ArticleWarning' );

		$this->assertContains( 'ext.editwarning.overlay', $out->getModules() );
		$this->assertStringContainsString( 'edit-warning-overlay', $out->getHTML() );

		$wgOut = $previousOut;
	}

	/**
	 * @covers \EditWarning\EditWarningMessage::show
	 */
	public function testShowDoesNotAddOverlayModuleForNoticeTypes() {
		global $wgOut;
		$previousOut = $wgOut;

		$out = RequestContext::getMain()->getOutput();
		$wgOut = $out;

		$msg = $this->newMessage();
		$msg->setContent( 'Notice content' );

		$msg->show( 'ArticleNotice' );

		$this->assertNotContains( 'ext.editwarning.overlay', $out->getModules() );

		$wgOut = $previousOut;
	}
}
