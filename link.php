<?php

require_once( dirname(__FILE__).'/src/loader.php' );

/**
 * Alot of this may seem overkill. But we don't want to link a site until we've verified all the data
 * is correct as it is intended to be
 */

if ( !isset( $_GET['a'] ) || $_GET['a'] != 'check' ) {
	if ( !isset( $_GET['key'] ) ) {
		die( '-9:KeyNotProvided' );
	}
	if ( !isset( $_GET['pin'] ) ) {
		die( '-9:PinNotProvided' );
	}
	if ( !isset( $_GET['accname'] ) ) {
		die( '-9:AccnameNotProvided' );
	}
}

$sKey = get_option( Worpit_Plugin::$VariablePrefix.'key' );
$sPin = trim( get_option( Worpit_Plugin::$VariablePrefix.'pin' ) );
$sAssigned = get_option( Worpit_Plugin::$VariablePrefix.'assigned' );
$fAssigned = ($sAssigned == 'Y');

if ( empty( $sKey ) && !$fAssigned ) {
	die( '-12:KeyIsEmpty' );
}

$sRequestedKey = isset( $_GET['key'] )? trim( $_GET['key'] ): '';
$sRequestedPin = isset( $_GET['pin'] )? md5( trim( $_GET['pin'] ) ): '';
$sRequestedAcc = isset( $_GET['accname'] )? trim( $_GET['accname'] ): '';

if ( $sRequestedKey == trim( $sKey ) && !$fAssigned ) {
	if ( !update_option( Worpit_Plugin::$VariablePrefix.'pin', $sRequestedPin ) ) {
		die( '-10:UpdateOptionFailed:'.Worpit_Plugin::$VariablePrefix.'pin:'.$sRequestedPin );
	}
	if ( !update_option( Worpit_Plugin::$VariablePrefix.'assigned', 'Y' ) ) {
		die( '-10:UpdateOptionFailed:'.Worpit_Plugin::$VariablePrefix.'assigned:Y' );
	}
	if ( !update_option( Worpit_Plugin::$VariablePrefix.'assigned_to', $sRequestedAcc ) ) {
		die( '-10:UpdateOptionFailed:'.Worpit_Plugin::$VariablePrefix.'assigned_to:'.$sRequestedAcc );
	}
	
	/**
	 * Now double check that everything is as we expect it to be before allowing the site to be added
	 */
	$sOption = get_option( Worpit_Plugin::$VariablePrefix.'key' );
	if ( $sOption != $sRequestedKey ) {
		die( '-11:GetOptionFailed:'.Worpit_Plugin::$VariablePrefix.'key:'.$sRequestedKey );
	}
	
	$sOption = get_option( Worpit_Plugin::$VariablePrefix.'pin' );
	if ( $sOption != $sRequestedPin ) {
		die( '-11:GetOptionFailed:'.Worpit_Plugin::$VariablePrefix.'pin:'.$sRequestedPin );
	}
	
	$sOption = get_option( Worpit_Plugin::$VariablePrefix.'assigned' );
	if ( $sOption != 'Y' ) {
		die( '-11:GetOptionFailed:'.Worpit_Plugin::$VariablePrefix.'assigned:Y' );
	}
	
	$sOption = get_option( Worpit_Plugin::$VariablePrefix.'assigned_to' );
	if ( $sOption != $sRequestedAcc ) {
		die( '-11:GetOptionFailed:'.Worpit_Plugin::$VariablePrefix.'assigned_to:'.$sRequestedAcc );
	}
	
	die( '0' );
}
else if ( $fAssigned ) {
	die( '-1:AlreadyAssigned:'.$sAssigned );
}
else {
	die( '-2:KeyMismatch:'.$sRequestedKey );
}

//parse_str($_SERVER['QUERY_STRING'], $_GET);
