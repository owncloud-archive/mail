<?php

/**
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
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace {
	// add include path to this apps 3rdparty
	$incPath = __DIR__."/../3rdparty";
	set_include_path(get_include_path() . PATH_SEPARATOR . $incPath);

	// load Horde's auto loader
	require_once 'Horde/Autoloader/Default.php';

	OC::$CLASSPATH['OC_Translation_Handler'] = 'apps/mail/lib/OC_Translation_Handler.php';

	// bypass Horde Translation system
	Horde_Translation::setHandler('Horde_Imap_Client', new OC_Translation_Handler());
}

namespace OCA\Mail\DependencyInjection {

use OCA\AppFramework\DependencyInjection\DIContainer as BaseContainer;

/**
 * 3rd Party
 */
use OC_Translation_Handler;

/**
 * Controllers
 */
use OCA\Mail\Controller\AccountsController;
use OCA\Mail\Controller\PageController;

/**
 * Mappers
 */
use OCA\Mail\Db\MailAccountMapper;


class DIContainer extends BaseContainer {


	/**
	 * Define your dependencies in here
	 */
	public function __construct(){
		// tell parent container about the app name
		parent::__construct('mail');	

		// use this to specify the template directory
		$this['TwigTemplateDirectory'] = __DIR__ . '/../templates';

		// if you want to cache the template directory in yourapp/cache
		// uncomment this line. Remember to give your webserver access rights
		// to the cache folder 
		// $this['TwigTemplateCacheDirectory'] = __DIR__ . '/../cache';		
		

		/** 
		 * Controllers
		 */
		$this['MailAccountController'] = $this->share(function($c){
			return new AccountsController($c['API'], $c['Request'], $c['MailAccountMapper']);
		});
		$this['PageController'] = $this->share(function($c){
			return new PageController($c['API'], $c['Request'], $c['MailAccountMapper']);
		});
		/*
		$this['SettingsController'] = $this->share(function($c){
			return new SettingsController($c['API'], $c['Request']);
		});*/


		/**
		 * Mappers
		 */
		$this['MailAccountMapper'] = $this->share(function($c){
			return new MailAccountMapper($c['API']);
		});


	}
}

}