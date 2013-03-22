<?php
/**
 * Copyright (c) 2013 Thomas Müller
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Mail;

use \OCA\AppFramework\App;

use \OCA\Mail\DependencyInjection\DIContainer;

// oC JS config
$this->create('mail_editor', 'js/mail_editor.js')
	->actionInclude('mail/js/mail_editor.php');

/*************************
 * Define your routes here
 ************************/

/**
 * Normal Routes
 */
$this->create('mail_index', '/')->action(
	function($params){
		App::main('PageController', 'index', $params, new DIContainer());
	}
);

$this->create('mail_accounts_create', '/accounts/create')->post()->action(
	function($params){
		App::main('MailAccountController', 'create', $params, new DIContainer());
	}
);
