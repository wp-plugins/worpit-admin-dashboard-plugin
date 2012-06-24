<?php

require_once( dirname(__FILE__).'/src/loader.php' );

if ( function_exists( 'is_multisite' ) && is_multisite() ) {
	die( '-3:Multisite' );
}

$sKey = get_option( Worpit_Plugin::$VariablePrefix.'key' );
$sPin = get_option( Worpit_Plugin::$VariablePrefix.'pin' );
$fAssigned = (get_option( Worpit_Plugin::$VariablePrefix.'assigned' ) === 'Y');

if ( $_REQUEST['key'] === $sKey && !$fAssigned ) {
	update_option( Worpit_Plugin::$VariablePrefix.'pin', md5( $_REQUEST['pin'] ) );
	update_option( Worpit_Plugin::$VariablePrefix.'assigned', 'Y' );
	update_option( Worpit_Plugin::$VariablePrefix.'assigned_to', $_REQUEST['accname'] );
	die( '0' );
}
else if ( $fAssigned ) {
	die( '-1:Already assigned' );
}
else {
	die( '-2:Key mismatch' );
}