<?php

class Worpit_Plugin_Base {
	
	static public $VERSION;
	
	static public $PluginName;
	static public $PluginPath;
	static public $PluginDir;
	static public $PluginUrl;
	static public $PluginBasename;
	
	static public $ParentTitle			= 'iControlWP';
	static public $ParentName			= 'iControlWP';
	static public $ParentPermissions	= 'manage_options';
	static public $ParentMenuId			= 'worpit-admin';
	static public $VariablePrefix		= 'worpit_admin_';
	
	static public $ViewExt				= '.php';
	static public $ViewDir				= 'views';
	
	static public $NetworkAdminOnly		= true;
	
	static public $DefaultTransientLife = 86400; // 1 day
	
	protected $m_sParentMenuIdSuffix;
	protected $m_aAllPluginOptions;
	
	public function __construct() {

		if ( is_admin() ) {
			add_action( 'network_admin_notices', array( $this, 'onWpNetworkAdminNotices' ) );
			add_action( 'admin_notices', array( $this, 'onWpAdminNotices' ) );
		}
	}

	protected function getFullParentMenuId() {
		return self::$ParentMenuId;
	}

	/**
	 * Override this method to handle all the admin notices
	 */
	public function onWpAdminNotices() { }
	
	/**
	 * Override this method to handle all the admin notices
	 */
	public function onWpNetworkAdminNotices() { }

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
		$sFile = dirname(__FILE__).WORPIT_DS.'..'.WORPIT_DS.'..'.WORPIT_DS.self::$ViewDir.WORPIT_DS.$insView.self::$ViewExt;
		
		if ( !is_file( $sFile ) ) {
			echo "View not found: ".$sFile;
			return false;
		}
		
		if ( count( $inaData ) > 0 ) {
			extract( $inaData, EXTR_PREFIX_ALL, 'icwp' );
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
		if ( is_admin() && !empty( $sSubPageNow ) && preg_match( '/^'.self::$ParentMenuId.'/i', $sSubPageNow ) ) {
			return true;
		}
		return false;
	}
	
	protected function flushW3TotalCache() {
		if ( class_exists( 'W3_Plugin_TotalCacheAdmin' ) ) {
			$oW3TotalCache =& w3_instance( 'W3_Plugin_TotalCacheAdmin' );
			$oW3TotalCache->flush_all();
		}
	}
	
	protected function getImageUrl( $insImage ) {
		return self::$PluginUrl.'images'.WORPIT_DS.$insImage;
	}
	
	protected function getCssUrl( $insStylesheet ) {
		return self::$PluginUrl.'css'.WORPIT_DS.$insStylesheet;
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

	protected function createPluginSubMenuItems(){
		$this->m_aPluginMenu = array();
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
	 * Cloudflare compatible.
	 * @param boolean $infAsLong
	 * @return boolean|string - visitor IP Address as IP2Long
	 */
	public function getVisitorIpAddress( $infAsLong = true ) {
		$this->loadDataProcessor();
		return ICWP_Processor_Data_CP::GetVisitorIpAddress( $infAsLong );
	}

	/**
	 *
	 */
	protected static function loadDataProcessor() {
		require_once( dirname(__FILE__).'/processors/base/icwp-processor-data.php');
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
	
	/**
	 * @param string $insUrl
	 * @param array $inaArgs
	 * @return boolean|array
	 */
	static public function RemoteUrlRead( $insUrl, $inaArgs = array() ) {
		
		if ( !function_exists('wp_remote_get') || empty( $insUrl ) ) {
			return false;
		}
		$aResponse = wp_remote_get( $insUrl, $inaArgs );
		if ( $aResponse['response']['code'] != 200 ) {
			return false;
		}
		$aResponseBody = $aResponse['body'];
		return $aResponseBody;
	}
	
	static public function getOption( $insKey, $infDefault = false ) {
		return get_option( self::$VariablePrefix.$insKey, $infDefault );
	}

	static public function addOption( $insKey, $insValue ) {
		return add_option( self::$VariablePrefix.$insKey, $insValue );
	}

	static public function updateOption( $insKey, $insValue ) {
		if ( self::getOption( $insKey ) == $insValue ) {
			return true;
		}
		return update_option( self::$VariablePrefix.$insKey, $insValue );
	}

	static public function deleteOption( $insKey ) {
		return delete_option( self::$VariablePrefix.$insKey );
	}
	
	static public function SetTransient( $insKey, $insValue, $innExpire = null ) {#
		
		if ( is_null( $innExpire ) ) {
			$innExpire = self::$DefaultTransientLife;
		}
		set_transient( self::$VariablePrefix.$insKey, $insValue, $innExpire );
	}
	
	static public function GetTransient( $insKey ) {
		return get_transient( self::$VariablePrefix.$insKey );
	}

	/**
	 * @param string $insKey
	 * @param boolean $infIncludeCookie
	 * @return mixed|null
	 */
	protected function fetchRequest( $insKey, $infIncludeCookie = true ) {
		$mFetchVal = $this->fetchPost( $insKey );
		if ( is_null( $mFetchVal ) ) {
			$mFetchVal = $this->fetchGet( $insKey );
			if ( is_null( $mFetchVal && $infIncludeCookie ) ) {
				$mFetchVal = $this->fetchCookie( $insKey );
			}
		}
		return $mFetchVal;
	}
	/**
	 * @param string $insKey
	 * @return mixed|null
	 */
	protected function fetchGet( $insKey ) {
		if ( function_exists( 'filter_input' ) && defined( 'INPUT_GET' ) ) {
			return filter_input( INPUT_GET, $insKey );
		}
		return $this->arrayFetch( $_GET, $insKey );
	}
	/**
	 * @param string $insKey		The $_POST key
	 * @return mixed|null
	 */
	protected function fetchPost( $insKey ) {
		if ( function_exists( 'filter_input' ) && defined( 'INPUT_POST' ) ) {
			return filter_input( INPUT_POST, $insKey );
		}
		return $this->arrayFetch( $_POST, $insKey );
	}
	/**
	 * @param string $insKey		The $_POST key
	 * @return mixed|null
	 */
	protected function fetchCookie( $insKey ) {
		if ( function_exists( 'filter_input' ) && defined( 'INPUT_COOKIE' ) ) {
			return filter_input( INPUT_COOKIE, $insKey );
		}
		return $this->arrayFetch( $_COOKIE, $insKey );
	}

	/**
	 * @param array $inaArray
	 * @param string $insKey		The array key
	 * @return mixed|null
	 */
	protected function arrayFetch( &$inaArray, $insKey ) {
		if ( empty( $inaArray ) ) {
			return null;
		}
		if ( !isset( $inaArray[$insKey] ) ) {
			return null;
		}
		return $inaArray[$insKey];
	}
}