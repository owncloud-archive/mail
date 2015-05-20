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

namespace OCA\mail\lib\service;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use OCP\ISession;
use OCP\Security\StringUtils;

/**
 * Class SecurityToken provides security tokens. Essentially this is used for
 * CSRF protection but we can't use ownCloud's CSRF function here since this
 * token is valid for a whole ownCloud instance and not a limited scope.
 *
 * Leaking ownCloud's CSRF token would thus be a potential security risk. Thus
 * we have another generic solution in the mail app. Based on the URL.
 *
 * This protection is only suitable to protect routes following a REST pattern
 * and not using GET or POST parameters.
 *
 * @package OCA\mail\lib\service
 */
class SecurityToken {
	/** @var ISession */
	private $userSession;
	/** @var ISecureRandom */
	private $secureRandom;
	/** @var string Identifier to store the key in */
	private $identifier = 'mail.secret.key';
	/** @var ICrypto */
	private $crypto;

	/**
	 * @param ISession $userSession
	 * @param ISecureRandom $secureRandom
	 * @param ICrypto $crypto
	 */
	public function __construct(ISession $userSession,
								ISecureRandom $secureRandom,
								ICrypto $crypto) {
		$this->userSession = $userSession;
		$this->secureRandom = $secureRandom;
		$this->crypto = $crypto;
	}

	/**
	 * @param string $uri
	 * @param string $token
	 * @return bool True if token matches the expected token
	 */
	public function verifyToken($uri, $token) {
		if(StringUtils::equals($this->calculateToken($uri), $token)) {
			return true;
		}

		return false;
	}

	/**
	 * Calculate the secret token
	 *
	 * @param string $uri
	 * @return string
	 */
	public function calculateToken($uri) {
		return base64_encode($this->crypto->calculateHMAC($uri, $this->getSecretKey()));
	}

	/**
	 * Get the secret key assigned to the session and if none exists create one
	 *
	 * @return string
	 */
	private function getSecretKey() {
		$secret = $this->userSession->get($this->identifier);
		if(is_null($secret)) {
			$secret = $this->secureRandom->getMediumStrengthGenerator()->generate(32);
			$this->userSession->set($this->identifier, $secret);
		}

		return $secret;
	}

}
