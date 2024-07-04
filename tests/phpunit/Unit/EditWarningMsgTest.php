<?php

use EditWarning\EditWarningMsg;
use PHPUnit\Framework\TestCase;

class EditWarningMsgTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @covers EditWarning\EditWarningMsg::getInstance
	 */
	public function testGetInstanceArticleNotice() {
		$instance = EditWarningMsg::getInstance( 'ArticleNotice', 'http://example.com', [ 'param1' ] );

		$this->assertInstanceOf( 'EditWarning\EditWarningInfoMsg', $instance );
	}

	/**
	 * @covers EditWarning\EditWarningMsg::getInstance
	 */
	public function testGetInstanceArticleWarning() {
		$instance = EditWarningMsg::getInstance( 'ArticleWarning', 'http://example.com', [ 'param1' ] );

		$this->assertInstanceOf( 'EditWarning\EditWarningWarnMsg', $instance );
	}

	/**
	 * @covers EditWarning\EditWarningMsg::getInstance
	 */
	public function testGetInstanceArticleSectionWarning() {
		$instance = EditWarningMsg::getInstance( 'ArticleSectionWarning', 'http://example.com', [ 'param1' ] );

		$this->assertInstanceOf( 'EditWarning\EditWarningWarnMsg', $instance );
	}

	/**
	 * @covers EditWarning\EditWarningMsg::getInstance
	 */
	public function testGetInstanceSectionNotice() {
		$instance = EditWarningMsg::getInstance( 'SectionNotice', 'http://example.com', [ 'param1' ] );

		$this->assertInstanceOf( 'EditWarning\EditWarningInfoMsg', $instance );
	}

	/**
	 * @covers EditWarning\EditWarningMsg::getInstance
	 */
	public function testGetInstanceSectionWarning() {
		$instance = EditWarningMsg::getInstance( 'SectionWarning', 'http://example.com', [ 'param1' ] );

		$this->assertInstanceOf( 'EditWarning\EditWarningWarnMsg', $instance );
	}

	/**
	 * @covers EditWarning\EditWarningMsg::getInstance
	 */
	public function testGetInstanceCancel() {
		$instance = EditWarningMsg::getInstance( 'Cancel' );

		$this->assertInstanceOf( 'EditWarning\EditWarningCancelMsg', $instance );
	}

	/**
	 * @covers EditWarning\EditWarningMsg::getInstance
	 */
	public function testGetInstanceUnknownType() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( "Unknown message type." );

		EditWarningMsg::getInstance( 'UnknownType' );
	}
}
