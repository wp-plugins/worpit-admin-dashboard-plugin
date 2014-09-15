<?php
/**
 * Copyright (c) 2013 iControlWP <support@icontrolwp.com>
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

require_once(dirname(__FILE__) . '/base/icwp-processor-basedb.php');

if ( !class_exists('ICWP_Processor_Compatibility_CP') ):

class ICWP_Processor_Compatibility_CP extends ICWP_Processor_Base_CP {

	const Slug = 'compatibility';

	public function __construct( $insOptionPrefix = '' ) {
		parent::__construct( $this->constructStorageKey( $insOptionPrefix, self::Slug ), self::Slug );
		$this->reset();
	}

	/**
	 */
	public function run() {
		parent::run();
		if ( !$this->getCanRun() ) {
			return;
		}

		// always do whitelists when the admin side is loaded - no need on the frontend
		if ( is_admin() ) {
			$this->setupWhitelists();
		}

		if ( $this->isRequestIcwp() ) {
			// Only when the request comes from iControlWP.
			$this->unhookMaintenanceModePlugins();
			$this->unhookSecurityPlugins();
		}
	}

	/**
	 * @return bool
	 */
	public function isRequestIcwp() {
		$sIp = $this->getVisitorIpAddress( false );
		if ( $sIp === false ) {//couldn't get an IP
			$this->loadDataProcessor();
			$sLink = ICWP_Processor_Data_CP::FetchGet( 'worpit_link' );
			$sPreLink = ICWP_Processor_Data_CP::FetchGet( 'worpit_prelink' );
			return ( $sLink || $sPreLink );
		}
		else if ( in_array( $sIp, $this->getOption( 'service_ip_addresses_ipv4', array() ) )
			|| in_array( $sIp, $this->getOption( 'service_ip_addresses_ipv6', array() ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Should be hooked to 'plugins_loaded' and will add iCWP IPs automatically where possible
	 * to the whitelists of certain plugins that might otherwise block us.
	 */
	public function setupWhitelists() {
		$this->addToWordfence();
		$this->addToBadBehaviour();
		$this->addToWordpressFirewall2();
//		$this->addToIThemesSecurity();
		// Add WordPress Simple Firewall plugin whitelist
		add_filter( 'icwp_simple_firewall_whitelist_ips', array( $this, 'addToSimpleFirewallWhitelist' ) );
	}

	protected function addToWordfence() {
		$fInstalled = ( class_exists( 'wfConfig', false ) && method_exists( 'wfConfig', 'get' ) && method_exists( 'wfConfig', 'set' ) );
		if ( !$fInstalled ) {
			return;
		}

		$fAdded = false;
		$aServiceIps = $this->getOption( 'service_ip_addresses_ipv4' );
		$sWfIpWhitelist = wfConfig::get( 'whitelisted' );
		if ( empty($sWfIpWhitelist) ) {
			$aWfIps = $aServiceIps;
			$fAdded = true;
		}
		else {
			$aWfIps = explode(',', $sWfIpWhitelist);
			foreach( $aServiceIps as $sServiceIp ) {
				if ( !in_array( $sServiceIp, $aWfIps ) ) {
					$aWfIps[] = $sServiceIp;
					$fAdded = true;
				}
			}
		}

		if ( $fAdded ) {
			$sWfIpWhitelist = implode(',', $aWfIps);
			wfConfig::set( 'whitelisted', $sWfIpWhitelist );
		}
	}

	protected function addToBadBehaviour() {
		$fInstalled = ( defined('BB2_VERSION') && defined('BB2_CORE') && function_exists('bb2_read_whitelist') );
		if ( !$fInstalled ) {
			return;
		}

		$fAdded = false;
		$aServiceIps = $this->getOption( 'service_ip_addresses_ipv4' );
		$sBbIpWhitelist = bb2_read_whitelist();
		if ( empty( $sBbIpWhitelist['ip'] ) || !is_array( $sBbIpWhitelist['ip'] ) ) {
			$sBbIpWhitelist['ip'] = $aServiceIps;
			$fAdded = true;
		}
		else {
			foreach( $aServiceIps as $sServiceIp ) {
				if ( !in_array( $sServiceIp, $sBbIpWhitelist['ip'] ) ) {
					$sBbIpWhitelist['ip'][] = $sServiceIp;
					$fAdded = true;
				}
			}
		}

		if ( $fAdded ) {
			update_option( 'bad_behavior_whitelist', $sBbIpWhitelist );
		}
	}

	/**
	 * If Wordfence is found on the site, it'll add the iControlWP IP address to the whitelist
	 * @return boolean
	 */
	protected function addToWordpressFirewall2() {
		$fUpdate = false;
		$mWhiteListIps = get_option( 'WP_firewall_whitelisted_ip' );
		if ( $mWhiteListIps !== false ) { //WP firewall 2 is installed.

			$aFirewallIps = maybe_unserialize( $mWhiteListIps );
			if ( !is_array( $aFirewallIps ) ) {
				return;
			}

			$aServiceIps = $this->getOption( 'service_ip_addresses_ipv4', array() );
			foreach( $aServiceIps as $sAddress ) {
				if ( !in_array( $sAddress, $aFirewallIps ) ) {
					$aFirewallIps[] = $sAddress;
					$fUpdate = true;
				}
			}
			if ( $fUpdate ) {
				update_option( 'WP_firewall_whitelisted_ip', serialize( $aFirewallIps ) );
			}
		}
		return $fUpdate;
	}

	/**
	 * If Wordfence is found on the site, it'll add the iControlWP IP address to the whitelist
	 * @return boolean
	 */
	protected function addToIThemesSecurity() {
		// Now handle it as the new iThemes Security
		global $itsec_globals;
		if ( isset( $itsec_globals ) && is_array( $itsec_globals ) && !empty( $itsec_globals['settings'] ) ) {
			$aItsecIpsWhiteList = isset( $itsec_globals['settings']['white_list'] ) ? $itsec_globals['settings']['white_list'] : array();
			$aItsecIpsLockoutWhiteList = isset( $itsec_globals['settings']['lockout_white_list'] ) ? $itsec_globals['settings']['lockout_white_list'] : array();

			$aOurIps = $this->getOption( 'service_ip_addresses_ipv4', array() );
			$fAdded = false;
			foreach( $aOurIps as $sIp ) {
				if ( !in_array( $sIp, $aItsecIpsWhiteList ) ) {
					$aItsecIpsWhiteList[] = $sIp;
					$fAdded = true;
				}

				if ( !in_array( $sIp, $aItsecIpsLockoutWhiteList ) ) {
					$aItsecIpsLockoutWhiteList[] = $sIp;
					$fAdded = true;
				}
			}
			if ( $fAdded ) {
				$itsec_globals['settings']['lockout_white_list'] = $aItsecIpsLockoutWhiteList;
				$itsec_globals['settings']['white_list'] = $aItsecIpsWhiteList;
				update_site_option( 'itsec_global', $itsec_globals );
			}
		}
	}

	/**
	 * Adds the iControlWP public IP addresses to the Simple Firewall Whitelist.
	 *
	 * @param array $aWhitelistIps
	 * @return array
	 */
	public function addToSimpleFirewallWhitelist( $aWhitelistIps ) {
		$aServiceIps = $this->getOption( 'service_ip_addresses_ipv4', array() );
		foreach ( $aServiceIps as $sAddress ) {
			if ( !in_array( $sAddress, $aWhitelistIps ) ) {
				$aWhitelistIps[ $sAddress ] = $this->getOption( 'service_name', 'iControlWP' );
			}
		}
		return $aWhitelistIps;
	}

	/**
	 * Removes any interruption from Maintenance Mode plugins while iControlWP is executing a package.
	 * @return void
	 */
	public function unhookMaintenanceModePlugins() {
		//ET Anticipate Maintenance Plugin from elegant themes
		if ( class_exists( 'ET_Anticipate' ) ) {
			remove_action( 'init', 'ET_Anticipate_Init', 5 );
		}

		// WP Maintenance Mode Plugin
		// http://wordpress.org/extend/plugins/themefuse-maintenance-mode/developers/
		if ( class_exists( 'WPMaintenanceMode' ) ) {
			remove_action( 'plugins_loaded', array ( 'WPMaintenanceMode', 'get_instance' ) );
		}

		//Maintenance Mode Plugin
		global $myMaMo;
		if ( class_exists( 'MaintenanceMode' ) && isset( $myMaMo ) && is_object( $myMaMo ) ) {
			remove_action( 'plugins_loaded', array( $myMaMo, 'ApplyMaintenanceMode') );
		}

		// ThemeFuse Maintenance Mode Plugin
		// http://wordpress.org/extend/plugins/themefuse-maintenance-mode/developers/
		if ( class_exists( 'tf_maintenance' ) ) {
			remove_action( 'init', 'tf_maintenance_Init', 5 );
		}

		//underConstruction plugin
		global $underConstructionPlugin;
		if ( class_exists( 'underConstruction' ) && isset( $underConstructionPlugin ) && is_object( $underConstructionPlugin ) ) {
			remove_action( 'template_redirect', array( $underConstructionPlugin, 'uc_overrideWP' ) );
			remove_action( 'admin_init', array( $underConstructionPlugin, 'uc_admin_override_WP' ) );
			remove_action( 'wp_login', array( $underConstructionPlugin, 'uc_admin_override_WP' ) );
		}

		//Ultimate Maintenance Mode plugin
		global $seedprod_umm;
		if ( class_exists( 'SeedProd_Ultimate_Maintenance_Mode' ) && isset( $seedprod_umm ) && is_object( $seedprod_umm ) ) {
			remove_action( 'template_redirect', array( $seedprod_umm, 'render_maintenancemode_page' ) );
		}
		/* doesn't seem to work.
		global $seed_csp3;
		if ( class_exists( 'SEED_CSP3_PLUGIN' ) && isset( $seed_csp3 ) && is_object( $seed_csp3 ) ) {
			remove_action( 'template_redirect', array( $seed_csp3, 'render_comingsoon_page' ), 9 );
			remove_action( 'template_redirect', array( $seed_csp3, 'render_comingsoon_page' ) );
		}
		*/

		/*
		// This tries to ensure that no-one can just add "worpit_link" to a url to by-pass maintenance mode.
		if ( ( isset( $_GET['worpit_link'] ) || isset( $_GET['worpit_prelink'] ) ) && !$this->isVisitorIcwp() ) {
			add_action( 'init', array( $this, 'goBackHome' ), 99 );
		}
		*/
	}

	protected function unhookSecurityPlugins() {
		$this->removeSecureWpHooks();
		$this->removeAiowpsHooks(); //wp-security-core.php line 25
		$this->removeBetterWpSecurityHooks();
	}

	/**
	 * Remove actions setup by All In One WP Security plugin that interferes with iControlWP packages.
	 * @return void
	 */
	protected function removeAiowpsHooks() {
		if ( class_exists( 'AIO_WP_Security' ) && isset( $GLOBALS['aio_wp_security'] ) && is_object( $GLOBALS['aio_wp_security'] ) ) {
			remove_action( 'init', array( $GLOBALS['aio_wp_security'], 'wp_security_plugin_init'), 0);
		}
	}
	/**
	 * Remove actions setup by Secure WP plugin that interfere with Worpit synchronizing packages.
	 * @return void
	 */
	protected function removeSecureWpHooks() {
		global $SecureWP;
		if ( class_exists( 'SecureWP' ) && isset( $SecureWP ) && is_object( $SecureWP ) ) {
			remove_action( 'init', array( $SecureWP, 'replace_wp_version' ), 1 );
			remove_action( 'init', array( $SecureWP, 'remove_core_update' ), 1 );
			remove_action( 'init', array( $SecureWP, 'remove_plugin_update' ), 1 );
			remove_action( 'init', array( $SecureWP, 'remove_theme_update' ), 1 );
			remove_action( 'init', array( $SecureWP, 'remove_wp_version_on_admin' ), 1 );
		}
	}

	/**
	 * Remove actions setup by Better WP Security plugin that interfere with iControlWP synchronizing packages.
	 * Check secure.php for changes to these hooks.
	 * @return void
	 */
	protected function removeBetterWpSecurityHooks() {
		global $bwps, $bwpsoptions;

		if ( class_exists( 'bwps_secure' ) && isset( $bwps ) && is_object( $bwps ) ) {
			remove_action( 'plugins_loaded', array( $bwps, 'randomVersion' ) );
			remove_action( 'plugins_loaded', array( $bwps, 'pluginupdates' ) );
			remove_action( 'plugins_loaded', array( $bwps, 'themeupdates' ) );
			remove_action( 'plugins_loaded', array( $bwps, 'coreupdates' ) );
			remove_action( 'plugins_loaded', array( $bwps, 'siteinit' ) );
		}

		// Adds our IP addresses to the BWPS whitelist
		if ( !is_null( $bwpsoptions ) && is_array( $bwpsoptions ) ) {
			$sServiceIps = implode( "\n", $this->getOption( 'service_ip_addresses_ipv4', array() ) );
			if ( !isset( $bwpsoptions['id_whitelist'] ) || strlen( $bwpsoptions['id_whitelist'] ) == 0 ) {
				$bwpsoptions['id_whitelist'] = $sServiceIps;
			}
			else if ( strpos( $bwpsoptions['id_whitelist'], $sServiceIps ) === false ) {
				$bwpsoptions['id_whitelist'] .= "\n".$sServiceIps;
			}
		}
	}

	/**
	 * Based on various settings will determine whether the code may be printed
	 *
	 * @return boolean
	 */
	protected function getCanRun() {
		if ( $this->getDoIgnoreCurrentUser() ) {
			return false;
		}
		return true;
	}
}

endif;