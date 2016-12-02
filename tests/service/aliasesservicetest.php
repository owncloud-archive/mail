<?php

/**
 * @author Tahaa Karim <tahaalibra@gmail.com>
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

namespace OCA\Mail\Tests\Service\Autocompletion;

use OCA\Mail\Db\Alias;
use Test\TestCase;
use OCA\Mail\Service\AliasesService;

class AliasesServiceTest extends TestCase {

	private $service;
	private $user = 'herbert';
	private $mapper;
	private $alias;

	protected function setUp() {
		parent::setUp();

		$this->mapper = $this->getMockBuilder('OCA\Mail\Db\AliasMapper')
			->disableOriginalConstructor()
			->getMock();
		$this->service = new AliasesService($this->mapper);
		$this->alias = $this->getMockBuilder('OCA\Mail\Db\Alias')
			->disableOriginalConstructor()
			->getMock();
	}

	public function testFindAll() {
		$accountId = 123;

		$this->mapper->expects($this->once())
			->method('findAll')
			->with($accountId, $this->user)
			->will($this->returnValue([$this->alias]));

		$expected = [
			$this->alias
		];
		$actual = $this->service->findAll($accountId, $this->user);

		$this->assertEquals($expected, $actual);

	}

	public function testFind() {
		$aliasId = 123;

		$this->mapper->expects($this->once())
			->method('find')
			->with($aliasId, $this->user)
			->will($this->returnValue($this->alias));

		$expected = $this->alias;
		$actual = $this->service->find($aliasId, $this->user);

		$this->assertEquals($expected, $actual);

	}

	public function testCreate() {
		$accountId = 123;
		$alias = "alias@marvel.com";
		$aliasName = "alias";

		$aliasEntity = new Alias();
		$aliasEntity->setAccountId($accountId);
		$aliasEntity->setAlias($alias);
		$aliasEntity->setName($aliasName);

		$this->mapper->expects($this->once())
			->method('insert')
			->with($aliasEntity)
		    ->will($this->returnValue($aliasEntity));

		$result = $this->service->create($accountId, $alias, $aliasName);

		$this->assertEquals(
			[
				'accountId' => $aliasEntity->getAccountId(),
				'name' => $aliasEntity->getName(),
				'alias' => $aliasEntity->getAlias(),
				'id' => $aliasEntity->getId()
			],
			[
				'accountId' => $result->getAccountId(),
				'name' => $result->getName(),
				'alias' => $result->getAlias(),
				'id' => $result->getId()
			]
		);

	}

	public function testDelete() {
		$aliasId = 123;

		$this->mapper->expects($this->once())
			->method('find')
			->with($aliasId, $this->user)
			->will($this->returnValue($this->alias));
		$this->mapper->expects($this->once())
			->method('delete')
			->with($this->alias);

		$this->service->delete($aliasId, $this->user);

	}

}
