<?php

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

/**
 * Convert to Linux Path
 * @param string $insPath
 * @return string
 */
function worpitCLP( $insPath ) {
	return str_replace( '\\', '/', $insPath );
}

/**
 * @uses die()
 *
 * @param unknown_type $innCode
 * @param unknown_type $insMessage
 * @return void
 */
function worpitFatal( $innCode, $insMessage ) {
	die( '<worpitfatal>-'.$innCode.':'.$insMessage.'</worpitfatal>' );
}

/**
 * Obtains the absolute path of wp-load.php, this will be a proper path generated from realpath()
 * @return string
 */
function worpitFindWpLoad() {
	$sLoaderPath	= dirname(__FILE__);
	$sFilename		= 'wp-load.php';
	$nLimiter		= 0;
	$nMaxLimit		= count( explode( WORPIT_DS, trim( $sLoaderPath, WORPIT_DS ) ) );
	$fFound			= false;

	do {
		if ( @is_file( $sLoaderPath.WORPIT_DS.$sFilename ) ) {
			$fFound = true;
			break;
		}
		$sLoaderPath = realpath( $sLoaderPath.WORPIT_DS.'..' );
		$nLimiter++;
	}
	while ( $nLimiter < $nMaxLimit );

	if ( !$fFound ) {
		worpitFatal( 9999, 'Failed to find WP env ('.$sLoaderPath.WORPIT_DS.$sFilename.')' );
	}

	return $sLoaderPath.WORPIT_DS.$sFilename;
}

/**
 * @param string $insName
 * @return string
 */
function worpitGetOption( $insName ) {
	return get_option( Worpit_Plugin::$VariablePrefix.$insName );
}

/**
 * @param array $inaData
 * @return boolean
 */
function worpitAuthenticate( $inaData ) {
	$sOption = worpitGetOption( 'assigned' );
	$fAssigned = ($sOption == 'Y');
	if ( !$fAssigned ) {
		worpitFatal( 9999, 'NotAssigned:'.$sOption );
	}

	$sKey = worpitGetOption( 'key' );
	if ( $sKey != trim( $inaData['key'] ) ) {
		worpitFatal( 9998, 'InvalidKey:'.@$inaData['key'] );
	}

	$sPin = worpitGetOption( 'pin' );
	if ( $sPin !== md5( trim( $inaData['pin'] ) ) ) {
		worpitFatal( 9997, 'InvalidPin:'.@$inaData['pin'] );
	}

	if ( isset( $inaData['timeout'] ) ) {
		@set_time_limit( intval( $inaData['timeout'] ) );
	}
	else {
		@set_time_limit( 60 );
	}
	return true;
}

/**
 * @param array $inaData
 * @return boolean
 */
function worpitVerifyPackageRequest( $inaData ) {
	if ( worpitGetOption( 'can_handshake' ) != 'Y' || worpitGetOption( 'handshake_enabled' ) != 'Y' ) {
		return true;
	}
	
	$sUrl = sprintf( WORPIT_VERIFICATION_CHECK_URL.'%s/%s/%s', $inaData['verification_code'], $inaData['package_name'], $inaData['pin']	);
	$fRemoteRead = worpitRemoteReadBasic( $sUrl, $sContents );
	
	if ( !$fRemoteRead || empty( $sContents ) || $sContents === false ) {
		$fCanHandshake = worpitCheckCanHandshake();
		update_option( Worpit_Plugin::$VariablePrefix.'can_handshake', ($fCanHandshake? 'Y': 'N') );
		update_option( Worpit_Plugin::$VariablePrefix.'handshake_enabled', ($fCanHandshake? 'Y': 'N') );
		worpitFatal( 9996, 'VerifyCallFailed: '.$sUrl.' : '.$sContents );
	}

	$oJson = json_decode( trim( $sContents ) );
	if ( !isset( $oJson->success ) || $oJson->success !== true ) {
		worpitFatal( 9995, 'VerifyInvalid: '.$sUrl.' : '.$sContents );
	}

	return true;
}

/**
 * @uses die()
 * @return void
 */
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
		worpitFatal( 5, 'GetRequestEmpty' );
	}

	if ( version_compare( PHP_VERSION, '5.0.0', '<' ) ) {
		worpitFatal( 4, 'InvalidPhpVersion' );
	}
}

/**
 * This method is used by the verify package, therefore if the content is not json
 * parseable (i.e. HEADER = true), this will severely bust the verification process.
 *
 * @param string $insUrl
 * @param string $outsResponse
 * @return boolean
 */
function worpitRemoteReadBasic( $insUrl, &$outsResponse = '' ) {
	$insUrl = trim( $insUrl );
	$aUrlParts = parse_url( $insUrl );
	if ( !$aUrlParts ) {
		return false;
	}

	if ( function_exists( 'curl_version' ) ) {
		$oCurl = curl_init();
		curl_setopt( $oCurl, CURLOPT_URL,				$insUrl );
		curl_setopt( $oCurl, CURLOPT_USERAGENT,			WORPIT_USER_AGENT );
		curl_setopt( $oCurl, CURLOPT_RETURNTRANSFER,	1 );
		@curl_setopt( $oCurl, CURLOPT_FOLLOWLOCATION,	true );
		@curl_setopt( $oCurl, CURLOPT_MAXREDIRS,		10 );
		curl_setopt( $oCurl, CURLOPT_CONNECTTIMEOUT,	15 );
		curl_setopt( $oCurl, CURLOPT_TIMEOUT,			20 );
		curl_setopt( $oCurl, CURLOPT_HEADER,			false );

		if ( preg_match( '/^https/i', $insUrl ) ) {
			curl_setopt( $oCurl, CURLOPT_SSL_VERIFYPEER,	false );
			curl_setopt( $oCurl, CURLOPT_SSL_VERIFYHOST,	0 );
		}
	
		curl_setopt( $oCurl, CURLOPT_AUTOREFERER,		true );
		curl_setopt( $oCurl, CURLOPT_HTTPHEADER,		array( 'Expect:' ) ); //Fixes the HTTP/1.1 417 Expectation Failed Bug

		$outsResponse = curl_exec( $oCurl );
		//$aInfo = curl_getinfo( $oCurl );
		$nHttpCode = curl_getinfo( $oCurl, CURLINFO_HTTP_CODE );
		$sError = curl_error( $oCurl );
		curl_close( $oCurl );
		
		if ( !empty( $sError ) || $nHttpCode != 200 ) {
			return false;
		}

		return true;
	}
	else if ( ini_get( 'allow_url_fopen' ) == '1' ) {
		$aOptions = array(
			'http' => array(
				'user_agent'	=> WORPIT_USER_AGENT,
				'max_redirects'	=> 10,
				'timeout'		=> 20,
			)
		);
		$oContext = stream_context_create( $aOptions );
		$outsResponse = file_get_contents( $insUrl, false, $oContext );

		return ( $outsResponse !== false );
	}
	else {
		list( $sDisgard, $sUrl ) = explode( '://', $insUrl, 2 );
		list( $sHost, $sUri ) = explode( '/', $sUrl, 2 );
		
		$outsResponse = worpitHttpRequest( 'GET', $sHost, 80, $sUri );
		
		return !empty( $outsResponse );
	}
}

/**
 * @link http://es.php.net/manual/en/function.fsockopen.php
 *
 * @param string $verb				HTTP Request Method (GET and POST supported)
 * @param string $ip				Target IP/Hostname
 * @param integer $port				Target TCP port
 * @param string $uri				Target URI
 * @param array $getdata			HTTP GET Data ie. array('var1' => 'val1', 'var2' => 'val2')
 * @param array $postdata			HTTP POST Data ie. array('var1' => 'val1', 'var2' => 'val2')
 * @param array $cookie				HTTP Cookie Data ie. array('var1' => 'val1', 'var2' => 'val2')
 * @param array $custom_headers		Custom HTTP headers ie. array('Referer: http://localhost/
 * @param integer $timeout			Socket timeout in seconds
 * @param boolean $req_hdr			Include HTTP request headers
 * @param boolean $res_hdr			Include HTTP response headers
 * @return string
 */
function worpitHttpRequest( $verb = 'GET', $ip, $port = 80, $uri = '/', $getdata = array(), $postdata = array(), $cookie = array(), $custom_headers = array(), $timeout = 1, $req_hdr = false, $res_hdr = false ) {
	$ret = '';
	$verb = strtoupper( $verb );
	$cookie_str = '';
	$getdata_str = count( $getdata )? '?': '';
	$postdata_str = '';

	foreach ( $getdata as $k => $v ) {
		$getdata_str .= urlencode( $k ) .'='. urlencode( $v ) . '&';
	}

	foreach ( $postdata as $k => $v) {
		$postdata_str .= urlencode( $k ) .'='. urlencode( $v ) .'&';
	}

	foreach ( $cookie as $k => $v ) {
		$cookie_str .= urlencode( $k ) .'='. urlencode( $v ) .'; ';
	}

	$crlf = "\r\n";
	$req = $verb .' '. $uri . $getdata_str .' HTTP/1.1' . $crlf;
	$req .= 'Host: '. $ip . $crlf;
	$req .= 'User-Agent: Mozilla/5.0 Firefox/3.6.12' . $crlf;
	$req .= 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8' . $crlf;
	$req .= 'Accept-Language: en-us,en;q=0.5' . $crlf;
	$req .= 'Accept-Encoding: deflate' . $crlf;
	$req .= 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7' . $crlf;

	foreach ( $custom_headers as $k => $v ) {
		$req .= $k .': '. $v . $crlf;
	}

	if ( !empty( $cookie_str ) ) {
		$req .= 'Cookie: '. substr( $cookie_str, 0, -2 ) . $crlf;
	}

	if ( $verb == 'POST' && !empty( $postdata_str ) ) {
		$postdata_str = substr( $postdata_str, 0, -1 );
		$req .= 'Content-Type: application/x-www-form-urlencoded' . $crlf;
		$req .= 'Content-Length: '. strlen( $postdata_str ) . $crlf . $crlf;
		$req .= $postdata_str;
	}
	else {
		$req .= $crlf;
	}

	if ( $req_hdr ) {
		$ret .= $req;
	}

	if ( ( $fp = @fsockopen( $ip, $port, $errno, $errstr ) ) == false ) {
		return "Error $errno: $errstr\n";
	}

	stream_set_timeout( $fp, 0, $timeout * 1000 );

	fputs( $fp, $req );
	while ( $line = fgets( $fp ) ) {
		$ret .= $line;
	}
	fclose( $fp );

	if (!$res_hdr ) {
		$ret = substr( $ret, strpos( $ret, "\r\n\r\n" ) + 4 );
	}
	return $ret;
}

/**
* @return boolean
*/
function worpitCheckCanHandshake() {
	$fRemoteRead = worpitRemoteReadBasic( WORPIT_VERIFICATION_TEST_URL, $sContents );
	
	if ( !$fRemoteRead || empty( $sContents ) || $sContents === false ) {
		return false;
	}

	$oJson = json_decode( trim( $sContents ) );

	if ( !isset( $oJson->success ) || $oJson->success !== true ) {
		return false;
	}
	return true;
}

/**
 * @param string $insFunc
 * @return boolean
 */
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

/**
 * @param integer $innTimeout
 * @return boolean
 */
function worpitSetTimeLimit( $innTimeout ) {
	if ( worpitFunctionExists( 'set_time_limit' ) && @ini_get( 'safe_mode' ) == 0 ) {
		@set_time_limit( $innTimeout );
		return true;
	}
	return false;
}
