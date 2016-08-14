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
use OC\AppFramework\Http;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\JSONResponse;
use OCA\Mail\Controller\AliasesController;
use OCP\IUserSession;

class AliasesControllerTest extends PHPUnit_Framework_TestCase {

	private $controller;
	private $appName = 'mail';
	private $request;
	private $aliasService;
	private $userId = 'john';
	private $userSession;
	private $user;

	public function setUp() {
		$this->request = $this->getMockBuilder('OCP\IRequest')
			->getMock();
		$this->aliasService = $this->getMockBuilder('OCA\Mail\Service\AliasesService')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$this->user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($this->user));

		$this->controller = new AliasesController($this->appName, $this->request, $this->aliasService, $this->userSession);
	}

	public function testIndex() {
		$aliases = [];
		$accountId = 28;

		$this->user->expects($this->once())
			->method('getUID')
			->will($this->returnValue($this->userId));

		$this->aliasService->expects($this->once())
			->method('findAll')
			->with($accountId, $this->userId)
			->will($this->returnValue($aliases));

		$response = $this->controller->index($accountId);

		$expectedResponse = new JSONResponse([
			[
				// complete this
			]
		]);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroy() {
		$aliasId = 10;

		$this->user->expects($this->once())
			->method('getUID')
			->will($this->returnValue($this->userId));

		$this->aliasService->expects($this->once())
			->method('delete')
			->with($this->equalTo($aliasId), $this->equalTo($this->userId));

		$response = $this->controller->destroy($aliasId);

		$expectedResponse = new JSONResponse();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreate() {
		$accountId = 28;
		$alias = "alias@marvel.com";
		$aliasName = "Peter Parker";

		$this->user->expects($this->once())
			->method('getUID')
			->will($this->returnValue($this->userId));

		$this->aliasService->expects($this->once())
			->method('create')
			->with($this->equalTo($accountId), $this->equalTo($alias), $this->equalTo($aliasName));

		$response = $this->controller->create($accountId, $alias, $aliasName);

		$expected = new JSONResponse([
			'alias' => $alias,
			'name' => $aliasName
		]);

		$this->assertEquals($expected, $response);
	}


}
