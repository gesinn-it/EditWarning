<?php

use PHPUnit\Framework\TestCase;
use EditWarning\EditWarning;

class EditWarningTest extends TestCase {

	public function testConstructor() {
		$editWarning = new EditWarning( 1, 2, 3 );
		$this->assertEquals( 1, $editWarning->getUserID() );
		$this->assertEquals( 2, $editWarning->getArticleID() );
		$this->assertEquals( 3, $editWarning->getSection() );
	}

}
