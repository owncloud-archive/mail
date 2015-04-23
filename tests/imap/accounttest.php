<?php

namespace OCA\Mail\Tests\Imap;

class AccountTest extends AbstractTest {

	public function testListMailBoxes() {
		$newMailBox = $this->createMailBox('nasty stuff');
		$mailBoxes = $this->getTestAccount()->getListArray();
		$this->assertInternalType('array', $mailBoxes);

		$m = array_filter($mailBoxes['folders'], function($item) use ($newMailBox) {
			return $item['name'] === $newMailBox->getDisplayName();
		});
		$this->assertTrue(count($m) === 1);
	}

	public function testListMessages() {
		$newMailBox = parent::createMailBox('nasty stuff');
		$count = $newMailBox->getTotalMessages();
		$this->assertEquals(0, $count);
		$messages = $newMailBox->getMessages();
		$this->assertInternalType('array', $messages);
		$this->assertEquals(0, count($messages));
		$this->createTestMessage($newMailBox);
		$count = $newMailBox->getTotalMessages();
		$this->assertEquals(1, $count);
		$messages = $newMailBox->getMessages();
		$this->assertInternalType('array', $messages);
		$this->assertEquals(1, count($messages));
	}

	public function testDetectChangeInMailBox() {
		$newMailBox = parent::createMailBox('nasty stuff');
		$status0 = $newMailBox->getStatus();

//		$this->createTestMessage($newMailBox);
		$status1 = $newMailBox->getStatus();

		$this->assertEquals($status0['uidvalidity'], $status1['uidvalidity']);
		$this->assertEquals($status0['uidnext'], $status1['uidnext']);
		$this->assertEquals($status0, $status1);
	}

}
