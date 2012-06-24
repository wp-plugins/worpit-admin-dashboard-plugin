<?php

if ( !defined( 'DS' ) ) {
	define( 'DS',		DIRECTORY_SEPARATOR );
}

define( 'LOADER_PATH',	dirname(__FILE__) );
define( 'VIEWS_PATH',	dirname(__FILE__).'/../views' );

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
		if ( is_file( $sLoaderPath.DS.$sFilename ) ) {
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
		array_shift( &$aNamespace );

		foreach ( $aNamespace as $sPath ) {
			$sFile .= DS.strtolower( $sPath );
		}
		$sFile .= '.php';

		if ( !is_file( $sFile ) ) {
			trigger_error( "Class ".$insClass." not found in src", E_USER_ERROR );
		}

		@include_once( $sFile );
		if ( !class_exists( $insClass, false ) ) {
			//trigger_error( "Class ".$insClass." not found", E_USER_ERROR );
		}
	}
}

function worpitAuthenticate( $inaData ) {
	$fAssigned = (get_option( Worpit_Plugin::$VariablePrefix.'assigned' ) === 'Y');
	if ( !$fAssigned ) {
		die( '-9999:Assigned' );
	}

	$sKey = get_option( Worpit_Plugin::$VariablePrefix.'key' );
	if ( $sKey !== $inaData['key'] ) {
		die( '-9998:Key' );
	}

	$sPin = get_option( Worpit_Plugin::$VariablePrefix.'pin' );
	if ( $sPin !== md5( $inaData['pin'] ) ) {
		die( '-9997:Pin' );
	}

	if ( isset( $inaData['timeout'] ) ) {
		set_time_limit( intval( $inaData['timeout'] ) );
	}
	else {
		set_time_limit( 60 );
	}
	return true;
}

function worpitVerifyPackageRequest( $inaData ) {
	// TODO: Use cURL if available or openSSL if available.
	
	$sContents = @file_get_contents(
		sprintf( 'http://worpitapp.com/dashboard/system/verification/check/%s/%s/%s',
			$inaData['verification_code'], $inaData['package_name'], $inaData['pin']
		)
	);
	
	if ( empty( $sContents ) || $sContents === false ) {
		die( '-9996:VerfifyCallFailed:'.$sContents );
	}
	
	$oJson = json_decode( $sContents );
	if ( !isset( $oJson->success ) || $oJson->success !== true ) {
		die( '-9995:VerfifyInvalid:'.$sContents );
	}
	
	return true;
}

require_once( worpitFindWpLoad() );

/**
 * Setup some autoloading magic
 */
spl_autoload_register( 'worpitClassAutoLoader' );

// TODO: setup some error handling and general logging.

/**
 * Include some required files that won't get picked up by the autoloader, and
 * also there's no point in going through that route anyway.
 */
require_once( dirname(__FILE__).'/plugin/base.php' );
require_once( dirname(__FILE__).'/functions/filesystem.php' );
require_once( dirname(__FILE__).'/functions/svn.php' );

$sMethod = 'index';
if ( isset( $_REQUEST['m'] ) && preg_match( '/[A-Z0-9_]+/i', $_REQUEST['m'] ) ) {
	$sMethod = $_REQUEST['m'];
}