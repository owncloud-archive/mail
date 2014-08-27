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
	 *  @var Mailbox[]
	 */
	private $mailboxes;

	/**
	 * @param MailAccount $info
	 */
	function __construct(MailAccount $account) {
		$this->account = $account;
		$this->mailboxes = null;
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
	private function listMailboxes($pattern) {
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
			$mailboxes[] = new Mailbox($conn, $mailbox['mailbox']->utf7imap, $mailbox['attributes'], $mailbox['delimiter']);
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

	protected function getMailboxes() {
		if ($this->mailboxes === null) {
			$this->mailboxes = $this->listMailboxes('*');
		}

		return $this->mailboxes;
	}
	/**
	 * @return array
	 */
	public function getListArray() {

		$folders = array();
		foreach ($this->getMailboxes() as $mailbox) {
			$folders[] = $mailbox->getListArray();
		}
		$folders = $this->sortFolders($folders);
		return array(
			'id'             => $this->getId(),
			'email'          => $this->getEMailAddress(),
			'folders'        => array_values($folders),
			'specialFolders' => $this->getSpecialFoldersIds()
		);
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

	public function getSpecialFoldersIds() {
		$folderRoles = array('inbox', 'sent', 'draft', 'trash', 'archive', 'junk');
		$specialFoldersIds = array();
		
		foreach ($folderRoles as $role) {
			$folder = $this->getSpecialFolder($role, true);
			$specialFoldersIds[$role] = empty($folder) ? null : base64_encode($folder->getFolderId());
		}
		return $specialFoldersIds;
	}

	public function getSentFolder() {
		return $this->getSpecialFolder('sent', true);
	}
	
	/*
	 * @param string $role Special role of the folder we want to get ('sent', 'inbox', etc.)
	 * @param bool $guessBest If set to true, return only the folder with the most messages in it
	 *
	 * @return Mailbox[] if $guessBest is false, or Mailbox if $guessBest is true
	 */ 
	protected function getSpecialFolder($role, $guessBest=true) {
		
		$specialFolders = array();
		foreach ($this->getMailboxes() as $mailbox) {
			if ($role === $mailbox->getSpecialRole()) {
				$specialFolders[] = $mailbox;
			}
		}

		if ($guessBest === true && count($specialFolders) > 0) {
			$maxMessages = 0;
			$maxFolder = reset($specialFolders);
			foreach ($specialFolders as $folder) {
				if ($folder->getTotalMessages() > $maxMessages) {
					$maxMessages = $folder->getTotalMessages();
					$maxFolder = $folder;
				}
			}
			return $maxFolder;
		} else {
			return $specialFolders;
		}
	}

	protected function sortFolders($folders) {
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
						'all'     => 0,
						'inbox'   => 1,
						'draft'   => 2,
						'sent'    => 3,
						'archive' => 4,
						'junk'    => 5,
						'trash'   => 6,
					);
					return $specialRolesOrder[$a['specialRole']] - $specialRolesOrder[$b['specialRole']];
				}
			} elseif ($a['specialRole'] === null && $b['specialRole'] === null) {
				return strcasecmp($a['name'], $b['name']);
			}
		});
		return $folders;
	}
}

