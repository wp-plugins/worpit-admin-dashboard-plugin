<?php

if ( !class_exists( 'ICWP_APP_Processor_Plugin_SiteLink', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base.php' );

	class ICWP_APP_Processor_Plugin_SiteLink extends ICWP_APP_Processor_Base {

		/**
		 * @return stdClass
		 */
		public function run() {

			$oResponse = new stdClass();
			$oResponse->status = '';
			$oResponse->success = false;
			$oResponse->code = 0;

			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();

			if ( $oFO->getIsSiteLinked() ) {
				$oResponse->message = 'Assigned To:'.$this->getOption( 'assigned_to' );
				$oResponse->status = 'AlreadyAssigned';
				$oResponse->code = 1;
			}

			// First is the check to see that we can simply call the site and communicate with the plugin
			if ( $oFO->fetchIcwpRequestParam( 'a' ) == 'check' ) {
				$oResponse->success = true;
				return $oResponse;
			}

			// At this point we're in the 2nd stage of the link...

			// bail immediately if we're already assigned
			if ( $oResponse->status == 'AlreadyAssigned' ) {
				return $oResponse;
			}

			$sRequestedKey = $oFO->fetchIcwpRequestParam( 'key' );
			if ( empty( $sRequestedKey ) ) {
				$oResponse->message = 'KeyEmpty:'.'.';
				$oResponse->code = 2;
				return $oResponse;
			}
			if ( $sRequestedKey != $oFO->getPluginAuthKey() ) {
				$oResponse->message = 'KeyMismatch:'.$sRequestedKey.'.';
				$oResponse->code = 3;
				return $oResponse;
			}

			$sRequestPin = $oFO->fetchIcwpRequestParam( 'pin' );
			if ( empty( $sRequestPin ) ) {
				$oResponse->message = 'PinEmpty:.';
				$oResponse->code = 4;
				return $oResponse;
			}

			$sRequestedAcc = urldecode( $oFO->fetchIcwpRequestParam( 'accname' ) );
			if ( empty( $sRequestedAcc ) ) {
				$oResponse->message = 'AccountEmpty:.';
				$oResponse->code = 5;
				return $oResponse;
			}
			if ( !is_email( $sRequestedAcc ) ) {
				$oResponse->message = 'AccountNotValid:'.$sRequestedAcc;
				$oResponse->code = 6;
				return $oResponse;
			}

			$oFO->setPluginPin( $sRequestPin );
			$oFO->setPluginAssigned( $sRequestedAcc );

			$oResponse->success = true;
			return $oResponse;
		}
	}

endif;
