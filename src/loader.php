<?php

define( 'WORPIT_DS',						DIRECTORY_SEPARATOR );
define( 'WORPIT_LOADER_PATH',				dirname(__FILE__) );
define( 'WORPIT_VIEWS_PATH',				realpath( dirname(__FILE__).'/../views' ) );
define( 'WORPIT_PHP_ERROR_LOG',				realpath( dirname(__FILE__).'/../php_error_log' ) );
define( 'WORPIT_USER_AGENT',				'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.5 (KHTML, like Gecko) Chrome/19.0.1084.52 Safari/536.5' ); // 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)'
define( 'WORPIT_VERIFICATION_TEST_URL', 	'http://worpitapp.com/dashboard/system/verification/test/' );
define( 'WORPIT_VERIFICATION_CHECK_URL', 	'http://worpitapp.com/dashboard/system/verification/check/' );

/**
 * We want to mimic that we are WordPress admin when running any of this code.
 * It should be clear to know that the core plugin file "worpit.php" does not
 * include this file -infact it should never include this file.
 */
if ( !defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}

/**
 * Thirdparty inclusions
 */
require_once( dirname(__FILE__).'/functions/JSON.php' );

/**
 * Worpit specific functions
 */
require_once( dirname(__FILE__).'/functions/core.php' );
require_once( dirname(__FILE__).'/functions/filesystem.php' );
require_once( dirname(__FILE__).'/functions/svn.php' );

/**
 * Core classes
 */
require_once( dirname(__FILE__).'/controllers/base.php' );
require_once( dirname(__FILE__).'/controllers/transport.php' );
require_once( dirname(__FILE__).'/helper/wordpress.php' );
require_once( dirname(__FILE__).'/plugin/base.php' );

/**
 * We want full error reporting and handling so that eventually we can help people fix their blogs and also
 * because our responses are wrapped, then we can
 */
if ( worpitFunctionExists( 'ini_set' ) ) {
	@ini_set( 'error_log',		WORPIT_PHP_ERROR_LOG );
	@ini_set( 'log_errors',		1 );
	@ini_set( 'display_errors',	1 );
}

/**
 * Hardcore error reporting so that we know about as much as possible. All our responses are wrapped up so we're ok!
 */
if ( worpitFunctionExists( 'error_reporting' ) ) {
	error_reporting( E_ALL );
}

/**
 * Begin
 */
worpitValidateSystem();

require_once( worpitFindWpLoad() );

/**
 * Log request in as an admin.
 */
if ( function_exists( 'wp_set_current_user' ) ) {
	wp_set_current_user( 1 );
}

// TODO: setup some error handling and general logging.

$sMethod = 'index';
if ( isset( $_GET['m'] ) && preg_match( '/[A-Z0-9_]+/i', $_GET['m'] ) ) {
	$sMethod = $_GET['m'];
}
