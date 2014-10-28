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
	static protected $VariablePrefix		= 'worpit_admin_';

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
	 * @var ICWP_APP_Plugin_Controller
	 */
	protected static $oPluginController;

	/**
	 * @param ICWP_APP_Plugin_Controller $oPluginController
	 */
	public function __construct( ICWP_APP_Plugin_Controller $oPluginController ) {

		self::$oPluginController = $oPluginController;
		$this->getController()->loadAllFeatures();

		add_action( $this->getController()->doPluginPrefix( 'plugin_shutdown' ), array( $this, 'runStatsSystem' ) );
		add_action( $this->getController()->doPluginPrefix( 'plugin_activate' ), array( $this, 'onPluginActivate' ) );
		add_filter( $this->getController()->doPluginPrefix( 'plugin_labels' ), array( $this, 'doRelabelPlugin' ) );

		add_action( 'wp_loaded', array( $this, 'onWpLoaded' ) );

		// need to run this as early as possible
		$this->runSecuritySystem();
		$this->runWhiteLabelSystem();
	}

	/**
	 * @return ICWP_APP_Plugin_Controller
	 */
	public static function getController() {
		return self::$oPluginController;
	}

	/**
	 */
	public function onWpLoaded() {
		$this->runGoogleAnalyticsSystem();
		$this->runAutoUpdatesSystem();
	}

	/**
	 * @param string $sKey
	 * @param mixed $mDefault
	 *
	 * @return mixed
	 */
	static public function getOption( $sKey, $mDefault = false ) {
		return self::getController()->loadCorePluginFeatureHandler()->getOpt( $sKey, $mDefault );
//		return self::loadWpFunctionsProcessor()->getOption( self::$VariablePrefix.$sKey, $mDefault );
	}

	/**
	 * @param string $sKey
	 * @param bool $mValue
	 *
	 * @return mixed
	 */
	static public function updateOption( $sKey, $mValue ) {
		$oCorePluginFeature = self::getController()->loadCorePluginFeatureHandler();
		$oCorePluginFeature->setOpt( $sKey, $mValue );
		$oCorePluginFeature->savePluginOptions();
		return true;
//		return self::loadWpFunctionsProcessor()->updateOption( self::$VariablePrefix.$sKey, $mValue );
	}

	/**
	 * @param array $aPluginLabels
	 *
	 * @return array
	 */
	public function doRelabelPlugin( $aPluginLabels ) {

		$aImageUrls = array(
			'icon_url_16x16' => $this->getController()->getPluginUrl_Image( 'icontrolwp_16x16.png' ),
			'icon_url_32x32' => $this->getController()->getPluginUrl_Image( 'icontrolwp_32x32.png' )
		);

		return array_merge(
			$aPluginLabels,
			$aImageUrls
		);
	}

	/**
	 */
	public function onWpPluginsLoaded() {
		$this->runCompatibilitySystem();
	}

	/**
	 * @return bool
	 */
	public static function GetIsHandshakeEnabled() {
		apply_filters( self::getController()->doPluginPrefix( 'can_handshake' ), false );
	}

	/**
	 * @return boolean
	 */
	static public function IsLinked() {
		apply_filters( self::getController()->doPluginPrefix( 'is_linked' ), false );
	}

	/**
	 * @return integer
	 */
	public static function GetActivatedAt() {
		return ICWP_Plugin::getOption( 'activated_at' );
	}

	/**
	 * @return integer
	 */
	public static function GetVersion() {
		return self::getController()->getVersion();
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
		$oSystem = $this->GetWhiteLabelSystem();
		if ( $oSystem->getIsSystemEnabled() ) {
			$oSystem->run();
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

		$sName = $this->getController()->getHumanName();

		$oSys = $this->getCompatibilitySystem();
		$oSys->setIsSystemEnabled( true );
		$oSys->setOption( 'service_ip_addresses_ipv4', self::$ServiceIpAddressesIpv4 );
		$oSys->setOption( 'service_ip_addresses_ipv6', self::$ServiceIpAddressesIpv6 );
		$oSys->setOption( 'service_name', $sName );
		$oSys->run();
	}

	/**
	 * @param string $sFile
	 *
	 * @return string
	 */
	static private function GetSrcDir_Systems( $sFile = '' ) {
		return dirname(__FILE__).WORPIT_DS.'src'.WORPIT_DS.'plugin'.WORPIT_DS.$sFile;
	}

	private function doDebugDataGather() {
//		if ( $oDp->FetchPost( 'icwp_admin_form_submit_debug' ) ) {
//
//			if ( $oDp->FetchPost( 'submit_gather' ) ) {
//				$sUniqueName = uniqid().'_'.time().'.txt';
//				$sTarget = dirname(__FILE__).'/'.$sUniqueName;
//
//				$fCanWrite = true;
//				if ( !file_put_contents( $sTarget, 'TEST' ) ) {
//					$fCanWrite = false;
//				}
//				else {
//					if ( !is_file( $sTarget ) ) {
//						$fCanWrite = false;
//					}
//				}
//
//				include_once( dirname(__FILE__).'/src/functions/filesystem.php' );
//
//				$aData = array(
//					'_SERVER'				=> $_SERVER,
//					'_ENV'					=> $_ENV,
//					'ini_get_all'			=> @ini_get_all(),
//					'extensions_loaded'		=> @get_loaded_extensions(),
//					'php_version'			=> @phpversion(),
//					'has_exec'				=> function_exists( 'exec' )? 1: 0,
//					'fileperms'				=> array(
//						array(
//							'target'	=> dirname(__FILE__).'/src/controllers/',
//							'perms'		=> fileperms( dirname(__FILE__).'/src/controllers/' ),
//							'is_dir'	=> is_dir( dirname(__FILE__).'/src/controllers/' )? 1: 0
//						),
//						array(
//							'target'	=> dirname(__FILE__).'/src/',
//							'perms'		=> fileperms( dirname(__FILE__).'/src/' ),
//							'is_dir'	=> is_dir( dirname(__FILE__).'/src/' )? 1: 0
//						),
//						array(
//							'target'	=> __FILE__,
//							'perms'		=> fileperms( __FILE__ ),
//							'is_dir'	=> is_dir( __FILE__ )? 1: 0
//						),
//						array(
//							'target'	=> dirname(__FILE__),
//							'perms'		=> fileperms( dirname(__FILE__) ),
//							'is_dir'	=> is_dir( dirname(__FILE__) )? 1: 0
//						),
//						array(
//							'target'	=> dirname(__FILE__).'/../',
//							'perms'		=> fileperms( dirname(__FILE__).'/../' ),
//							'is_dir'	=> is_dir( dirname(__FILE__).'/../' )? 1: 0
//						)
//					)
//				);
//
//				$aData['.htaccess'] = worpitBackwardsRecursiveFileSearch( dirname(__FILE__).'/src/controllers', 7, '.htaccess' );
//				$aData['error_log'] = worpitBackwardsRecursiveFileSearch( dirname(__FILE__).'/src/controllers', 7, 'error_log' );
//				$aData['php_error_log'] = worpitBackwardsRecursiveFileSearch( dirname(__FILE__).'/src/controllers', 7, 'php_error_log' );
//
//				if ( !$fCanWrite ) {
//					echo "<h4>Your system configuration does not allow writing to the filesystem.</h4>";
//					echo "<p>Please take a moment and send the contents of this page to support@icontrolwp.com</p>";
//					echo "<hr />";
//					var_dump( $aData );
//				}
//				else {
//					file_put_contents( $sTarget, print_r( $aData, true ) );
//					$this->updateOption( 'debug_file', $sUniqueName );
//				}
//			}
//			else if ( $oDp->FetchPost( 'submit_information' ) ) {
//				$sTarget = $this->getOption( 'debug_file' );
//				$sTargetAbs = dirname(__FILE__).'/'.$sTarget;
//				if ( !empty( $sTarget ) && is_file( $sTargetAbs ) ) {
//					if ( wp_mail( 'support@icontrolwp.com', 'Debug Configuration', 'See attachment', '', $sTargetAbs ) ) {
//						unlink( $sTargetAbs );
//						$this->deleteOption( 'debug_file' );
//					}
//				}
//			}
//			header( "Location: admin.php?page=".self::$ParentMenuId );
//			return;
//		}
	}
}

if ( !class_exists('ICWP_Plugin') ) {
	class ICWP_Plugin extends Worpit_Plugin {}
}

require_once( 'icwp-plugin-controller.php' );

$oICWP_App_Controller = ICWP_APP_Plugin_Controller::GetInstance( __FILE__ );
if ( !is_null( $oICWP_App_Controller ) ) {
	$g_oWorpit = new Worpit_Plugin( $oICWP_App_Controller );
}