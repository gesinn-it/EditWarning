<?php

use EditWarning\EditWarningLock;
use PHPUnit\Framework\TestCase;

class EditWarningLockTest extends TestCase {

	private function makeDbRow( $userId, $userName, $section, $timestamp ) {
		return (object)[
			'user_id' => $userId,
			'user_name' => $userName,
			'section' => $section,
			'lock_timestamp' => $timestamp,
		];
	}

	/**
	 * @covers EditWarning\EditWarningLock::__construct
	 * @covers EditWarning\EditWarningLock::getParent
	 * @covers EditWarning\EditWarningLock::getUserID
	 * @covers EditWarning\EditWarningLock::getUserName
	 * @covers EditWarning\EditWarningLock::getSection
	 * @covers EditWarning\EditWarningLock::getTimestamp
	 */
	public function testConstructorSetsAllFieldsFromDbRow() {
		$parent = new stdClass();
		$row = $this->makeDbRow( 42, 'Alice', 3, 1234567890 );

		$lock = new EditWarningLock( $parent, $row );

		$this->assertSame( $parent, $lock->getParent() );
		$this->assertSame( 42, $lock->getUserID() );
		$this->assertSame( 'Alice', $lock->getUserName() );
		$this->assertSame( 3, $lock->getSection() );
		$this->assertSame( 1234567890, $lock->getTimestamp() );
	}

	/**
	 * @covers EditWarning\EditWarningLock::setParent
	 * @covers EditWarning\EditWarningLock::getParent
	 */
	public function testSetParent() {
		$lock = new EditWarningLock( null, $this->makeDbRow( 1, 'Bob', 0, 1 ) );
		$newParent = new stdClass();

		$lock->setParent( $newParent );

		$this->assertSame( $newParent, $lock->getParent() );
	}

	/**
	 * @covers EditWarning\EditWarningLock::setUserID
	 * @covers EditWarning\EditWarningLock::getUserID
	 */
	public function testSetUserID() {
		$lock = new EditWarningLock( null, $this->makeDbRow( 1, 'Bob', 0, 1 ) );

		$lock->setUserID( 99 );

		$this->assertSame( 99, $lock->getUserID() );
	}

	/**
	 * @covers EditWarning\EditWarningLock::setUserName
	 * @covers EditWarning\EditWarningLock::getUserName
	 */
	public function testSetUserName() {
		$lock = new EditWarningLock( null, $this->makeDbRow( 1, 'Bob', 0, 1 ) );

		$lock->setUserName( 'Carol' );

		$this->assertSame( 'Carol', $lock->getUserName() );
	}

	/**
	 * @covers EditWarning\EditWarningLock::setSection
	 * @covers EditWarning\EditWarningLock::getSection
	 */
	public function testSetSection() {
		$lock = new EditWarningLock( null, $this->makeDbRow( 1, 'Bob', 0, 1 ) );

		$lock->setSection( 7 );

		$this->assertSame( 7, $lock->getSection() );
	}

	/**
	 * @covers EditWarning\EditWarningLock::setTimestamp
	 * @covers EditWarning\EditWarningLock::getTimestamp
	 */
	public function testSetTimestamp() {
		$lock = new EditWarningLock( null, $this->makeDbRow( 1, 'Bob', 0, 1 ) );

		$lock->setTimestamp( 987654321 );

		$this->assertSame( 987654321, $lock->getTimestamp() );
	}
}
