<?php

if ( !class_exists( 'ICWP_APP_Processor_Security_V1', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base.php' );

	class ICWP_APP_Processor_Security_V1 extends ICWP_APP_Processor_Base {

		/**
		 */
		public function run() {

			if ( $this->getIsOption( 'cloudflare_flexible_ssl', 'Y' ) ) {
				$this->doCloudflareFlexibleSslCompatibility();
			}

			if ( $this->getIsOption( 'disallow_file_edit', 'Y' ) ) {
				if ( !defined( 'DISALLOW_FILE_EDIT' ) ) {
					define( 'DISALLOW_FILE_EDIT', true );
				}
				add_filter( 'user_has_cap', array( $this, 'disallowFileEditing' ), 100, 3 );
			}

			if ( $this->getIsOption( 'force_ssl_admin', 'Y' ) && function_exists( 'force_ssl_admin' ) ) {
				if ( !defined( 'FORCE_SSL_ADMIN' ) ) {
					define( 'FORCE_SSL_ADMIN', true );
				}
				force_ssl_admin( true );
			}

			if ( $this->getIsOption( 'hide_wp_version', 'Y' ) ) {
				remove_action( 'wp_head', 'wp_generator' );
			}

			if ( $this->getIsOption( 'hide_wlmanifest_link', 'Y' ) ) {
				remove_action( 'wp_head', 'wlwmanifest_link' );
			}

			if ( $this->getIsOption( 'hide_rsd_link', 'Y' ) ) {
				remove_action( 'wp_head', 'rsd_link' );
			}
		}

		/**
		 * Does nothing if the site already "knows" it's SSL.
		 */
		protected function doCloudflareFlexibleSslCompatibility() {
			if ( is_ssl() ) {
				return;
			}
			if ( ( isset( $_SERVER['HTTP_CF_VISITOR'] ) && strpos( $_SERVER['HTTP_CF_VISITOR'], 'https' ) !== false ) ||
			     ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) == 'https' ) ) {
				$_SERVER['HTTPS'] = 'on';
			}
		}

		/**
		 * @param array $aAllCaps
		 * @param array $aCap
		 * @param array $aArgs
		 *
		 * @return array
		 */
		public function disallowFileEditing( $aAllCaps, $aCap, $aArgs ) {

			$aEditCapabilities = array( 'edit_themes', 'edit_plugins', 'edit_files' );
			$sRequestedCapability = $aArgs[0];

			if ( !in_array( $sRequestedCapability, $aEditCapabilities ) ) {
				return $aAllCaps;
			}
			$aAllCaps[ $sRequestedCapability ] = false;
			return $aAllCaps;
		}

	}

endif;

if ( !class_exists( 'ICWP_APP_Processor_Security', false ) ):
	class ICWP_APP_Processor_Security extends ICWP_APP_Processor_Security_V1 { }
endif;