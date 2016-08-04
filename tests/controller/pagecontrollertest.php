<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use Test\TestCase;
use OCA\Mail\Controller\PageController;

class PageControllerTest extends TestCase {

	private $appName;
	private $request;
	private $userId;
	private $mailAccountMapper;
	private $urlGenerator;
	private $config;
	private $defaultAccountManager;
	private $controller;

	protected function setUp() {
		parent::setUp();

		$this->appName = 'mail';
		$this->userId = 'george';
		$this->request = $this->getMock('\OCP\IRequest');
		$this->mailAccountMapper = $this->getMockBuilder('OCA\Mail\Db\MailAccountMapper')
			->disableOriginalConstructor()
			->getMock();
		$this->urlGenerator = $this->getMock('OCP\IURLGenerator');
		$this->config = $this->getMock('OCP\IConfig');
		$this->defaultAccountManager = $this->getMockBuilder('OCA\Mail\Service\DefaultAccount\DefaultAccountManager')
			->disableOriginalConstructor()
			->getMock();
		$this->controller = new PageController($this->appName, $this->request, $this->mailAccountMapper,
			$this->urlGenerator, $this->config, $this->defaultAccountManager, $this->userId);
	}

	public function testIndex() {
		$this->defaultAccountManager->expects($this->once())
			->method('createOrUpdateDefaultAccount');

		$this->config->expects($this->at(0))
			->method('getSystemValue')
			->with('version', '0.0.0')
			->will($this->returnValue('8.2.0'));
		$this->config->expects($this->at(1))
			->method('getSystemValue')
			->with('debug', false)
			->will($this->returnValue(true));
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('mail', 'installed_version')
			->will($this->returnValue('1.2.3'));

		$expected = new TemplateResponse($this->appName, 'index', [
			'debug' => true,
			'app-version' => '1.2.3',
			'has-dav-support' => 0,
		]);
		// set csp rules for ownCloud 8.1
		if (class_exists('OCP\AppFramework\Http\ContentSecurityPolicy')) {
			$csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
			$csp->addAllowedFrameDomain('\'self\'');
			$expected->setContentSecurityPolicy($csp);
		}

		$response = $this->controller->index();

		$this->assertEquals($expected, $response);
	}

	public function testComposeSimple() {
		$address = 'user@example.com';
		$uri = "mailto:$address";

		$expected = new RedirectResponse('#mailto?to=' . urlencode($address));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

	public function testComposeWithSubject() {
		$address = 'user@example.com';
		$subject = 'hello there';
		$uri = "mailto:$address?subject=$subject";

		$expected = new RedirectResponse('#mailto?to=' . urlencode($address)
			. '&subject=' . urlencode($subject));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

	public function testComposeWithCc() {
		$address = 'user@example.com';
		$cc = 'other@example.com';
		$uri = "mailto:$address?cc=$cc";

		$expected = new RedirectResponse('#mailto?to=' . urlencode($address)
			. '&cc=' . urlencode($cc));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

	public function testComposeWithBcc() {
		$address = 'user@example.com';
		$bcc = 'blind@example.com';
		$uri = "mailto:$address?bcc=$bcc";

		$expected = new RedirectResponse('#mailto?to=' . urlencode($address)
			. '&bcc=' . urlencode($bcc));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

	public function testComposeWithMultilineBody() {
		$address = 'user@example.com';
		$body = 'Hi!\nWhat\'s up?\nAnother line';
		$uri = "mailto:$address?body=$body";

		$expected = new RedirectResponse('#mailto?to=' . urlencode($address)
			. '&body=' . urlencode($body));

		$response = $this->controller->compose($uri);

		$this->assertEquals($expected, $response);
	}

}
