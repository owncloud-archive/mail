<?php

namespace OCA\Mail;

/**
 * Navigation Entry
 */
$api = new \OCA\AppFramework\Core\API('mail');

$api->addNavigationEntry(array(
	
  	// the string under which your app will be referenced
  	// in owncloud, for instance: \OC_App::getAppPath('APP_ID')
	'id' => $api->getAppName(),

  	// sorting weight for the navigation. The higher the number, the higher
  	// will it be listed in the navigation
	'order' => 1,
	
  	// the route that will be shown on startup
	'href' => $api->linkToRoute('mail_index'),
	
  	// the icon that will be shown in the navigation
	'icon' => $api->imagePath('mail.svg'),
	
  	// the title of your application. This will be used in the
  	// navigation or on the settings page of your app
	'name' => $api->getTrans()->t('Mail')
));

//OCP\App::registerPersonal('mail','settings');
