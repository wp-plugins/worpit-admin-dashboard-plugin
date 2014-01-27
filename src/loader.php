<?php

// This fixes most if not all problems associated with w3 total cache objects
if ( function_exists('wp_using_ext_object_cache') ) { 
	wp_using_ext_object_cache( false );
}
else {
	global $_wp_using_ext_object_cache;
	$_wp_using_ext_object_cache = false;
}
global $wp_object_cache;
if( !empty( $wp_object_cache ) && is_object($wp_object_cache) ) {
	@$wp_object_cache->flush(); 
}

/**
 * Configure defines required for Worpit, and make safe incase loader is ever included
 * more than once.
 */
if ( !defined( 'WORPIT_DS' ) )						define( 'WORPIT_DS',						DIRECTORY_SEPARATOR );
if ( !defined( 'WORPIT_LOADER_PATH' ) )				define( 'WORPIT_LOADER_PATH',				dirname(__FILE__) );
if ( !defined( 'WORPIT_VIEWS_PATH' ) )				define( 'WORPIT_VIEWS_PATH',				realpath( dirname(__FILE__).'/../views' ) );
if ( !defined( 'WORPIT_PHP_ERROR_LOG' ) ) 			define( 'WORPIT_PHP_ERROR_LOG',				realpath( dirname(__FILE__).'/../php_error_log' ) );
if ( !defined( 'WORPIT_USER_AGENT' ) )				define( 'WORPIT_USER_AGENT',				'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.5 (KHTML, like Gecko) Chrome/19.0.1084.52 Safari/536.5' );
if ( !defined( 'WORPIT_VERIFICATION_TEST_URL' ) )	define( 'WORPIT_VERIFICATION_TEST_URL', 	'http://worpitapp.com/dashboard/system/verification/test/' );
if ( !defined( 'ICWP_VERIFICATION_TEST_URL' ) ) 	define( 'ICWP_VERIFICATION_TEST_URL', 	    'http://app.icontrolwp.com/system/verification/test/' );
if ( !defined( 'WORPIT_VERIFICATION_CHECK_URL' ) )	define( 'WORPIT_VERIFICATION_CHECK_URL',	'http://worpitapp.com/dashboard/system/verification/check/' );
if ( !defined( 'ICWP_VERIFICATION_CHECK_URL' ) )	define( 'ICWP_VERIFICATION_CHECK_URL',  	'http://app.icontrolwp.com/system/verification/check/' );
if ( !defined( 'WORPIT_RETRIEVE_URL' ) )			define( 'WORPIT_RETRIEVE_URL', 				'http://worpitapp.com/dashboard/system/package/retrieve/' );
if ( !defined( 'ICWP_RETRIEVE_URL' ) )		    	define( 'ICWP_RETRIEVE_URL', 				'http://app.icontrolwp.com/system/package/retrieve/' );

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
 * We know that other plugins include similar classes, so we want to minimise conflicts.
 */
if ( !class_exists( 'JSON', false ) ) {
	require_once( dirname(__FILE__).'/functions/JSON.php' );
}

/**
 * Worpit specific functions
 */
require_once( dirname(__FILE__).'/functions/core.php' );
require_once( dirname(__FILE__).'/functions/filesystem.php' );

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

if ( !defined( 'WORPIT_DIRECT_API' ) ) {
	/**
	 * Already loaded when going through the WP frontend.
	 */
	require_once( worpitFindWpLoad() );
	
	/**
	 * Ensure that Worpit Plugin is accessible right after the WP system has been initiated.
	 * When going through the WP frontend, this will always be defined by this point.
	 */
	if ( !class_exists( 'Worpit_Plugin' ) ) {
		worpitFatal( 1, 'PluginInactive' );
	}
	
	/**
	 * Log request in as an admin.
	 */
	if ( function_exists( 'wp_set_current_user' ) ) {
		wp_set_current_user( 1 );
		wp_set_auth_cookie( 1 );
	}
}

// TODO: setup some error handling and general logging.

$sMethod = 'index';
if ( isset( $_GET['m'] ) && preg_match( '/[A-Z0-9_]+/i', $_GET['m'] ) ) {
	$sMethod = $_GET['m'];
}