<?php
/**
 * Copyright (c) 2015 iControlWP <support@icontrolwp.com>
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