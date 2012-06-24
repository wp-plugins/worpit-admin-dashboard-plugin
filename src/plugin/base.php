<?php

class Worpit_Plugin_Base {
	
	static public $VERSION;
	
	static public $PluginName;
	static public $PluginPath;
	static public $PluginDir;
	static public $PluginUrl;
	static public $PluginBasename;
	
	static public $ParentTitle			= 'Worpit';
	static public $ParentName			= 'Worpit';
	static public $ParentPermissions	= 'manage_options';
	static public $ParentMenuId			= 'worpit-admin';
	static public $VariablePrefix		= 'worpit_admin_';
	
	static public $ViewExt				= '.php';
	static public $ViewDir				= 'views';
	
	public function __construct() {
		add_action( 'init',				array( &$this, 'onWpInit' ), 1 );
		add_action( 'admin_init',		array( &$this, 'onWpAdminInit' ) );
		add_action( 'plugins_loaded',	array( &$this, 'onWpPluginsLoaded' ) );
		
		if ( is_admin() ) {
			add_action( 'admin_notices', array( &$this, 'onWpAdminNotices' ) );
		}
	}

	/**
	 * Override this method to handle all the admin notices
	 */
	public function onWpAdminNotices() { }

	/**
	 * This is called from within onWpAdminInit. Use this solely to manage upgrades of the plugin
	 */
	protected function handlePluginUpgrade() { }
	
	protected function fixSubmenu() {
		global $submenu;
		if ( isset( $submenu[self::$ParentMenuId] ) ) {
			$submenu[self::$ParentMenuId][0][0] = 'Dashboard';
		}
	}
	
	protected function display( $insView, $inaData = array() ) {
		$sFile = dirname(__FILE__).DS.'..'.DS.'..'.DS.self::$ViewDir.DS.$insView.self::$ViewExt;
		
		if ( !is_file( $sFile ) ) {
			echo "View not found: ".$sFile;
			return false;
		}
		
		if ( count( $inaData ) > 0 ) {
			extract( $inaData, EXTR_PREFIX_ALL, 'wpv' );
		}
		
		ob_start();
			include( $sFile );
			$sContents = ob_get_contents();
		ob_end_clean();
		
		echo $sContents;
		return true;
	}
	
	protected function isSelfAdminPage() {
		$sSubPageNow = isset( $_GET['page'] )? $_GET['page']: '';
		if ( is_admin() && !empty( $sSubPageNow ) && ( preg_match( '/^'.self::$ParentMenuId.'/i', $sSubPageNow ) !== false ) ) {
			return true;
		}
		return false;
	}
	
	protected function handlePluginFormSubmit() {}

	protected function flushW3TotalCache() {
		if ( class_exists( 'W3_Plugin_TotalCacheAdmin' ) ) {
			$oW3TotalCache =& w3_instance( 'W3_Plugin_TotalCacheAdmin' );
			$oW3TotalCache->flush_all();
		}
	}
	
	protected function getImageUrl( $insImage ) {
		return self::$PluginUrl.'images'.DS.$insImage;
	}
	
	protected function getCssUrl( $insStylesheet ) {
		return self::$PluginUrl.'css'.DS.$insStylesheet;
	}
	
	protected function getSubmenuPageTitle( $insTitle ) {
		return self::$ParentTitle.' - '.$insTitle;
	}
	
	protected function getSubmenuId( $insId ) {
		return self::$ParentMenuId.'-'.$insId;
	}
	
	protected function enqueueBootstrapAdminCss() {
		wp_register_style( 'worpit_bootstrap_wpadmin_css', $this->getCssUrl( 'bootstrap-wpadmin.css' ), false, self::$VERSION );
		wp_enqueue_style( 'worpit_bootstrap_wpadmin_css' );
	
		wp_register_style( 'worpit_bootstrap_wpadmin_css_fixes',  $this->getCssUrl( 'bootstrap-wpadmin-fixes.css' ), array( 'worpit_bootstrap_wpadmin_css' ), self::$VERSION );
		wp_enqueue_style( 'worpit_bootstrap_wpadmin_css_fixes' );
	}
	
	public function onWpInit() {
		add_action( 'admin_menu',			array( &$this, 'onWpAdminMenu' ) );
		add_action( 'plugin_action_links',	array( &$this, 'onWpPluginActionLinks' ), 10, 4 );
	}
	
	public function onWpAdminInit() {

		//Do Plugin-Specific Admin Work
		if ( $this->isSelfAdminPage() ) {
			//Links up CSS styles for the plugin itself (set the admin bootstrap CSS as a dependency also)
			$this->enqueueBootstrapAdminCss();
		}
	}
	
	public function onWpPluginsLoaded() {
		if ( $this->isSelfAdminPage() ) {
			$this->handlePluginFormSubmit();
		}
	}
	
	public function onWpAdminMenu() {
		add_menu_page( self::$ParentTitle, self::$ParentName, self::$ParentPermissions, self::$ParentMenuId, array( $this, 'onDisplayMainMenu' ), $this->getImageUrl( 'worpit_16x16.png' ) );
	}
	
	public function onDisplayMainMenu() {
		$aData = array(
			'plugin_url'	=> self::$PluginUrl
		);
		$this->display( 'worpit_index', $aData );
	}
	
	public function onWpPluginActionLinks( $inaLinks, $insFile ) {
		if ( $insFile == self::$PluginBasename ) {
			$sSettingsLink = '<a href="'.admin_url( "admin.php" ).'?page='.self::$ParentMenuId.'">' . __( 'Settings', 'worpit' ) . '</a>';
			array_unshift( $inaLinks, $sSettingsLink );
		}
		return $inaLinks;
	}

	/**
	 * Strength can be 1, 3, 7, 15
	 *
	 * @param integer $innLength
	 * @param integer $innStrength
	 * @param boolean $infIgnoreAmb
	 */
	static public function Generate( $innLength = 10, $innStrength = 7, $infIgnoreAmb = true ) {
		$aChars = array( 'abcdefghijkmnopqrstuvwxyz' );
	
		if ( $innStrength & 2 ) {
			$aChars[] = '023456789';
		}
	
		if ( $innStrength & 4 ) {
			$aChars[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
		}
	
		if ( $innStrength & 8 ) {
			$aChars[] = '$%^&*#';
		}
	
		if ( !$infIgnoreAmb ) {
			$aChars[] = 'OOlI1';
		}
	
		$sPassword = '';
		$sCharset = implode( '', $aChars );
		for ( $i = 0; $i < $innLength; $i++ ) {
			$sPassword .= $sCharset[(rand() % strlen( $sCharset ))];
		}
		return $sPassword;
	}
	
	/**
	 * Provides the basic HTML template for printing a WordPress Admin Notices
	 *
	 * @param $insNotice - The message to be displayed.
	 * @param $insMessageClass - either error or updated
	 * @param $infPrint - if true, will echo. false will return the string
	 * @return boolean|string
	 */
	protected function getAdminNotice( $insNotice = '', $insMessageClass = 'updated', $infPrint = false ) {

		$sFullNotice = '
			<div id="message" class="'.$insMessageClass.'">
				<style>
					#message form { margin: 0px; }
				</style>
				'.$insNotice.'
			</div>
		';

		if ( $infPrint ) {
			echo $sFullNotice;
			return true;
		} else {
			return $sFullNotice;
		}
	}//getAdminNotice
	
	protected function redirect( $insUrl, $innTimeout = 1 ) {
		echo '
			<script type="text/javascript">
				function redirect() {
					window.location = "'.$insUrl.'";
				}
				var oTimer = setTimeout( "redirect()", "'.($innTimeout * 1000).'" );
			</script>';
	}
}