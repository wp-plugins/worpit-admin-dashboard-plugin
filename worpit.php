<?php
/*
Plugin Name: iControlWP
Plugin URI: http://icwp.io/home
Description: Take Control Of All WordPress Sites From A Single Dashboard
Version: 2.9.5.1
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

if ( !defined( 'ICWP_DS' ) ) {
	define( 'ICWP_DS', DIRECTORY_SEPARATOR );
}

if ( !defined( 'WORPIT_DS' ) ) {
	define( 'WORPIT_DS', DIRECTORY_SEPARATOR );
}
if ( class_exists( 'Worpit_Plugin', false ) ) {
	return;
}

require_once( dirname(__FILE__).ICWP_DS.'src'.ICWP_DS.'icwp-foundation.php' );
class Worpit_Plugin extends ICWP_APP_Foundation {

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

	}

	/**
	 * @return ICWP_APP_Plugin_Controller
	 */
	public static function getController() {
		return self::$oPluginController;
	}

	/**
	 * @param string $sKey
	 * @param mixed $mDefault
	 *
	 * @return mixed
	 */
	static public function getOption( $sKey, $mDefault = false ) {
		return self::getController()->loadCorePluginFeatureHandler()->getOpt( $sKey, $mDefault );
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
	}

	/**
	 * @return bool
	 */
	public static function GetHandshakingEnabled() {
		return self::getController()->loadCorePluginFeatureHandler()->getIsHandshakeEnabled();
	}

	/**
	 * @return boolean
	 */
	static public function IsLinked() {
		return self::getController()->loadCorePluginFeatureHandler()->getIsSiteLinked();
	}

	/**
	 * @return integer
	 */
	public static function GetVersion() {
		return self::getController()->getVersion();
	}

	/**
	 * @return ICWP_APP_FeatureHandler_WhiteLabel
	 */
	public static function GetWhiteLabelSystem() {
		return self::getController()->loadFeatureHandler( array( 'slug' => 'whitelabel' ) );
	}

	/**
	 * @return ICWP_APP_FeatureHandler_Statistics
	 */
	public static function GetStatsSystem() {
		return self::getController()->loadFeatureHandler( array( 'slug' => 'statistics' ) );
	}

	/**
	 * @return ICWP_APP_FeatureHandler_GoogleAnalytics
	 */
	public static function GetGoogleAnalyticsSystem() {
		return self::getController()->loadFeatureHandler( array( 'slug' => 'google_analytics' ) );
	}

	/**
	 * @return ICWP_APP_FeatureHandler_AutoUpdates
	 */
	public static function GetAutoUpdatesSystem() {
		return self::getController()->loadFeatureHandler( array( 'slug' => 'autoupdates' ) );
	}

	/**
	 * @return ICWP_APP_FeatureHandler_Security
	 */
	public static function GetSecuritySystem() {
		return self::getController()->loadFeatureHandler( array( 'slug' => 'security' ) );
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