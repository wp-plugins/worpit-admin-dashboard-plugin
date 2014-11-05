<?php

require_once( dirname( __FILE__ ) . '/loader.php' );

/**
 * Alot of this may seem overkill. But we don't want to link a site until we've verified all the data
 * is correct as it is intended to be
 */
if ( !isset( $_GET['a'] ) || $_GET['a'] != 'check' ) {
	if ( !isset( $_GET['key'] ) ) {
		worpitFatal( 9, 'KeyNotProvided' );
	}
	if ( !isset( $_GET['pin'] ) ) {
		worpitFatal( 9, 'PinNotProvided' );
	}
	if ( !isset( $_GET['accname'] ) ) {
		worpitFatal( 9, 'AccnameNotProvided' );
	}
}

$sKey		= worpitGetOption( 'key' );
$sPin		= trim( worpitGetOption( 'pin' ) );
$sAssigned	= worpitGetOption( 'assigned' );
$fAssigned	= ($sAssigned == 'Y');

if ( empty( $sKey ) && !$fAssigned ) {
	worpitFatal( 12, 'KeyIsEmpty' );
}

$sRequestedKey = isset( $_GET['key'] )? trim( $_GET['key'] ): '';
$sRequestedPin = isset( $_GET['pin'] )? md5( trim( $_GET['pin'] ) ): '';
$sRequestedAcc = isset( $_GET['accname'] )? trim( $_GET['accname'] ): '';

if ( $sRequestedKey == trim( $sKey ) && !$fAssigned ) {
	if ( !ICWP_Plugin::updateOption( 'pin', $sRequestedPin ) ) {
		worpitFatal( 10, 'UpdateOptionFailed:'.'pin:'.$sRequestedPin );
	}
	if ( !ICWP_Plugin::updateOption( 'assigned', 'Y' ) ) {
		worpitFatal( 10, 'UpdateOptionFailed:'.'assigned:Y' );
	}
	if ( !ICWP_Plugin::updateOption( 'assigned_to', $sRequestedAcc ) ) {
		worpitFatal( 10, 'UpdateOptionFailed:'.'assigned_to:'.$sRequestedAcc );
	}
	
	/**
	 * Now double check that everything is as we expect it to be before allowing the site to be added
	 */
	$sOption = worpitGetOption( 'key' );
	if ( $sOption != $sRequestedKey ) {
		worpitFatal( 11, 'GetOptionFailed:'.'key:'.$sRequestedKey );
	}
	
	$sOption = worpitGetOption( 'pin' );
	if ( $sOption != $sRequestedPin ) {
		worpitFatal( 11, 'GetOptionFailed:'.'pin:'.$sRequestedPin );
	}
	
	$sOption = ICWP_Plugin::getOption( 'assigned' );
	if ( $sOption != 'Y' ) {
		worpitFatal( 11, 'GetOptionFailed:'.'assigned:Y' );
	}
	
	$sOption = ICWP_Plugin::getOption( 'assigned_to' );
	if ( $sOption != $sRequestedAcc ) {
		worpitFatal( 11, 'GetOptionFailed:'.'assigned_to:'.$sRequestedAcc );
	}
	
	die( '<worpitresponse>0</worpitresponse>' );
}
else if ( $fAssigned ) {
	worpitFatal( 1, 'AlreadyAssigned:'.$sAssigned );
}
else {
	worpitFatal( 2, 'KeyMismatch:'.$sRequestedKey );
}

//parse_str($_SERVER['QUERY_STRING'], $_GET);
