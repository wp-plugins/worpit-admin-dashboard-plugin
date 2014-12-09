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

if ( !class_exists('ICWP_APP_AutoupdatesProcessor_V6') ):

	class ICWP_APP_AutoupdatesProcessor_V6 extends ICWP_APP_Processor_Base {

		const FilterPriority = 1001;

		/**
		 * @var boolean
		 */
		protected $fDoForceRunAutoupdates = false;

		/**
		 * @param ICWP_APP_FeatureHandler_Autoupdates $oFeatureOptions
		 */
		public function __construct( ICWP_APP_FeatureHandler_Autoupdates $oFeatureOptions ) {
			parent::__construct( $oFeatureOptions );
		}

		/**
		 * @param boolean $fDoForceRun
		 */
		public function setForceRunAutoupdates( $fDoForceRun ) {
			$this->fDoForceRunAutoupdates = $fDoForceRun;
		}

		/**
		 * @return boolean
		 */
		public function getIfForceRunAutoupdates() {
			return apply_filters( $this->getFeatureOptions()->doPluginPrefix( 'force_autoupdate' ), $this->fDoForceRunAutoupdates );
		}

		/**
		 */
		public function run() {

			$oDp = $this->loadDataProcessor();
			if ( $oDp->FetchGet( 'forcerun' ) == 1 ) {
				$this->setForceRunAutoupdates( true );
			}

			add_filter( 'allow_minor_auto_core_updates',	array( $this, 'autoupdate_core_minor' ), self::FilterPriority );
			add_filter( 'allow_major_auto_core_updates',	array( $this, 'autoupdate_core_major' ), self::FilterPriority );

			add_filter( 'auto_update_translation',	array( $this, 'autoupdate_translations' ), self::FilterPriority, 2 );
			add_filter( 'auto_update_plugin',		array( $this, 'autoupdate_plugins' ), self::FilterPriority, 2 );
			add_filter( 'auto_update_theme',		array( $this, 'autoupdate_themes' ), self::FilterPriority, 2 );

			if ( $this->getIsOption('enable_autoupdate_ignore_vcs', 'Y') ) {
				add_filter( 'automatic_updates_is_vcs_checkout', array( $this, 'disable_for_vcs' ), 10, 2 );
			}

			if ( $this->getIsOption('enable_autoupdate_disable_all', 'Y') ) {
				add_filter( 'automatic_updater_disabled', '__return_true', self::FilterPriority );
			}

			add_filter( 'auto_core_update_send_email', array( $this, 'autoupdate_send_email' ), self::FilterPriority, 1 ); //more parameter options here for later
			add_filter( 'auto_core_update_email', array( $this, 'autoupdate_email_override' ), self::FilterPriority, 1 ); //more parameter options here for later

			add_action( 'wp_loaded', array( $this, 'force_run_autoupdates' ) );
		}

		/**
		 * Will force-run the WordPress automatic updates process and then redirect to the updates screen.
		 *
		 * @return bool
		 */
		public function force_run_autoupdates() {

			if ( !$this->getIfForceRunAutoupdates() ) {
				return true;
			}
			return $this->loadWpFunctionsProcessor()->doForceRunAutomaticUpdates();
		}

		/**
		 * This is a filter method designed to say whether a major core WordPress upgrade should be permitted,
		 * based on the plugin settings.
		 *
		 * @param boolean $fUpdate
		 * @return boolean
		 */
		public function autoupdate_core_major( $fUpdate ) {
			if ( $this->getIsOption( 'autoupdate_core', 'core_never' ) ) {
				return false;
			}
			else if ( $this->getIsOption( 'autoupdate_core', 'core_major' ) ) {
				return true;
			}
			return $fUpdate;
		}

		/**
		 * This is a filter method designed to say whether a minor core WordPress upgrade should be permitted,
		 * based on the plugin settings.
		 *
		 * @param boolean $fUpdate
		 * @return boolean
		 */
		public function autoupdate_core_minor( $fUpdate ) {
			if ( $this->getIsOption('autoupdate_core', 'core_never') ) {
				return false;
			}
			else if ( $this->getIsOption('autoupdate_core', 'core_minor') ) {
				return true;
			}
			return $fUpdate;
		}

		/**
		 * This is a filter method designed to say whether a WordPress translations upgrades should be permitted,
		 * based on the plugin settings.
		 *
		 * @param boolean $fUpdate
		 * @param string $sSlug
		 * @return boolean
		 */
		public function autoupdate_translations( $fUpdate, $sSlug ) {
			if ( $this->getIsOption( 'enable_autoupdate_translations', 'Y' ) ) {
				return true;
			}
			return $fUpdate;
		}

		/**
		 * This is a filter method designed to say whether WordPress plugin upgrades should be permitted,
		 * based on the plugin settings.
		 *
		 * @param boolean $fDoAutoUpdate
		 * @param StdClass|string $mItem
		 *
		 * @return boolean
		 */
		public function autoupdate_plugins( $fDoAutoUpdate, $mItem ) {

			// first, is global auto updates for plugins set
			if ( $this->getIsOption( 'enable_autoupdate_plugins', 'Y' ) ) {
				return true;
			}

			if ( is_object( $mItem ) && isset( $mItem->plugin ) )  { // WP 3.8.2+
				$sItemFile = $mItem->plugin;
			}
			else if ( is_string( $mItem ) ) { // WP pre-3.8.2
				$sItemFile = $mItem;
			}
			// at this point we don't have a slug to use so we just return the current update setting
			else {
				return $fDoAutoUpdate;
			}

			// If it's this plugin and autoupdate this plugin is set...
			if ( $sItemFile === $this->getFeatureOptions()->getPluginBaseFile() ) {
				if ( $this->getIsOption( 'autoupdate_plugin_self', 'Y' ) ) {
					$fDoAutoUpdate = true;
				}
			}

			$aAutoupdateFiles = $this->getFeatureOptions()->getAutoUpdates( 'plugins' );
			if ( !empty( $aAutoupdateFiles ) && is_array( $aAutoupdateFiles ) && in_array( $sItemFile, $aAutoupdateFiles ) ) {
				$fDoAutoUpdate = true;
			}
			return $fDoAutoUpdate;
		}

		/**
		 * This is a filter method designed to say whether WordPress theme upgrades should be permitted,
		 * based on the plugin settings.
		 *
		 * @param boolean $fDoAutoUpdate
		 * @param stdClass|string $mItem
		 *
		 * @return boolean
		 */
		public function autoupdate_themes( $fDoAutoUpdate, $mItem ) {

			// first, is global auto updates for themes set
			if ( $this->getIsOption( 'enable_autoupdate_themes', 'Y' ) ) {
				return true;
			}

			if ( is_object( $mItem ) && isset( $mItem->theme ) ) { // WP 3.8.2+
				$sItemFile = $mItem->theme;
			}
			else if ( is_string( $mItem ) ) { // WP pre-3.8.2
				$sItemFile = $mItem;
			}
			// at this point we don't have a slug to use so we just return the current update setting
			else {
				return $fDoAutoUpdate;
			}

			$aAutoupdateFiles = $this->getFeatureOptions()->getAutoUpdates( 'themes' );
			if ( !empty( $aAutoupdateFiles ) && is_array( $aAutoupdateFiles ) && in_array( $sItemFile, $aAutoupdateFiles ) ) {
				$fDoAutoUpdate = true;
			}
			return $fDoAutoUpdate;
		}

		/**
		 * This is a filter method designed to say whether WordPress automatic upgrades should be permitted
		 * if a version control system is detected.
		 *
		 * @param $checkout
		 * @param $context
		 * @return boolean
		 */
		public function disable_for_vcs( $checkout, $context ) {
			return false;
		}

		/**
		 * A filter on whether or not a notification email is send after core upgrades are attempted.
		 *
		 * @param boolean $fSendEmail
		 * @return boolean
		 */
		public function autoupdate_send_email( $fSendEmail ) {
			return $this->getIsOption( 'enable_upgrade_notification_email', 'Y' );
		}

		/**
		 * A filter on the target email address to which to send upgrade notification emails.
		 *
		 * @param array $aEmailParams
		 * @return array
		 */
		public function autoupdate_email_override( $aEmailParams ) {
			$sOverride = $this->getOption( 'override_email_address', '' );
			if ( !empty( $sOverride ) && is_email( $sOverride ) ) {
				$aEmailParams['to'] = $sOverride;
			}
			return $aEmailParams;
		}

		/**
		 * Removes all filters that have been added from auto-update related WordPress filters
		 */
		protected function removeAllAutoupdateFilters() {
			$aFilters = array(
				'allow_minor_auto_core_updates',
				'allow_major_auto_core_updates',
				'auto_update_translation',
				'auto_update_plugin',
				'auto_update_theme',
				'automatic_updates_is_vcs_checkout',
				'automatic_updater_disabled'
			);
			foreach( $aFilters as $sFilter ) {
				remove_all_filters( $sFilter );
			}
		}
	}

endif;

if ( !class_exists('ICWP_APP_Processor_Autoupdates') ):
	class ICWP_APP_Processor_Autoupdates extends ICWP_APP_AutoupdatesProcessor_V6 { }
endif;
