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

namespace OCA\Mail\Db;

use OCA\AppFramework\Db\DoesNotExistException;
use OCA\AppFramework\Db\Mapper;
use OCA\AppFramework\Core\API;

class MailAccountMapper extends Mapper {

	private $tableName;

	/**
	 * @param \OCA\AppFramework\Core\API $api Instance of the API abstraction layer
	 */
	public function __construct($api){
		parent::__construct($api);
		$this->tableName = '*PREFIX*mail_mailaccounts';
	}

	/** Finds an Mail Account by id
	 *
	 * @param $mailAccountId
	 * @return MailAccount
	 */
	public function find($mailAccountId){
		$row = $this->findQuery($this->tableName, $mailAccountId);
		return new MailAccount($row);
	}

	/**
	 * Finds all Mail Accounts by user id existing for this user
	 * @param string $ocUserId the id of the user that we want to find
	 * @param $ocUserId
	 * @return array
	 * @throws \OCA\AppFramework\Db\DoesNotExistException
	 * @throws DoesNotExistException if no Mail Account exists
	 */
	public function findByUserId($ocUserId){
		$sql = 'SELECT * FROM ' . $this->tableName . ' WHERE ocuserid = ?';
		$params = array($ocUserId);

		$results = $this->execute($sql, $params)->fetchRow();
		if($results){
			$mailAccounts = array();
			foreach ($results as $result){
				$mailAccount = new MailAccount($result);
				$mailAccounts[] = $mailAccount;
			}
			return $mailAccounts;
		}else{
			throw new DoesNotExistException('There are no Mail Accounts configured for user id ' . $ocUserId);
		}
	}

	/**
	 * Saves an User Account into the database
	 * @param MailAccount $mailAccount
	 * @internal param \OCA\Mail\Db\Account $User $userAccount the User Account to be saved
	 * @return MailAccount with the filled in mailaccountid
	 */
	public function save($mailAccount){
		$sql = 'INSERT INTO ' . $this->tableName . '(
			 ocuserid,
			 mailaccountid,
			 mailaccountname,
			 email,
			 inboundhost,
			 inboundHostPort,
			 inboundsslmode,
			 inbounduser,
			 inboundpassword,
			 inboundservice,
			 outboundhost,
			 outboundhostport,
			 outboundsslmode,
			 outbounduser,
			 outboundpassword,
			 outboundservice
			 )' . 'VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

		$params = array(
			$mailAccount->getOcUserId(),
			$mailAccount->getMailAccountId(),
			$mailAccount->getMailAccountName(),
			$mailAccount->getEmail(),
			$mailAccount->getInboundHost(),
			$mailAccount->getInboundHostPort(),
			$mailAccount->getInboundSslMode(),
			$mailAccount->getInboundUser(),
			$mailAccount->getInboundPassword(),
			$mailAccount->getInboundService(),
			$mailAccount->getOutboundHost(),
			$mailAccount->getOutboundHostPort(),
			$mailAccount->getOutboundSslMode(),
			$mailAccount->getOutboundUser(),
			$mailAccount->getOutboundPassword(),
			$mailAccount->getOutboundService()
		);

		$this->execute($sql, $params);

		return $mailAccount;
	}

	/**
	 * Updates a Mail Account
	 * @param  MailAccount $mailAccount
	 */
	public function update($mailAccount){
		$sql = 'UPDATE ' . $this->tableName . 'SET
		 	mailaccountname = ?,
		 	email = ?,
		 	inboundhost = ?,
		 	inboundHostPort = ?,
		 	inboundsslmode = ?,
		 	inbounduser = ?,
		 	inboundpassword = ?,
		 	inboundservice = ?,
		 	outboundhost = ?,
		 	outboundhostport = ?,
		 	outboundsslmode = ?,
		 	outbounduser = ?,
		 	outboundpassword = ?,
		 	outboundservice = ?
			WHERE mailaccountid = ?';

		$params = array(
			$mailAccount->getMailAccountName(),
			$mailAccount->getEmail(),
			$mailAccount->getInboundHost(),
			$mailAccount->getInboundHostPort(),
			$mailAccount->getInboundSslMode(),
			$mailAccount->getInboundUser(),
			$mailAccount->getInboundPassword(),
			$mailAccount->getInboundService(),
			$mailAccount->getOutboundHost(),
			$mailAccount->getOutboundHostPort(),
			$mailAccount->getOutboundSslMode(),
			$mailAccount->getOutboundUser(),
			$mailAccount->getOutboundPassword(),
			$mailAccount->getOutboundService(),
			$mailAccount->getMailAccountId()
		);

		$this->execute($sql, $params);
	}

	/**
	 * @param int $mailAccountId
	 */
	public function delete($mailAccountId){
		$this->deleteQuery($this->tableName, $mailAccountId);
	}

}