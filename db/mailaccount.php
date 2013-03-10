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

namespace \OCA\mail\Db;

class MailAccount {

	private $ocUserId;
	private $mailAccountId;
	private $email;
	private $inboundHost;
	private $inboundHostPort;
	private $inboundSslMode;
	private $inboundUser;
	private $inboundPassword;
	private $inboundService;
	private $outboundHost;
	private $outboundHostPort;
	private $outboundSslMode;
	private $outboundUser;
	private $outboundPassword;
	private $outboundService;
	
	public function __construct($fromRow=null){
		if($fromRow){
			$this->fromRow($fromRow);
		}
	}
	
	public function getOcUserid(){
		return $this->ocUserId;
	}
	
	public function getMailAccountId(){
		return $this->mailAccountId;
	}
	
	public function getEmail(){
		return $this->email;
	}
	
	public function setEmail($email){
		$this->email = $email;
	}
	
	public function getInboundHost(){
		return $this->inboundHost;
	}
	
	public function setInboundHost($inboundHost){
		$this->inboundHost = $inboundHost;
	}
	
	public function getInboundHostPort(){
		return $this->inboundHostPort;
	}
	
	public function setInboundHostPort($inboundHostPort){
		$this->inboundHostPort = $inboundHostPort;
	}
	
	public function getInboundSslMode(){
		return $this->inboundSslMode;
	}
	
	public function setInboundSslMode($inboundSslMode){
		$this->inboundSslMode = $inboundSslMode;
	}
	
	public function getInboundUser(){
		return $this->inboundUser;
	}
	
	public function setInboundUser($inboundUser){
		$this->inboundUser = $inboundUser;
	}
	
	public function getInboundPassword(){
		return $this->decryptPassword($this->inboundPassword);
	}
	
	public function setInboundPassword($inboundPassword){
		$this->inboundPassword = $this->encryptPassword($inboundPassword);
	}
	
	public function getInboundService(){
		return $this->inboundService;
	}
	
	public function setInboundService($inboundService){
		$this->inboundService = $inboundService;
	}
	
	public function getOutboundHost(){
		return $this->outboundHost;
	}
	
	public function setOutboundHost($outboundHost){
		$this->outboundHost = $outboundHost;
	}
	
	public function getOutboundHostPort(){
		return $this->outboundHostPort;
	}
	
	public function setOutboundHostPort($outboundHostPort){
		$this->outboundHostPort = $outboundHostPort;
	}
	
	public function getOutboundSslMode(){
		return $this->outboundSslMode;
	}
	
	public function setOutboundSslMode($outboundSslMode){
		$this->outboundSslMode = $outboundSslMode;
	}
	
	public function getOutboundUser(){
		return $this->outboundUser;
	}
	
	public function setOutboundUser($outbounduser){
		$this->outboundUser = $outboundUser;
	}
	
	public function getOutboundPassword(){
		return $this->decryptPassword($this->outboundPassword);
	}
	
	public function setOutboundPassword($outboundPassword){
		$this->outboundPassword = $this->encryptPassword($outboundPassword);
	}
	
	public function getOutboundService(){
		return $this->outboundService;
	}
	
	public function setOutboundService($outboundService){
		$this->outboundService = $outboundService;
	}
	
	
	/**
	 * private functions
	 */
	
	private function fromRow($row){
		$this->ocUserId = $row['ocuserid'];
		$this->mailAccountId = $row['mailaccountid'];
		$this->email = $row['email'];
		$this->inboundHost = $row['inboundhost'];
		$this->inboundHostPort = $row['inboundhostport'];
		$this->inboundSslMode = $row['inboundsslmode'];
		$this->inboundUser = $row['inbounduser'];
		$this->inboundPassword = $row['inboundpassword'];
		$hits->inboundService = $row['inboundservice'];
		$this->outboundHost = $row['outboundhost'];
		$this->outboundHostPort = $row['outboundhostport'];
		$this->outboundSslMode = $row['outboundsslmode'];
		$this->outboundUser = $row['outbounduser'];
		$this->outboundPassword = $row['outboundpassword'];
		$this->outboundService = $row['outboundservice'];
	}
	
	/**
	 * @return the encrypted password as a string
	 */
	private function encryptPassword($decryptedPassword){
		return $this->ciphering($encrypted=0, $decryptedPassword);
	}
	
	/**
	 * @return the decrypted password as a string
	 */
	private function decryptPassword($encryptedPassword){
		return $this->ciphering($encrypted=1, $encryptedPassword);
	}
	
	/**
	 * This function does the encryption and decreption of the password
	 * @param bool $encrypted if 1 then the string $password will be decrypted
	 * @return the password encrypted or decrypted as a string
	 */
	private function ciphering($encrypted, $password, $salt='!oScf3b0!7w%rLd13'){
		// create the key which is a SHA256 hash of $salt and $ocUserId
		$key = hash('SHA256', $salt . $this->ocUserId, true);
		
		// open cipher using block size 128-bit to be AES compliant in CBC mode
		$td = mcrypt_module_open('rijndael-128', '', 'cbc', '');
		
		// create a initialization vector (iv)
		$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);
		
		// initialize encryption/decryption using the generated key
		mcrypt_generic_init($td, $key, $vi);
		
		if($encrypted){			
			// decrypt encrypted password
			$password = mdecrypt_generic($td, $encrypted);
			// close decryption handler
			mcrypt_generic_deinit($td);
		}else{
			// encrypt password
			$password = mcrypt_generic($td, $decrypted);
			// close encryption handler
			mcrypt_generic_deinit($td);
		}
		
		// close cipher
		mcrypt_module_close($td);
		
		return $password;
	}

}