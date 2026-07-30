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
class EditWarningHooksEditTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		global $wgPHP_SELF;
		$wgPHP_SELF = '/index.php';
		unset( $_GET['section'], $_POST['wpSection'] );
	}

	protected function tearDown(): void {
		unset( $_GET['section'], $_POST['wpSection'] );
		global $wgOut;
		$wgOut = null;
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->delete( 'editwarning_locks', [ '1=1' ], __METHOD__ );
		parent::tearDown();
	}

	private function newOutputPageForEdit( Title $title, User $user, array $requestParams = [] ) {
		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setTitle( $title );
		$context->setUser( $user );
		$context->setRequest( new FauxRequest( $requestParams + [ 'action' => 'edit' ] ) );
		$out = new OutputPage( $context );
		$out->setTitle( $title );
		$context->setOutput( $out );

		// EditWarningMessage::show() writes into the $wgOut global directly.
		global $wgOut;
		$wgOut = $out;

		return $out;
	}

	private function getLockRow( $articleId ) {
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		return $dbw->selectRow( 'editwarning_locks', '*', [ 'article_id' => $articleId ], __METHOD__ );
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::edit
	 */
	public function testEditOnNonExistingPageDoesNothing() {
		$title = Title::makeTitle( NS_MAIN, 'EditWarningHooksEditTest-NonExisting-' . __LINE__ );
		$user = $this->getTestUser()->getUser();
		$out = $this->newOutputPageForEdit( $title, $user );

		$result = EditWarningHooks::edit( $out, $this->createMock( Skin::class ) );

		$this->assertTrue( $result );
		$this->assertFalse( $this->getLockRow( $title->getArticleID() ) );
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::edit
	 */
	public function testEditCreatesArticleLockForNewEdit() {
		$page = $this->getExistingTestPage( 'EditWarningHooksEditTest-Article-' . __LINE__ );
		$title = $page->getTitle();
		$user = $this->getTestUser()->getUser();
		$out = $this->newOutputPageForEdit( $title, $user );

		$result = EditWarningHooks::edit( $out, $this->createMock( Skin::class ) );

		$this->assertTrue( $result );
		$row = $this->getLockRow( $title->getArticleID() );
		$this->assertNotFalse( $row, 'Editing an unlocked article should create a lock.' );
		$this->assertSame( (string)$user->getId(), (string)$row->user_id );
		$this->assertSame( '0', (string)$row->section );
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::edit
	 */
	public function testEditDoesNotCreateArticleLockForAnonymousUser() {
		$page = $this->getExistingTestPage( 'EditWarningHooksEditTest-Anon-' . __LINE__ );
		$title = $page->getTitle();
		$user = MediaWikiServices::getInstance()->getUserFactory()->newAnonymous( '127.0.0.1' );
		$out = $this->newOutputPageForEdit( $title, $user );

		$result = EditWarningHooks::edit( $out, $this->createMock( Skin::class ) );

		$this->assertTrue( $result );
		$this->assertFalse( $this->getLockRow( $title->getArticleID() ) );
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::edit
	 */
	public function testEditUpdatesOwnArticleLockTimestamp() {
		$page = $this->getExistingTestPage( 'EditWarningHooksEditTest-Update-' . __LINE__ );
		$title = $page->getTitle();
		$user = $this->getTestUser()->getUser();
		$originalTimestamp = time() + 60;
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->insert( 'editwarning_locks', [
			'user_id' => $user->getId(),
			'user_name' => $user->getName(),
			'article_id' => $title->getArticleID(),
			'lock_timestamp' => $originalTimestamp,
			'section' => 0,
		], __METHOD__ );

		$out = $this->newOutputPageForEdit( $title, $user );
		$result = EditWarningHooks::edit( $out, $this->createMock( Skin::class ) );

		$this->assertTrue( $result );
		$row = $this->getLockRow( $title->getArticleID() );
		$this->assertGreaterThan( $originalTimestamp, (int)$row->lock_timestamp,
			'Lock timestamp should have been refreshed further into the future.' );
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::edit
	 */
	public function testEditByOtherUserDoesNotOverwriteExistingLock() {
		$page = $this->getExistingTestPage( 'EditWarningHooksEditTest-Other-' . __LINE__ );
		$title = $page->getTitle();
		$firstUser = $this->getTestUser()->getUser();
		$secondUser = $this->getMutableTestUser( [], 'second-user' )->getUser();

		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->insert( 'editwarning_locks', [
			'user_id' => $firstUser->getId(),
			'user_name' => $firstUser->getName(),
			'article_id' => $title->getArticleID(),
			'lock_timestamp' => time() + 600,
			'section' => 0,
		], __METHOD__ );

		$out = $this->newOutputPageForEdit( $title, $secondUser );
		$result = EditWarningHooks::edit( $out, $this->createMock( Skin::class ) );

		$this->assertTrue( $result );
		$row = $this->getLockRow( $title->getArticleID() );
		$this->assertSame( (string)$firstUser->getId(), (string)$row->user_id,
			'Existing lock by another user must not be overwritten.' );
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::edit
	 */
	public function testEditCreatesSectionLockForNewSectionEdit() {
		$page = $this->getExistingTestPage( 'EditWarningHooksEditTest-Section-' . __LINE__ );
		$title = $page->getTitle();
		$user = $this->getTestUser()->getUser();
		$_GET['section'] = '2';

		$out = $this->newOutputPageForEdit( $title, $user );
		$result = EditWarningHooks::edit( $out, $this->createMock( Skin::class ) );

		$this->assertTrue( $result );
		$row = $this->getLockRow( $title->getArticleID() );
		$this->assertNotFalse( $row );
		$this->assertSame( '2', (string)$row->section );
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::edit
	 */
	public function testEditViaFormeditActionIsTreatedAsEdit() {
		// PageForms drives article editing through action=formedit rather than action=edit.
		$page = $this->getExistingTestPage( 'EditWarningHooksEditTest-Formedit-' . __LINE__ );
		$title = $page->getTitle();
		$user = $this->getTestUser()->getUser();
		$out = $this->newOutputPageForEdit( $title, $user, [ 'action' => 'formedit' ] );

		$result = EditWarningHooks::edit( $out, $this->createMock( Skin::class ) );

		$this->assertTrue( $result );
		$row = $this->getLockRow( $title->getArticleID() );
		$this->assertNotFalse( $row, 'action=formedit should create a lock just like action=edit.' );
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::edit
	 * @covers \EditWarning\EditWarningHooks::showWarningMsg
	 */
	public function testEditShowsWarningWhenArticleLockedByOtherUser() {
		$page = $this->getExistingTestPage( 'EditWarningHooksEditTest-WarnArticle-' . __LINE__ );
		$title = $page->getTitle();
		$firstUser = $this->getTestUser()->getUser();
		$secondUser = $this->getMutableTestUser( [], 'second-user' )->getUser();

		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->insert( 'editwarning_locks', [
			'user_id' => $firstUser->getId(),
			'user_name' => $firstUser->getName(),
			'article_id' => $title->getArticleID(),
			'lock_timestamp' => time() + 600,
			'section' => 0,
		], __METHOD__ );

		$out = $this->newOutputPageForEdit( $title, $secondUser );
		$result = EditWarningHooks::edit( $out, $this->createMock( Skin::class ) );

		$this->assertTrue( $result );
		$this->assertStringContainsString( $firstUser->getName(), $out->getHTML() );
		$this->assertContains( 'ext.editwarning.overlay', $out->getModules() );
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::edit
	 */
	public function testEditChangesOwnArticleLockToSectionLock() {
		$page = $this->getExistingTestPage( 'EditWarningHooksEditTest-ArticleToSection-' . __LINE__ );
		$title = $page->getTitle();
		$user = $this->getTestUser()->getUser();

		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->insert( 'editwarning_locks', [
			'user_id' => $user->getId(),
			'user_name' => $user->getName(),
			'article_id' => $title->getArticleID(),
			'lock_timestamp' => time() + 600,
			'section' => 0,
		], __METHOD__ );
		$_GET['section'] = '4';

		$out = $this->newOutputPageForEdit( $title, $user );
		$result = EditWarningHooks::edit( $out, $this->createMock( Skin::class ) );

		$this->assertTrue( $result );
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$rows = iterator_to_array( $dbr->select(
			'editwarning_locks', '*', [ 'article_id' => $title->getArticleID() ], __METHOD__
		) );
		$this->assertCount( 1, $rows, 'The article-level lock should have been replaced by a single section lock.' );
		$this->assertSame( '4', (string)$rows[0]->section );
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::edit
	 */
	public function testEditUpdatesOwnSectionLockTimestamp() {
		$page = $this->getExistingTestPage( 'EditWarningHooksEditTest-SectionUpdate-' . __LINE__ );
		$title = $page->getTitle();
		$user = $this->getTestUser()->getUser();
		$originalTimestamp = time() + 60;

		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->insert( 'editwarning_locks', [
			'user_id' => $user->getId(),
			'user_name' => $user->getName(),
			'article_id' => $title->getArticleID(),
			'lock_timestamp' => $originalTimestamp,
			'section' => 3,
		], __METHOD__ );
		$_GET['section'] = '3';

		$out = $this->newOutputPageForEdit( $title, $user );
		$result = EditWarningHooks::edit( $out, $this->createMock( Skin::class ) );

		$this->assertTrue( $result );
		$row = $this->getLockRow( $title->getArticleID() );
		$this->assertGreaterThan( $originalTimestamp, (int)$row->lock_timestamp );
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::edit
	 * @covers \EditWarning\EditWarningHooks::showWarningMsg
	 */
	public function testEditShowsWarningWhenSectionLockedByOtherUser() {
		$page = $this->getExistingTestPage( 'EditWarningHooksEditTest-WarnSection-' . __LINE__ );
		$title = $page->getTitle();
		$firstUser = $this->getTestUser()->getUser();
		$secondUser = $this->getMutableTestUser( [], 'second-user' )->getUser();

		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->insert( 'editwarning_locks', [
			'user_id' => $firstUser->getId(),
			'user_name' => $firstUser->getName(),
			'article_id' => $title->getArticleID(),
			'lock_timestamp' => time() + 600,
			'section' => 3,
		], __METHOD__ );
		$_GET['section'] = '3';

		$out = $this->newOutputPageForEdit( $title, $secondUser );
		$result = EditWarningHooks::edit( $out, $this->createMock( Skin::class ) );

		$this->assertTrue( $result );
		$this->assertStringContainsString( $firstUser->getName(), $out->getHTML() );
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::edit
	 * @covers \EditWarning\EditWarningHooks::showWarningMsg
	 */
	public function testEditShowsSectionConflictWarningWhenEditingWholeArticle() {
		$page = $this->getExistingTestPage( 'EditWarningHooksEditTest-Conflict-' . __LINE__ );
		$title = $page->getTitle();
		$firstUser = $this->getTestUser()->getUser();
		$secondUser = $this->getMutableTestUser( [], 'second-user' )->getUser();

		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->insert( 'editwarning_locks', [
			'user_id' => $firstUser->getId(),
			'user_name' => $firstUser->getName(),
			'article_id' => $title->getArticleID(),
			'lock_timestamp' => time() + 600,
			'section' => 3,
		], __METHOD__ );

		// secondUser now tries to edit the whole article while a section is locked by firstUser.
		$out = $this->newOutputPageForEdit( $title, $secondUser );
		$result = EditWarningHooks::edit( $out, $this->createMock( Skin::class ) );

		$this->assertTrue( $result );
		// The section-conflict message is deliberately generic (no username): several sections could be
		// locked by different users at once, so naming just one lock holder would be misleading.
		$this->assertStringContainsString( 'edited currently', $out->getHTML() );
		$this->assertStringNotContainsString( $firstUser->getName(), $out->getHTML() );
		$row = $this->getLockRow( $title->getArticleID() );
		$this->assertSame( '3', (string)$row->section, 'The existing section lock must remain untouched.' );
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::edit
	 */
	public function testEditConvertsOwnSectionLocksToArticleLock() {
		$page = $this->getExistingTestPage( 'EditWarningHooksEditTest-SectionToArticle-' . __LINE__ );
		$title = $page->getTitle();
		$user = $this->getTestUser()->getUser();

		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->insert( 'editwarning_locks', [
			'user_id' => $user->getId(),
			'user_name' => $user->getName(),
			'article_id' => $title->getArticleID(),
			'lock_timestamp' => time() + 600,
			'section' => 2,
		], __METHOD__ );

		$out = $this->newOutputPageForEdit( $title, $user );
		$result = EditWarningHooks::edit( $out, $this->createMock( Skin::class ) );

		$this->assertTrue( $result );
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$rows = iterator_to_array( $dbr->select(
			'editwarning_locks', '*', [ 'article_id' => $title->getArticleID() ], __METHOD__
		) );
		$this->assertCount( 1, $rows );
		$this->assertSame( '0', (string)$rows[0]->section,
			'The section lock should have been replaced by an article lock.' );
	}

	/**
	 * @covers \EditWarning\EditWarningHooks::edit
	 * @covers \EditWarning\EditWarningHooks::removeWarning
	 */
	public function testViewingPageAfterSaveRemovesOwnLock() {
		$page = $this->getExistingTestPage( 'EditWarningHooksEditTest-RemoveOnSave-' . __LINE__ );
		$title = $page->getTitle();
		$user = $this->getTestUser()->getUser();

		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->insert( 'editwarning_locks', [
			'user_id' => $user->getId(),
			'user_name' => $user->getName(),
			'article_id' => $title->getArticleID(),
			'lock_timestamp' => time() + 600,
			'section' => 0,
		], __METHOD__ );

		// Viewing the page (action=view, the default) after saving/canceling should clear the lock.
		$out = $this->newOutputPageForEdit( $title, $user, [ 'action' => 'view' ] );
		$result = EditWarningHooks::edit( $out, $this->createMock( Skin::class ) );

		$this->assertTrue( $result );
		$this->assertFalse( $this->getLockRow( $title->getArticleID() ) );
	}
}
