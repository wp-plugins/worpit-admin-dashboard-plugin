<?php

require_once( dirname(__FILE__).ICWP_DS.'base.php' );

if ( !class_exists( 'ICWP_APP_FeatureHandler_Autoupdates_V3', false ) ):

	class ICWP_APP_FeatureHandler_Autoupdates_V3 extends ICWP_APP_FeatureHandler_Base {

		/**
		 * @return string
		 */
		protected function getProcessorClassName() {
			return 'ICWP_APP_Processor_Autoupdates';
		}

		/**
		 * @param string $sContext
		 *
		 * @return array
		 */
		public function getAutoUpdates( $sContext = 'plugins' ) {
			$aUpdates = $this->getOpt( 'auto_update_'.$sContext, array() );
			return is_array( $aUpdates ) ? $aUpdates : array();
		}

		/**
		 * @param array $aUpdateItems
		 * @param string $sContext
		 *
		 * @return array
		 */
		public function setAutoUpdates( $aUpdateItems, $sContext = 'plugins' ) {
			if ( is_array( $aUpdateItems ) ) {
				$this->setOpt( 'auto_update_'.$sContext, $aUpdateItems );
			}
		}

		/**
		 * @param string $sSlug
		 * @param bool $bSetOn
		 * @param string $sContext
		 */
		public function setAutoUpdate( $sSlug, $bSetOn = false, $sContext = 'plugins' ) {
			$aAutoUpdateItems = $this->getAutoUpdates( $sContext );

			$nInArray = array_search( $sSlug, $aAutoUpdateItems );
			if ( $bSetOn && $nInArray === false ) {
				$aAutoUpdateItems[] = $sSlug;
			}
			else if ( !$bSetOn && $nInArray !== false ) {
				unset( $aAutoUpdateItems[$nInArray] );
			}
			$this->setAutoUpdates( $aAutoUpdateItems, $sContext );
		}
	}

endif;

class ICWP_APP_FeatureHandler_Autoupdates extends ICWP_APP_FeatureHandler_Autoupdates_V3 { }