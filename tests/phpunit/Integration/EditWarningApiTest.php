<?php

/**
 * @group EditWarning
 * @group API
 * @group Database
 * @group medium
 *
 * @covers \EditWarning\EditWarningApi
 */
class EditWarningApiTest extends ApiTestCase {

	public function testLock() {
		$result = $this->doApiRequest( [
			'action' => 'editwarning',
			'ewaction' => 'lock',
			'articleid' => 1,
			'user' => 'Admin'
		] );
		$this->assertSame( '1', $result[0]['success']['editwarning']['lock']['articleid'] );
	}

	public function testUnlock() {
		$result = $this->doApiRequest( [
			'action' => 'editwarning',
			'ewaction' => 'unlock',
			'articleid' => 1,
			'user' => 'Admin'
		] );
		$this->assertSame( '1', $result[0]['success']['editwarning']['unlock']['articleid'] );
	}
}
