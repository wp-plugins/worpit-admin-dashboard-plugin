<?php
/*
Plugin Name: iControlWP
Plugin URI: http://icwp.io/home
Description: Take Control Of All WordPress Sites From A Single Dashboard
Version: 2.8.1
Author: iControlWP
Author URI: http://www.icontrolwp.com/
*/

/**
 * Copyright (c) 2014 iControlWP <support@icontrolwp.com>
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

if ( !defined( 'WORPIT_DS' ) ) {
	define( 'WORPIT_DS', DIRECTORY_SEPARATOR );
}

require_once( dirname(__FILE__).ICWP_DS.'src'.ICWP_DS.'icwp-foundation.php' );
class Worpit_Plugin extends ICWP_APP_Foundation {

	/** OLD VARS */
	static public $VariablePrefix		= 'worpit_admin_';

	/**
	 * @var string
	 */
	const DashboardUrlBase = 'https://app.icontrolwp.com/';

	/**
	 * @var string
	 */
	const RemoteAddSiteUrl = 'https://app.icontrolwp.com/system/remote/add_site';

	/**
	 * @var integer
	 */
	const LinkLimitTimeout = 0; //1hr

	/**
	 * @access static
	 * @var array
	 */
	static private $ServiceIpAddressesIpv4 = array(
		'valid' => array(
			'198.61.176.9', //wd01
			'23.253.56.59', //app01
			'23.253.62.185', //app01
			'23.253.32.180' //wd02
		),
		'old' => array(
			'198.61.173.69'
		)
	);

	/**
	 * @access static
	 * @var array
	 */
	static private $ServiceIpAddressesIpv6 = array(
		'valid' => array(
			'2001:4801:7817:0072:ca75:cc9b:ff10:4699', //wd01
			'2001:4801:7817:72:ca75:cc9b:ff10:4699', //wd01
			'2001:4801:7824:0101:ca75:cc9b:ff10:a7b2', //app01
			'2001:4801:7824:101:ca75:cc9b:ff10:a7b2', //app01
			'2001:4801:7822:0103:be76:4eff:fe10:89a9', //wd02
			'2001:4801:7822:103:be76:4eff:fe10:89a9' //wd02
		),
		'old' => array()
	);

	/**
	 * @var array
	 */
	protected $aLabelData;

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
	 * @var ICWP_Security
	 */
	protected static $oSecuritySystem = NULL;

	/**
	 * @var ICWP_Compatibility
	 */
	protected static $oCompatibilitySystem = NULL;

	/**
	 * @param ICWP_APP_Plugin_Controller $oPluginController
	 */
	public function __construct( ICWP_APP_Plugin_Controller $oPluginController ) {

		$this->oPluginController = $oPluginController;
		$this->getController()->loadAllFeatures();
		add_filter( $this->getController()->doPluginPrefix( 'has_permission_to_view' ), array( $this, 'hasPermissionToView' ) );
		add_filter( $this->getController()->doPluginPrefix( 'has_permission_to_submit' ), array( $this, 'hasPermissionToSubmit' ) );

		add_action( 'admin_init', array( $this, 'onWpAdminInit' ) );

		add_action( $this->getController()->doPluginPrefix( 'plugin_shutdown' ), array( $this, 'runStatsSystem' ) );
		add_action( $this->getController()->doPluginPrefix( 'plugin_activate' ), array( $this, 'onPluginActivate' ) );

		add_action( 'wp_loaded', array( $this, 'onWpLoaded' ) );

		// TODO: put this in a more appropriate place
		ICWP_Plugin::updateOption( 'installed_version', $this->getController()->getVersion() );

		// need to run this as early as possible
		$this->runSecuritySystem();
	}

	public function onWpAdminInit() {
		$oCon = $this->getController();
		if ( $oCon->getIsValidAdminArea() ) {
			if ( is_admin() ) {
				if ( $this->GetWhiteLabelSystem()->getIsSystemEnabled() ) {
					//renames parts of the plugin for white label within the plugins listing.
					add_filter( 'all_plugins', array( $this, 'relabel_icwp_plugin' ) );
				}
			}

			if ( current_user_can( 'manage_options' ) && $this->getOption( 'do_activation_redirect', false ) ) {
				$this->deleteOption( 'do_activation_redirect');
				if ( $this->getOption( 'assigned', 'N' ) == 'N' ) {
					wp_redirect( 'admin.php?page=worpit-admin' );
				}
			}
		}
	}

	/**
	 */
	public function onWpLoaded() {
		$this->runGoogleAnalyticsSystem();
		$this->runAutoUpdatesSystem();
	}

	public function onPluginActivate() {
		$fIsAssigned = $this->getOption( 'assigned' ) === 'Y';
		if ( !$fIsAssigned ) {
			$aOptions = $this->getPluginDefaultOptions();
			foreach( $aOptions as $sKey => $mValue ) {
				$this->addOption( $sKey, $mValue );
			}
		}
		// Allows for redirect to plugin page once the plugin is activated.
		$this->addOption( 'do_activation_redirect', true );
	}

	/**
	 * @return ICWP_APP_Plugin_Controller
	 */
	public function getController() {
		return $this->oPluginController;
	}

	/**
	 * @param boolean $fHasPermission
	 * @return boolean
	 */
	public function hasPermissionToView( $fHasPermission = true ) {
		return $this->hasPermissionToSubmit( $fHasPermission );
	}

	/**
	 * @param boolean $fHasPermission
	 * @return boolean
	 */
	public function hasPermissionToSubmit( $fHasPermission = true ) {
		// first a basic admin check
		return $fHasPermission && is_super_admin() && current_user_can( $this->getController()->getBasePermissions() );
	}

	/**
	 * @param string $sKey
	 * @param bool $mDefault
	 *
	 * @return mixed
	 */
	static public function getOption( $sKey, $mDefault = false ) {
		return self::loadWpFunctionsProcessor()->getOption( self::$VariablePrefix.$sKey, $mDefault );
	}

	/**
	 * @param string $sKey
	 * @param bool $mValue
	 *
	 * @return mixed
	 */
	static public function addOption( $sKey, $mValue ) {
		return self::loadWpFunctionsProcessor()->addOption( self::$VariablePrefix.$sKey, $mValue );
	}

	/**
	 * @param string $sKey
	 * @param bool $mValue
	 *
	 * @return mixed
	 */
	static public function updateOption( $sKey, $mValue ) {
		return self::loadWpFunctionsProcessor()->updateOption( self::$VariablePrefix.$sKey, $mValue );
	}

	/**
	 * @param string $sKey
	 *
	 * @return mixed
	 */
	static public function deleteOption( $sKey ) {
		return self::loadWpFunctionsProcessor()->deleteOption( self::$VariablePrefix.$sKey );
	}

	/**
	 * @param $fCheckIps
	 * @return boolean
	 */
	public static function GetIcwpAuthenticated( $fCheckIps = false ) {

		// We shouldn't be recognised as authenticated unless we're at least assigned
		if ( !self::IsLinked() ) {
			return false;
		}

		if ( $fCheckIps && !self::GetIsVisitorIcwp() ) {
			return false;
		}

		$sAuthKey = self::getOption( 'key' );
		$oDp = self::loadDataProcessor();

		// This relies on the fact that if handshaking is enabled, we also pre-check (handshake) package requests.
		$sGetIcwpKey = $oDp->FetchGet( 'icwp_key' );
		if ( !empty( $sGetIcwpKey ) && ($sGetIcwpKey == $sAuthKey) && self::GetIsHandshakeEnabled() ) {
			return true;
		}

		// Otherwise we use the old-style Key + PIN Auth sent in the POST
		$sPostKey = $oDp->FetchPost( 'key' );
		$sPostPin = $oDp->FetchPost( 'pin' );
		if ( empty( $sPostKey ) || empty( $sPostPin ) ) {
			return false;
		}

		if ( $sAuthKey != trim( $sPostKey ) ) {
			return false;
		}

		$sPin = self::getOption( 'pin' );
		if ( $sPin !== md5( trim( $sPostPin ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public static function GetIsVisitorIcwp() {
		$sIp = self::loadDataProcessor()->getVisitorIpAddress( true );
		return in_array( $sIp, self::$ServiceIpAddressesIpv4 ) || in_array( $sIp, self::$ServiceIpAddressesIpv6 );
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

		$oDp = $this->loadDataProcessor();
		//Clicked the button to acknowledge the installation of the plugin
		$nUserId = $oDp->FetchPost( 'icwp_user_id' );
		if ( $nUserId && $oDp->FetchPost( 'icwp_ack_plugin_notice' ) ) {
			update_user_meta( $nUserId, self::$VariablePrefix.'ack_plugin_notice', 'Y' );
			header( "Location: admin.php?page=".self::$ParentMenuId );
			return;
		}

		//Clicked the button to enable/disable hand-shaking
		if ( $oDp->FetchPost( 'icwp_admin_form_submit_handshake' ) ) {
			if ( $oDp->FetchPost( 'icwp_admin_handshake_enabled' ) ) {
				$this->setHandshakeEnabled( true );
			}
			else {
				$this->setHandshakeEnabled( false );
			}
			header( "Location: admin.php?page=".self::$ParentMenuId );
			return;
		}

		//Clicked the button to remotely add site
		if ( $oDp->FetchPost( 'icwp_admin_form_submit_add_remotely' ) ) {
			$sAuthKey = $oDp->FetchPost( 'account_auth_key' );
			$sEmailAddress = $oDp->FetchPost( 'account_email_address' );
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
		if ( $oDp->FetchPost( 'icwp_admin_form_submit_debug' ) ) {
			if ( $oDp->FetchPost( 'submit_gather' ) ) {
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
					$this->updateOption( 'debug_file', $sUniqueName );
				}
			}
			else if ( $oDp->FetchPost( 'submit_information' ) ) {
				$sTarget = $this->getOption( 'debug_file' );
				$sTargetAbs = dirname(__FILE__).'/'.$sTarget;
				if ( !empty( $sTarget ) && is_file( $sTargetAbs ) ) {
					if ( wp_mail( 'support@icontrolwp.com', 'Debug Configuration', 'See attachment', '', $sTargetAbs ) ) {
						unlink( $sTargetAbs );
						$this->deleteOption( 'debug_file' );
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
						$sTo = $this->getOption( 'assigned_to' );
						$sKey = $this->getOption( 'key' );
						$sPin = $this->getOption( 'pin' );

						if ( !empty( $sTo ) && !empty( $sKey ) && !empty( $sPin ) ) {
							$aParts = array( urlencode( $sTo ), $sKey, $sPin );
							$sContents = @file_get_contents( self::DashboardUrlBase.'system/verification/reset/'.implode( '/', $aParts ) );
						}

						$aOptions = $this->getPluginDefaultOptions();
						foreach( $aOptions as $sKey => $mValue ) {
							$this->updateOption( $sKey, $mValue );
						}
					}
					break;
			}
		}
	}

	/**
	 * @param array $aPlugins
	 * @return array
	 */
	public function hide_icwp_plugin( $aPlugins ) {
		$sPluginFile = $this->getController()->getRootFile();
		if ( array_key_exists( $sPluginFile, $aPlugins ) ) {
			unset( $aPlugins[$sPluginFile] );
		}
		return $aPlugins;
	}

	/**
	 * @param array $aPlugins
	 * @return array
	 */
	public function relabel_icwp_plugin( $aPlugins ) {
		$aLabelData = $this->getPluginLabelData();
		if ( $aLabelData['service_name'] == $this->getController()->getHumanName() ) {
			return $aPlugins;
		}

		$sPluginFile = $this->getController()->getRootFile();
		if ( array_key_exists( $sPluginFile, $aPlugins ) ) {
			$aPlugins[$sPluginFile]['Name'] = $aLabelData['service_name'];
			$aPlugins[$sPluginFile]['Description'] = $aLabelData['tag_line'];
			$aPlugins[$sPluginFile]['Title'] = $aLabelData['service_name'];
			$aPlugins[$sPluginFile]['Author'] = $aLabelData['service_name'];
			$aPlugins[$sPluginFile]['AuthorName'] = $aLabelData['service_name'];
			$aPlugins[$sPluginFile]['PluginURI'] = $aLabelData['plugin_home_url'];
			$aPlugins[$sPluginFile]['AuthorURI'] = $aLabelData['plugin_home_url'];
		}
		return $aPlugins;
	}

	/**
	 * @see Worpit_Plugin_Base::onWpPluginsLoaded()
	 */
	public function onWpPluginsLoaded() {
		// If the plugin is being initialised from iControlWP Dashboard
//			$this->setAuthorizedUser();
		$this->runCompatibilitySystem();
	}

	/**
	 * @return bool
	 */
	public static function GetIsHandshakeEnabled() {
		return self::getOption( 'can_handshake' ) == 'Y' && self::getOption( 'handshake_enabled' ) == 'Y';
	}

	/**
	 * @param bool $fSetEnabled
	 */
	protected function setHandshakeEnabled( $fSetEnabled = true ) {
		require_once( dirname(__FILE__).'/src/loader.php' );
		$sCanHandshake = worpitCheckCanHandshake();
		if ( $fSetEnabled && !$sCanHandshake ) {//only set enabled if it's possible
			return;
		}
		$this->updateOption( 'can_handshake', $sCanHandshake ? 'Y' : 'N' );
		$this->updateOption( 'handshake_enabled', $fSetEnabled ? 'Y' : 'N' );
	}

	/**
	 * (non-PHPdoc)
	 * @see Worpit_Plugin_Base::onDisplayMainMenu()
	 */
	public function onDisplayMainMenu() {
		$sDebugFile = ICWP_Plugin::getOption( 'debug_file' );
		$aData = array(
			'plugin_url'		=> $this->getController()->getPluginUrl(),
			'label_data'		=> $this->getPluginLabelData(),
			'key'				=> $this->getOption( 'key' ),
			'pin'				=> $this->getOption( 'pin' ),
			'assigned'			=> $this->getOption( 'assigned' ),
			'assigned_to'		=> $this->getOption( 'assigned_to' ),
			'is_linked'			=> self::IsLinked(),

			'can_handshake'		=> $this->getOption( 'can_handshake' ),
			'handshake_enabled'	=> $this->getOption( 'handshake_enabled' ),
			'debug_file_url'	=> empty( $sDebugFile )? false: $this->getController()->getPluginUrl().$sDebugFile,

			'nonce_field'		=> self::$ParentMenuId,
			'form_action'		=> 'admin.php?page='.self::$ParentMenuId,

			'image_url'			=> $this->getImageUrl( '' ),

			'options_ga'		=> $this->getGoogleAnalyticsSystem()->getSystemOptions(),
			'options_au'		=> $this->getAutoUpdatesSystem()->getSystemOptions(),
			'options_ss'		=> $this->getStatsSystem()->getSystemOptions()
		);
		$this->display( 'icwp_index', $aData );
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
	 * @param string $sAuthKey
	 * @param string $sEmailAddress
	 * @return boolean
	 */
	public function doRemoteLink( $sAuthKey, $sEmailAddress ) {
		if ( self::IsLinked() ) {
			return false;
		}

		if ( strlen( $sAuthKey ) == 32 && is_email( $sEmailAddress ) ) {

			//looks good. Now attempt remote link.
			$aPostVars = array(
				'wordpress_url'				=> home_url(),
				'plugin_url'				=> $this->getController()->getPluginUrl(),
				'account_email_address'		=> $sEmailAddress,
				'account_auth_key'			=> $sAuthKey,
				'plugin_key'				=> $this->getOption( 'key' )
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
	 *
	 */
	public function getPluginDefaultOptions() {
		$nTime = $this->loadDataProcessor()->GetRequestTime();
		return array(
			'key'				=> self::Generate( 24, 7 ),
			'pin'				=> '',
			'assigned'			=> 'N',
			'assigned_to'		=> '',
			'can_handshake'		=> 'N',
			'handshake_enabled'	=> 'N',
			'activated_at'		=> $nTime,
			'installed_at'		=> $nTime,
			'installed_version'	=> $this->getController()->getVersion()
		);
	}

	/**
	 * @return array|boolean
	 */
	public function getDefaultPluginLabelData() {
		return array(
			'service_name'		=> $this->getController()->getHumanName(),
			'tag_line'			=> 'Take Control Of All WordPress Sites From A Single Dashboard',
			'plugin_home_url'	=> 'http://icwp.io/home',
			'icon_url_16x16'	=> $this->getController()->getPluginUrl_Image( 'icontrolwp_16x16.png' ),
			'icon_url_32x32'	=> $this->getController()->getPluginUrl_Image( 'icontrolwp_32x32.png' )
		);
	}

	/**
	 * @return array
	 */
	public function getPluginLabelData() {
		if ( empty( $this->aLabelData ) ) {
			$this->runWhiteLabelSystem();
		}
		return $this->aLabelData;
	}

	/**
	 * @return integer
	 */
	public static function GetActivatedAt() {
		return ICWP_Plugin::getOption( 'activated_at' );
	}

	/**
	 * @return ICWP_WhiteLabel
	 */
	public static function GetWhiteLabelSystem() {
		if ( is_null( self::$oWhiteLabelSystem ) ) {
			self::$oWhiteLabelSystem = include_once( self::GetSrcDir_Systems( 'system-white-label.php' ) );
		}
		return self::$oWhiteLabelSystem;
	}

	/**
	 * Runs the white label processes
	 */
	protected function runWhiteLabelSystem() {
		$this->aLabelData = $this->getDefaultPluginLabelData();

		$oWhiteLabelSystem = $this->getWhiteLabelSystem();
		if ( $oWhiteLabelSystem->getIsSystemEnabled() ) {
			$aWhiteLabelData = $oWhiteLabelSystem->getSystemOptions();
			if ( !empty( $aWhiteLabelData ) ) {
				$this->aLabelData = $aWhiteLabelData;
			}
		}
	}

	/**
	 * @return ICWP_Stats
	 */
	public static function GetStatsSystem() {
		if ( is_null( self::$oStatsSystem ) ) {
			self::$oStatsSystem = include_once( self::GetSrcDir_Systems( 'system-stats.php' ) );
		}
		return self::$oStatsSystem;
	}

	/**
	 * Runs the statistic processes (hooked to 'shutdown')
	 */
	public function runStatsSystem() {
		$oStatsSystem = $this->getStatsSystem();
		if ( $oStatsSystem->getIsSystemEnabled() ) {
			$oStatsSystem->run();
		}
	}

	/**
	 * @return ICWP_GoogleAnalytics
	 */
	public static function GetGoogleAnalyticsSystem() {
		if ( is_null( self::$oGoogleAnalyticsSystem ) ) {
			self::$oGoogleAnalyticsSystem = include_once( self::GetSrcDir_Systems( 'system-google-analytics.php' ) );
		}
		return self::$oGoogleAnalyticsSystem;
	}

	/**
	 * Runs the statistic processes (hooked to 'wp_loaded')
	 */
	protected function runGoogleAnalyticsSystem() {
		$oGoogleAnalyticsSystem = $this->getGoogleAnalyticsSystem();
		if ( $oGoogleAnalyticsSystem->getIsSystemEnabled() ) {
			$oGoogleAnalyticsSystem->run();
		}
	}

	/**
	 * @return ICWP_AutoUpdates
	 */
	public static function GetAutoUpdatesSystem() {
		if ( is_null( self::$oAutoUpdatesSystem ) ) {
			self::$oAutoUpdatesSystem = include_once( self::GetSrcDir_Systems( 'system-autoupdates.php' ) );
		}
		return self::$oAutoUpdatesSystem;
	}

	/**
	 * Runs the statistic processes (hooked to 'wp_loaded')
	 */
	protected function runAutoUpdatesSystem() {
		$oAutoUpdatesSystem = $this->getAutoUpdatesSystem();
		if ( $oAutoUpdatesSystem->getIsSystemEnabled() ) {
			$oAutoUpdatesSystem->run();
		}
	}

	/**
	 * @return ICWP_Security
	 */
	public static function GetSecuritySystem() {
		if ( is_null( self::$oSecuritySystem ) ) {
			self::$oSecuritySystem = include_once( self::GetSrcDir_Systems( 'system-security.php' ) );
		}
		return self::$oSecuritySystem;
	}

	/**
	 * Runs the statistic processes (hooked to 'plugins_loaded')
	 */
	protected function runSecuritySystem() {
		$oSecuritySystem = $this->getSecuritySystem();
		if ( $oSecuritySystem->getIsSystemEnabled() ) {
			$oSecuritySystem->run();
		}
	}

	/**
	 * @return ICWP_Compatibility
	 */
	public static function GetCompatibilitySystem() {
		if ( is_null( self::$oCompatibilitySystem ) ) {
			self::$oCompatibilitySystem = include_once( self::GetSrcDir_Systems( 'system-compatibility.php' ) );
		}
		return self::$oCompatibilitySystem;
	}

	/**
	 * Runs the plugin compatibility processes (hooked to 'plugins_loaded')
	 */
	protected function runCompatibilitySystem() {

		$aLabelData = $this->getPluginLabelData();

		$oSys = $this->getCompatibilitySystem();
		$oSys->setIsSystemEnabled( true );
		$oSys->setOption( 'service_ip_addresses_ipv4', self::$ServiceIpAddressesIpv4 );
		$oSys->setOption( 'service_ip_addresses_ipv6', self::$ServiceIpAddressesIpv6 );
		$oSys->setOption( 'service_name', $aLabelData['service_name'] );
		$oSys->run();
	}

	protected function createMenu() {

		//if the site is a multisite and we're not on the network admin, get out.
		if ( function_exists( 'is_multisite' ) ) {
			if ( is_multisite() && !is_network_admin() && self::$NetworkAdminOnly ) {
				return true;
			}
		}

		$sFullParentMenuId = $this->getFullParentMenuId();
		$aLabelData = $this->getPluginLabelData();
		add_menu_page(
			$aLabelData['service_name'], //self::$ParentTitle
			$aLabelData['service_name'], //self::$ParentName
			self::$ParentPermissions,
			$sFullParentMenuId,
			array( $this, 'onDisplayMainMenu' ),
			$aLabelData['icon_url_16x16']
		);

		$this->fixSubmenu();
	}

	/**
	 * @param string $sFile
	 *
	 * @return string
	 */
	static private function GetSrcDir_Systems( $sFile = '' ) {
		return dirname(__FILE__).WORPIT_DS.'src'.WORPIT_DS.'plugin'.WORPIT_DS.$sFile;
	}
}

if ( !class_exists('ICWP_Plugin') ) {
	class ICWP_Plugin extends Worpit_Plugin {}
}

require_once( 'icwp-plugin-controller.php' );

$oICWP_App_Controller = ICWP_APP_Plugin_Controller::GetInstance( __FILE__ );
if ( !is_null( $oICWP_App_Controller ) ) {
	$g_oWorpit = new Worpit_Plugin( $oICWP_App_Controller );

	if ( $g_oWorpit->GetIcwpAuthenticated() ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	}
}