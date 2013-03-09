<?php
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
	private $outboundHost;
	private $outboundHostPort;
	private $outboundSslMode;
	private $outboundUser;
	private $outboundPassword;
	
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
		return $this->inboundPassword;
	}
	
	public function setInboundPassword($inboundPassword){
		$this->inboundPassword = $inboundPassword;
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
		return $this->outboundPassword;
	}
	
	public function setOutboundPassword($outboundPassword){
		$this->outboundPassword = $outboundpassword;
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
		$this->outboundHost = $row['outboundhost'];
		$this->outboundHostPort = $row['outboundhostport'];
		$this->outboundSslMode = $row['outboundsslmode'];
		$this->outboundUser = $row['outbounduser'];
		$this->outboundPassword = $row['outboundpassword'];
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
	private function ciphering($encrypted, $password, $salt='!kQm*fF3pXe1Kbm%9'){
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