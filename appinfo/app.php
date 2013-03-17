<?php

//OC::$CLASSPATH['OCA\Mail\App'] = 'apps/mail/lib/mail.php';
//OC::$CLASSPATH['OCA\Mail\Account'] = 'apps/mail/lib/account.php';
//OC::$CLASSPATH['OCA\Mail\Mailbox'] = 'apps/mail/lib/mailbox.php';
//OC::$CLASSPATH['OCA\Mail\Message'] = 'apps/mail/lib/message.php';
OC::$CLASSPATH['OC_Translation_Handler'] = 'apps/mail/lib/OC_Translation_Handler.php';


/**
 * Navigation Entry
 */
\OCP\App::addNavigationEntry( array(
	
  	// the string under which your app will be referenced
  	// in owncloud, for instance: \OC_App::getAppPath('APP_ID')
  	'id' => 'mail',

  	// sorting weight for the navigation. The higher the number, the higher
  	// will it be listed in the navigation
  	'order' => 1,
	
  	// the route that will be shown on startup
  	'href' => \OC_Helper::linkToRoute('mail_index'),
	
  	// the icon that will be shown in the navigation
  	'icon' => \OCP\Util::imagePath('mail', 'mail.svg' ),
	
  	// the title of your application. This will be used in the
  	// navigation or on the settings page of your app
  	'name' => \OC_L10N::get('mail')->t('Mail App') 
	
));

//OCP\App::registerPersonal('mail','settings');
