<?php

if ( !class_exists( 'ICWP_APP_FeatureHandler_Statistics_V1', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base.php' );

	class ICWP_APP_FeatureHandler_Statistics_V1 extends ICWP_APP_FeatureHandler_Base {

		/**
		 * @return string
		 */
		protected function getProcessorClassName() {
			return 'ICWP_APP_Processor_Statistics';
		}

		/**
		 * @return array
		 */
		public function retrieveDailyStats() {
			/** @var ICWP_APP_Processor_Statistics $oFp */
			$oFp = $this->getProcessor();
			return $oFp->getDailyTotals();
		}

		/**
		 * @return array
		 */
		public function retrieveMonthlyStats() {
			/** @var ICWP_APP_Processor_Statistics $oFp */
			$oFp = $this->getProcessor();
			return $oFp->getMonthlyTotals();
		}

		/**
		 * @return string
		 */
		public function getStatisticsTableName() {
			return $this->doPluginPrefix( $this->getOpt( 'statistics_table_name' ), '_' );
		}
	}

endif;

class ICWP_APP_FeatureHandler_Statistics extends ICWP_APP_FeatureHandler_Statistics_V1 { }