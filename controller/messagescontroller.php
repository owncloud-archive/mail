<?php
/**
 * ownCloud - Mail app
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller thomas.mueller@tmit.eu
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

class MessagesController extends Controller
{

	/**
	 * @var \OCA\Mail\Db\MailAccountMapper
	 */
	private $mapper;

	/**
	 * @param \OCA\AppFramework\Http\Request $request: an instance of the request
	 * @param \OCA\AppFramework\Core\API $api: an api wrapper instance
	 * @param \OCA\Mail\Db\MailAccountMapper $mailAccountMapper: a mail account wrapper instance
	 *
	 * TODO: inject imap
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
		$mailBox = $this->getFolder();
		$json = $mailBox->getMessages();

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
		$mailBox = $this->getFolder();
		$messageId = $this->params('messageId');

		$m = $mailBox->getMessage($messageId);
		$json = $m->as_array();

		return new JSONResponse($json);
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @return \OCA\AppFramework\Http\JSONResponse
	 */
	public function update()
	{
		//
		// TODO: update is only valid for drafts
		//
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
			$mailBox = $this->getFolder();
			$messageId = $this->params('messageId');
			//
			// TODO: let's see how we implement delete
			//
			$mailBox->deleteMessage($messageId);

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
		//
		// TODO: create is only valid for drafts
		//
		$response = new Response();
		$response->setStatus(Http::STATUS_NOT_IMPLEMENTED);
		return $response;
	}

	/**
	 * TODO: private functions below have to be removed from controller -> imap service to be build
	 */

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
			'username' => $user,
			'password' => $password,
			'hostspec' => $host,
			'port' => $port,
			'secure' => $ssl_mode,
			'timeout' => 2));

		$imapConnection->login();
		return $imapConnection;
	}

	private function getAccount()
	{
		$userId = $this->api->getUserId();
		$accountId = $this->params('accountId');
		return $this->mapper->find($userId, $accountId);
	}

	/**
	 * @return \OCA\Mail\Mailbox
	 */
	private function getFolder()
	{
		$account = $this->getAccount();
		$m = new \OCA\Mail\Account($account);
		$folderId = $this->params('folderId');
		return $m->getMailbox($folderId);
	}

	private function getImap(MailAccount $account)
	{
		return $this->getImapConnection(
			$account->getInboundHost(),
			$account->getInboundHostPort(),
			$account->getInboundUser(),
			$account->getInboundPassword(),
			$account->getInboundSslMode()
		);
	}

}
