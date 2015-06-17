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
namespace OCA\mail\lib\middleware;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OCA\mail\lib\service\SecurityToken;
use OCP\AppFramework\Middleware;
use OCP\IRequest;

/**
 * Class SignedRequestMiddleware
 *
 * @package OCA\mail\lib\middleware
 */
class SignedRequestMiddleware extends Middleware {
	/** @var ControllerMethodReflector */
	protected $reflector;
	/** @var IRequest */
	private $request;
	/** @var SecurityToken */
	private $securityToken;

	/**
	 * @param ControllerMethodReflector $reflector
	 * @param SecurityToken $securityToken
	 * @param IRequest $request
	 */
	public function __construct(ControllerMethodReflector $reflector,
								SecurityToken $securityToken,
								IRequest $request) {
		$this->reflector = $reflector;
		$this->securityToken = $securityToken;
		$this->request = $request;
	}

	/**
	 * Check if sharing is enabled before the controllers is executed
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @throws \Exception
	 */
	public function beforeController($controller, $methodName) {
		if($this->reflector->hasAnnotation('SignedRequestRequired')) {

			$path = parse_url($this->request->getRequestUri())['path'];
			$token = $this->request->getParam('token', null);

			if(!$this->securityToken->verifyToken($path, $token)) {
				throw new \Exception('Request signing token is invalid.');
			}
		}
	}
}
