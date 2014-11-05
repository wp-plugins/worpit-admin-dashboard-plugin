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

require_once( dirname(__FILE__).ICWP_DS.'icwp-processor-base.php' );

if ( !class_exists('ICWP_APP_Processor_Plugin') ):

	class ICWP_APP_Processor_Plugin extends ICWP_APP_Processor_Base {

		/**
		 * @var ICWP_APP_FeatureHandler_Plugin
		 */
		protected $oFeatureOptions;

		/**
		 * @param ICWP_APP_FeatureHandler_Plugin $oFeatureOptions
		 */
		public function __construct( ICWP_APP_FeatureHandler_Plugin $oFeatureOptions ) {
			parent::__construct( $oFeatureOptions );
		}

		/**
		 */
		public function run() {

			/**
			 * Always perform the API check, as this is used for linking as well and requires
			 * a different variation of POST variables.
			 */
			add_action( 'plugins_loaded', array( $this, 'onWpPluginsLoaded' ), 1 );
			add_action( 'init', array( $this, 'onWpInit' ), 1 );
			add_action( 'wp_loaded', array( $this, 'onWpLoaded' ), 1 );
			$oCon = $this->getController();
			add_filter( $oCon->doPluginPrefix( 'verify_site_can_handshake' ), array( $this, 'doVerifyCanHandshake' ) );
			add_filter( $oCon->doPluginPrefix( 'verify_is_icwp_authenticated' ), array( $this, 'getIcwpAuthenticated' ) );
			add_filter( $oCon->doPluginPrefix( 'is_linked' ), array( $this->getFeatureOptions(), 'getIsSiteLinked' ) );

			if ( true ) {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			}

			$oDp = $this->loadDataProcessor();
			if ( ( $oDp->FetchRequest( 'getworpitpluginurl' ) == 1 ) ) {
				$this->returnIcwpPluginUrl();
			}

			if ( $oCon->getIsValidAdminArea() ) {
				$oFO = $this->getFeatureOptions();
				add_filter( $oFO->doPluginPrefix( 'admin_notices' ), array( $this, 'adminNoticeFeedback' ) );
				add_filter( $oFO->doPluginPrefix( 'admin_notices' ), array( $this, 'adminNoticeAddSite' ) );
			}

			add_action( 'wp_footer', array( $this, 'printPluginUri') );
		}

		public function onWpPluginsLoaded() {
			$this->doWpEngine();
		}

		public function onWpInit() {
		}

		public function onWpLoaded() {
			$this->doAPI();
		}

		/**
		 * @param boolean $fIsIcwp
		 *
		 * @return boolean
		 */
		public function getIcwpAuthenticated( $fIsIcwp ) {

			if ( !$fIsIcwp ) {
				return false;
			}

			// We shouldn't be recognised as authenticated unless we're at least linked
			if ( !$this->getFeatureOptions()->getIsSiteLinked() ) {
				return false;
			}

			$oDp = $this->loadDataProcessor();

			// Otherwise we use the old-style Key + PIN Auth sent in the POST
			$sAuthKey = $this->getOption( 'key' );
			$sPostKey = $oDp->FetchPost( 'key' );
			$sPostPin = $oDp->FetchPost( 'pin' );
			if ( empty( $sPostKey ) || empty( $sPostPin ) ) {
				return false;
			}

			if ( $sAuthKey != trim( $sPostKey ) ) {
				return false;
			}

			$sPin = $this->getOption( 'pin' );
			if ( $sPin !== md5( trim( $sPostPin ) ) ) {
				return false;
			}

			return true;
		}

		/**
		 * @param boolean $fCanHandshake
		 *
		 * @return boolean
		 */
		public function doVerifyCanHandshake( $fCanHandshake ) {

			$nTimeout = 20;
			$sHandshakeVerifyUrl = $this->getFeatureOptions()->getOpt( 'handshake_verify_url' );
			$aArgs = array(
				'timeout'		=> $nTimeout,
				'redirection'	=> $nTimeout,
				'sslverify'		=> true //this is default, but just to make sure.
			);
			$oFs = $this->loadFileSystemProcessor();
			$sResponse = $oFs->getUrlContent( $sHandshakeVerifyUrl, $aArgs );

			if ( !$sResponse ) {
				return false;
			}
			$oJson = $this->loadDataProcessor()->doJsonDecode( trim( $sResponse ) );

			return ( isset( $oJson->success ) && $oJson->success === true );
		}

		/**
		 * If any of the conditions are met and our plugin executes either the transport or link
		 * handlers, then all execution will end
		 * @uses die
		 * @return void
		 */
		protected function doAPI() {
			if ( isset( $_GET['worpit_link'] ) && !empty( $_GET['worpit_link'] ) ) {
				define( 'WORPIT_DIRECT_API', 1 );
				include_once( dirname(__FILE__).'/link.php' );
				die();
			}
			else if ( isset( $_GET['worpit_api'] ) && !empty( $_GET['worpit_api'] ) ) {
				define( 'WORPIT_DIRECT_API', 1 );
				include_once( dirname(__FILE__).'/transport.php' );
				die();
			}
		}

		/**
		 *
		 */
		protected function doWpEngine() {
			if ( @getenv( 'IS_WPE' ) == '1' && class_exists( 'WpeCommon', false ) && $this->setAuthorizedUser() ) {
				$oWpEngineCommon = WpeCommon::instance();
				$oWpEngineCommon->set_wpe_auth_cookie();
			}
		}

		/**
		 * @return void
		 */
		protected function setAuthorizedUser() {
			// moved this to here to ensure it can't get called from elsewhere
			if ( !apply_filters( $this->getController()->doPluginPrefix( 'verify_is_icwp_authenticated' ), true ) ) {
				return false;
			}

			$oDp = $this->loadDataProcessor();
			$oWp = $this->loadWpFunctionsProcessor();
			$sWpUser = $oDp->FetchPost( 'wpadmin_user' );
			if ( empty( $sWpUser ) ) {

				if ( version_compare( $oWp->getWordpressVersion(), '3.1', '>=' ) ) {
					$aUserRecords = get_users( 'role=administrator' );
					if ( is_array( $aUserRecords ) && count( $aUserRecords ) ) {
						$oUser = $aUserRecords[0];
					}
				}
				else {
					$oUser = $oWp->getUserById( 1 );
				}
				$sWpUser = is_a( $oUser, 'WP_User' ) ? $oUser->get( 'user_login' ) : '';
			}

			$oWp->setUserLoggedIn( empty( $sWpUser ) ? 'admin' : $sWpUser );
			return true;
		}

		/**
		 * @return void
		 */
		public function printPluginUri() {
			if ( $this->getOption( 'assigned' ) !== 'Y' ) {
				echo '<!-- Worpit Plugin: '.$this->getController()->getPluginUrl().' -->';
			}
		}

		/**
		 * @uses die
		 * @return void
		 */
		protected function returnIcwpPluginUrl() {
			die( '<worpitresponse>'. $this->getController()->getPluginUrl() .'</worpitresponse>' );
		}

		/**
		 * @param array $aAdminNotices
		 * @return array
		 */
		public function adminNoticeFeedback( $aAdminNotices ) {
			$aAdminFeedbackNotice = $this->getOption( 'feedback_admin_notice' );

			if ( !empty( $aAdminFeedbackNotice ) && is_array( $aAdminFeedbackNotice ) ) {

				foreach ( $aAdminFeedbackNotice as $sNotice ) {
					if ( empty( $sNotice ) || !is_string( $sNotice ) ) {
						continue;
					}
					$aAdminNotices[] = $this->getAdminNoticeHtml( '<p>'.$sNotice.'</p>', 'updated', false );
				}
				$this->getFeatureOptions()->doClearAdminFeedback( 'feedback_admin_notice', array() );
			}

			return $aAdminNotices;
		}

		/**
		 * @param array $aAdminNotices
		 * @return array
		 */
		public function adminNoticeAddSite( $aAdminNotices ) {

			$oCon = $this->getController();
			$oWp = $this->loadWpFunctionsProcessor();
			$sAckPluginNotice = $oWp->getUserMeta( $oCon->doPluginOptionPrefix( 'ack_plugin_notice' ) );

			if ( apply_filters( $oCon->doPluginPrefix( 'is_linked' ), false ) ) {
				return;
			}

			$nCurrentUserId = 0;
			$sNonce = wp_nonce_field( $oCon->getPluginPrefix() );
			$sServiceName = $oCon->getHumanName();
			$sFormAction = $oCon->getPluginUrl_AdminMainPage();
			ob_start();
			include( $this->getFeatureOptions()->getViewSnippet( 'admin_notice_add_site' ) );
			$sNoticeMessage = ob_get_contents();
			ob_end_clean();

			$aAdminNotices[] = $this->getAdminNoticeHtml( $sNoticeMessage, 'error', false );
			return $aAdminNotices;
		}

		/**
		 * @return int
		 */
		protected function getInstallationDays() {
			$nTimeInstalled = $this->getFeatureOptions()->getOpt( 'installation_time' );
			if ( empty( $nTimeInstalled ) ) {
				return 0;
			}
			return round( ( time() - $nTimeInstalled ) / DAY_IN_SECONDS );
		}

	}

endif;
