<?php

if ( !class_exists( 'ICWP_APP_FeatureHandler_Security_V1', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base.php' );

	class ICWP_APP_FeatureHandler_Security_V1 extends ICWP_APP_FeatureHandler_Base {

		/**
		 * @return string
		 */
		protected function getProcessorClassName() {
			return 'ICWP_APP_Processor_Security';
		}
	}

endif;

class ICWP_APP_FeatureHandler_Security extends ICWP_APP_FeatureHandler_Security_V1 { }