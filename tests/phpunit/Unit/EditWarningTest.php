<?php

use PHPUnit\Framework\TestCase;
use EditWarning\EditWarning;

class EditWarningTest extends TestCase {

	public function testConstructor() {
		$editWarning = new EditWarning( 1, 2, 3 );
		$this->assertSame( 1, $editWarning->getUserID() );
		$this->assertSame( 2, $editWarning->getArticleID() );
		$this->assertSame( 3, $editWarning->getSection() );
	}

}
