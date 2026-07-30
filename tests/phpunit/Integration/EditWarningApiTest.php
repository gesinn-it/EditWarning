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

	public function testLockWithoutTokenIsRejected() {
		$this->expectException( ApiUsageException::class );

		$this->doApiRequest( [
			'action' => 'editwarning',
			'ewaction' => 'lock',
			'articleid' => 1,
		], null, false, $this->getTestUser()->getAuthority(), false );
	}

	public function testLockStoresRowUnderAuthenticatedUserNotUserParam() {
		$performer = $this->getTestUser()->getAuthority();

		$this->doApiRequestWithToken( [
			'action' => 'editwarning',
			'ewaction' => 'lock',
			'articleid' => 1,
			'user' => 'SomeoneElse',
		], null, $performer, 'csrf' );

		$row = $this->getLockRow( 1 );

		$this->assertNotFalse( $row );
		$this->assertSame( $performer->getUser()->getName(), $row->user_name );
	}

	public function testLock() {
		$result = $this->doApiRequestWithToken( [
			'action' => 'editwarning',
			'ewaction' => 'lock',
			'articleid' => 1,
		], null, $this->getTestUser()->getAuthority(), 'csrf' );
		$this->assertSame( 1, $result[0]['success']['editwarning']['lock']['articleid'] );
	}

	public function testUnlock() {
		$result = $this->doApiRequestWithToken( [
			'action' => 'editwarning',
			'ewaction' => 'unlock',
			'articleid' => 1,
		], null, $this->getTestUser()->getAuthority(), 'csrf' );
		$this->assertSame( 1, $result[0]['success']['editwarning']['unlock']['articleid'] );
	}

	public function testLockWithSectionStoresSectionOnLockRow() {
		$this->doApiRequestWithToken( [
			'action' => 'editwarning',
			'ewaction' => 'lock',
			'articleid' => 42,
			'section' => 3,
		], null, $this->getTestUser()->getAuthority(), 'csrf' );

		$row = $this->getLockRow( 42 );

		$this->assertNotFalse( $row );
		$this->assertSame( '3', $row->section );
	}

	public function testLockRejectsAnonymousUser() {
		$anon = MediaWikiServices::getInstance()->getUserFactory()->newAnonymous( '127.0.0.1' );

		$this->expectException( ApiUsageException::class );

		$this->doApiRequestWithToken( [
			'action' => 'editwarning',
			'ewaction' => 'lock',
			'articleid' => 1,
		], null, $anon, 'csrf' );
	}
}
