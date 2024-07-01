<?php

use EditWarning\EditWarning;
use PHPUnit\Framework\TestCase;

class EditWarningTest extends TestCase {

	/**
	 * @covers EditWarning\EditWarning::__construct
	 */
	public function testConstructor() {
		$editWarning = new EditWarning( 1, 2, 3 );
		$this->assertSame( 1, $editWarning->getUserID() );
		$this->assertSame( 2, $editWarning->getArticleID() );
		$this->assertSame( 3, $editWarning->getSection() );
	}

}
