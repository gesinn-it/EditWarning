<?php

use EditWarning\EditWarning;
use PHPUnit\Framework\TestCase;

class EditWarningTest extends TestCase {

	/**
	 * @var EditWarning An instance of EditWarning used for testing.
	 */
	protected $editWarningInstance;

	protected function setUp(): void {
		parent::setUp();
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
}
