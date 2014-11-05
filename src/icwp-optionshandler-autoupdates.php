<?php
/**
 * Copyright (c) 2014 iControlWP <support@icontrolwp.com>
 * All rights reserved.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require_once( dirname(__FILE__).'/icwp-optionshandler-base.php' );

if ( !class_exists('ICWP_APP_FeatureHandler_Autoupdates_V3') ):

	class ICWP_APP_FeatureHandler_Autoupdates_V3 extends ICWP_APP_FeatureHandler_Base {

		/**
		 * @var ICWP_APP_Processor_Autoupdates
		 */
		protected $oFeatureProcessor;

		/**
		 * @return ICWP_APP_Processor_Autoupdates|null
		 */
		protected function loadFeatureProcessor() {
			if ( !isset( $this->oFeatureProcessor ) ) {
				require_once( $this->getController()->getPath_SourceFile( sprintf( 'icwp-processor-%s.php', $this->getFeatureSlug() ) ) );
				$this->oFeatureProcessor = new ICWP_APP_Processor_Autoupdates( $this );
			}
			return $this->oFeatureProcessor;
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

		public function doPrePluginOptionsSave() {

			// Migrate from old system
			$aOldOptions = $this->loadWpFunctionsProcessor()->getOption( 'icwp_autoupdates_system_options' );
			if ( !empty( $aOldOptions ) && is_array( $aOldOptions ) ) {
				if ( isset( $aOldOptions['enabled'] ) && $aOldOptions['enabled'] ) {
					$this->setIsMainFeatureEnabled( true );
				}
				if ( isset( $aOldOptions['tracking_id'] ) ) {
					$this->setOpt( 'tracking_id', $aOldOptions['tracking_id']  );
				}
				$this->setOpt( 'auto_update_plugins', ( isset( $aOldOptions['auto_update_plugins'] ) && is_array( $aOldOptions['auto_update_plugins'] ) ) ? $aOldOptions['auto_update_plugins'] : array() );
				$this->setOpt( 'auto_update_themes', ( isset( $aOldOptions['auto_update_themes'] ) && is_array( $aOldOptions['auto_update_themes'] ) ) ? $aOldOptions['auto_update_themes'] : array() );
				$this->loadWpFunctionsProcessor()->deleteOption( 'icwp_autoupdates_system_options' );
			}
		}
	}

endif;

class ICWP_APP_FeatureHandler_Autoupdates extends ICWP_APP_FeatureHandler_Autoupdates_V3 { }