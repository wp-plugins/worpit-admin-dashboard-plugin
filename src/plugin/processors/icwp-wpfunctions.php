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

require_once( dirname(__FILE__).'/base/icwp-processor-data.php' );

if ( !class_exists('ICWP_WpFunctions_CP') ):

	class ICWP_WpFunctions_CP {

		/**
		 * @var string
		 */
		protected $sWpVersion;

		/**
		 * @var ICWP_WpFunctions_CP
		 */
		protected static $oInstance = NULL;

		/**
		 * @return ICWP_WpFunctions_CP
		 */
		public static function & GetInstance() {
			if ( is_null( self::$oInstance ) ) {
				self::$oInstance = new ICWP_WpFunctions_CP();
			}
			return self::$oInstance;
		}


		public function __construct() {}

		/**
		 * @param string $insPluginFile
		 * @return boolean|stdClass
		 */
		public function getIsPluginUpdateAvailable( $insPluginFile ) {
			$aUpdates = $this->getWordpressUpdates();
			if ( empty( $aUpdates ) ) {
				return false;
			}
			if ( isset( $aUpdates[ $insPluginFile ] ) ) {
				return $aUpdates[ $insPluginFile ];
			}
			return false;
		}

		public function getPluginUpgradeLink( $insPluginFile ) {
			$sUrl = self_admin_url( 'update.php' ) ;
			$aQueryArgs = array(
				'action' 	=> 'upgrade-plugin',
				'plugin'	=> urlencode( $insPluginFile ),
				'_wpnonce'	=> wp_create_nonce( 'upgrade-plugin_' . $insPluginFile )
			);
			return add_query_arg( $aQueryArgs, $sUrl );
		}

		public function getWordpressUpdates() {
			$oCurrent = $this->getTransient( 'update_plugins' );
			return $oCurrent->response;
		}

		/**
		 * The full plugin file to be upgraded.
		 *
		 * @param string $insPluginFile
		 * @return boolean
		 */
		public function doPluginUpgrade( $insPluginFile ) {

			if ( !$this->getIsPluginUpdateAvailable($insPluginFile)
				|| ( isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] == 'update.php' ) ) {
				return true;
			}
			$sUrl = $this->getPluginUpgradeLink( $insPluginFile );
			wp_redirect( $sUrl );
			exit();
		}
		/**
		 * @param string $insKey
		 * @return object
		 */
		protected function getTransient( $insKey ) {

			// TODO: Handle multisite

			if ( version_compare( $this->getWordPressVersion(), '2.7.9', '<=' ) ) {
				return get_option( $insKey );
			}

			if ( function_exists( 'get_site_transient' ) ) {
				return get_site_transient( $insKey );
			}

			if ( version_compare( $this->getWordPressVersion(), '2.9.9', '<=' ) ) {
				return apply_filters( 'transient_'.$insKey, get_option( '_transient_'.$insKey ) );
			}

			return apply_filters( 'site_transient_'.$insKey, get_option( '_site_transient_'.$insKey ) );
		}

		/**
		 * @return string
		 */
		public function getWordPressVersion() {
			if ( empty( $this->sWpVersion ) ) {
				$sVersionFile = ABSPATH.WPINC.'/version.php';
				$sVersionContents = file_get_contents( $sVersionFile );

				if ( preg_match( '/wp_version\s=\s\'([^(\'|")]+)\'/i', $sVersionContents, $aMatches ) ) {
					$this->sWpVersion = $aMatches[1];
				}
				else {
					global $wp_version;
					$this->sWpVersion = $wp_version;
				}
			}
			return $this->sWpVersion;
		}
	}

endif;