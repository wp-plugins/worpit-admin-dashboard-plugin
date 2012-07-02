<?php

/**
 * @param string $insBaseDir
 * @param string $insPrefix
 * @return boolean|string
 */
if ( !function_exists( 'createTempDir' ) ) {
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
		
		$nCurrentBasePerms = fileperms( $insBaseDir );
		chmod( $insBaseDir, 0755 );
		
		$fSuccess = true;
		
		if ( !mkdir( $sTemp.$sDir, 0777, true ) ) {
			if ( !mkdir( $sTemp.$sDir, 0755, true ) ) {
				$fSuccess = false;
			}
			else {
				chmod( $sTemp.$sDir, 0777 );
			}
		}
		return ($fSuccess? $sTemp.$sDir: false);
	}
}

/**
 * @param string $insDir
 * @param string $outsOutput
 * @return boolean
 */
if ( !function_exists( 'removeTempDir' ) ) {
	function removeTempDir( $insDir ) {
		if ( is_dir( $insDir ) ) {
			return rrmdir( $insDir );
		}
		return false;
	}
}

/**
 * @param string $insDir
 * @return void
 */
if ( !function_exists( 'rrmdir' ) ) {
	function rrmdir( $insDir ) {
		foreach ( glob( $insDir . '/*' ) as $sFile ) {
			if ( is_dir( $sFile ) ) {
				rrmdir( $sFile );
			}
			else {
				unlink( $sFile );
			}
		}
		return rmdir( $insDir );
	}
}