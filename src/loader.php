<?php

if ( !defined( 'DS' ) ) {
	define( 'DS',		DIRECTORY_SEPARATOR );
}

define( 'LOADER_PATH',	dirname(__FILE__) );
define( 'VIEWS_PATH',	dirname(__FILE__).'/../views' );

if ( !defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}

/**
 * Convert to Linux Path
 *
 * @param $insPath
 */
function clp( $insPath ) {
	return str_replace( '\\', '/', $insPath );
}

function worpitFindWpLoad() {
	$sLoaderPath = dirname(__FILE__);
	$sFilename = 'wp-load.php';
	$nLimiter = 0;
	$fFound = false;

	do {
		if ( @is_file( $sLoaderPath.DS.$sFilename ) ) {
			$fFound = true;
			break;
		}
		$sLoaderPath .= DS.'..';
		$nLimiter++;
	}
	while ( $nLimiter < 10 );

	if ( !$fFound ) {
		die( '-9999:Failed to find WP env ('.$sLoaderPath.DS.$sFilename.')' );
	}
	
	return $sLoaderPath.DS.$sFilename;
}

function worpitClassAutoLoader( $insClass ) {
	$sFile = '';
	$aNamespace = explode( '_', strtolower( $insClass ) );
	array_unshift( $aNamespace, 'src' );

	if ( count( $aNamespace ) > 1 && $aNamespace[0] == 'src' ) {
		$sFile = LOADER_PATH;
		array_shift( $aNamespace );

		foreach ( $aNamespace as $sPath ) {
			$sFile .= DS.strtolower( $sPath );
		}
		$sFile .= '.php';

		if ( !@is_file( $sFile ) ) {
			return false;
		}

		@include_once( $sFile );
		if ( !class_exists( $insClass, false ) ) {
			//trigger_error( "Class ".$insClass." not found", E_USER_ERROR );
		}
	}
}

function worpitAuthenticate( $inaData ) {
	$sOption = get_option( Worpit_Plugin::$VariablePrefix.'assigned' );
	$fAssigned = ($sOption == 'Y');
	if ( !$fAssigned ) {
		die( '-9999:NotAssigned:'.$sOption );
	}

	$sKey = get_option( Worpit_Plugin::$VariablePrefix.'key' );
	if ( $sKey != trim( $inaData['key'] ) ) {
		die( '-9998:InvalidKey:'.$inaData['key'] );
	}

	$sPin = get_option( Worpit_Plugin::$VariablePrefix.'pin' );
	if ( $sPin !== md5( trim( $inaData['pin'] ) ) ) {
		die( '-9997:InvalidPin:'.$inaData['pin'] );
	}

	if ( isset( $inaData['timeout'] ) ) {
		@set_time_limit( intval( $inaData['timeout'] ) );
	}
	else {
		@set_time_limit( 60 );
	}
	return true;
}

function worpitVerifyPackageRequest( $inaData ) {
	if ( get_option( Worpit_Plugin::$VariablePrefix.'can_handshake' ) != 'Y' ) {
		return true;
	}
	
	if ( get_option( Worpit_Plugin::$VariablePrefix.'handshake_enabled' ) != 'Y' ) {
		return true;
	}
	
	// TODO: Use cURL if available or openSSL if available.
	$sUrl = sprintf( 'http://worpitapp.com/dashboard/system/verification/check/%s/%s/%s',
		$inaData['verification_code'], $inaData['package_name'], $inaData['pin']
	);
	
	$sContents = @file_get_contents( $sUrl );
	
	if ( empty( $sContents ) || $sContents === false ) {
		update_option( Worpit_Plugin::$VariablePrefix.'can_handshake', (worpitCheckCanHandshake()? 'Y': 'N') );
		die( '-9996:VerifyCallFailed: '.$sUrl.' : '.$sContents );
	}
	
	$oJson = json_decode( $sContents );
	if ( !isset( $oJson->success ) || $oJson->success !== true ) {
		die( '-9995:VerifyInvalid: '.$sUrl.' : '.$sContents );
	}
	
	return true;
}

function worpitValidateSystem() {
	/*
	if ( ini_get( 'safe_mode' ) ) {
		die( '-4:SafeModeEnabled' );
	}
	*/
	//WorpitApp IP: 69.36.185.61
	//$_SERVER['REMOTE_ADDR'];

	//-6,-7,-8 reserved

	if ( count( $_GET ) == 0 ) {
		die( '-5:GetRequestEmpty' );
	}

	if ( version_compare( PHP_VERSION, '5.0.0', '<' ) ) {
		die( '-4:InvalidPhpVersion' );
	}

	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		die( '-3:Multisite' );
	}
}

function worpitCheckCanHandshake() {
	$sContents = @file_get_contents( 'http://worpitapp.com/dashboard/system/verification/test/' );
	
	if ( empty( $sContents ) || $sContents === false ) {
		return false;
	}
	
	$oJson = json_decode( $sContents );
	
	if ( !isset( $oJson->success ) || $oJson->success !== true ) {
		return false;
	}
	return true;
}

function worpitFunctionExists( $insFunc ) {
	if ( extension_loaded( 'suhosin' ) ) {
		$sBlackList = @ini_get( "suhosin.executor.func.blacklist" );
		if ( !empty( $sBlackList ) ) {
			$aBlackList = explode( ',', $sBlackList );
			$aBlackList = array_map( 'trim', $aBlackList );
			$aBlackList = array_map( 'strtolower', $aBlackList );
			return ( function_exists( $insFunc ) == true && array_search( $insFunc, $aBlackList ) === false );
		}
	}
	return function_exists( $insFunc );
}

if ( worpitFunctionExists( 'ini_set' ) ) {
	@ini_set( 'error_log',		dirname(__FILE__).'/../php_error_log' );
	@ini_set( 'log_errors',		1 );
	@ini_set( 'display_errors',	0 );
}

if ( worpitFunctionExists( 'error_reporting' ) ) {
	error_reporting( E_ALL );
}

worpitValidateSystem();

require_once( worpitFindWpLoad() );

/**
 * Setup some autoloading magic; PHP => 5.1.2
 */
if ( worpitFunctionExists( 'spl_autoload_register' ) ) {
	spl_autoload_register( 'worpitClassAutoLoader' );
}
else {
	require_once( dirname(__FILE__).'/controllers/base.php' );
	require_once( dirname(__FILE__).'/controllers/transport.php' );
	require_once( dirname(__FILE__).'/helper/wordpress.php' );
}

// TODO: setup some error handling and general logging.

/**
 * Include some required files that won't get picked up by the autoloader, and
 * also there's no point in going through that route anyway.
 */
require_once( dirname(__FILE__).'/plugin/base.php' );
require_once( dirname(__FILE__).'/functions/filesystem.php' );
require_once( dirname(__FILE__).'/functions/svn.php' );
require_once( dirname(__FILE__).'/functions/JSON.php' );

$sMethod = 'index';
if ( isset( $_GET['m'] ) && preg_match( '/[A-Z0-9_]+/i', $_GET['m'] ) ) {
	$sMethod = $_GET['m'];
}

if ( !function_exists( 'json_encode' ) ) {
	function json_encode( $inmData ) {
		$oJson = new JSON();
		return $oJson->serialize( $inmData );
	}
}

if ( !function_exists( 'json_decode' ) ) {
	function json_decode( $insData ) {
		$oJson = new JSON();
		return $oJson->unserialize( $insData );
	}
}
