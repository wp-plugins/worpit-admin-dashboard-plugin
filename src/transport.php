<?php

require_once( dirname( __FILE__ ) . '/loader.php' );

$oController = new Worpit_Controllers_Transport();
if ( method_exists( $oController, $sMethod ) ) {
	$oController->{$sMethod}();
}
else {
	worpitFatal( 1, 'Request method does not exist' );
}