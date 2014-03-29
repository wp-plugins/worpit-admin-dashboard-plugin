<?php
/*
Plugin Name: iControlWP
Plugin URI: http://icwp.io/home
Description: Take Control Of All WordPress Sites From A Single Dashboard
Version: 2.7.7
Author: iControlWP
Author URI: http://www.icontrolwp.com/
*/

/**
 * Copyright (c) 2013 iControlWP <support@icontrolwp.com>
 * All rights reserved.
 *
 * "iControlWP" (previously "Worpit") is distributed under the GNU General Public License, Version 2,
 * June 1991. Copyright (C) 1989, 1991 Free Software Foundation, Inc., 51 Franklin
 * St, Fifth Floor, Boston, MA 02110, USA
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

include_once( dirname(__FILE__).'/src/plugin/base.php' );
include_once( dirname(__FILE__).'/src/plugin/custom_options.php' );
//include_once( dirname(__FILE__).'/src/lib/security.php' );

if ( !defined( 'WORPIT_DS' ) ) {
	define( 'WORPIT_DS', DIRECTORY_SEPARATOR );
}

global $wpdb;

class Worpit_Plugin extends Worpit_Plugin_Base {

	/**
	 * @var string
	 */
	const DashboardUrlBase = 'https://app.icontrolwp.com/';
	
	/**
	 * @var string
	 */
	const RemoteAddSiteUrl = 'https://app.icontrolwp.com/system/remote/add_site';
	
	/**
	 * @var string
	 */
	const ServiceName = 'iControlWP';

	/**
	 * @var integer
	 */
	const LinkLimitTimeout = 0; //1hr

	/**
	 * @access static
	 * @var string
	 */
	static public $VERSION = '2.7.7';

	/**
	 * @access static
	 * @var array
	 */
	static private $ServiceIpAddressesIpv4 = array(
		'198.61.176.9',
		'198.61.173.69',
		'23.253.56.59',
		'23.253.62.185'
	);

	/**
	 * @access static
	 * @var array
	 */
	static private $ServiceIpAddressesIpv6 = array(
		'2001:4801:7817:0072:ca75:cc9b:ff10:4699',
		'2001:4801:7817:72:ca75:cc9b:ff10:4699',
		'2001:4801:7824:0101:ca75:cc9b:ff10:a7b2',
		'2001:4801:7824:101:ca75:cc9b:ff10:a7b2',
	);
	
	/**
	 * @access static
	 * @var string
	 */
	static public $CustomOptionsDbName = 'custom_options';
	
	/**
	 * @access static
	 * @var array
	 */
	static public $CustomOptions; //the array of options written to WP Options
	
	/**
	 * @var array
	 */
	protected $m_aWordPressSecurityOptions;
	
	/**
	 * @var Worpit_Auditor
	 */
	protected $m_oAuditor;

	/**
	 * @var string
	 */
	protected $m_sPluginFile;

	/**
	 * @var string
	 */
	protected $m_aLabelData;

	/**
	 * @var ICWP_Stats
	 */
	protected static $oStats = NULL;

	/**
	 * @var ICWP_WhiteLabel
	 */
	protected static $oWhiteLabelSystem = NULL;

	/**
	 * @var ICWP_Stats
	 */
	protected static $oStatsSystem = NULL;

	/**
	 * @var ICWP_GoogleAnalytics
	 */
	protected static $oGoogleAnalyticsSystem = NULL;

	/**
	 * @var ICWP_AutoUpdates
	 */
	protected static $oAutoUpdatesSystem = NULL;

	/**
	 * @var Worpit_Plugin
	 */
	protected static $oInstance = NULL;

	/**
	 * @return Worpit_Plugin
	 */
	public static function & GetInstance() {
		if ( is_null( self::$oInstance ) ) {
			self::$oInstance = new Worpit_Plugin();
		}
		return self::$oInstance;
	}

	/**
	 */
	public function __construct() {
		self::$oInstance = $this;

		parent::__construct();

		self::$PluginName		= basename(__FILE__);
		self::$PluginPath		= plugin_basename( dirname(__FILE__) );
		self::$PluginBasename	= plugin_basename( __FILE__ );
		self::$PluginDir		= WP_PLUGIN_DIR.WORPIT_DS.self::$PluginPath.WORPIT_DS;
		self::$PluginUrl		= plugins_url( '/', __FILE__ ); //this seems to use SSL more reliably than WP_PLUGIN_URL

		// Used for autoupdates
		$this->m_sPluginFile	= plugin_basename( __FILE__ );
		
		if ( is_admin() ) {
			/**
			 * Registers activate and deactivation hooks
			 */
			new Worpit_Install();
			new Worpit_Uninstall();
		}

		/**
		 * Always perform the API check, as this is used for linking as well and requires
		 * a different variation of POST variables.
		 */
		add_action( 'init', array( $this, 'doAPI' ), 1 );
		
		self::Load_CustomOptionsData();
		
		new Worpit_Plugin_Custom_Options( self::$CustomOptions );
	}
	
	/**
	 * @param string $insPluginKey
	 * @return boolean
	 */
	public function isPluginInstalled( $insPluginKey ) {
		$fInstalled = false;
		switch ( $insPluginKey ) {
			case 'wordfence':
				$fInstalled = ( class_exists( 'wfConfig', false ) && method_exists( 'wfConfig', 'get' ) && method_exists( 'wfConfig', 'set' ) );
				break;
		}
		return $fInstalled;
	}
	
	/**
	 * Should be hooked to 'plugins_loaded' and will remove the hooks of various plugins that
	 * mess with iCWP normal functioning.
	 */
	public function removePluginHooks() {
		$this->removeSecureWpHooks();
		$this->removeAiowpsHooks(); //wp-security-core.php line 25
		$this->removeBetterWpSecurityHooks();
		$this->removeMaintenanceModeHooks();
	}
	
	/**
	 * Should be hooked to 'plugins_loaded' and will add iCWP IPs automatically where possible
	 * to the whitelists of certain plugins that might otherwise block us.
	 */
	public function addToWhitelists() {
		$this->addToWordfenceWhitelist();
		$this->addToWordpressFirewall2();
		// Add WordPress Simple Firewall plugin whitelist
		add_filter( 'icwp_simple_firewall_whitelist_ips', array( $this, 'addToSimpleFirewallWhitelist' ) );
	}
	
	/**
	 * If Wordfence is found on the site, it'll add the iControlWP IP address to the whitelist
	 */
	protected function addToWordfenceWhitelist() {
		if ( !$this->isPluginInstalled( 'wordfence' ) ) {
			return;
		}
// 			wfConfig::set( 'whitelisted', '' );
// 			return;
			
		$fAdded = false;
		$sWfIpWhitelist = wfConfig::get( 'whitelisted' );
		$aServiceIps = self::$ServiceIpAddressesIpv4;
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
			self::updateOption( 'flag_whitelisted_ips_with_wordfence', 'Y' );
		}
	}
	
	/**
	 * Adds the iControlWP public IP addresses to the Simple Firewall Whitelist.
	 * 
	 * @param array $aWhitelistIps
	 * @return array
	 */
	public function addToSimpleFirewallWhitelist( $aWhitelistIps ) {
		foreach ( self::$ServiceIpAddressesIpv4 as $sAddress ) {
			if ( !in_array( $sAddress, $aWhitelistIps ) ) {
				$aWhitelistIps[ $sAddress ] = $this->m_aLabelData['service_name'];
			}
		}
		return $aWhitelistIps;
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
			
			foreach( self::$ServiceIpAddressesIpv4 as $sAddress ) {
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
	 * To force it to re-load from the WordPress options table pass true.
	 * @param boolean $infForceReload		(optional)
	 * @return void
	 */
	public static function Load_CustomOptionsData( $infForceReload = false ) {
		if ( isset( self::$CustomOptions ) && !$infForceReload ) {
			return true; //no need to reload the data if we have it already.
		}
		
		$oOptionVal = self::getOption( self::$CustomOptionsDbName );
		if ( $oOptionVal !== false ) { //these options have been set before
			self::$CustomOptions = $oOptionVal;
		}
		else {
			//first time create of options
			self::$CustomOptions = array();
		}
		
		self::validateCustomOptions();
		self::Store_CustomOptionsData();
	}
	
	/**
	 * Add new options here and they'll be added automatically in all future runs.
	 */
	protected static function validateCustomOptions() {
		self::Load_CustomOptionsData();
		$aDefaultOptions = array(
			'sec_hide_wp_version'			=>	'N',
			'sec_hide_wlmanifest_link'		=>	'N',
			'sec_hide_rsd_link'				=>	'N',
			'sec_set_random_script_version'	=>	'N',
			'sec_random_script_version'		=>	'',
		);
		
		foreach( $aDefaultOptions as $sKey => $sValue ) {
			if ( !array_key_exists( $sKey, self::$CustomOptions ) ) {
				self::$CustomOptions[$sKey] = $sValue;
			}
		}
	}
	
	/**
	 * @return boolean|void
	 */
	public static function Store_CustomOptionsData() {
		return self::updateOption( self::$CustomOptionsDbName, self::$CustomOptions );
	}
	
	/**
	 * Give an associative array.
	 * 	'key1' => 'value1'
	 * 	'key2' => 'value2'
	 *
	 * @param $inaNewOptions
	 * @return string
	 */
	public static function Update_CustomOptions( $inaNewOptions ) {
		self::Load_CustomOptionsData();
		
		foreach( $inaNewOptions as $sNewKey => $sNewValue ) {
			self::$CustomOptions[$sNewKey] = $sNewValue;
		}
		return self::Store_CustomOptionsData();
	}
	
	/**
	 * @param string $insKey
	 * @param boolean $infForceReload		(optional)
	 * @return string
	 */
	static public function Get_CustomOption( $insKey, $infForceReload = false ) {
		self::Load_CustomOptionsData( $infForceReload );
		return self::$CustomOptions[$insKey];
	}
	
	/**
	 * If any of the conditions are met and our plugin executes either the transport or link
	 * handlers, then all execution will end
	 * @uses die
	 * @return void
	 */
	public function doAPI() {
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
	 * @uses die
	 * @return void
	 */
	public function returnIcwpPluginUrl() {
		die( '<worpitresponse>'. plugins_url( '/', __FILE__ ) .'</worpitresponse>' );
	}

	/**
	 * A modified copy of that in transport.php to verify the key and the pin
	 *
	 * @return boolean
	 */
	public function icwpAuthenticate() {
		
		if ( !isset( $_POST['key'] ) || !isset( $_POST['pin'] ) ) {
			return false;
		}


		$sOption = Worpit_Plugin::getOption( 'assigned' );
		$fAssigned = ($sOption == 'Y');
		if ( !$fAssigned ) {
			return false;
		}

		$sKey = Worpit_Plugin::getOption( 'key' );
		if ( $sKey != trim( $_POST['key'] ) ) {
			return false;
		}

		$sPin = Worpit_Plugin::getOption( 'pin' );
		if ( $sPin !== md5( trim( $_POST['pin'] ) ) ) {
			return false;
		}
		
		return true;
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
			$fAdded = true;
			$sServiceIps = implode( "\n", self::$ServiceIpAddressesIpv4 );
			if ( !isset( $bwpsoptions['id_whitelist'] ) || strlen( $bwpsoptions['id_whitelist'] ) == 0 ) {
				$bwpsoptions['id_whitelist'] = $sServiceIps;
			}
			else if ( strpos( $bwpsoptions['id_whitelist'], $sServiceIps ) === false ) {
				$bwpsoptions['id_whitelist'] .= "\n".$sServiceIps;
			}
			else {
				$fAdded = false; //not used (yet)
			}
		}
	}
	
	/**
	 * Removes any interruption from Maintenance Mode plugins while iControlWP is executing a package.
	 * @return void
	 */
	public function removeMaintenanceModeHooks() {

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
	
	/**
	 * Redirects back to the home URL.
	 */
	public function goBackHome() {
		wp_redirect( get_bloginfo('url') );
	}
	
	/**
	 * @return void
	 */
	public function setAuthorizedUser() {
		$nId = 1;
		if ( isset( $_POST['wpadmin_user'] ) ) {
			$oUser = function_exists( 'get_user_by' )? get_user_by( 'login', $_POST['wpadmin_user'] ): get_userdatabylogin( $_POST['wpadmin_user'] );
			
			if ( $oUser ) {
				$nId = $oUser->ID;
			}
		}
		else {
			global $wp_version;
			if ( version_compare( $wp_version, '3.1', '>=' ) ) {
				$aUserRecords = get_users( 'role=administrator' );
				if ( is_array( $aUserRecords ) && count( $aUserRecords ) ) {
					$oUser = $aUserRecords[0];
					$nId = $oUser->ID;
				}
			}
		}
		
		/**
		 * We couldn't find a user at all, so we make a last attempt at just using ID 1
		 */
		$nId = ( $nId <= 0 )? 1: $nId;

		if ( !is_user_logged_in() ) {
			wp_set_current_user( $nId );
			wp_set_auth_cookie( $nId );
		}

		if ( @getenv( 'IS_WPE' ) == '1' && class_exists( 'WpeCommon', false ) ) {
			$oWpEngineCommon = WpeCommon::instance();
			$oWpEngineCommon->set_wpe_auth_cookie();
		}
	}
	
	protected function initPluginOptions() {
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Worpit_Plugin_Base::handlePluginFormSubmit()
	 */
	protected function handlePluginFormSubmit() {
		if ( !current_user_can( 'manage_options' ) || !isset( $_POST['icwp_admin_form_submit'] ) ) {
			return;
		}
		
		check_admin_referer( self::$ParentMenuId );
		
		//Clicked the button to acknowledge the installation of the plugin
		$nUserId = $this->fetchPost( 'icwp_user_id' );
		if ( $nUserId && $this->fetchPost( 'icwp_ack_plugin_notice' ) ) {
			update_user_meta( $nUserId, self::$VariablePrefix.'ack_plugin_notice', 'Y' );
			header( "Location: admin.php?page=".self::$ParentMenuId );
			return;
		}
		
		//Clicked the button to enable/disable hand-shaking
		if ( $this->fetchPost( 'icwp_admin_form_submit_handshake' ) ) {
			if ( $this->fetchPost( 'icwp_admin_handshake_enabled' ) ) {
				Worpit_Plugin::updateOption( 'handshake_enabled', 'Y' );
			}
			else {
				Worpit_Plugin::updateOption( 'handshake_enabled', 'N' );
			}
			header( "Location: admin.php?page=".self::$ParentMenuId );
			return;
		}
		
		//Clicked the button to remotely add site
		if ( $this->fetchPost( 'icwp_admin_form_submit_add_remotely' ) ) {
			$sAuthKey = $this->fetchPost( 'account_auth_key' );
			$sEmailAddress = $this->fetchPost( 'account_email_address' );
			if ( $sAuthKey && $sEmailAddress ) {
				$sAuthKey = trim( $sAuthKey );
				$sEmailAddress = trim( $sEmailAddress );
				$this->doRemoteLink( $sAuthKey, $sEmailAddress );
			}
			else {
				//add error message.
			}
			wp_redirect( admin_url('admin.php?page='.self::$ParentMenuId ) );
			return;
		}
		
		//Clicked a button to debug, either gather, or send
		if ( isset( $_POST['icwp_admin_form_submit_debug'] ) ) {
			if ( isset( $_POST['submit_gather'] ) ) {
				$sUniqueName = uniqid().'_'.time().'.txt';
				$sTarget = dirname(__FILE__).'/'.$sUniqueName;
				
				$fCanWrite = true;
				if ( !file_put_contents( $sTarget, 'TEST' ) ) {
					$fCanWrite = false;
				}
				else {
					if ( !is_file( $sTarget ) ) {
						$fCanWrite = false;
					}
				}
				
				include_once( dirname(__FILE__).'/src/functions/filesystem.php' );
				
				$aData = array(
					'_SERVER'				=> $_SERVER,
					'_ENV'					=> $_ENV,
					'ini_get_all'			=> @ini_get_all(),
					'extensions_loaded'		=> @get_loaded_extensions(),
					'php_version'			=> @phpversion(),
					'has_exec'				=> function_exists( 'exec' )? 1: 0,
					'fileperms'				=> array(
						array(
							'target'	=> dirname(__FILE__).'/src/controllers/',
							'perms'		=> fileperms( dirname(__FILE__).'/src/controllers/' ),
							'is_dir'	=> is_dir( dirname(__FILE__).'/src/controllers/' )? 1: 0
						),
						array(
							'target'	=> dirname(__FILE__).'/src/',
							'perms'		=> fileperms( dirname(__FILE__).'/src/' ),
							'is_dir'	=> is_dir( dirname(__FILE__).'/src/' )? 1: 0
						),
						array(
							'target'	=> __FILE__,
							'perms'		=> fileperms( __FILE__ ),
							'is_dir'	=> is_dir( __FILE__ )? 1: 0
						),
						array(
							'target'	=> dirname(__FILE__),
							'perms'		=> fileperms( dirname(__FILE__) ),
							'is_dir'	=> is_dir( dirname(__FILE__) )? 1: 0
						),
						array(
							'target'	=> dirname(__FILE__).'/../',
							'perms'		=> fileperms( dirname(__FILE__).'/../' ),
							'is_dir'	=> is_dir( dirname(__FILE__).'/../' )? 1: 0
						)
					)
				);
				
				$aData['.htaccess'] = worpitBackwardsRecursiveFileSearch( dirname(__FILE__).'/src/controllers', 7, '.htaccess' );
				$aData['error_log'] = worpitBackwardsRecursiveFileSearch( dirname(__FILE__).'/src/controllers', 7, 'error_log' );
				$aData['php_error_log'] = worpitBackwardsRecursiveFileSearch( dirname(__FILE__).'/src/controllers', 7, 'php_error_log' );
				
				if ( !$fCanWrite ) {
					echo "<h4>Your system configuration does not allow writing to the filesystem.</h4>";
					echo "<p>Please take a moment and send the contents of this page to support@icontrolwp.com</p>";
					echo "<hr />";
					var_dump( $aData );
				}
				else {
					file_put_contents( $sTarget, print_r( $aData, true ) );
					Worpit_Plugin::updateOption( 'debug_file', $sUniqueName );
				}
			}
			else if ( isset( $_POST['submit_information'] ) ) {
				$sTarget = Worpit_Plugin::getOption( 'debug_file' );
				$sTargetAbs = dirname(__FILE__).'/'.$sTarget;
				if ( !empty( $sTarget ) && is_file( $sTargetAbs ) ) {
					if ( wp_mail( 'support@icontrolwp.com', 'Debug Configuration', 'See attachment', '', $sTargetAbs ) ) {
						unlink( $sTargetAbs );
						Worpit_Plugin::deleteOption( 'debug_file' );
					}
				}
			}
			header( "Location: admin.php?page=".self::$ParentMenuId );
			return;
		}

		$sPage = $this->fetchGet( 'page' );
		if ( !empty($sPage) ) {
			switch ( $sPage ) {
				case parent::$ParentMenuId :
					if ( $this->fetchPost('icwp_admin_form_submit_resetplugin') ) {
						$sTo = Worpit_Plugin::getOption( 'assigned_to' );
						$sKey = Worpit_Plugin::getOption( 'key' );
						$sPin = Worpit_Plugin::getOption( 'pin' );

						if ( !empty( $sTo ) && !empty( $sKey ) && !empty( $sPin ) ) {
							$aParts = array( urlencode( $sTo ), $sKey, $sPin );
							$sContents = @file_get_contents( self::DashboardUrlBase.'system/verification/reset/'.implode( '/', $aParts ) );
						}

						$aOptions = Worpit_Plugin::GetPluginDefaultOptions();
						foreach( $aOptions as $sKey => $mValue ) {
							Worpit_Plugin::updateOption( $sKey, $mValue );
						}
					}
				break;
			}
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Worpit_Plugin_Base::handlePluginUpgrade()
	 */
	protected function handlePluginUpgrade() {
		$sInstalledVersion = Worpit_Plugin::getOption( 'installed_version' );
		if ( empty( $sInstalledVersion ) ) {
			$sInstalledVersion = '0.1';
		}

		if ( version_compare( $sInstalledVersion, '2.7.5', '<' ) ) {
			Worpit_Plugin::deleteOption( 'display_promo' );
		}

		if ( version_compare( $sInstalledVersion, '1.0.5' ) < 0 ) {
			Worpit_Plugin::addOption( 'can_handshake', 'N' );
			Worpit_Plugin::addOption( 'handshake_enabled', 'N' );
		}
		Worpit_Plugin::updateOption( 'installed_version', self::$VERSION );
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Worpit_Plugin_Base::onWpInit()
	 */
	public function onWpInit() {
		parent::onWpInit();

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'onWpAdminMenu' ) );
			if ( self::$NetworkAdminOnly ) {
				add_action(	'network_admin_menu', array( $this, 'onWpNetworkAdminMenu' ) );
			}
			$this->runWhiteLabelSystem();
		}
		add_action( 'wp_enqueue_scripts', array( $this, 'onWpEnqueueScripts' ) );
		add_action( 'wp_footer', array( $this, 'printPluginUri') );
	}

	/**
	 *
	 */
	public function onWpLoaded() {
		$this->runGoogleAnalyticsSystem();
		$this->runAutoUpdatesSystem();
	}

	/**
	 * (non-PHPdoc)
	 * @see Worpit_Plugin_Base::onWpInit()
	 */
	public function onWpShutdown() {
		parent::onWpShutdown();
		if ( !is_admin() ) {
			$this->runStatsSystem();
		}
	}

	/**
	 * @param array $inaPlugins
	 * @return array
	 */
	public function hide_icwp_plugin( $inaPlugins ) {
		foreach ( $inaPlugins as $sName => $aData ) {
			if ( strpos( $sName, 'worpit-admin-dashboard-plugin' ) === 0 ) {
			}
		}
		if ( array_key_exists( $this->m_sPluginFile, $inaPlugins ) ) {
			unset( $inaPlugins[$this->m_sPluginFile] );
		}
		return $inaPlugins;
	}

	/**
	 * @param array $inaPlugins
	 * @return array
	 */
	public function relabel_icwp_plugin( $inaPlugins ) {
		if ( $this->m_aLabelData['service_name'] == self::ServiceName ) {
			return $inaPlugins;
		}

		if ( array_key_exists( $this->m_sPluginFile, $inaPlugins ) ) {
			$inaPlugins[$this->m_sPluginFile]['Name'] = $this->m_aLabelData['service_name'];
			$inaPlugins[$this->m_sPluginFile]['Description'] = $this->m_aLabelData['tag_line'];
			$inaPlugins[$this->m_sPluginFile]['Title'] = $this->m_aLabelData['service_name'];
			$inaPlugins[$this->m_sPluginFile]['Author'] = $this->m_aLabelData['service_name'];
			$inaPlugins[$this->m_sPluginFile]['AuthorName'] = $this->m_aLabelData['service_name'];
			$inaPlugins[$this->m_sPluginFile]['PluginURI'] = $this->m_aLabelData['plugin_home_url'];
			$inaPlugins[$this->m_sPluginFile]['AuthorURI'] = $this->m_aLabelData['plugin_home_url'];
		}
		return $inaPlugins;
	}

	/**
	 * (non-PHPdoc)
	 * @see Worpit_Plugin_Base::onWpAdminInit()
	 */
	public function onWpAdminInit() {
		parent::onWpAdminInit();

		if ( is_admin() ) {
			if ( self::$oWhiteLabelSystem->getIsSystemEnabled() ) {
				//renames parts of the plugin for white label within the plugins listing.
				add_filter( 'all_plugins', array( $this, 'relabel_icwp_plugin' ) );
			}
		}
		add_action( 'plugin_action_links', array( $this, 'onWpPluginActionLinks' ), 10, 4 );

		//Do Plugin-Specific Admin Work
		if ( $this->isSelfAdminPage() ) {
			wp_register_style( 'worpit-admin', $this->getCssUrl( 'icontrolwp-admin.css' ) );
			wp_enqueue_style( 'worpit-admin' );
		}
		
		if ( current_user_can( 'manage_options' ) && self::getOption('do_activation_redirect', false) ) {
			self::deleteOption( 'do_activation_redirect');
			if ( self::getOption( 'assigned', 'N' ) == 'N' ) {
				wp_redirect( 'admin.php?page=worpit-admin' );
			}
		}
	}
	
	/**
	 * @see Worpit_Plugin_Base::onWpPluginsLoaded()
	 */
	public function onWpPluginsLoaded() {
		parent::onWpPluginsLoaded();

		if ( ( isset( $_POST['getworpitpluginurl'] ) && $_POST['getworpitpluginurl'] == 1 )
			|| ( isset( $_GET['getworpitpluginurl'] ) && $_GET['getworpitpluginurl'] == 1 ) ) {
			$this->returnIcwpPluginUrl();
		}

		if ( is_admin() ) {
			$this->handlePluginUpgrade();
			$this->addToWhitelists();
		}
		// Always auto-update this plugin
		add_filter( 'auto_update_plugin', array( $this, 'autoupdate_me' ), 10000, 2 );

		// If the plugin is being initialised from iControlWP Dashboard
		if ( isset( $_GET['worpit_link'] ) || isset( $_GET['worpit_prelink'] ) ) {
			$this->removeMaintenanceModeHooks();
		}
		else if ( $this->icwpAuthenticate() ) {
			$this->removePluginHooks();
			$this->setAuthorizedUser();
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Worpit_Plugin_Base::onWpAdminMenu()
	 */
	public function onWpAdminMenu() {
		parent::onWpAdminMenu();
		
		//add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Bootstrap CSS' ), 'Bootstrap CSS', self::ParentPermissions, $this->getSubmenuId( 'bootstrap-css' ), array( &$this, 'onDisplayPlugin' ) );
		//$this->fixSubmenu();
	}

	/**
	 * (non-PHPdoc)
	 * @see Worpit_Plugin_Base::createPluginSubMenuItems()
	 */
	protected function createPluginSubMenuItems(){
		$this->m_aPluginMenu = array(
			//Menu Page Title => Menu Item name, page ID (slug), callback function for this page - i.e. what to do/load.
			//$this->getSubmenuPageTitle( 'View Settings' ) => array( 'View Settings', $this->getSubmenuId('view-settings'), 'onDisplayViewSettings' )
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Worpit_Plugin_Base::onDisplayMainMenu()
	 */
	public function onDisplayMainMenu() {
		$sDebugFile = Worpit_Plugin::getOption( 'debug_file' );
		$aData = array(
			'plugin_url'		=> self::$PluginUrl,
			'label_data'		=> $this->m_aLabelData,
			'key'				=> Worpit_Plugin::getOption( 'key' ),
			'pin'				=> Worpit_Plugin::getOption( 'pin' ),
			'assigned'			=> Worpit_Plugin::getOption( 'assigned' ),
			'assigned_to'		=> Worpit_Plugin::getOption( 'assigned_to' ),
			'is_linked'			=> self::IsLinked(),

			'can_handshake'		=> Worpit_Plugin::getOption( 'can_handshake' ),
			'handshake_enabled'	=> Worpit_Plugin::getOption( 'handshake_enabled' ),
			'debug_file_url'	=> empty( $sDebugFile )? false: self::$PluginUrl.$sDebugFile,
			
			'nonce_field'		=> self::$ParentMenuId,
			'form_action'		=> 'admin.php?page='.self::$ParentMenuId,
				
			'image_url'			=> $this->getImageUrl( '' ),

			'options_ga'		=> self::GetGoogleAnalyticsSystem()->getSystemOptions(),
			'options_au'		=> self::GetAutoUpdatesSystem()->getSystemOptions(),
			'options_ss'		=> self::GetStatsSystem()->getSystemOptions()
		);
		$this->display( 'icwp_index', $aData );
	}
	
	/**
	 *
	 */
	public function onDisplayViewSettings() {
		//populates plugin options with existing configuration
		$this->populateAllPluginOptions();
		
		//Specify what set of options are available for this page
		$aAvailableOptions = array( &$this->m_aWordPressSecurityOptions ) ;
		
		$aData = array(
			'plugin_url'		=> self::$PluginUrl,
			'assigned'			=> Worpit_Plugin::getOption( 'assigned' ),
			'assigned_to'		=> Worpit_Plugin::getOption( 'assigned_to' ),
			'aAllOptions'		=> $aAvailableOptions,

			'can_handshake'		=> Worpit_Plugin::getOption( 'can_handshake' ),
			'handshake_enabled'	=> Worpit_Plugin::getOption( 'handshake_enabled' ),
			'image_url'			=> $this->getImageUrl( '' )
		);
		$this->display( 'icwp_view_settings', $aData );
	}

	/**
	 * Override this method to handle all the admin notices
	 *
	 * (non-PHPdoc)
	 * @see Worpit_Plugin_Base::onWpAdminNotices()
	 */
	public function onWpAdminNotices() {
		//Do we have admin priviledges?
		if ( !current_user_can( 'manage_options' ) ) {
			return;
		}
	}
	
	public function onWpNetworkAdminNotices() {
		
		if ( !is_super_admin() || !is_network_admin() ) {
			return;
		}

		global $current_user;
		$user_id = $current_user->ID;

		$sAckPluginNotice = get_user_meta( $user_id, self::$VariablePrefix.'ack_plugin_notice', true );

		if ( Worpit_Plugin::getOption( 'assigned' ) !== 'Y' && $sAckPluginNotice !== 'Y' ) {
			$sNotice = '
				<form method="post" action="admin.php?page=worpit-admin">
				'.wp_nonce_field( self::$ParentMenuId ).'
					<p><strong>Warning:</strong> Now that you have installed the '.self::ServiceName.' plugin, you should now add it to your '.self::ServiceName.' account.
					<input type="hidden" name="icwp_admin_form_submit" value="1" />
					<input type="hidden" name="icwp_ack_plugin_notice" value="Y" />
					<input type="hidden" value="'.$user_id.'" name="icwp_user_id" id="_icwp_user_id">
					<input type="submit" value="Get your Authentication Key here" name="submit" class="button-primary">
					</p>
				</form>
			';
			$this->getAdminNotice( $sNotice, 'error', true );
		}
		
		// if the user is searching from WorpitApp.com
		if ( isset( $_GET['worpitapp'] ) && $_GET['worpitapp'] == 'install' ) {
			$sNotice = '
				<form method="post" action="admin.php?page=worpit-admin">
					<p>Looking for your '.self::ServiceName.' Authentication Key?
					<input type="submit" value="Get your Authentication Key here" name="submit" class="button-primary">
					</p>
				</form>
			';
			$this->getAdminNotice( $sNotice, 'updated', true );
		}
	}

	/**
	 * @return void
	 */
	public function printPluginUri() {
		if ( $this->getOption('assigned') !== 'Y' ) {
			echo '<!-- Worpit Plugin: '.plugins_url( '/', __FILE__ ) .' -->';
		}
	}
	
	/**
	 * @return void
	 */
	public function onWpEnqueueScripts() { }

	/**
	 * @return boolean
	 */
	static public function IsLinked() {
		return ( self::getOption( 'assigned' ) == 'Y' && self::getOption( 'assigned_to' ) != '' );
	}
	
	/**
	 * This function always returns false, however the return is never actually used just yet.
	 *
	 * @param string $insAuthKey
	 * @param string $insEmailAddress
	 * @return boolean
	 */
	public function doRemoteLink( $insAuthKey, $insEmailAddress ) {
		if ( self::IsLinked() ) {
			return false;
		}
		
 		if ( strlen( $insAuthKey ) == 32 && is_email( $insEmailAddress ) ) {
				
			//looks good. Now attempt remote link.
			$aPostVars = array(
				'wordpress_url'				=> home_url(),
				'plugin_url'				=> self::$PluginUrl,
				'account_email_address'		=> $insEmailAddress,
				'account_auth_key'			=> $insAuthKey,
				'plugin_key'				=> self::getOption( 'key' )
			);
			$aArgs = array(
				'body'	=> $aPostVars
			);
			$oResponse = wp_remote_post( self::RemoteAddSiteUrl, $aArgs );
			/* if ( $oResponse['response']['code'] == 200 ) {
				return true;
			}
			*/
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function isVisitorIcwp() {
		$sIp = $this->getVisitorIpAddress( false );
		return ( $sIp === false || in_array( $sIp, self::$ServiceIpAddressesIpv4 ) );
	}

	/**
	 *
	 */
	public static function GetPluginDefaultOptions() {
		return array(
			'key'				=> Worpit_Plugin::Generate( 24, 7 ),
			'pin'				=> '',
			'assigned'			=> 'N',
			'assigned_to'		=> '',
			'can_handshake'		=> 'N',
			'handshake_enabled'	=> 'N',
			'activated_at'		=> time(),
			'installed_version'	=> Worpit_Plugin::$VERSION
		);
	}

	/**
	 * This is a filter method designed to say whether WordPress plugin upgrades should be permitted,
	 * based on the plugin settings.
	 *
	 * @param boolean $infUpdate
	 * @param boolean $insPluginSlug
	 * @return boolean
	 */
	public function autoupdate_me( $infUpdate, $insPluginSlug ) {
		if ( $insPluginSlug === $this->m_sPluginFile ) {
			return true;
		}
		return $infUpdate;
	}

	/**
	 * @return ICWP_WhiteLabel
	 */
	public static function & GetWhiteLabelSystem() {
		if ( is_null( self::$oWhiteLabelSystem ) ) {
			self::$oWhiteLabelSystem = ( include_once( dirname(__FILE__).'/src/plugin/system-white-label.php' ) );
		}
		return self::$oWhiteLabelSystem;
	}

	/**
	 * Runs the white label processes
	 */
	protected function runWhiteLabelSystem() {
		$oWhiteLabelSystem = self::GetWhiteLabelSystem();
		$this->m_aLabelData = $this->getDefaultPluginLabelData();
		if ( $oWhiteLabelSystem->getIsSystemEnabled() ) {
			$aWhiteLabelData = $oWhiteLabelSystem->getSystemOptions();
			if ( !empty($aWhiteLabelData) ) {
				$this->m_aLabelData = $aWhiteLabelData;
			}
		}
	}

	/**
	 * @return array|boolean
	 */
	public function getDefaultPluginLabelData() {
		return array(
			'service_name'		=> self::ServiceName,
			'tag_line'			=> 'Take Control Of All WordPress Sites From A Single Dashboard',
			'plugin_home_url'	=> 'http://icwp.io/home',
			'icon_url_16x16'	=> $this->getImageUrl( 'icontrolwp_16x16.png' ),
			'icon_url_32x32'	=> $this->getImageUrl( 'icontrolwp_32x32.png' )
		);
	}

	/**
	 * @return integer
	 */
	public function getActivatedAt() {
		return Worpit_Plugin::getOption('activated_at');
	}

	/**
	 * @return ICWP_Stats
	 */
	public static function & GetStatsSystem() {
		if ( is_null( self::$oStatsSystem ) ) {
			self::$oStatsSystem = ( include_once( dirname(__FILE__).'/src/plugin/stats.php' ) );
		}
		return self::$oStatsSystem;
	}

	/**
	 * Runs the statistic processes (hooked to 'shutdown')
	 */
	protected function runStatsSystem() {
		$oStatsSystem = self::GetStatsSystem();
		if ( $oStatsSystem->getIsSystemEnabled() ) {
			$oStatsSystem->run();
		}
	}

	/**
	 * @return ICWP_GoogleAnalytics
	 */
	public static function & GetGoogleAnalyticsSystem() {
		if ( is_null( self::$oGoogleAnalyticsSystem ) ) {
			self::$oGoogleAnalyticsSystem = (include_once(dirname(__FILE__) . '/src/plugin/system-google-analytics.php'));
		}
		return self::$oGoogleAnalyticsSystem;
	}

	/**
	 * Runs the statistic processes (hooked to 'wp_loaded')
	 */
	protected function runGoogleAnalyticsSystem() {
		$oGoogleAnalyticsSystem = self::GetGoogleAnalyticsSystem();
		if ( $oGoogleAnalyticsSystem->getIsSystemEnabled() ) {
			$oGoogleAnalyticsSystem->run();
		}
	}

	/**
	 * @return ICWP_AutoUpdates
	 */
	public static function & GetAutoUpdatesSystem() {
		if ( is_null( self::$oAutoUpdatesSystem ) ) {
			self::$oAutoUpdatesSystem = (include_once(dirname(__FILE__) . '/src/plugin/system-autoupdates.php'));
		}
		return self::$oAutoUpdatesSystem;
	}

	/**
	 * Runs the statistic processes (hooked to 'wp_loaded')
	 */
	protected function runAutoUpdatesSystem() {
		$oAutoUpdatesSystem = self::GetAutoUpdatesSystem();
		$oAutoUpdatesSystem->convertFromOldSystem();
		if ( $oAutoUpdatesSystem->getIsSystemEnabled() ) {
			$oAutoUpdatesSystem->run();
		}
	}

	protected function createMenu() {

		//if the site is a multisite and we're not on the network admin, get out.
		if ( function_exists( 'is_multisite' ) ) {
			if ( is_multisite() && !is_network_admin() && self::$NetworkAdminOnly ) {
				return true;
			}
		}

		$sFullParentMenuId = $this->getFullParentMenuId();
		add_menu_page(
			$this->m_aLabelData['service_name'], //self::$ParentTitle
			$this->m_aLabelData['service_name'], //self::$ParentName
			self::$ParentPermissions,
			$sFullParentMenuId,
			array( $this, 'onDisplayMainMenu' ),
			$this->m_aLabelData['icon_url_16x16']
		);

		$this->fixSubmenu();
	}
}

class Worpit_Install {
	
	/**
	 */
	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'onWpActivatePlugin' ) );
	}
	
	/**
	 * @return void
	 */
	public function onWpActivatePlugin() {
		$fIsAssigned = Worpit_Plugin::getOption( 'assigned' ) === 'Y';
		if ( !$fIsAssigned ) {
			$aOptions = Worpit_Plugin::GetPluginDefaultOptions();
			foreach( $aOptions as $sKey => $mValue ) {
				Worpit_Plugin::addOption( $sKey, $mValue );
			}
			Worpit_Plugin::addOption( 'installed_at',	Worpit_Plugin::getOption( 'activated_at' ) );
		}

		// Allows for redirect to plugin page once the plugin is activated.
		Worpit_Plugin::addOption( 'do_activation_redirect', true );
	}
	
	protected function executeSql() { }
}

class Worpit_Uninstall {
	
	// TODO: when uninstalling, maybe have a iControlWP save settings offsite-like setting
	
	/**
	 */
	public function __construct() {
		register_deactivation_hook( __FILE__, array( $this, 'onWpDeactivatePlugin' ) );
	}
	
	/**
	 * @return void
	 */
	public function onWpDeactivatePlugin() { }
}

if ( !class_exists('ICWP_Plugin') ) {
	class ICWP_Plugin extends Worpit_Plugin {}
}

$g_oWorpit = Worpit_Plugin::GetInstance();

if ( $g_oWorpit->icwpAuthenticate() ) {
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
}