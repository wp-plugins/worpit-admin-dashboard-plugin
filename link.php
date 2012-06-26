<?php

require_once( dirname(__FILE__).'/src/loader.php' );

worpitValidateSystem();

/**
 * Alot of this may seem overkill. But we don't want to link a site until we've verified all the data
 * is correct as it is intended to be
 */
$sKey = trim( get_option( Worpit_Plugin::$VariablePrefix.'key' ) );
$sPin = trim( get_option( Worpit_Plugin::$VariablePrefix.'pin' ) );
$sAssigned = get_option( Worpit_Plugin::$VariablePrefix.'assigned' );
$fAssigned = ($sAssigned == 'Y');

$sRequestedKey = trim( $_REQUEST['key'] );
$sRequestedPin = md5( trim( $_REQUEST['pin'] ) );
$sRequestedAcc = trim( $_REQUEST['accname'] );

if ( $sRequestedKey == $sKey && !$fAssigned ) {
	if ( !update_option( Worpit_Plugin::$VariablePrefix.'pin', $sRequestedPin ) ) {
		die( '-3:UpdateOptionFailed:'.Worpit_Plugin::$VariablePrefix.'pin:'.$sRequestedPin );
	}
	if ( !update_option( Worpit_Plugin::$VariablePrefix.'assigned', 'Y' ) ) {
		die( '-4:UpdateOptionFailed:'.Worpit_Plugin::$VariablePrefix.'assigned:Y' );
	}
	if ( !update_option( Worpit_Plugin::$VariablePrefix.'assigned_to', $sRequestedAcc ) ) {
		die( '-6:UpdateOptionFailed:'.Worpit_Plugin::$VariablePrefix.'assigned_to:'.$sRequestedAcc );
	}
	
	/**
	 * Now double check that everything is as we expect it to be before allowing the site to be added
	 */
	$sOption = get_option( Worpit_Plugin::$VariablePrefix.'key' );
	if ( $sOption != $sRequestedKey ) {
		die( '-7:GetOptionFailed:'.Worpit_Plugin::$VariablePrefix.'key:'.$sRequestedPin );
	}
	
	$sOption = get_option( Worpit_Plugin::$VariablePrefix.'pin' );
	if ( $sOption != $sRequestedPin ) {
		die( '-8:GetOptionFailed:'.Worpit_Plugin::$VariablePrefix.'pin:'.$sRequestedPin );
	}
	
	$sOption = get_option( Worpit_Plugin::$VariablePrefix.'assigned' );
	if ( $sOption != 'Y' ) {
		die( '-9:GetOptionFailed:'.Worpit_Plugin::$VariablePrefix.'assigned:Y' );
	}
	
	$sOption = get_option( Worpit_Plugin::$VariablePrefix.'assigned_to' );
	if ( $sOption != $sRequestedAcc ) {
		die( '-10:GetOptionFailed:'.Worpit_Plugin::$VariablePrefix.'assigned_to:'.$sRequestedAcc );
	}
	
	die( '0' );
}
else if ( $fAssigned ) {
	die( '-1:AlreadyAssigned:'.$sAssigned );
}
else {
	die( '-2:KeyMismatch:'.$sKey.':'.$sRequestedKey );
}
