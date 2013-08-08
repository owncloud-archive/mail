<?php
/**
 * ownCloud - Mail app
 *
 * @author Sebastian Schmid
 * @copyright 2013 Sebastian Schmid mail@sebastian-schmid.de
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

namespace OCA\Mail\Controller;

use OCA\AppFramework\Controller\Controller;


use OCA\AppFramework\Db\DoesNotExistException;
use OCA\AppFramework\Http\Http;
use OCA\AppFramework\Http\JSONResponse;
use OCA\AppFramework\Http\NotFoundResponse;
use OCA\AppFramework\Http\Response;
use OCA\Mail\Db\MailAccount;

class AccountsController extends Controller
{

	/**
	 * @var \OCA\Mail\Db\MailAccountMapper
	 */
	private $mapper;

	/**
	 * @param \OCA\AppFramework\Http\Request $request: an instance of the request
	 * @param \OCA\AppFramework\Core\API $api: an api wrapper instance
	 * @param \OCA\Mail\Db\MailAccountMapper $mailAccountMapper: a mail account wrapper instance
	 */
	public function __construct($api, $request, $mailAccountMapper)
	{
		parent::__construct($api, $request);
		$this->mapper = $mailAccountMapper;
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @return \OCA\AppFramework\Http\JSONResponse
	 */
	public function index()
	{
		$userId = $this->api->getUserId();
		$mailAccounts = $this->mapper->findByUserId($userId);

		$json = array();
		foreach ($mailAccounts as $mailAccount) {
			$json[] = $mailAccount->toJson();
		}

		return new JSONResponse($json);
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @return \OCA\AppFramework\Http\JSONResponse
	 */
	public function show()
	{
		try {
			$accountId = $this->params('accountId');
			$userId = $this->api->getUserId();
			$account = $this->mapper->find($userId, $accountId);

			return new JSONResponse($account->toJson());
		} catch (DoesNotExistException $e) {
			return new NotFoundResponse();
		}
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @return \OCA\AppFramework\Http\JSONResponse
	 */
	public function update()
	{
		$response = new Response();
		$response->setStatus(Http::STATUS_NOT_IMPLEMENTED);
		return $response;
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @return \OCA\AppFramework\Http\JSONResponse
	 */
	public function destroy()
	{
		try {
			$accountId = $this->params('accountId');
			$userId = $this->api->getUserId();
			$this->mapper->find($userId, $accountId);
			$this->mapper->delete($accountId);

			return new JSONResponse();
		} catch (DoesNotExistException $e) {
			return new NotFoundResponse();
		}
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @return \OCA\AppFramework\Http\JSONResponse
	 */
	public function create()
	{
		$ocUserId = $this->api->getUserId();
		if ($this->params('auto-detect') === true) {
			$email = $this->params('email');
			$password = $this->params('password');
			$newAccountId = $this->createAutoDetected($email, $ocUserId, $password);
		} else {
			$email = $this->params('email');
			$inboundHost = $this->params('imap-server');
			$inboundHostPort = $this->params('imap-port');
			$inboundUser = $this->params('imap-user');
			$inboundPassword = $this->params('imap-password');
			$inboundSslMode = $this->params('imap-ssl-mode');

			$newAccountId = $this->addAccount($ocUserId, $email, $inboundHost, $inboundHostPort,
				$inboundUser, $inboundPassword, $inboundSslMode);
		}

		if ($newAccountId) {
			return new JSONResponse(
				array('data' => array('id' => $newAccountId)),
				Http::STATUS_CREATED);
		}

		$l = $this->api->getTrans();
		return new JSONResponse(
			array('message' => $l->t('Auto detect failed. Please use manual mode.')),
			HTTP::STATUS_BAD_REQUEST);
	}

	/**
	 * TODO: private functions below have to be removed from controller -> repository pattern ???
	 */

	/**
	 * check if the host is Google Apps
	 */
	private function isGoogleAppsAccount($host)
	{
		// filter pure gmail accounts
		if (stripos($host, 'google') === true OR stripos($host, 'gmail') === true) {
			return true;
		}

		//
		// TODO: will not work on windows - ignore this for now
		//
		if (getmxrr($host, $mx_records, $mx_weight) === false) {
			return false;
		}

		if (stripos($mx_records[0], 'google') === true) {
			return true;
		}

		return false;
	}

	/**
	 * try to log into the mail account using different ports
	 * and use SSL if available
	 * IMAP - port 143
	 * Secure IMAP (IMAP4-SSL) - port 585
	 * IMAP4 over SSL (IMAPS) - port 993
	 */
	private function testAccount($ocUserId, $email, $host, $user, $password)
	{
		$account = array(
			'name' => $email,
			'host' => $host,
			'user' => $user,
			'password' => $password,
		);

		$ports = array(143, 585, 993);
		$encryptionProtocols = array('ssl', 'tls', null);
		$hostPrefixes = array('', 'imap.');
		foreach ($hostPrefixes as $hostPrefix) {
			$url = $hostPrefix . $host;
			$account['host'] = $url;
			foreach ($ports as $port) {
				$account['port'] = $port;
				foreach ($encryptionProtocols as $encryptionProtocol) {
					$account['ssl_mode'] = $encryptionProtocol;
					try {
						$this->getImapConnection($url, $port, $user, $password, $encryptionProtocol);
						$this->api->log("Test-Account-Successful: $ocUserId, $url, $port, $user, $encryptionProtocol");
						return $this->addAccount($ocUserId, $email, $url, $port, $user, $password, $encryptionProtocol);
					} catch (\Horde_Imap_Client_Exception $e) {
						$this->api->log("Test-Account-Failed: $ocUserId, $url, $port, $user, $encryptionProtocol");
					}
				}
			}
		}
		return null;
	}

	/**
	 * @param string $host
	 * @param int $port
	 * @param string $user
	 * @param string $password
	 * @param string $ssl_mode
	 * @return \Horde_Imap_Client_Socket a ready to use IMAP connection
	 */
	private function getImapConnection($host, $port, $user, $password, $ssl_mode)
	{
		$imapConnection = new \Horde_Imap_Client_Socket(array(
			'username' => $user, 'password' => $password, 'hostspec' => $host, 'port' => $port, 'secure' => $ssl_mode, 'timeout' => 2));
		$imapConnection->login();
		return $imapConnection;
	}

	/**
	 * Saves the mail account credentials for a users mail account
	 *
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @param $ocUserId
	 * @param $email
	 * @param $inboundHost
	 * @param $inboundHostPort
	 * @param $inboundUser
	 * @param $inboundPassword
	 * @param $inboundSslMode
	 * @return int MailAccountId
	 */
	private function addAccount($ocUserId, $email, $inboundHost, $inboundHostPort, $inboundUser, $inboundPassword, $inboundSslMode)
	{

		$mailAccount = new MailAccount();
		$mailAccount->setOcUserId($ocUserId);
		$mailAccount->setMailAccountId(time());
		$mailAccount->setMailAccountName($email);
		$mailAccount->setEmail($email);
		$mailAccount->setInboundHost($inboundHost);
		$mailAccount->setInboundHostPort($inboundHostPort);
		$mailAccount->setInboundSslMode($inboundSslMode);
		$mailAccount->setInboundUser($inboundUser);
		$mailAccount->setInboundPassword($inboundPassword);

		$this->mapper->save($mailAccount);

		return $mailAccount->getMailAccountId();
	}

	/**
	 * @param $email
	 * @param $ocUserId
	 * @param $password
	 * @return int|null
	 */
	private function createAutoDetected($email, $ocUserId, $password)
	{
		// splitting the email address into user and host part
		list($user, $host) = explode("@", $email);

		/**
		 * Google Apps
		 * used if $host points to Google Apps
		 */
		if ($this->isGoogleAppsAccount($host)) {
			return $this->testAccount($ocUserId, $email, "imap.gmail.com", $email, $password);
		}

		/*
		 * IMAP login with full email address as user
		 * works for a lot of providers (e.g. Google Mail)
		 */
		return $this->testAccount($ocUserId, $email, $host, $email, $password);
	}

}
