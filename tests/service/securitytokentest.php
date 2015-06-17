<?php

namespace OCA\mail\tests\service;

use OCA\mail\lib\service\SecurityToken;
use OCP\ISession;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use Test\TestCase;

/**
 * Class SecurityTokenTest
 *
 * @package OCA\mail\tests\service
 */
class SecurityTokenTest extends TestCase {
	/** @var SecurityToken */
	private $securityToken;
	/** @var ISession */
	private $userSession;
	/** @var ISecureRandom */
	private $secureRandom;
	/** @var ICrypto */
	private $crypto;

	public function setUp() {
		parent::setUp();

		$this->userSession = $this->getMockBuilder('\OCP\ISession')
			->disableOriginalConstructor()->getMock();
		$this->secureRandom = $this->getMockBuilder('\OCP\Security\ISecureRandom')
			->disableOriginalConstructor()->getMock();
		$this->crypto = $this->getMockBuilder('\OCP\Security\ICrypto')
			->disableOriginalConstructor()->getMock();

		$this->securityToken = new SecurityToken(
			$this->userSession,
			$this->secureRandom,
			$this->crypto
		);
	}

	public function testCalculateTokenWithoutExistingToken() {
		$this->secureRandom
			->expects($this->once())
			->method('getMediumStrengthGenerator')
			->will($this->returnSelf());
		$this->secureRandom
			->expects($this->once())
			->method('generate')
			->with(32)
			->will($this->returnValue('6Hjod8BHxWZNBE9ziWXxQQgZd4w9j2eJ'));

		$this->crypto
			->expects($this->once())
			->method('calculateHMAC')
			->with('/index.php/apps/mail/testURL', '6Hjod8BHxWZNBE9ziWXxQQgZd4w9j2eJ')
			->will($this->returnValue('ExpectedUnencodedToken'));

		$this->assertSame('RXhwZWN0ZWRVbmVuY29kZWRUb2tlbg==', $this->securityToken->calculateToken('/index.php/apps/mail/testURL'));
	}

	public function testCalculateTokenWithExistingToken() {
		$this->userSession
			->expects($this->once())
			->method('get')
			->with('mail.secret.key')
			->will($this->returnValue('StoredSecret'));

		$this->crypto
			->expects($this->once())
			->method('calculateHMAC')
			->with('/index.php/apps/mail/testURL', 'StoredSecret')
			->will($this->returnValue('ExpectedUnencodedToken'));

		$this->assertSame('RXhwZWN0ZWRVbmVuY29kZWRUb2tlbg==', $this->securityToken->calculateToken('/index.php/apps/mail/testURL'));
	}

	public function testVerifyTokenPass() {
		$this->userSession
			->expects($this->once())
			->method('get')
			->with('mail.secret.key')
			->will($this->returnValue('StoredSecret'));

		$this->crypto
			->expects($this->once())
			->method('calculateHMAC')
			->with('/index.php/apps/mail/testURL', 'StoredSecret')
			->will($this->returnValue('ExpectedUnencodedToken'));

		$this->assertTrue($this->securityToken->verifyToken('/index.php/apps/mail/testURL', 'RXhwZWN0ZWRVbmVuY29kZWRUb2tlbg=='));
	}

	public function testVerifyTokenFail() {
		$this->userSession
			->expects($this->once())
			->method('get')
			->with('mail.secret.key')
			->will($this->returnValue('StoredSecret'));

		$this->crypto
			->expects($this->once())
			->method('calculateHMAC')
			->with('/index.php/apps/mail/testURL', 'StoredSecret')
			->will($this->returnValue('ExpectedUnencodedToken'));

		$this->assertFalse($this->securityToken->verifyToken('/index.php/apps/mail/testURL', 'aWrongToken'));
	}
}
