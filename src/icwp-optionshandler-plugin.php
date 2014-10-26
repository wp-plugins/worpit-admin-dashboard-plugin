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

if ( !class_exists('ICWP_APP_FeatureHandler_Plugin') ):

	class ICWP_APP_FeatureHandler_Plugin extends ICWP_APP_FeatureHandler_Base {

		/**
		 * @var ICWP_APP_Processor_Plugin
		 */
		protected $oFeatureProcessor;

		public function __construct( $oPluginController, $aFeatureProperties = array() ) {
			parent::__construct( $oPluginController, $aFeatureProperties );
//			add_filter( $this->doPluginPrefix( $this->getFeatureSlug().'display_data' ), array( $this, 'getDisplayData' ) );
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

		public function doExtraSubmitProcessing() {

			$oDp = $this->loadDataProcessor();
			//Clicked the button to enable/disable hand-shaking
			if ( $oDp->FetchPost( 'icwp_admin_form_submit_handshake' ) ) {
				if ( $oDp->FetchPost( 'icwp_admin_handshake_enabled' ) ) {
					ICWP_Plugin::SetHandshakeEnabled( true );
				}
				else {
					ICWP_Plugin::SetHandshakeEnabled( false );
				}
				header( "Location: admin.php?page=".self::$ParentMenuId );
				return;
			}

			$this->doAddAdminFeedback( sprintf( _wpsf__( '%s Plugin options updated successfully.' ), $this->getController()->getHumanName() ) );
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
		 * @return mixed
		 */
		public function getIsMainFeatureEnabled() {
			return true;
		}

		/**
		 * @param array $aSummaryData
		 * @return array
		 */
		public function filter_getFeatureSummaryData( $aSummaryData ) {
			return $aSummaryData;
		}

		/**
		 * @param array $aOptionsParams
		 * @return array
		 * @throws Exception
		 */
		protected function loadStrings_SectionTitles( $aOptionsParams ) {

			$sSectionSlug = $aOptionsParams['section_slug'];
			switch( $aOptionsParams['section_slug'] ) {

				case 'section_general_plugin_options' :
					$sTitle = _wpsf__( 'General Plugin Options' );
					break;

				default:
					throw new Exception( sprintf( 'A section slug was defined but with no associated strings. Slug: "%s".', $sSectionSlug ) );
			}
			$aOptionsParams['section_title'] = $sTitle;
			return $aOptionsParams;
		}

		/**
		 * @param array $aOptionsParams
		 * @return array
		 * @throws Exception
		 */
		protected function loadStrings_Options( $aOptionsParams ) {

			$sKey = $aOptionsParams['key'];
			switch( $sKey ) {
				default:
					throw new Exception( sprintf( 'An option has been defined but without strings assigned to it. Option key: "%s".', $sKey ) );
			}

			$aOptionsParams['name'] = $sName;
			$aOptionsParams['summary'] = $sSummary;
			$aOptionsParams['description'] = $sDescription;
			return $aOptionsParams;
		}

		/**
		 * This is the point where you would want to do any options verification
		 */
		protected function doPrePluginOptionsSave() {

			$aOldOptions = array(
				'key',
				'pin',
				'assigned',
				'assigned_to',
				'can_handshake',
				'handshake_enabled'
			);
			foreach( $aOldOptions as $sOption ) {
				$this->setOpt( $sOption, ICWP_Plugin::getOption( $sOption ) );
			}

			$nInstalledAt = $this->getOpt( 'installation_time' );
			if ( empty($nInstalledAt) || $nInstalledAt <= 0 ) {
				$this->setOpt( 'installation_time', time() );
			}
		}

		protected function updateHandler() {
			parent::updateHandler();

			if ( $this->getVersion() == '0.0' ) {
				return;
			}
		}
	}

endif;