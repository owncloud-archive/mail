<?php
/**
 * ownCloud - Mail
 *
 * @author Sebastian Schmid
 * @copyright 2013 Sebastian Schmid mail@sebastian-schmid.de
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Db;

use OC\AppFramework\Db\Db;

class MailAccountMapperTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var MailAccountMapper
	 */
	private $mapper;

	/**
	 * @var \OC\DB\Connection
	 */
	private $db;

	/**
	 * @var MailAccount
	 */
	private $account;

	/**
	 * Initialize Mapper
	 */
	public function setup(){
		$this->db = new Db();
		$this->mapper = new MailAccountMapper($this->db);

		$this->account = new MailAccount();
		$this->account->setName('Peter Parker');
		$this->account->setInboundHost('mail.marvel.com');
		$this->account->setInboundPort(159);
		$this->account->setInboundUser('spiderman');
		$this->account->setInboundPassword('xxxxxxxx');
		$this->account->setInboundSslMode('tls');
		$this->account->setEmail('peter.parker@marvel.com');
		$this->account->setOutboundHost('smtp.marvel.com');
		$this->account->setOutboundPort(458);
		$this->account->setOutboundUser('spiderman');
		$this->account->setOutboundPassword('xxxx');
		$this->account->setOutboundSslMode('ssl');
		$this->account->setUserId('user12345');
	}

	public function testFind(){

		/** @var MailAccount $b */
		$b = $this->mapper->insert($this->account);

		$result = $this->mapper->find($b->getUserId(), $b->getId());
		$this->assertEquals($b->toJson(), $result->toJson());
	}

	public function testSave() {

		$a = $this->account;

		// test insert
		$b = $this->mapper->save($a);
		$this->assertNotNull($b);
		$this->assertNotNull($a->getId());
		$this->assertNotNull($b->getId());
		$this->assertEquals($a->getId(), $b->getId());

		// update the entity
		$b->setEmail('spiderman@marvel.com');
		$c = $this->mapper->save($b);
		$this->assertNotNull($c);
		$this->assertNotNull($c->getId());
		$this->assertNotNull($b->getId());
		$this->assertEquals($b->getId(), $c->getId());
	}

}
