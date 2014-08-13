<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Mail;

use OCA\Mail\Cache\Cache;
use OCA\Mail\Db\MailAccount;

class Account {
	private $info;

	// input $conn = IMAP conn, $folder_id = folder id
	function __construct($info) {
		$this->info = $info;
		if ($info instanceof MailAccount) {
			$this->info = array(
				'host' => $info->getInboundHost(),
				'user' => $info->getInboundUser(),
				'password' => $info->getInboundPassword(),
				'port' => $info->getInboundHostPort(),
				'ssl_mode' => $info->getInboundSslMode(),
				'id' => $info->getMailAccountId(),
				'email' => $info->getEmail(),
				'name' => $info->getMailAccountName(),
			);
		}
	}

	public function getId() {
		return $this->info['id'];
	}

	public function getName() {
		return $this->info['name'];
	}

	public function getEMailAddress() {
		return $this->info['email'];
	}

	public function getImapConnection() {
		//
		// TODO: cache connections for / within accounts???
		//
		$host = $this->info['host'];
		$user = $this->info['user'];
		$password = $this->info['password'];
		$port = $this->info['port'];
		$ssl_mode = $this->info['ssl_mode'];

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
		$mboxes = $conn->listMailboxes($pattern);
		
		$mailboxes = array();
		foreach ($mboxes as $mailbox) {
			$mailboxes[] = new Mailbox($conn, $mailbox['mailbox']->utf7imap);
		}
		return $mailboxes;
	}

	/**
	 * @param $folder_id
	 * @return \OCA\Mail\Mailbox
	 */
	public function getMailbox($folder_id) {
		$conn = $this->getImapConnection();
		return new Mailbox($conn, $folder_id);
	}

	/**
	 * @return array
	 */
	public function getListArray() {
		// if successful -> get all folders of that account
		$mboxes = $this->listMailboxes('*');
		// sort mailboxes
		usort($mboxes, function($a, $b) {
			if ($a->getFolderId() === 'INBOX') {
				$result = -1;
			} elseif ($b->getFolderId() === 'INBOX') {
				$result = 1;
			} elseif (strpos($a->getFolderId(), 'INBOX')===0 && strpos($b->getFolderId(),'INBOX')===false) {
				$result = -1;
			} elseif (strpos($b->getFolderId(), 'INBOX')===0 && strpos($a->getFolderId(),'INBOX')===false) {
				$result = 1;
			} else {
				$result = strcasecmp($a->getFolderId(), $b->getFolderId());
			}
			return $result;
		});
		
		$folders = array();
		foreach ($mboxes as $mailbox) {
			$folders[] = $mailbox->getListArray();
		}
		

		return array('id' => $this->getId(), 'email' => $this->getEMailAddress(), 'folders' => array_values($folders));
	}

	private static function move_to_top(&$array, $key) {
		$temp = array($key => $array[$key]);
		unset($array[$key]);
		$array = $temp + $array;
	}

	/**
	 * @return \Horde_Mail_Transport_Sendmail
	 */
	public function createTransport() {
		//
		// TODO: implement according to the SMTP settings
		//
		return new \Horde_Mail_Transport_Sendmail();
	}

	public function getSentFolder() {
		//
		// TODO: read settings/server special folders how the sent folder is named
		//
		$conn = $this->getImapConnection();
		return new Mailbox($conn, 'Sent');
	}
}
