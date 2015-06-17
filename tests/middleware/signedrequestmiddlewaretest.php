<?php
/**
 * ownCloud
 *
 * @author Lukas Reschke
 * @copyright 2015 Lukas Reschke <lukas@owncloud.com>
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\mail\tests\middleware;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OCA\mail\lib\middleware\SignedRequestMiddleware;
use OCA\mail\lib\service\SecurityToken;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use Test\TestCase;

/**
 * Class SignedRequestMiddlewareTest
 *
 * @package OCA\mail\tests\middleware
 */
class SignedRequestMiddlewareTest extends TestCase {
	/** @var SignedRequestMiddleware */
	private $signedRequestMiddleware;
	/** @var ControllerMethodReflector */
	private $controllerMethodReflector;
	/** @var SecurityToken */
	private $securityToken;
	/** @var IRequest */
	private $request;

	public function setUp() {
		parent::setUp();

		$this->controllerMethodReflector = $this->getMockBuilder('\OC\AppFramework\Utility\ControllerMethodReflector')
			->disableOriginalConstructor()->getMock();
		$this->securityToken = $this->getMockBuilder('\OCA\mail\lib\service\SecurityToken')
			->disableOriginalConstructor()->getMock();
		$this->request = $this->getMockBuilder('\OCP\IRequest')
			->disableOriginalConstructor()->getMock();

		$this->signedRequestMiddleware = new SignedRequestMiddleware(
			$this->controllerMethodReflector,
			$this->securityToken,
			$this->request
		);
	}

	public function testBeforeControllerNoAnnotation() {
		/** @var Controller $controller */
		$controller = $this->getMockBuilder('\OCP\AppFramework\Controller')
			->disableOriginalConstructor()->getMock();

		$this->controllerMethodReflector
			->expects($this->once())
			->method('hasAnnotation')
			->with('SignedRequestRequired')
			->will($this->returnValue(false));
		$this->signedRequestMiddleware->beforeController($controller, 'GET');
	}
}
