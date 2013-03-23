<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Mail;

class Account {

	/**
	 * @var \OCA\Mail\Db\MailAccount
	 */
	private $mailAccount;
	/**
	 * @param an object of MailAccount as $mailAccount
	 */
	function __construct($mailAccount) {
		$this->mailAccount = $mailAccount;
	}

	/**
	 * @return int internal AccountId for this mail account
	 */
	public function getAccountId() {
		return $this->mailAccount->getMailAccountId();
	}

	public function getName() {
		return $this->mailAccount->getMailAccountName();
	}

	public function getEMailAddress() {
		return $this->mailAccount->getEmail();
	}

	/**
	 * @return a ready to use IMAP connection
	 */
	public function getImapConnection() {
		$host = $this->mailAccount->getInboundHost();
		$port = $this->mailAccount->getInboundHostPort();
		$user = $this->mailAccount->getInboundUser();
		$password = $this->mailAccount->getInboundPassword();
		$ssl_mode = $this->mailAccount->getInboundSslMode();
		$imapConnection = new \Horde_Imap_Client_Socket(array(
			'username' => $user, 'password' => $password, 'hostspec' => $host, 'port' => $port, 'secure' => $ssl_mode, 'timeout' => 2));
		$imapConnection->login();
		return $imapConnection;
	}


	/**
	 * @param $pattern
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
	 * @return Mailbox
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

		$folders = array();
		foreach ($mboxes as $mailbox) {
			$folders[] = $mailbox->getListArray();
		}

		$inbox = null;
		foreach ($folders as $key=>$value) {
			if ($value['id'] === 'INBOX') {
				  $inbox = $key;
			}
		}

		if ($inbox) {
			self::move_to_top($folders, $inbox);
		}

		return array('id' => $this->getAccountId(), 'email' => $this->getEMailAddress(), 'folders' => $folders);
	}

	private static function move_to_top(&$array, $key) {
		$temp = array($key => $array[$key]);
		unset($array[$key]);
		$array = $temp + $array;
	}
}
