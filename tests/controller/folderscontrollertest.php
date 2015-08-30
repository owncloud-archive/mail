<?php

/**
 * ownCloud - Mail app
 *
 * @author Christoph Wurst
 * @copyright 2015 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
use OC\AppFramework\Http;
use OCA\Mail\Controller\FoldersController;

class FoldersControllerTest extends PHPUnit_Framework_TestCase {

	private $controller;
	private $appName = 'mail';
	private $request;
	private $accountService;
	private $userId = 'john';

	public function setUp() {
		$this->request = $this->getMockBuilder('OCP\IRequest')
			->getMock();
		$this->accountService = $this->getMockBuilder('OCA\Mail\Service\AccountService')
			->disableOriginalConstructor()
			->getMock();
		$this->controller = new FoldersController($this->appName, $this->request, $this->accountService, $this->userId);
	}

	public function folderDataProvider() {
		$files = [
		    'folders_german',
		];
		// Add directory prefix to tests/data
		$data = array_map(function ($file) {
			$path = dirname(__FILE__) . '/../data/' . $file . '.json';
			return [json_decode(file_get_contents($path), true)];
		}, $files);

		// Add empty account = no folders
		array_push($data, [
		    [
			'folders' => [
			],
		    ],
		]);

		return $data;
	}

	/**
	 * @dataProvider folderDataProvider
	 */
	public function testIndex($data) {
		$account = $this->getMockBuilder('OCA\Mail\Account')
			->disableOriginalConstructor()
			->getMock();
		$accountId = 28;
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $accountId)
			->will($this->returnValue($account));
		$account->expects($this->once())
			->method('getListArray')
			->will($this->returnValue($data));

		$this->controller->index($accountId);

		//TODO: check result
	}

	public function testShow() {
		$result = $this->controller->show();
		$this->assertEquals(Http::STATUS_NOT_IMPLEMENTED, $result->getStatus());
	}

	public function testUpdate() {
		$result = $this->controller->update();
		$this->assertEquals(Http::STATUS_NOT_IMPLEMENTED, $result->getStatus());
	}

	public function testDestroy() {
		$accountId = 28;
		$folderId = 'my folder';
		$account = $this->getMockBuilder('OCA\Mail\Account')
			->disableOriginalConstructor()
			->getMock();
		$this->accountService->expects($this->once())
			->method('find')
			->with($this->userId, $accountId)
			->will($this->returnValue($account));
		$imapConnection = $this->getMockBuilder('Horde_Imap_Client_Socket')
			->disableOriginalConstructor()
			->getMock();
		$account->expects($this->once())
			->method('getImapConnection')
			->will($this->returnValue($imapConnection));
		$imapConnection->expects($this->once())
			->method('deleteMailbox')
			->with($folderId);

		$this->controller->destroy($accountId, $folderId);
	}

}
