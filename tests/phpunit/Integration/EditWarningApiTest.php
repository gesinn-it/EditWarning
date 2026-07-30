<?php

use MediaWiki\MediaWikiServices;

/**
 * @group EditWarning
 * @group API
 * @group Database
 * @group medium
 *
 * @covers \EditWarning\EditWarningApi
 */
class EditWarningApiTest extends ApiTestCase {

	protected function tearDown(): void {
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->delete( 'editwarning_locks', [ '1=1' ], __METHOD__ );
		parent::tearDown();
	}

	private function getLockRow( $articleId ) {
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		return $dbw->selectRow( 'editwarning_locks', '*', [ 'article_id' => $articleId ], __METHOD__ );
	}

	public function testLock() {
		$result = $this->doApiRequest( [
			'action' => 'editwarning',
			'ewaction' => 'lock',
			'articleid' => 1,
			'user' => 'Admin'
		] );
		$this->assertSame( 1, $result[0]['success']['editwarning']['lock']['articleid'] );
	}

	public function testUnlock() {
		$result = $this->doApiRequest( [
			'action' => 'editwarning',
			'ewaction' => 'unlock',
			'articleid' => 1,
			'user' => 'Admin'
		] );
		$this->assertSame( 1, $result[0]['success']['editwarning']['unlock']['articleid'] );
	}

	public function testLockWithSectionStoresSectionOnLockRow() {
		$this->doApiRequest( [
			'action' => 'editwarning',
			'ewaction' => 'lock',
			'articleid' => 42,
			'section' => 3,
			'user' => 'Admin'
		] );

		$row = $this->getLockRow( 42 );

		$this->assertNotFalse( $row );
		$this->assertSame( '3', $row->section );
	}
}
