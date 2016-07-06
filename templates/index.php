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
style('mail','../js/vendor/trumbowyg/dist/ui/trumbowyg.min');
style('mail','../js/vendor/trumbowyg/dist/plugins/colors/ui/trumbowyg.colors.min');
style('mail', 'mail');
style('mail', 'mobile');
script('mail', 'vendor/autosize/jquery.autosize');
script('mail', 'vendor/jquery-storage-api/jquery.storageapi');
script('mail', 'vendor/jquery-visibility/jquery-visibility');
script('mail', 'vendor/requirejs/require');
script('mail', 'searchproxy');
if ($_['debug']) {
	// Load JS dependencies asynchronously as specified in require_config.js
	script('mail', 'require_config');
} else {
	// Load optimzed requirejs dependencies in one single file
	script('mail', 'mail.min');
}
?>

<input type="hidden" id="config-installed-version" value="<?php p($_['app-version']); ?>">
<input type="hidden" id="has-dav-support" value="<?php p($_['has-dav-support']); ?>">

<div id="user-displayname"
     style="display: none"><?php p(\OCP\User::getDisplayName(\OCP\User::getUser())); ?></div>
<div id="user-email"
     style="display: none"><?php p(\OCP\Config::getUserValue(\OCP\User::getUser(), 'settings', 'email', '')); ?></div>
<div id="app">
	<div id="app-navigation" class="icon-loading">
		<div id="mail-new-message-fixed"></div>
		<ul>
			<li id="app-navigation-accounts"></li>
		</ul>
		<div id="app-settings">
			<div id="app-settings-header">
				<button class="settings-button"
						data-apps-slide-toggle="#app-settings-content"></button>
			</div>
			<div id="app-settings-content"></div>
		</div>
	</div>
	<div id="app-content">
		<!-- This additional container div is only needed to make core's snapper toggle button work -->
		<div class="mail-content container">
			<!-- placeholder until Marionette has started, will be replaced by a LoadingView -->
			<div class="container icon-loading"></div>
		</div>
	</div>
</div>

