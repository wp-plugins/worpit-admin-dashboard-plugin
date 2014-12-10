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

if ( !class_exists( 'ICWP_APP_Processor_Plugin', false ) ):

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
		 * @return ICWP_APP_FeatureHandler_Plugin
		 */
		protected function getFeatureOptions() {
			return $this->oFeatureOptions;
		}

		/**
		 */
		public function run() {
			/**
			 * Always perform the API check, as this is used for linking as well and requires
			 * a different variation of POST variables.
			 */
			add_action( $this->getApiHook(), array( $this, 'doAPI' ), 1 );

			$oCon = $this->getController();
			add_filter( $oCon->doPluginPrefix( 'get_service_ips_v4' ), array( $this, 'getServiceIpAddressesV4' ) );
			add_filter( $oCon->doPluginPrefix( 'get_service_ips_v6' ), array( $this, 'getServiceIpAddressesV6' ) );

			add_filter( $oCon->doPluginPrefix( 'verify_site_can_handshake' ), array( $this, 'doVerifyCanHandshake' ) );
			add_filter( $oCon->doPluginPrefix( 'hide_plugin' ), array( $this->getFeatureOptions(), 'getIfHidePlugin' ) );
			add_filter( $oCon->doPluginPrefix( 'filter_hidePluginMenu' ), array( $this->getFeatureOptions(), 'getIfHidePlugin' ) );

			$oDp = $this->loadDataProcessor();
			if ( ( $oDp->FetchRequest( 'getworpitpluginurl', false ) == 1 ) || $oDp->FetchRequest( 'geticwppluginurl', false ) == 1 ) {
				$this->returnIcwpPluginUrl();
			}

			if ( $oCon->getIsValidAdminArea() ) {
				$oFO = $this->getFeatureOptions();
				add_filter( $oFO->doPluginPrefix( 'admin_notices' ), array( $this, 'adminNoticeFeedback' ) );
				add_filter( $oFO->doPluginPrefix( 'admin_notices' ), array( $this, 'adminNoticeAddSite' ) );
			}

			add_action( 'wp_footer', array( $this, 'printPluginUri') );
		}

		/**
		 * @return string
		 */
		protected function getApiHook() {
			return 'wp_loaded';
		}

		/**
		 * @return array
		 */
		public function getServiceIpAddressesV4() {
			return $this->getValidServiceIps( 'ipv4' );
		}

		/**
		 * @return array
		 */
		public function getServiceIpAddressesV6() {
			return $this->getValidServiceIps( 'ipv6' );
		}

		/**
		 * @param string $sIps
		 *
		 * @return array
		 */
		protected function getValidServiceIps( $sIps = 'ipv4' ) {
			$aLists = $this->getOption( 'service_ip_addresses_'.$sIps, array() );
			if ( isset( $aLists['valid'] ) && is_array( $aLists['valid'] ) ) {
				return $aLists['valid'];
			}
			return array();
		}

		/**
		 * @param boolean $fCanHandshake
		 *
		 * @return boolean
		 */
		public function doVerifyCanHandshake( $fCanHandshake ) {
			$oDp = $this->loadDataProcessor();
			$oFO = $this->getFeatureOptions();

			$oFO->setOpt( 'time_last_check_can_handshake', $this->time() );

			// First simply check SSL usage
			if ( $oDp->getCanOpensslSign() ) {
				return true;
			}

			$nTimeout = 20;
			$sHandshakeVerifyTestUrl = $oFO->getOpt( 'handshake_verify_test_url' );
			$aArgs = array(
				'timeout'		=> $nTimeout,
				'redirection'	=> $nTimeout,
				'sslverify'		=> true //this is default, but just to make sure.
			);
			$oFs = $this->loadFileSystemProcessor();
			$sResponse = $oFs->getUrlContent( $sHandshakeVerifyTestUrl, $aArgs );

			if ( !$sResponse ) {
				return false;
			}
			$oJsonResponse = $oDp->doJsonDecode( trim( $sResponse ) );
			return ( is_object( $oJsonResponse ) && isset( $oJsonResponse->success ) && $oJsonResponse->success === true );
		}

		/**
		 * If any of the conditions are met and our plugin executes either the transport or link
		 * handlers, then all execution will end
		 * @uses die
		 * @return void
		 */
		public function doAPI() {
			$oDp = $this->loadDataProcessor();
			if ( $oDp->FetchGet( 'worpit_link' ) == 1 ) {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				require_once( 'icwp-processor-plugin_sitelink.php' );
				$oLinkProcessor = new ICWP_APP_Processor_Plugin_SiteLink( $this->getFeatureOptions() );
				$oLinkResponse = $oLinkProcessor->run();
				$this->sendApiResponse( $oLinkResponse );
				die();
			}
			else if ( $oDp->FetchGet('worpit_api') == 1 ) {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				require_once( 'icwp-processor-plugin_api.php' );
				$oApiProcessor = new ICWP_APP_Processor_Plugin_Api( $this->getFeatureOptions() );
				$oApiResponse = $oApiProcessor->run();
				$this->sendApiResponse( $oApiResponse );
				die();
			}
		}

		/**
		 * @uses die() / wp_die()
		 *
		 * @param stdClass|string $mResponse
		 * @param boolean $fDoBinaryEncode
		 */
		protected function sendApiResponse( $mResponse, $fDoBinaryEncode = true ) {

			if ( is_object( $mResponse ) && isset( $mResponse->die ) && $mResponse->die ) {
				wp_die( $mResponse->error_message );
				return;
			}

			$oResponse = $fDoBinaryEncode ? base64_encode( serialize( $mResponse ) ) : $mResponse;

			$this->sendHeaders( $fDoBinaryEncode );
			echo "<icwp>".$oResponse."</icwp>";
			echo "<icwpversion>".$this->getFeatureOptions()->getVersion()."</icwpversion>";
			die();
		}

		/**
		 * @param bool $fAsBinary
		 */
		protected function sendHeaders( $fAsBinary = true ) {
			if ( $fAsBinary ) {
				header( "Content-type: application/octet-stream" );
				header( "Content-Transfer-Encoding: binary");
			}
			else {
				header( "Content-type: text/html" );
				header( "Content-Transfer-Encoding: quoted-printable");
			}
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
		 * @return void
		 */
		protected function returnIcwpPluginUrl() {
			$this->sendApiResponse( $this->getController()->getPluginUrl(), false );
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

			if ( $this->getFeatureOptions()->getIsSiteLinked() ) {
				return;
			}

			$nCurrentUserId = 0;
			$sNonce = wp_nonce_field( $oCon->getPluginPrefix() );
			$sServiceName = $oCon->getHumanName();
			$sFormAction = $oCon->getPluginUrl_AdminMainPage();
			$sAuthKey = $this->getFeatureOptions()->getPluginAuthKey();
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
