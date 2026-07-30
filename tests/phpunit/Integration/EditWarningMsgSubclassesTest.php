<?php

use EditWarning\EditWarningCancelMsg;
use EditWarning\EditWarningInfoMsg;
use EditWarning\EditWarningWarnMsg;

/**
 * @group EditWarning
 * @group Database
 * @group medium
 */
class EditWarningMsgSubclassesTest extends MediaWikiIntegrationTestCase {

	private function templatesPath() {
		return dirname( __DIR__, 3 ) . '/templates';
	}

	/**
	 * @covers \EditWarning\EditWarningCancelMsg::__construct
	 */
	public function testCancelMsgLoadsTemplateAndLabel() {
		$msg = new EditWarningCancelMsg( $this->templatesPath() );

		$this->assertSame( [ 'CANCELED' => 'Canceled editing.' ], $msg->getLabels() );
		$this->assertStringContainsString( '{{{CANCELED}}}', $msg->getContent() );
	}

	/**
	 * @covers \EditWarning\EditWarningInfoMsg::__construct
	 */
	public function testInfoMsgLoadsTemplateAndLabels() {
		$msg = new EditWarningInfoMsg( $this->templatesPath(), 'http://example.com/cancel' );

		$labels = $msg->getLabels();
		$this->assertSame( 'http://example.com/cancel', $labels['URL'] );
		$this->assertSame( 'Cancel', $labels['BUTTON_CANCEL'] );
		$this->assertStringContainsString( '{{{URL}}}', $msg->getContent() );
	}

	/**
	 * @covers \EditWarning\EditWarningWarnMsg::__construct
	 */
	public function testWarnMsgLoadsTemplateAndLabels() {
		$msg = new EditWarningWarnMsg( $this->templatesPath(), 'http://example.com/cancel' );

		$labels = $msg->getLabels();
		$this->assertSame( 'http://example.com/cancel', $labels['URL'] );
		$this->assertSame( 'Cancel', $labels['BUTTON_CANCEL'] );
		$this->assertStringContainsString( '{{{URL}}}', $msg->getContent() );
	}
}
