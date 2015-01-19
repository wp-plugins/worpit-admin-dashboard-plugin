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

require_once( 'base.php' );

if ( !class_exists( 'ICWP_APP_Processor_Whitelabel_V1', false ) ):

	class ICWP_APP_Processor_Whitelabel_V1 extends ICWP_APP_Processor_Base {

		/**
		 */
		public function run() {
			add_filter( $this->getController()->doPluginPrefix( 'plugin_labels' ), array( $this, 'doRelabelPlugin' ) );
			add_filter( 'plugin_row_meta', array( $this, 'fRemoveDetailsMetaLink' ), 200, 2 );
		}

		/**
		 * @param array $aPluginLabels
		 *
		 * @return array
		 */
		public function doRelabelPlugin( $aPluginLabels ) {
			// these are the old white labelling keys which will be replaced upon final release of white labelling.
			$sServiceName = $this->getOption( 'service_name' );
			if ( !empty( $sServiceName ) ) {
				$aPluginLabels['Name'] = $sServiceName;
				$aPluginLabels['Title'] = $sServiceName;
				$aPluginLabels['Author'] = $sServiceName;
				$aPluginLabels['AuthorName'] = $sServiceName;
			}
			$sTagLine = $this->getOption( 'tag_line' );
			if ( !empty( $sTagLine ) ) {
				$aPluginLabels['Description'] = $sTagLine;
			}
			$sUrl = $this->getOption( 'plugin_home_url' );
			if ( !empty( $sUrl ) ) {
				$aPluginLabels['PluginURI'] = $sUrl;
				$aPluginLabels['AuthorURI'] = $sUrl;
			}
			return $aPluginLabels;
		}

		/**
		 * @filter
		 * @param array $aPluginMeta
		 * @param string $sPluginBaseFileName
		 * @return array
		 */
		public function fRemoveDetailsMetaLink( $aPluginMeta, $sPluginBaseFileName ) {
			if ( $sPluginBaseFileName == $this->getController()->getPluginBaseFile() ) {
				if ( isset( $aPluginMeta[2] ) && strpos( $aPluginMeta[2], 'plugin=worpit-admin-dashboard-plugin' ) > 0 ) {
					unset( $aPluginMeta[ 2 ] );
				}
			}
			return $aPluginMeta;
		}
	}

endif;

if ( !class_exists( 'ICWP_APP_Processor_Whitelabel', false ) ):
	class ICWP_APP_Processor_Whitelabel extends ICWP_APP_Processor_Whitelabel_V1 { }
endif;