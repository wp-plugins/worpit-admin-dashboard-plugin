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
			add_action( 'init', array( $this, 'onWpInit' ), 1 );
			add_action( 'plugins_loaded', array( $this, 'onWpPluginsLoaded' ), 1 );

			$oDp = $this->loadDataProcessor();

			if ( ( $oDp->FetchRequest( 'getworpitpluginurl' ) == 1 ) ) {
				$this->returnIcwpPluginUrl();
			}

			if ( $this->getController()->getIsValidAdminArea() ) {
				$oFO = $this->getFeatureOptions();
				add_filter( $oFO->doPluginPrefix( 'admin_notices' ), array( $this, 'adminNoticeFeedback' ) );
				add_filter( $oFO->doPluginPrefix( 'admin_notices' ), array( $this, 'adminNoticeAddSite' ) );
			}

			add_action( 'wp_footer', array( $this, 'printPluginUri') );
		}

		public function onWpPluginsLoaded() {
			$this->doWpEngine();
			$this->doPluginStates();
		}

		public function onWpInit() {
			$this->doAPI();
		}

		/**
		 * @param $fOverride
		 */
		protected function doPluginStates() {
			$oDp = $this->loadDataProcessor();
			$oWp = $this->loadWpFunctionsProcessor();

			if ( $oDp->FetchGet( 'geticwpstate' ) ) {
				$aStates = array(
					'can_handshake'			=> $this->getOption( 'can_handshake' ),
					'handshake_enabled'		=> $this->getOption( 'handshake_enabled' ),
					'plugin_url'			=> $this->getController()->getPluginUrl()
				);
				$sResponse = '<icwpresponse>'.serialize( $aStates ).'</icwpresponse>';
				die( $sResponse );
			}
			if ( $oDp->FetchGet( 'seticwpstate' ) && ICWP_Plugin::GetIsVisitorIcwp()  ) {
				//TODO: FIX
				if ( $oDp->FetchGet( 'handshake_enabled' ) && $this->getOption( 'handshake_enabled' ) != 'Y' ) {
					$this->setHandshakeEnabled( true );
				}
			}
		}

		/**
		 */
		protected function doSetPluginStates() {

			if ( !empty( $_GET['seticwpstate'] ) && self::GetIsVisitorIcwp() ) {
			}
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
			if ( @getenv( 'IS_WPE' ) == '1' && class_exists( 'WpeCommon', false ) ) {
				$this->setAuthorizedUser();
				$oWpEngineCommon = WpeCommon::instance();
				$oWpEngineCommon->set_wpe_auth_cookie();
			}
		}

		/**
		 * @return void
		 */
		protected function setAuthorizedUser() {
			// moved this to here to ensure it can't get called from elsewhere
			if ( !ICWP_Plugin::GetIcwpAuthenticated( true ) ) {
				return;
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

			if ( Worpit_Plugin::IsLinked() || $sAckPluginNotice == 'Y' ) {
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
