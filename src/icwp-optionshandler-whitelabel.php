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

require_once( 'icwp-optionshandler-base.php' );

if ( !class_exists('ICWP_APP_FeatureHandler_Whitelabel_V1') ):

	class ICWP_APP_FeatureHandler_Whitelabel_V1 extends ICWP_APP_FeatureHandler_Base {

		/**
		 * @return string
		 */
		protected function getProcessorClassName() {
			return 'ICWP_APP_Processor_Whitelabel';
		}

		public function doPrePluginOptionsSave() {
			// Migrate from old system
			$aOldOptions = $this->loadWpFunctionsProcessor()->getOption( 'icwp_whitelabel_system_options' );
			if ( !empty( $aOldOptions ) && is_array( $aOldOptions ) ) {
				if ( isset( $aOldOptions['enabled'] ) && $aOldOptions['enabled'] ) {
					$this->setIsMainFeatureEnabled( true );
				}
				if ( isset( $aOldOptions['service_name'] ) ) {
					$this->setOpt( 'service_name', $aOldOptions['service_name']  );
				}
				if ( isset( $aOldOptions['tag_line'] ) ) {
					$this->setOpt( 'tag_line', $aOldOptions['tag_line']  );
				}
				if ( isset( $aOldOptions['plugin_home_url'] ) ) {
					$this->setOpt( 'plugin_home_url', $aOldOptions['plugin_home_url']  );
				}
				if ( isset( $aOldOptions['icon_url_16x16'] ) ) {
					$this->setOpt( 'icon_url_16x16', $aOldOptions['icon_url_16x16']  );
				}
				if ( isset( $aOldOptions['icon_url_32x32'] ) ) {
					$this->setOpt( 'icon_url_32x32', $aOldOptions['icon_url_32x32']  );
				}
				$this->loadWpFunctionsProcessor()->deleteOption( 'icwp_whitelabel_system_options' );
			}
		}
	}

endif;

class ICWP_APP_FeatureHandler_Whitelabel extends ICWP_APP_FeatureHandler_Whitelabel_V1 { }