<?php

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

require_once( dirname(__FILE__).'/system-base.php' );

class ICWP_AutoUpdates extends ICWP_System_Base {

	/**
	 * @var ICWP_Processor_AutoUpdates_CP
	 */
	protected $oProcessor;

	public function __construct() {
		$this->sOptionsKey = 'autoupdates_system_options';
		parent::__construct();
	}

	/**
	 * Does all the setup of the individual processors
	 *
	 * @param bool $infForce
	 * @return bool
	 */
	public function run( $infForce = false ) {
		if ( !$this->getIsSystemEnabled() ) {
			return false;
		}
		$oProcessor = $this->getAutoUpdatesProcessor();
		$oProcessor->setForceRunAutoUpdates( $infForce );
		$oProcessor->run();
		return true;
	}

	/**
	 *
	 */
	public function convertFromOldSystem() {
		$aOld = Worpit_Plugin::getOption('auto_update_plugins');
		$aAutoUpdateItems = $this->getOption( 'auto_update_plugins', array() );
		if ( !empty( $aOld ) ) {
			$this->loadSystemOptions();
			$aAutoUpdateItems = array_unique( array_merge( $aOld, $aAutoUpdateItems ) );
			$this->setOption( 'auto_update_plugins', $aAutoUpdateItems );
			$this->setIsSystemEnabled(true);
			Worpit_Plugin::deleteOption('auto_update_plugins');
		}
		if ( !empty($aAutoUpdateItems) ) {
			$this->setIsSystemEnabled(true);
		}
	}

	/**
	 * @param string $insSlug
	 * @param bool $infSetOn
	 * @param string $insContext
	 */
	public function setAutoUpdate( $insSlug, $infSetOn = false, $insContext = 'plugins' ) {
		$aAutoUpdateItems = $this->getOption( 'auto_update_'.$insContext );

		$nInArray = array_search( $insSlug, $aAutoUpdateItems );
		if ( $infSetOn && $nInArray === false ) {
			$aAutoUpdateItems[] = $insSlug;
		}
		else if ( !$infSetOn && $nInArray !== false ) {
			unset( $aAutoUpdateItems[$nInArray] );
		}
		$this->setOption( 'auto_update_'.$insContext, $aAutoUpdateItems );
	}

	/**
	 * @param string $insContext
	 * @return array
	 */
	public function getAutoUpdates( $insContext = 'plugins' ) {
		$aAutoUpdateItems = $this->getOption( 'auto_update_'.$insContext, array() );

		$aOld = Worpit_Plugin::getOption('auto_update_plugins');
		if ( $insContext == 'plugins' && !empty( $aOld ) ) {
			Worpit_Plugin::deleteOption('auto_update_plugins');
			$aAutoUpdateItems = array_unique( array_merge( $aOld, $aAutoUpdateItems ) );
			$this->setOption( 'auto_update_plugins', $aAutoUpdateItems );
		}
		return $aAutoUpdateItems;
	}

	/**
	 * @return void
	 */
	protected function includeProcessors() {
		require_once( dirname(__FILE__).'/processors/icwp-processor-autoupdates.php' );
	}

	/**
	 * @return void
	 */
	protected function validateOptions() {
		parent::validateOptions();
		$aMinimumDefaults = array(
			'enable_autoupdate_disable_all'			=> false,
			'autoupdate_core'						=> 'core_minor',
			'enable_autoupdate_plugins'				=> false,
			'enable_autoupdate_themes'				=> false,
			'enable_autoupdate_translations'		=> false,
			'enable_autoupdate_ignore_vcs'			=> false,
			'enable_upgrade_notification_email'		=> false,
			'override_email_address'				=> '',
			'auto_update_plugins'					=> array(),
			'auto_update_themes'					=> array()
		);
		$this->aOptions = array_merge( $aMinimumDefaults, $this->aOptions );

		// handle the old style of automatic updates.
		if ( defined('DOING_CRON') && DOING_CRON ) {
			$this->cleanup_lists();
		}
	}

	/**
	 *
	 */
	private function cleanup_lists() {
		$aPlugins = get_plugins();
		$aUpdatePlugins = $this->getOption( 'auto_update_plugins' );
		$fChanged = false;
		foreach( $aUpdatePlugins as $nKey => $sPlugin ) {
			if ( !array_key_exists( $sPlugin, $aPlugins ) ) {
				$fChanged = true;
				unset( $aUpdatePlugins[$nKey] );
			}
		}
		if ( $fChanged ) {
			$this->setOption( 'auto_update_plugins', $aUpdatePlugins );
		}
	}

	/**
	 * @return ICWP_Processor_AutoUpdates_CP
	 */
	protected function & getAutoUpdatesProcessor() {
		if ( !is_null( $this->oProcessor ) ) {
			return $this->oProcessor;
		}
		$this->includeProcessors();
		$this->oProcessor = new ICWP_Processor_AutoUpdates_CP( self::Prefix );
		$this->oProcessor->setOptions( $this->getSystemOptions() );
		return $this->oProcessor;
	}
}

return new ICWP_AutoUpdates();