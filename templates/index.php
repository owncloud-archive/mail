<?php
/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author colm <mail@colm.be>
 * @author Damien <dcosset@hotmail.fr>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Imbreckx <zinks@iozero.be>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * ownCloud - Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
// TODO: remove DEBUG constant check once minimum oc
// core version >= 8.2, see https://github.com/owncloud/core/pull/18510
$debug = (defined('DEBUG') && DEBUG) || \OC::$server->getConfig()->getSystemValue('debug', false);

style('mail', 'mail');
style('mail', 'mobile');
script('mail', 'vendor/autosize/jquery.autosize');
script('mail', 'vendor/jquery-storage-api/jquery.storageapi');
script('mail', 'vendor/jquery-visibility/jquery-visibility');
script('mail', 'vendor/requirejs/require');
script('mail', 'searchproxy');
if ($debug) {
	// Load JS dependencies asynchronously as specified in require_config.js
	script('mail', 'require_config');
} else {
	// Load optimzed requirejs dependencies in one single file
	script('mail', 'mail.min');
}
?>

<div id="user-displayname"
     style="display: none"><?php p(\OCP\User::getDisplayName(\OCP\User::getUser())); ?></div>
<div id="user-email"
     style="display: none"><?php p(\OCP\Config::getUserValue(\OCP\User::getUser(), 'settings', 'email', '')); ?></div>
<div id="app">
	<div id="app-navigation"></div>
	<div id="app-content"></div>
</div>
