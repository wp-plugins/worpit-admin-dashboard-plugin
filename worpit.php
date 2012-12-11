<?php

/*
Plugin Name: Worpit - Manage WordPress Better
Plugin URI: http://worpit.com/
Description: This is the WordPress plugin client for the Worpit (http://worpit.com) service.
Version: 1.2.0
Author: Worpit
Author URI: http://worpit.com/
*/

/**
 * Copyright (c) 2012 Worpit <helpdesk@worpit.com>
 * All rights reserved.
 *
 * "Worpit" is distributed under the GNU General Public License, Version 2,
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
 *
 */

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
include_once( dirname(__FILE__).'/src/plugin/base.php' );
include_once( dirname(__FILE__).'/src/plugin/custom_options.php' );

if ( !defined( 'WORPIT_DS' ) ) {
	define( 'WORPIT_DS', DIRECTORY_SEPARATOR );
}

global $wpdb;

class Worpit_Plugin extends Worpit_Plugin_Base {
	
	protected $m_oAuditor;

	static public $VERSION = '1.2.0';
	
	static public $CustomOptionsDbName = 'custom_options';
	static public $CustomOptions; //the array of options written to WP Options
	
	protected $m_aWordPressSecurityOptions;
	
	public function __construct() {
		parent::__construct();
		
		self::$PluginName		= basename(__FILE__);
		self::$PluginPath		= plugin_basename( dirname(__FILE__) );
		self::$PluginBasename	= plugin_basename( __FILE__ );
		self::$PluginDir		= WP_PLUGIN_DIR.WORPIT_DS.self::$PluginPath.WORPIT_DS;
		self::$PluginUrl		= WP_PLUGIN_URL.'/'.self::$PluginPath.'/';
		
		if ( is_admin() ) {
			/**
			 * Registers activate and deactivation hooks
			 */
			$oInstall = new Worpit_Install();
			$oUninstall = new Worpit_Uninstall();
		}

		// If the plugin is being initialised from Worpit App
		if ( isset( $_POST['key'] ) && isset( $_POST['pin'] ) ) {
			
			if ( $this->worpitAuthenticate( $_POST ) ) { //It's a valid request coming from Worpit...
				
				add_action( 'plugins_loaded', array($this, 'removeSecureWpHooks'), 1 );
				add_action( 'plugins_loaded', array($this, 'removeBetterWpSecurityHooks'), 1 );
				add_action( 'plugins_loaded', array($this, 'removeMaintenanceModeHook'), 1 );
				add_action( 'init', array($this, 'setAuthorizedUser'), 0 );
			}
		}
		
		self::Load_CustomOptionsData();
		new Worpit_Plugin_Custom_Options(self::$CustomOptions);

		if(  isset($_GET['test']) && is_admin() )
			var_dump(self::$CustomOptions);
		
// 		$this->m_oAuditor = new Worpit_Auditor();
	}
	
	/**
	 * To force it to re-load from the WordPress options table pass true.
	 * @param $infForceReload
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
	
	public static function Store_CustomOptionsData() {
		return self::updateOption( self::$CustomOptionsDbName, self::$CustomOptions );
	}
	
	/**
	 * Give an associative array.
	 * 	'key1' => 'value1'
	 * 	'key2' => 'value2'
	 *
	 * @param $inaNewOptions
	 */
	public static function Update_CustomOptions( $inaNewOptions ) {
		self::Load_CustomOptionsData();
		
		foreach( $inaNewOptions as $sNewKey => $sNewValue ) {
			self::$CustomOptions[$sNewKey] = $sNewValue;
		}
		return self::Store_CustomOptionsData();
	}
	
	/**
	 * @param $insKey
	 */
	public static function Get_CustomOption( $insKey, $infForceReload = false ) {
		
		self::Load_CustomOptionsData($infReload);
		return self::$CustomOptions[$insKey];
	}

	/**
	 * A modified copy of that in transport.php to verfiy the key and the pin
	 *
	 * @param $inaData - $_POST
	 * @return boolean
	 */
	protected function worpitAuthenticate( $inaData ) {

		$sOption = get_option( self::$VariablePrefix.'assigned' );
		$fAssigned = ($sOption == 'Y');
		if ( !$fAssigned ) {
			return false;
		}
	
		$sKey = get_option( self::$VariablePrefix.'key' );
		if ( $sKey != trim( $inaData['key'] ) ) {
			return false;
		}
	
		$sPin = get_option( self::$VariablePrefix.'pin' );
		if ( $sPin !== md5( trim( $inaData['pin'] ) ) ) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Remove actions setup by Secure WP plugin that interfere with Worpit synchronizing packages.
	 *
	 * Should be hooked before 'init' priority 1.
	 */
	public function removeSecureWpHooks() {
			
		global $SecureWP;
	
		if ( class_exists( 'SecureWP' ) && isset( $SecureWP ) && is_object( $SecureWP ) ) {
			remove_action( 'init', array($SecureWP, 'replace_wp_version'), 1 );
			remove_action( 'init', array($SecureWP, 'remove_core_update'), 1 );
			remove_action( 'init', array($SecureWP, 'remove_plugin_update'), 1 );
			remove_action( 'init', array($SecureWP, 'remove_theme_update'), 1 );
			remove_action( 'init', array($SecureWP, 'remove_wp_version_on_admin'), 1 );
		}
	}//removeSecureWpHooks
	
	/**
	 * Remove actions setup by Better WP Security plugin that interfere with Worpit synchronizing packages.
	 *
	 * Check secure.php for changes to these hooks.
	 */
	public function removeBetterWpSecurityHooks() {
		
		global $bwps;
		
		if ( class_exists( 'bwps_secure' ) && isset( $bwps ) && is_object( $bwps ) ) {
		
			remove_action( 'plugins_loaded', array( $bwps, 'randomVersion' ) );
			remove_action( 'plugins_loaded', array( $bwps, 'pluginupdates' ) );
			remove_action( 'plugins_loaded', array( $bwps, 'themeupdates' ) );
			remove_action( 'plugins_loaded', array( $bwps, 'coreupdates' ) );
		}
	}//removeBetterWpSecurityHooks
	
	/**
	 * Removes any interruption from the Maintenance Mode plugin while Worpit is executing a package.
	 */
	public function removeMaintenanceModeHook() {
		
		global $myMaMo;
		
		if ( class_exists( 'MaintenanceMode' ) && isset( $myMaMo ) && is_object( $myMaMo ) ) {
			remove_action('plugins_loaded', array( $myMaMo, 'ApplyMaintenanceMode') );
		}
	}//removeMaintenanceModeHook
	
	public function setAuthorizedUser() {
		wp_set_current_user( 1 );
	}
	
	protected function initPluginOptions() {
		/*
		foreach ( $this->m_aAllPluginOptions as &$aOptionsSection ) {
			
			if ( !isset( $aOptionsSection['section_options'] ) ) {
				continue;
			}
			
			foreach( $aOptionsSection['section_options'] as $aOption ) {
				list( $sName, $sValue, $sDefault, $sType ) = $aOption;
				
				self::$CustomOptions[ $sName ] = $sDefault;
			}
		}

		$this->m_aWordPressSecurityOptions = 	array(
				'section_title' => 'Worpit Security Settings',
				'section_options' => array(
					array( 'sec_hide_wp_version',			'',		'N', 	'checkbox',		'Hide WP Version', 'Do not publish WordPress version', 'asdf' ),
					array( 'sec_hide_wlmanifest_link',		'',		'N', 	'checkbox',		'Version', 'Do', 'asdf' ),
					array( 'sec_hide_rsd_link',				'',		'N', 	'checkbox',		'Version', 'Do', 'asdf' ),
					array( 'sec_set_random_script_version',	'',		'N', 	'checkbox',		'Version', 'Do', 'asdf' ),
					array( 'sec_random_script_version',		'',		'', 	'text',			'Version', 'Do', 'asdf' ),
			),
		);

		$this->m_aAllPluginOptions = array( &$this->m_aWordPressSecurityOptions );

		return true;
		*/
	}//initPluginOptions
	
	protected function handlePluginFormSubmit() {
		
		if ( !current_user_can( 'manage_options' ) || !isset( $_POST['worpit_admin_form_submit'] ) ) {
			return;
		}
		
		check_admin_referer( self::$ParentMenuId );
		
		//Someone clicked the button to acknowledge the installation of the plugin
		if ( isset( $_POST['worpit_user_id'] ) && isset( $_POST['worpit_ack_plugin_notice'] ) ) {
			$result = update_user_meta( $_POST['worpit_user_id'], self::$VariablePrefix.'ack_plugin_notice', 'Y' );
			header( "Location: admin.php?page=".self::$ParentMenuId );
			return;
		}
		
		//Someone clicked the button to enable/disable hand-shaking
		if ( isset( $_POST['worpit_admin_form_submit_handshake'] ) ) {
			if ( isset( $_POST['worpit_admin_handshake_enabled'] ) ) {
				update_option( self::$VariablePrefix.'handshake_enabled',	'Y' );
			}
			else {
				update_option( self::$VariablePrefix.'handshake_enabled',	'N' );
			}
			header( "Location: admin.php?page=".self::$ParentMenuId );
			return;
		}
		
		//Someone clicked a button to debug, either gather, or send
		if ( isset( $_POST['worpit_admin_form_submit_debug'] ) ) {
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
					echo "<p>Please take a moment and send the contents of this page to support@worpit.com</p>";
					echo "<hr />";
					var_dump( $aData );
				}
				else {
					file_put_contents( $sTarget, print_r( $aData, true ) );
					update_option( self::$VariablePrefix.'debug_file', $sUniqueName );
				}
			}
			else if ( isset( $_POST['submit_information'] ) ) {
				$sTarget = get_option( self::$VariablePrefix.'debug_file' );
				$sTargetAbs = dirname(__FILE__).'/'.$sTarget;
				if ( !empty( $sTarget ) && is_file( $sTargetAbs ) ) {
					if ( wp_mail( 'support@worpit.com', 'Debug Configuration', 'See attachment', '', $sTargetAbs ) ) {
						unlink( $sTargetAbs );
						delete_option( self::$VariablePrefix.'debug_file' );
					}
				}
			}
			header( "Location: admin.php?page=".self::$ParentMenuId );
			return;
		}
	
		if ( isset( $_GET['page'] ) ) {
			switch ( $_GET['page'] ) {
				case parent::$ParentMenuId:
					//$this->handleSubmit_Clear();
					if ( isset( $_POST['worpit_admin_form_submit_resetplugin'] ) ) {
						$sTo = get_option( self::$VariablePrefix.'assigned_to' );
						$sKey = get_option( self::$VariablePrefix.'key' );
						$sPin = get_option( self::$VariablePrefix.'pin' );
						
						if ( !empty( $sTo ) && !empty( $sKey ) && !empty( $sPin ) ) {
							$aParts = array( urlencode( $sTo ), $sKey, $sPin );
							$sContents = @file_get_contents( 'http://worpitapp.com/dashboard/system/verification/reset/'.implode( '/', $aParts ) );
						}

						update_option( self::$VariablePrefix.'key',					Worpit_Plugin::Generate( 24, 7 ) );
						update_option( self::$VariablePrefix.'pin',					'' );
						update_option( self::$VariablePrefix.'assigned',			'N' );
						update_option( self::$VariablePrefix.'assigned_to',			'' );
						update_option( self::$VariablePrefix.'can_handshake',		'N' );
						update_option( self::$VariablePrefix.'handshake_enabled',	'N' );
					}
				break;
			}
		}
	}
	
	protected function handlePluginUpgrade() {
		$sInstalledVersion = get_option( self::$VariablePrefix.'installed_version' );
		if ( empty( $sInstalledVersion ) ) {
			$sInstalledVersion = '0.1';
		}
		
		if ( version_compare( $sInstalledVersion, '1.0.5' ) < 0 ) {
			add_option( self::$VariablePrefix.'can_handshake',		'N' );
			add_option( self::$VariablePrefix.'handshake_enabled',	'N' );
		}
		
		if ( version_compare( $sInstalledVersion, self::$VERSION ) < 0 ) {
			update_option( Worpit_Plugin::$VariablePrefix.'installed_version', self::$VERSION );
		}
	}
	
	public function onWpInit() {
		parent::onWpInit();
		add_action( 'wp_footer', array( $this, 'printPluginUri') );
	}
	
	public function onWpAdminInit() {
		parent::onWpAdminInit();

		//Do Plugin-Specific Admin Work
		if ( $this->isSelfAdminPage() ) {
			wp_register_style( 'worpit-admin', $this->getCssUrl( 'worpit-admin.css' ) );
			wp_enqueue_style( 'worpit-admin' );
		}
		
	}
	
	public function onWpPluginsLoaded() {
		parent::onWpPluginsLoaded();
		
		if ( is_admin() ) {
			$this->handlePluginUpgrade();
		}
	}
	
	public function onWpAdminMenu() {
		parent::onWpAdminMenu();
		
		//add_submenu_page( self::ParentMenuId, $this->getSubmenuPageTitle( 'Bootstrap CSS' ), 'Bootstrap CSS', self::ParentPermissions, $this->getSubmenuId( 'bootstrap-css' ), array( &$this, 'onDisplayPlugin' ) );
		//$this->fixSubmenu();
	}

	protected function createPluginSubMenuItems(){
		$this->m_aPluginMenu = array(
			//Menu Page Title => Menu Item name, page ID (slug), callback function for this page - i.e. what to do/load.
			//$this->getSubmenuPageTitle( 'View Settings' ) => array( 'View Settings', $this->getSubmenuId('view-settings'), 'onDisplayViewSettings' )
		);
	}//createPluginSubMenuItems
	
	public function onDisplayMainMenu() {
		$sDebugFile = get_option( self::$VariablePrefix.'debug_file' );
		$aData = array(
			'plugin_url'		=> self::$PluginUrl,
			'key'				=> get_option( self::$VariablePrefix.'key' ),
			'pin'				=> get_option( self::$VariablePrefix.'pin' ),
			'assigned'			=> get_option( self::$VariablePrefix.'assigned' ),
			'assigned_to'		=> get_option( self::$VariablePrefix.'assigned_to' ),
				
			'can_handshake'		=> get_option( self::$VariablePrefix.'can_handshake' ),
			'handshake_enabled'	=> get_option( self::$VariablePrefix.'handshake_enabled' ),
			'debug_file_url'	=> empty( $sDebugFile )? false: self::$PluginUrl.$sDebugFile,
			
			'nonce_field'		=> self::$ParentMenuId,
			'form_action'		=> 'admin.php?page='.self::$ParentMenuId,
				
			'image_url'			=> $this->getImageUrl( '' )
		);
		$this->display( 'worpit_index', $aData );
	}
	
	public function onDisplayViewSettings() {
		
		//populates plugin options with existing configuration
		$this->populateAllPluginOptions();
		
		//Specify what set of options are available for this page
		$aAvailableOptions = array( &$this->m_aWordPressSecurityOptions ) ;
		
		$sDebugFile = get_option( self::$VariablePrefix.'debug_file' );
		$aData = array(
			'plugin_url'		=> self::$PluginUrl,
			'assigned'			=> $this->getOption( 'assigned' ),
			'assigned_to'		=> $this->getOption( 'assigned_to' ),
			'aAllOptions'		=> $aAvailableOptions,
				
			'can_handshake'		=> get_option( self::$VariablePrefix.'can_handshake' ),
			'handshake_enabled'	=> get_option( self::$VariablePrefix.'handshake_enabled' ),
				
			'image_url'			=> $this->getImageUrl( '' )
		);
		$this->display( 'worpit_view_settings', $aData );
	}

	/**
	 * Override this method to handle all the admin notices
	 */
	public function onWpAdminNotices() {
		
		//Do we have admin priviledges?
		if ( !current_user_can( 'manage_options' ) ) {
			return;
		}

		global $current_user;
		$user_id = $current_user->ID;

		$sWorpitAckPluginNotice = get_user_meta( $user_id, self::$VariablePrefix.'ack_plugin_notice', true );

		if ( get_option( self::$VariablePrefix.'assigned' ) !== 'Y' && $sWorpitAckPluginNotice !== 'Y' ) {
			$sNotice = '
					<form method="post" action="admin.php?page=worpit-admin">
					'.wp_nonce_field( self::$ParentMenuId ).'
						<p><strong>Warning:</strong> Now that you have installed the Worpit plugin, you should now add it to your Worpit account.
						<input type="hidden" name="worpit_admin_form_submit" value="1" />
						<input type="hidden" name="worpit_ack_plugin_notice" value="Y" />
						<input type="hidden" value="'.$user_id.'" name="worpit_user_id" id="worpit_user_id">
						<input type="submit" value="Get your Authentication Key here" name="submit" class="button-primary">
						</p>
					</form>
			';
		
			$this->getAdminNotice( $sNotice, 'error', true );
		}
		
		//if the user is searching from WorpitApp.com
		if ( isset( $_GET['worpitapp'] ) && $_GET['worpitapp'] == 'install' ) {
			$sNotice = '
					<form method="post" action="admin.php?page=worpit-admin">
						<p>Looking for your Worpit Authentication Key?
						<input type="submit" value="Get your Authentication Key here" name="submit" class="button-primary">
						</p>
					</form>
			';
		
			$this->getAdminNotice( $sNotice, 'updated', true );
			
		}
	}//onWpAdminNotices

	public function printPluginUri() {
		
		if ( $this->getOption('assigned') === 'N' ) {
			echo '<!-- Worpit Plugin: '.plugins_url( '/', __FILE__ ) .' -->';
		}
	}

}

class Worpit_Install {
	
	public function __construct() {
		register_activation_hook( __FILE__, array( &$this, 'onWpActivatePlugin' ) );
	}
	
	public function onWpActivatePlugin() {
		add_option( Worpit_Plugin::$VariablePrefix.'key',				Worpit_Plugin::Generate( 24, 7 ) );
		add_option( Worpit_Plugin::$VariablePrefix.'pin',				'' );
		add_option( Worpit_Plugin::$VariablePrefix.'assigned',			'N' );
		add_option( Worpit_Plugin::$VariablePrefix.'assigned_to',		'' );
		add_option( Worpit_Plugin::$VariablePrefix.'can_handshake',		'N' );
		add_option( Worpit_Plugin::$VariablePrefix.'handshake_enabled',	'N' );
		
		add_option( Worpit_Plugin::$VariablePrefix.'installed_version',	Worpit_Plugin::$VERSION );
		
		$this->executeSql();
	}
	
	protected function executeSql() {
		
	}
}

class Worpit_Uninstall {
	
	// TODO: when uninstalling, maybe have a Worpit save settings offsite-like setting
	
	public function __construct() {
		register_deactivation_hook( __FILE__, array( &$this, 'onWpDeactivatePlugin' ) );
	}
	
	public function onWpDeactivatePlugin() {
	}
}

class Worpit_Auditor {
	
	protected $m_sUniqId;
	
	protected $m_aActions;
	protected $m_aQueries;
	
	public function __construct() {
		$this->m_aUniqId = uniqid();
		$this->m_aActions = array();
		$this->m_aQueries = array();
		
		/**
		 * Add all the actions and filters
		 */
		add_action( 'shutdown', array( &$this, 'onShutdown' ) );
		if ( !defined( 'SAVEQUERIES' ) ){
			define( 'SAVEQUERIES', true );
		}
		
		/**
		 * We don't want to have to call add_action continuously, as that's repetitive, so we make
		 * a predictable loop (i.e. we know what way the function name will be written)
		 */
		$aActions = array(
			'wp_login'
		);
		
		foreach ( $aActions as $sAction ) {
			$sFunction = 'on'.str_replace( ' ', '', ucwords( str_replace( '_', ' ', $sAction ) ) );
			add_action( $sAction, array( &$this, $sFunction ) );
		}
	}
	
	public function __destruct() {
		// save ALL the queries and the actions to the new "worpit_audit" and "worpit_audit_item" tables
		// the SQL of which is execute when the plugin is activated (TODO: see function executeSql ).
		
		// insert a new worpit_audit record using the uniqid
		foreach ( $this->m_aActions as $aAction ) {
			// use $wpdb and save the $aAction information.
			// insert new worpit_audit_item records and be sure to use the last insert id of the worpit_audit above.
		}
		
		foreach ( $this->m_aQueries as $sQuery ) {
			// maybe do some crude regexp to get rid of most.
			if ( preg_match( '/^select/i', trim( $sQuery ) ) ) {
				continue;
			}
			
			// use wpdb and insert the query into the worpit_audit_query
			// also use the last insert id of the worpit audit record to link as parent of this log entry
		}
	}
	
	public function onShutdown() {
		// get all the queries from the wpdb object and assign to array
		$this->m_aQueries = array();// = wpdb->getqueries?
	}
	
	/**
	 * Potentially massive list of actions here:
	 *
	 * @link http://codex.wordpress.org/Plugin_API/Action_Reference/wp_login
	 */
	public function onWpLogin( $insUserLogin ) {
		$this->logAction(
			'wp_login',
			'User "'.$insUserLogin.'" logged in.',
			func_get_args()
		);
	}
	
	/**
	 * @link http://codex.wordpress.org/Plugin_API/Action_Reference/delete_user
	 */
	public function onDeleteUser( $insUserId ) {
		
	}
	
	/**
	 * The parameters are subject to change! They are just an initial sketch, maybe you can
	 * think more about this.
	 */
	protected function logAction( $insAction, $insText, $inaArgs ) {
		$this->m_aActions[] = array_merge( func_get_args(), array( time() ) );
	}
}

$g_oWorpit = new Worpit_Plugin();
