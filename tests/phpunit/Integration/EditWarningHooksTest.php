<?php

use EditWarning\EditWarningHooks;
use MediaWiki\MediaWikiServices;

/**
 * @group EditWarning
 * @group Database
 * @group medium
 *
 * @covers \EditWarning\EditWarningHooks
 */
class EditWarningHooksTest extends MediaWikiIntegrationTestCase {

	protected function tearDown(): void {
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->delete( 'editwarning_locks', [ '1=1' ], __METHOD__ );
		parent::tearDown();
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::logout
	 */
	public function testLogoutRemovesUserLocks() {
		$user = $this->getTestUser()->getUser();
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->insert( 'editwarning_locks', [
			'user_id' => $user->getId(),
			'user_name' => $user->getName(),
			'article_id' => 1,
			'lock_timestamp' => time() + 60,
			'section' => 0,
		], __METHOD__ );

		EditWarningHooks::logout( $user );

		$row = $dbw->selectRow( 'editwarning_locks', '*', [ 'user_id' => $user->getId() ], __METHOD__ );
		$this->assertFalse( $row, 'Lock should have been removed on logout.' );
	}
}
