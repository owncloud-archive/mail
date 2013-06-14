<?php
/**
 * Copyright (c) 2013 Thomas Müller
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Mail;

use OCA\AppFramework\routing\RouteConfig;
use \OCA\Mail\DependencyInjection\DIContainer;

/** @var $this \OC_Router */

// oC JS config
$this->create('mail_editor', 'js/mail_editor.js')
	->actionInclude('mail/js/mail_editor.php');

/*************************
 * Define your routes here
 ************************/

$routeConfig = new RouteConfig(new DIContainer(), $this, __DIR__ . '/routes.yaml');
$routeConfig->register();
