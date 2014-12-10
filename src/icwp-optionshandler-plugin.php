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

require_once( dirname(__FILE__).ICWP_DS.'icwp-optionshandler-base.php' );

if ( !class_exists('ICWP_APP_FeatureHandler_Plugin') ):

	class ICWP_APP_FeatureHandler_Plugin extends ICWP_APP_FeatureHandler_Base {

		/**
		 * @var ICWP_APP_Processor_Plugin
		 */
		protected $oFeatureProcessor;

		public function __construct( $oPluginController, $aFeatureProperties = array() ) {
			parent::__construct( $oPluginController, $aFeatureProperties );
		}

		/**
		 * @return ICWP_APP_Processor_Plugin|null
		 */
		protected function loadFeatureProcessor() {
			if ( !isset( $this->oFeatureProcessor ) ) {
				require_once( $this->getController()->getPath_SourceFile( sprintf( 'icwp-processor-%s.php', 'plugin' ) ) );
				$this->oFeatureProcessor = new ICWP_APP_Processor_Plugin( $this );
			}
			return $this->oFeatureProcessor;
		}

		/**
		 */
		public function doClearAdminFeedback() {
			$this->setOpt( 'feedback_admin_notice', array() );
		}

		/**
		 * @param string $sMessage
		 */
		public function doAddAdminFeedback( $sMessage ) {
			$aFeedback = $this->getOpt( 'feedback_admin_notice', array() );
			$aFeedback[] = $sMessage;
			$this->setOpt( 'feedback_admin_notice', $aFeedback );
		}

		/**
		 * @param boolean $fDoHidePlugin
		 *
		 * @return bool
		 */
		public function getIfHidePlugin( $fDoHidePlugin ) {
			return $this->getIsSiteLinked() && $this->getOptIs( 'enable_hide_plugin', 'Y' );
		}

		/**
		 * @return bool
		 */
		public function getIsHandshakeEnabled() {
			return $this->getCanHandshake();
		}

		/**
		 * @param bool $fDoVerify
		 *
		 * @return bool
		 */
		public function getCanHandshake( $fDoVerify = false ) {

			if ( !$fDoVerify ) { // we always verify can handshake at least once every 24hrs
				$nSinceLastHandshakeCheck = time() - $this->getOpt( 'time_last_check_can_handshake', 0 );
				if ( $nSinceLastHandshakeCheck > DAY_IN_SECONDS ) {
					$fDoVerify = true;
				}
			}

			if ( $fDoVerify ) {
				$fCanHandshake = apply_filters( $this->getController()->doPluginPrefix( 'verify_site_can_handshake' ), false );
				$this->setOpt( 'can_handshake', ( $fCanHandshake ? 'Y' : 'N' ) );
			}
			return $this->getOptIs( 'can_handshake', 'Y' );
		}

		/**
		 * @return bool
		 */
		public function setCanHandshake() {
			return $this->getCanHandshake( true );
		}

		/***
		 * @return bool
		 */
		public function getIsSiteLinked() {
			return ( $this->getAssigned() == 'Y' && is_email( $this->getAssignedTo() ) );
		}

		public function doExtraSubmitProcessing() {
			$oDp = $this->loadDataProcessor();

			if ( $oDp->FetchPost( $this->getController()->doPluginOptionPrefix( 'reset_plugin' ) ) ) {
				$sTo = $this->getAssignedTo();
				$sKey = $this->getPluginAuthKey();
				$sPin = $this->getPluginPin();

				if ( !empty( $sTo ) && !empty( $sKey ) && !empty( $sPin ) ) {
					$aParts = array( urlencode( $sTo ), $sKey, $sPin );
					$this->loadFileSystemProcessor()->getUrl( $this->getOpt( 'reset_site_url' ) . implode( '/', $aParts ) );
				}
				$this->setOpt( 'assigned_to', '' );
				$this->setOpt( 'assigned', 'N' );
				$this->setOpt( 'key', '' );
				$this->setOpt( 'pin', '' );
				return;
			}

			//Clicked the button to remotely add site$this->getController()->doPluginOptionPrefix( 'reset_plugin' )
			if ( $oDp->FetchPost( $this->getController()->doPluginOptionPrefix( 'remotely_add_site_submit' ) ) ) {
				$sAuthKey = $oDp->FetchPost( 'account_auth_key' );
				$sEmailAddress = $oDp->FetchPost( 'account_email_address' );
				if ( $sAuthKey && $sEmailAddress ) {

					$sAuthKey = trim( $sAuthKey );
					$sEmailAddress = trim( $sEmailAddress );

					$oResponse = $this->doRemoteAddSiteLink( $sAuthKey, $sEmailAddress );
					if ( $oResponse ) {
						$this->doAddAdminFeedback( sprintf( ( '%s Plugin options updated successfully.' ), $this->getController()->getHumanName() ) );
					}
				}
				$this->doAddAdminFeedback( sprintf( ( '%s Site NOT added.' ), $this->getController()->getHumanName() ) );
				return;
			}
			$this->doAddAdminFeedback( sprintf( ( '%s Plugin options updated successfully.' ), $this->getController()->getHumanName() ) );
		}

		/**
		 * This function always returns false, however the return is never actually used just yet.
		 *
		 * @param string $sAuthKey
		 * @param string $sEmailAddress
		 *
		 * @return boolean
		 */
		public function doRemoteAddSiteLink( $sAuthKey, $sEmailAddress ) {
			if ( $this->getIsSiteLinked() ) {
				return false;
			}

			if ( strlen( $sAuthKey ) == 32 && is_email( $sEmailAddress ) ) {

				//looks good. Now attempt remote link.
				$aPostVars = array(
					'wordpress_url'				=> home_url(),
					'plugin_url'				=> $this->getController()->getPluginUrl(),
					'account_email_address'		=> $sEmailAddress,
					'account_auth_key'			=> $sAuthKey,
					'plugin_key'				=> $this->getOpt( 'key' )
				);
				$aArgs = array(
					'body'	=> $aPostVars
				);
				return $this->loadFileSystemProcessor()->postUrl( $this->getOpt( 'remote_add_site_url' ), $aArgs );
			}
			return false;
		}

		/**
		 * @return array
		 */
		public function getActivePluginFeatures() {
			$aActiveFeatures = $this->getOptionsVo()->getRawData_SingleOption( 'active_plugin_features' );
			$aPluginFeatures = array();
			if ( empty( $aActiveFeatures['value'] ) || !is_array( $aActiveFeatures['value'] ) ) {
				return $aPluginFeatures;
			}

			foreach( $aActiveFeatures['value'] as $nPosition => $aFeature ) {
				if ( isset( $aFeature['hidden'] ) && $aFeature['hidden'] ) {
					continue;
				}
				$aPluginFeatures[ $aFeature['slug'] ] = $aFeature;
			}
			return $aPluginFeatures;
		}

		/**
		 * @return string
		 */
		public function getAssigned() {
			$sOptionKey = 'assigned';
			$sNewPlugin = $this->getOpt( $sOptionKey );
			if ( $sNewPlugin != 'Y' ) {
				$sOldPlugin = $this->getPluginOptionPre290( $sOptionKey );
				$this->deletePluginOptionPre290( $sOptionKey );
				if ( $sOldPlugin == 'Y' ) {
					$sNewPlugin = $sOldPlugin;
					$this->setOpt( $sOptionKey, $sNewPlugin );
				}
			}
			return $sNewPlugin;
		}

		public function getAssignedTo() {
			$sOptionKey = 'assigned_to';
			$sNewPlugin = $this->getOpt( $sOptionKey );
			if ( empty( $sNewPlugin ) ) {
				$sOldPlugin = $this->getPluginOptionPre290( $sOptionKey );
				$this->deletePluginOptionPre290( $sOptionKey );
				if ( !empty( $sOldPlugin ) ) {
					$sNewPlugin = $sOldPlugin;
					$this->setOpt( $sOptionKey, $sNewPlugin );
				}
			}
			return $sNewPlugin;
		}

		/**
		 * @return string
		 */
		public function getPluginAuthKey() {
			$sOptionKey = 'key';
			$sNewPlugin = $this->getOpt( $sOptionKey );
			if ( empty( $sNewPlugin ) ) {
				$sOldPlugin = $this->getPluginOptionPre290( $sOptionKey );
				$this->deletePluginOptionPre290( $sOptionKey );
				if ( !empty( $sOldPlugin ) ) {
					$sNewPlugin = $sOldPlugin;
				}
				else {
					$sNewPlugin = $this->loadDataProcessor()->GenerateRandomString( 24, 7 );
				}
				$this->setOpt( $sOptionKey, $sNewPlugin );
			}
			return $sNewPlugin;
		}

		/**
		 * @return string
		 */
		public function getPluginPin() {
			$sOptionKey = 'pin';
			$sNewPlugin = $this->getOpt( $sOptionKey );
			if ( empty( $sNewPlugin ) ) {
				$sOldPlugin = $this->getPluginOptionPre290( $sOptionKey );
				$this->deletePluginOptionPre290( $sOptionKey );
				if ( !empty( $sOldPlugin ) ) {
					$sNewPlugin = $sOldPlugin;
					$this->setOpt( $sOptionKey, $sNewPlugin );
				}
			}
			return $sNewPlugin;
		}

		/**
		 * @param array $aSummaryData
		 * @return array
		 */
		public function filter_getFeatureSummaryData( $aSummaryData ) {
			return $aSummaryData;
		}

		/**
		 * This is the point where you would want to do any options verification
		 */
		protected function doPrePluginOptionsSave() {

			$oDp = $this->loadDataProcessor();

			$sAuthKey = $this->getPluginAuthKey();
			if ( empty( $sAuthKey ) || strlen( $sAuthKey ) != 24 ) {
				$this->setOpt( 'key', $oDp->GenerateRandomString( 24, 7 ) );
			}

			$nActivatedAt = $this->getOpt( 'activated_at' );
			if ( empty( $nActivatedAt ) ) {
				$this->setOpt( 'activated_at', $oDp->GetRequestTime() );
			}
			$nInstalledAt = $this->getOpt( 'installed_at' );
			if ( empty( $nInstalledAt ) ) {
				$this->setOpt( 'installed_at', $oDp->GetRequestTime() );
			}

			$this->setOpt( 'installed_version', $this->getController()->getVersion() );

			$nInstalledAt = $this->getOpt( 'installation_time' );
			if ( empty( $nInstalledAt ) || $nInstalledAt <= 0 ) {
				$this->setOpt( 'installation_time', time() );
			}
		}

		protected function updateHandler() {
			parent::updateHandler();

			//migrate from old
			if ( !$this->getIsSiteLinked() ) {
				$aOldOptions = array(
					'key',
					'pin',
					'assigned',
					'assigned_to',
					'can_handshake',
					'handshake_enabled'
				);
				foreach( $aOldOptions as $sOption ) {
					$mValue = $this->getPluginOptionPre290( $sOption );
					if ( $mValue !== false ) {
						$this->setOpt( $sOption, $mValue );
//						$oWp->deleteOption( 'worpit_admin_' . $sOption );
					}
				}
			}
		}

		/**
		 * @param $sKey
		 *
		 * @return mixed|void
		 */
		private function getPluginOptionPre290( $sKey ) {
			return get_option( 'worpit_admin_'.$sKey );
		}

		/**
		 * @param $sKey
		 *
		 * @return mixed|void
		 */
		private function deletePluginOptionPre290( $sKey ) {
			return delete_option( 'worpit_admin_'.$sKey );
		}
	}

endif;