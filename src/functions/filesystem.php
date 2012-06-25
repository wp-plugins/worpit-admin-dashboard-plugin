<?php

/**
 * @param string $insBaseDir
 * @param string $insPrefix
 * @return boolean|string
 */
function createTempDir( $insBaseDir = null, $insPrefix = '' ) {
	$sTemp = rtrim( (is_null( $insBaseDir )? sys_get_temp_dir(): $insBaseDir), DS ).DS;
	
	$sCharset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz0123456789';
	do {
		$sDir = $insPrefix;
		for ( $i = 0; $i < 8; $i++ ) {
			$sDir .= $sCharset[(rand() % strlen( $sCharset ))];
		}
	}
	while( is_dir( $sTemp.$sDir ) );
	
	if ( !mkdir( $sTemp.$sDir, 0777 ) ) {
		return false;
	}
	
	return $sTemp.$sDir;
}

/**
 * @param string $insDir
 * @param string $outsOutput
 * @return unknown
 */
function removeTempDir( $insDir, &$outsOutput = array() ) {
	if ( stristr( PHP_OS, 'WIN' ) ) {
		exec( 'rmdir '.$insDir.' /s /q', $outsOutput, $nReturnVal );
	}
	else {
		exec( 'rm -rf '.$insDir, $outsOutput, $nReturnVal );
	}
	return $nReturnVal;
}