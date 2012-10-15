<?php

/**
 * Returns the temporary directory without a trailing slash.
 *
 * @param string $insBaseDir
 * @param string $insPrefix
 * @param string $outsRandomDir
 * @return boolean|string
 */
if ( !function_exists( 'worpitCreateTempDir' ) ) {
	function worpitCreateTempDir( $insBaseDir = null, $insPrefix = '', &$outsRandomDir = '' ) {
		$sTemp = rtrim( (is_null( $insBaseDir )? sys_get_temp_dir(): $insBaseDir), WORPIT_DS ).WORPIT_DS;
		
		$sCharset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz0123456789';
		do {
			$sDir = $insPrefix;
			for ( $i = 0; $i < 8; $i++ ) {
				$sDir .= $sCharset[(rand() % strlen( $sCharset ))];
			}
		}
		while ( is_dir( $sTemp.$sDir ) );
		
		$outsRandomDir = $sDir;
		
		$fSuccess = true;
		if ( !@mkdir( $sTemp.$sDir, 0755, true ) ) {
			$fSuccess = false;
		}
		
		return ($fSuccess? $sTemp.$sDir: false);
	}
}

/**
 * @param string $insDir
 * @param string $outsOutput
 * @return boolean
 */
if ( !function_exists( 'worpitRemoveTempDir' ) ) {
	function worpitRemoveTempDir( $insDir ) {
		if ( is_dir( $insDir ) ) {
			return worpitRemoveDir( $insDir );
		}
		return false;
	}
}

/**
 * @param string $insDir
 * @return void
 */
if ( !function_exists( 'worpitRemoveDir' ) ) {
	function worpitRemoveDir( $insDir ) {
		foreach ( glob( $insDir . '/*' ) as $sFile ) {
			if ( is_dir( $sFile ) ) {
				worpitRemoveDir( $sFile );
			}
			else {
				unlink( $sFile );
			}
		}
		return rmdir( $insDir );
	}
}

/**
 * @param string $insStartDir
 * @param integer $innLevels
 * @param string $insForFilename
 * @return boolean|string
 */
if ( !function_exists( 'worpitBackwardsRecursiveFileSearch' ) ) {
	function worpitBackwardsRecursiveFileSearch( $insStartDir, $innLevels, $insForFilename ) {
		$sSearchDir = $insStartDir;
		$nLimiter = 0;
		$fFound = false;
		
		do {
			if ( is_file( $sSearchDir.'/'.$insForFilename ) ) {
				$fFound = true;
				break;
			}
			$sSearchDir .= '/..';
			$nLimiter++;
		}
		while ( $nLimiter < $innLevels );
		
		if ( !$fFound ) {
			return false;
		}
				
		return file_get_contents( $sSearchDir.'/'.$insForFilename );
	}
}