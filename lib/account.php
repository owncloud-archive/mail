<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Mail;

use OCA\Mail\Cache\Cache;
use Horde_Imap_Client;
use OCA\Mail\Db\MailAccount;

class Account {

	/**
	 * @var MailAccount
	 */
	private $account;

	/**
	 * @param MailAccount $info
	 */
	function __construct(MailAccount $account) {
		$this->account = $account;
	}

	public function getId() {
		return $this->account->getId();
	}

	public function getName() {
		return $this->account->getName();
	}

	public function getEMailAddress() {
		return $this->account->getEmail();
	}

	public function getImapConnection() {
		$host = $this->account->getInboundHost();
		$user = $this->account->getInboundUser();
		$password = $this->account->getInboundPassword();
		$port = $this->account->getInboundPort();
		$ssl_mode = $this->account->getInboundSslMode();

		$client = new \Horde_Imap_Client_Socket(
			array(
				'username' => $user,
				'password' => $password,
				'hostspec' => $host,
				'port' => $port,
				'secure' => $ssl_mode,
				'timeout' => 2,
				'cache' => array(
					'backend' => new Cache(array(
							'cacheob' => \OC::$server->getCache()
						))
				)
			));
		$client->login();
		return $client;
	}

	/**
	 * @param string $pattern
	 * @return Mailbox[]
	 */
	public function listMailboxes($pattern) {
		// open the imap connection
		$conn = $this->getImapConnection();

		// if successful -> get all folders of that account
		$mboxes = $conn->listMailboxes($pattern, Horde_Imap_Client::MBOX_ALL, array(
			'attributes' => true,
			'special_use' => true,
			'sort' => true
		));

		$mailboxes = array();
		foreach ($mboxes as $mailbox) {
			$mailboxes[] = new Mailbox($conn, $mailbox['mailbox']->utf7imap, $mailbox['attributes']);
		}
		return $mailboxes;
	}

	/**
	 * @param $folderId
	 * @return \OCA\Mail\Mailbox
	 */
	public function getMailbox($folderId) {
		$conn = $this->getImapConnection();
		return new Mailbox($conn, $folderId, array());
	}

	/**
	 * @return array
	 */
	public function getListArray() {
		// if successful -> get all folders of that account
		$mboxes = $this->listMailboxes('*');
		$folders = array();
		foreach ($mboxes as $mailbox) {
			$folders[] = $mailbox->getListArray();
		}
		// sort mailboxes, with special folders at top
		usort($folders, function($a, $b) { 
			if ($a['specialRole'] === null && $b['specialRole'] !== null) {
				return 1;
			} elseif ($a['specialRole'] !== null && $b['specialRole'] === null) {
				return -1;
			} elseif ($a['specialRole'] !== null && $b['specialRole'] !== null) {
				if ($a['specialRole'] === $b['specialRole']) {
					return strcasecmp($a['name'], $b['name']);
				} else {
					$specialRolesOrder = array(
						'inbox'   => 0,
						'draft'   => 1,
						'sent'    => 2,
						'archive' => 3,
						'junk'    => 4,
						'trash'   => 5,
					);
					return $specialRolesOrder[$a['specialRole']] - $specialRolesOrder[$b['specialRole']];
				}
			} elseif ($a['specialRole'] === null && $b['specialRole'] === null) {
				return strcasecmp($a['name'], $b['name']);
			}
		});

		return array('id' => $this->getId(), 'email' => $this->getEMailAddress(), 'folders' => array_values($folders));
	}


	/**
	 * @return \Horde_Mail_Transport_Smtphorde
	 */
	public function createTransport() {
		$host = $this->account->getOutboundHost();
		$params = array(
			'host' => $host,
			'password' => $this->account->getOutboundPassword(),
			'port' => $this->account->getOutboundPort(),
			'username' => $this->account->getOutboundUser(),
			'secure' => $this->account->getOutboundSslMode(),
			'timeout' => 2
		);
		return new \Horde_Mail_Transport_Smtphorde($params);
	}

	public function getSentFolder() {
		//
		// TODO: read settings/server special folders how the sent folder is named
		//
		$conn = $this->getImapConnection();
		return new Mailbox($conn, 'Sent', array());
	}
}
