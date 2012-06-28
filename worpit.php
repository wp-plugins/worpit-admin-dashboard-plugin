<?php

/*
Plugin Name: Worpit Admin Dashboard
Plugin URI: http://worpit.com/
Description: This is the WordPress plugin client for the Worpit (http://worpit.com) service.
Version: 1.0.6
Author: Worpit
Author URI: http://worpit.com/
*/

/**
 * Copyright (c) 2011 Worpit <helpdesk@worpit.com>
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

define( 'DS', DIRECTORY_SEPARATOR );

global $wpdb;

class Worpit_Plugin extends Worpit_Plugin_Base {
	
	protected $m_oAuditor;

	public static $VERSION = '1.0.6';
	
	public function __construct() {
		parent::__construct();
		
		self::$PluginName		= basename(__FILE__);
		self::$PluginPath		= plugin_basename( dirname(__FILE__) );
		self::$PluginBasename	= plugin_basename( __FILE__ );
		self::$PluginDir		= WP_PLUGIN_DIR.DS.self::$PluginPath.DS;
		self::$PluginUrl		= WP_PLUGIN_URL.'/'.self::$PluginPath.'/';
		
		if ( is_admin() ) {
			/**
			 * Registers activate and deactivation hooks
			 */
			$oInstall = new Worpit_Install();
			$oUninstall = new Worpit_Uninstall();
		}
		
// 		$this->m_oAuditor = new Worpit_Auditor();
	}

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
			} else {
				update_option( self::$VariablePrefix.'handshake_enabled',	'N' );
			}
			header( "Location: admin.php?page=".self::$ParentMenuId );
			return;
		}
	
		if ( isset( $_GET['page'] ) ) {
			switch ( $_GET['page'] ) {
				case parent::$ParentMenuId:
					//$this->handleSubmit_Clear();
					{
						$sTo = get_option( self::$VariablePrefix.'assigned_to' );
						$sKey = get_option( self::$VariablePrefix.'key' );
						$sPin = get_option( self::$VariablePrefix.'pin' );
						
						if ( !empty( $sTo ) && !empty( $sKey ) && !empty( $sPin ) ) {
							$aParts = array(
								urlencode( get_option( self::$VariablePrefix.'assigned_to' ) ),
								get_option( self::$VariablePrefix.'key' ),
								get_option( self::$VariablePrefix.'pin' )
							);
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
	
	public function onDisplayMainMenu() {
		$aData = array(
			'plugin_url'		=> self::$PluginUrl,
			'key'				=> get_option( self::$VariablePrefix.'key' ),
			'pin'				=> get_option( self::$VariablePrefix.'pin' ),
			'assigned'			=> get_option( self::$VariablePrefix.'assigned' ),
			'assigned_to'		=> get_option( self::$VariablePrefix.'assigned_to' ),
				
			'can_handshake'		=> get_option( self::$VariablePrefix.'can_handshake' ),
			'handshake_enabled'	=> get_option( self::$VariablePrefix.'handshake_enabled' ),
			
			'nonce_field'		=> self::$ParentMenuId,
			'form_action'		=> 'admin.php?page='.self::$ParentMenuId,
				
			'image_url'			=> $this->getImageUrl( '' )
		);
		$this->display( 'worpit_index', $aData );
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
	}//onWpAdminNotices
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
		/*
		delete_option( Worpit_Plugin::$VariablePrefix.'key' );
		delete_option( Worpit_Plugin::$VariablePrefix.'pin' );
		delete_option( Worpit_Plugin::$VariablePrefix.'assigned' );
		delete_option( Worpit_Plugin::$VariablePrefix.'assigned_to' );
		*/
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
