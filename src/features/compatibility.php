<?php

if ( !class_exists( 'ICWP_APP_FeatureHandler_Compatibility', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base.php' );

	class ICWP_APP_FeatureHandler_Compatibility extends ICWP_APP_FeatureHandler_Base {

		/**
		 * @return string
		 */
		protected function getProcessorClassName() {
			return 'ICWP_APP_Processor_Compatibility';
		}
	}

endif;