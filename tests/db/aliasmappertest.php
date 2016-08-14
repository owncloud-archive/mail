<?php

/**
 * @author Tahaa Karim <tahaalibra@gmail.com>
 *
 * ownCloud - Mail
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

namespace OCA\Mail\Db;

use OC\AppFramework\Db\Db;

class AliasMapperTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var AliasMapper
	 */
	private $mapper;

	/**
	 * @var \OC\DB\Connection
	 */
	private $db;

	/**
	 * @var Alias
	 */
	private $alias;

	/**
	 * Initialize Mapper
	 */
	public function setup(){
		$db = \OC::$server->getDatabaseConnection();
		$this->db = new Db($db);
		$this->mapper = new AliasMapper($this->db);

		$this->alias = new Alias();
		$this->alias->setAccountId(1);
		$this->alias->setAlias('alias@marvel.com');
		$this->alias->setName('alias');
	}

	public function testFind(){

		$userId = 123;
		/** @var MailAccount $b */
		$b = $this->mapper->insert($this->alias);

		$result = $this->mapper->find($b->getId(), $userId);
		$this->assertEquals($b, $result);
	}

}
