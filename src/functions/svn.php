<?php

/**
 * @param string $insCmd
 * @param string $insTarget
 * @param string $insUser
 * @param string $insPass
 * @param string $insExtra
 * @param integer $innTimeout
 * @return string
 */
function buildSubversionRequest( $insCmd, $insTarget, $insUser, $insPass, $insExtra = '', $innTimeout = 0 ) {
	$aCommand = array(
		'svn',
		'--username '.$insUser,
		'--password '.$insPass,
		'--no-auth-cache',
		'--non-interactive',
		'--trust-server-cert'
	);
	
	if ( $innTimeout > 0 ) {
		$aCommand[] = '--config-option servers:global:http-timeout='.$innTimeout;
	}
	
	$aCommand[] = $insCmd;
	if ( $insTarget != '' ) {
		$aCommand[] = $insTarget;
	}
	
	if ( $insExtra != '' ) {
		$aCommand[] = $insExtra;
	}
	
	return implode( ' ', $aCommand ).' 2>&1';
}