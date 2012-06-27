<?php

require_once( dirname(__FILE__).'/src/loader.php' );

$oController = new Controllers_Transport();
if ( method_exists( $oController, $sMethod ) ) {
	$oController->{$sMethod}();
}