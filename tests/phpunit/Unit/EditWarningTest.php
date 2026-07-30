<?php

use EditWarning\EditWarning;
use PHPUnit\Framework\TestCase;
use Wikimedia\Rdbms\IDatabase;

class EditWarningTest extends TestCase {

	/**
	 * @var EditWarning An instance of EditWarning used for testing.
	 */
	protected $editWarningInstance;

	protected function setUp(): void {
		parent::setUp();
		global $wgTS_Timeout, $wgTS_Current, $wgEditWarning_Timeout;
		$wgTS_Timeout = 1;
		$wgTS_Current = 2;
		$wgEditWarning_Timeout = 10;
		// Initialize EditWarning instance with mock values
		$this->editWarningInstance = new EditWarning( 1, 123 );
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @covers EditWarning\EditWarning::__construct
	 */
	public function testConstructor() {
		$editWarning = new EditWarning( 1, 2, 3 );
		$this->assertSame( 1, $editWarning->getUserID() );
		$this->assertSame( 2, $editWarning->getArticleID() );
		$this->assertSame( 3, $editWarning->getSection() );
	}

	/**
	 * @covers EditWarning\EditWarning::getUserID
	 * @covers EditWarning\EditWarning::setUserID
	 */
	public function testSetUserID() {
		$this->editWarningInstance->setUserID( 55 );
		$this->assertSame( 55, $this->editWarningInstance->getUserID() );
	}

	/**
	 * @covers EditWarning\EditWarning::getUserName
	 * @covers EditWarning\EditWarning::setUserName
	 */
	public function testSetUserName() {
		$this->editWarningInstance->setUserName( 'Alice' );
		$this->assertSame( 'Alice', $this->editWarningInstance->getUserName() );
	}

	/**
	 * @covers EditWarning\EditWarning::getArticleID
	 * @covers EditWarning\EditWarning::setArticleID
	 */
	public function testSetArticleID() {
		$this->editWarningInstance->setArticleID( 77 );
		$this->assertSame( 77, $this->editWarningInstance->getArticleID() );
	}

	/**
	 * @covers EditWarning\EditWarning::getSection
	 * @covers EditWarning\EditWarning::setSection
	 */
	public function testSetSection() {
		$this->editWarningInstance->setSection( 4 );
		$this->assertSame( 4, $this->editWarningInstance->getSection() );
	}

	/**
	 * @covers EditWarning\EditWarning::getTimestamp
	 */
	public function testGetTimestampWithInvalidTypeThrows() {
		$this->expectException( InvalidArgumentException::class );
		$this->editWarningInstance->getTimestamp( 999 );
	}

	/**
	 * @covers EditWarning\EditWarning::getTimestamp
	 */
	public function testGetTimestampCurrentIsNotInTheFuture() {
		global $wgTS_Current;
		$timestamp = $this->editWarningInstance->getTimestamp( $wgTS_Current );
		$this->assertLessThanOrEqual( time(), $timestamp );
	}

	/**
	 * @covers EditWarning\EditWarning::getTimestamp
	 */
	public function testGetTimestampTimeoutIsInTheFuture() {
		global $wgTS_Timeout;
		$timestamp = $this->editWarningInstance->getTimestamp( $wgTS_Timeout );
		$this->assertGreaterThan( time(), $timestamp );
	}

	/**
	 * @covers EditWarning\EditWarning::anyLock
	 */
	public function testAnyLockFalseWhenNoLocks() {
		$this->assertFalse( $this->editWarningInstance->anyLock() );
	}

	/**
	 * @covers EditWarning\EditWarning::anyLock
	 * @covers EditWarning\EditWarning::load
	 */
	public function testAnyLockTrueAfterLoadingArticleLock() {
		$dbr = $this->makeSelectDb( [
			$this->makeLockRow( 1, 'Alice', 0, time() + 60 ),
		] );

		$this->editWarningInstance->load( $dbr );

		$this->assertTrue( $this->editWarningInstance->anyLock() );
	}

	/**
	 * @covers EditWarning\EditWarning::isArticleLocked
	 * @covers EditWarning\EditWarning::load
	 */
	public function testIsArticleLockedFalseWhenNoLocks() {
		$dbr = $this->makeSelectDb( [] );

		$this->editWarningInstance->load( $dbr );

		$this->assertFalse( $this->editWarningInstance->isArticleLocked() );
	}

	/**
	 * @covers EditWarning\EditWarning::isArticleLocked
	 * @covers EditWarning\EditWarning::load
	 */
	public function testIsArticleLockedTrueForArticleLevelLock() {
		// section = 0 marks an article-level lock (see addLock()).
		$dbr = $this->makeSelectDb( [
			$this->makeLockRow( 2, 'Bob', 0, time() + 60 ),
		] );

		$this->editWarningInstance->load( $dbr );

		$this->assertTrue( $this->editWarningInstance->isArticleLocked() );
	}

	/**
	 * @covers EditWarning\EditWarning::isArticleLockedByUser
	 * @covers EditWarning\EditWarning::load
	 */
	public function testIsArticleLockedByUserTrueForOwnLock() {
		// editWarningInstance was constructed with user_id 1.
		$dbr = $this->makeSelectDb( [
			$this->makeLockRow( 1, 'Alice', 0, time() + 60 ),
		] );

		$this->editWarningInstance->load( $dbr );

		$this->assertTrue( $this->editWarningInstance->isArticleLockedByUser() );
	}

	/**
	 * @covers EditWarning\EditWarning::isArticleLockedByUser
	 * @covers EditWarning\EditWarning::load
	 */
	public function testIsArticleLockedByUserFalseForOtherUsersLock() {
		$dbr = $this->makeSelectDb( [
			$this->makeLockRow( 2, 'Bob', 0, time() + 60 ),
		] );

		$this->editWarningInstance->load( $dbr );

		$this->assertFalse( $this->editWarningInstance->isArticleLockedByUser() );
	}

	/**
	 * @covers EditWarning\EditWarning::isArticleLockedByUser
	 */
	public function testIsArticleLockedByUserFalseWhenNoLock() {
		$this->assertFalse( $this->editWarningInstance->isArticleLockedByUser() );
	}

	/**
	 * @covers EditWarning\EditWarning::anySectionLocks
	 * @covers EditWarning\EditWarning::load
	 */
	public function testAnySectionLocksFalseWhenNone() {
		$dbr = $this->makeSelectDb( [] );

		$this->editWarningInstance->load( $dbr );

		$this->assertFalse( $this->editWarningInstance->anySectionLocks() );
	}

	/**
	 * @covers EditWarning\EditWarning::anySectionLocks
	 * @covers EditWarning\EditWarning::load
	 */
	public function testAnySectionLocksTrueWhenPresent() {
		$dbr = $this->makeSelectDb( [
			$this->makeLockRow( 1, 'Alice', 3, time() + 60 ),
		] );

		$this->editWarningInstance->load( $dbr );

		$this->assertTrue( $this->editWarningInstance->anySectionLocks() );
	}

	/**
	 * @covers EditWarning\EditWarning::anySectionLocksByUser
	 * @covers EditWarning\EditWarning::load
	 */
	public function testAnySectionLocksByUserTrueForOwnSectionLock() {
		$dbr = $this->makeSelectDb( [
			$this->makeLockRow( 1, 'Alice', 3, time() + 60 ),
		] );

		$this->editWarningInstance->load( $dbr );

		$this->assertTrue( $this->editWarningInstance->anySectionLocksByUser() );
	}

	/**
	 * @covers EditWarning\EditWarning::anySectionLocksByOthers
	 * @covers EditWarning\EditWarning::load
	 */
	public function testAnySectionLocksByOthersTrueForOtherUsersSectionLock() {
		$dbr = $this->makeSelectDb( [
			$this->makeLockRow( 2, 'Bob', 3, time() + 60 ),
		] );

		$this->editWarningInstance->load( $dbr );

		$this->assertTrue( $this->editWarningInstance->anySectionLocksByOthers() );
	}

	/**
	 * @covers EditWarning\EditWarning::anySectionLocksByOthers
	 * @covers EditWarning\EditWarning::load
	 */
	public function testAnySectionLocksByOthersFalseWhenOnlyOwnLock() {
		$dbr = $this->makeSelectDb( [
			$this->makeLockRow( 1, 'Alice', 3, time() + 60 ),
		] );

		$this->editWarningInstance->load( $dbr );

		$this->assertFalse( $this->editWarningInstance->anySectionLocksByOthers() );
	}

	/**
	 * @covers EditWarning\EditWarning::isSectionLocked
	 * @covers EditWarning\EditWarning::getSectionLock
	 * @covers EditWarning\EditWarning::load
	 */
	public function testIsSectionLockedTrueForMatchingSection() {
		$this->editWarningInstance->setSection( 3 );
		$dbr = $this->makeSelectDb( [
			$this->makeLockRow( 2, 'Bob', 3, time() + 60 ),
		] );

		$this->editWarningInstance->load( $dbr );

		$this->assertTrue( $this->editWarningInstance->isSectionLocked() );
	}

	/**
	 * @covers EditWarning\EditWarning::isSectionLocked
	 */
	public function testIsSectionLockedFalseWhenNoLocksAtAll() {
		$this->assertFalse( $this->editWarningInstance->isSectionLocked() );
	}

	/**
	 * @covers EditWarning\EditWarning::isSectionLocked
	 * @covers EditWarning\EditWarning::getSectionLock
	 * @covers EditWarning\EditWarning::load
	 */
	public function testIsSectionLockedFalseForDifferentSection() {
		$this->editWarningInstance->setSection( 5 );
		$dbr = $this->makeSelectDb( [
			$this->makeLockRow( 2, 'Bob', 3, time() + 60 ),
		] );

		$this->editWarningInstance->load( $dbr );

		$this->assertFalse( $this->editWarningInstance->isSectionLocked() );
	}

	/**
	 * @covers EditWarning\EditWarning::getSectionLock
	 * @covers EditWarning\EditWarning::load
	 */
	public function testGetSectionLockMergesUserAndOtherLocks() {
		$this->editWarningInstance->setSection( 3 );
		$dbr = $this->makeSelectDb( [
			$this->makeLockRow( 1, 'Alice', 4, time() + 60 ),
			$this->makeLockRow( 2, 'Bob', 3, time() + 60 ),
		] );

		$this->editWarningInstance->load( $dbr );
		$lock = $this->editWarningInstance->getSectionLock();

		$this->assertSame( 'Bob', $lock->getUserName() );
	}

	/**
	 * @covers EditWarning\EditWarning::isSectionLockedByUser
	 */
	public function testIsSectionLockedByUserTrue() {
		$this->editWarningInstance->setSection( 3 );
		$dbr = $this->makeSelectDb( [
			$this->makeLockRow( 1, 'Alice', 3, time() + 60 ),
		] );
		$this->editWarningInstance->load( $dbr );
		$lock = $this->editWarningInstance->getSectionLock();

		$this->assertTrue( $this->editWarningInstance->isSectionLockedByUser( $lock ) );
	}

	/**
	 * @covers EditWarning\EditWarning::isSectionLockedByUser
	 */
	public function testIsSectionLockedByUserFalse() {
		$this->editWarningInstance->setSection( 3 );
		$dbr = $this->makeSelectDb( [
			$this->makeLockRow( 2, 'Bob', 3, time() + 60 ),
		] );
		$this->editWarningInstance->load( $dbr );
		$lock = $this->editWarningInstance->getSectionLock();

		$this->assertFalse( $this->editWarningInstance->isSectionLockedByUser( $lock ) );
	}

	/**
	 * @covers EditWarning\EditWarning::getSectionLocksByOther
	 * @covers EditWarning\EditWarning::getSectionLocksByOtherCount
	 * @covers EditWarning\EditWarning::load
	 */
	public function testGetSectionLocksByOther() {
		$dbr = $this->makeSelectDb( [
			$this->makeLockRow( 2, 'Bob', 3, time() + 60 ),
			$this->makeLockRow( 3, 'Carol', 4, time() + 60 ),
		] );

		$this->editWarningInstance->load( $dbr );

		$this->assertSame( 2, $this->editWarningInstance->getSectionLocksByOtherCount() );
		$this->assertCount( 2, $this->editWarningInstance->getSectionLocksByOther() );
	}

	/**
	 * @covers EditWarning\EditWarning::saveLock
	 */
	public function testSaveLockInsertsRowWithoutSection() {
		$dbw = $this->createMock( IDatabase::class );
		$dbw->expects( $this->once() )
			->method( 'insert' )
			->with(
				'editwarning_locks',
				$this->callback( static function ( $values ) {
					return $values['user_id'] === 1
						&& $values['article_id'] === 123
						&& $values['section'] === 0;
				} )
			);

		$this->editWarningInstance->saveLock( $dbw );
	}

	/**
	 * @covers EditWarning\EditWarning::saveLock
	 */
	public function testSaveLockInsertsRowWithSection() {
		$dbw = $this->createMock( IDatabase::class );
		$dbw->expects( $this->once() )
			->method( 'insert' )
			->with(
				'editwarning_locks',
				$this->callback( static function ( $values ) {
					return $values['section'] === 5;
				} )
			);

		$this->editWarningInstance->saveLock( $dbw, 5 );
	}

	/**
	 * @covers EditWarning\EditWarning::updateLock
	 */
	public function testUpdateLockUpdatesMatchingRow() {
		$dbw = $this->createMock( IDatabase::class );
		$dbw->expects( $this->once() )
			->method( 'update' )
			->with(
				'editwarning_locks',
				$this->anything(),
				$this->callback( static function ( $conditions ) {
					return $conditions['user_id'] === 1
						&& $conditions['article_id'] === 123
						&& $conditions['section'] === 0;
				} )
			);

		$this->editWarningInstance->updateLock( $dbw );
	}

	/**
	 * @covers EditWarning\EditWarning::removeLock
	 */
	public function testRemoveLockDeletesByUserAndArticle() {
		$dbw = $this->createMock( IDatabase::class );
		$dbw->expects( $this->once() )
			->method( 'delete' )
			->with(
				'editwarning_locks',
				[ 'user_id' => 1, 'article_id' => 123 ]
			);

		$this->editWarningInstance->removeLock( $dbw );
	}

	/**
	 * @covers EditWarning\EditWarning::removeUserLocks
	 */
	public function testRemoveUserLocksDeletesByUser() {
		$dbw = $this->createMock( IDatabase::class );
		$dbw->expects( $this->once() )
			->method( 'delete' )
			->with(
				'editwarning_locks',
				[ 'user_id' => 1 ]
			);

		$this->editWarningInstance->removeUserLocks( $dbw );
	}

	/**
	 * @covers EditWarning\EditWarning::getSectionLock
	 */
	public function testNoSectionLocks() {
		// Set up _locks to simulate no section locks
		$locks = [
			'count' => 0,
			'section' => [
				'count' => 0,
				'user' => [ 'count' => 0, 'obj' => [] ],
				'other' => [ 'count' => 0, 'obj' => [] ]
			]
		];

		$this->editWarningInstance->setLocks( $locks );
		$result = $this->editWarningInstance->getSectionLock();

		$this->assertFalse( $result );
	}

	private function makeLockRow( $userId, $userName, $section, $timestamp ) {
		return (object)[
			'user_id' => $userId,
			'user_name' => $userName,
			'section' => $section,
			'lock_timestamp' => $timestamp,
		];
	}

	private function makeSelectDb( array $rows ) {
		$dbr = $this->createMock( IDatabase::class );
		$dbr->method( 'addQuotes' )->willReturnCallback( static function ( $value ) {
			return "'" . $value . "'";
		} );
		$dbr->method( 'select' )->willReturn( $rows );

		return $dbr;
	}
}
