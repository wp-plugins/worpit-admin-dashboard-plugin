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

require_once( dirname(__FILE__).'/base/icwp-processor-base.php' );

if ( !class_exists('ICWP_Processor_AutoUpdates_CP') ):

class ICWP_Processor_AutoUpdates_CP extends ICWP_Processor_Base_CP {
	const Slug = 'autoupdates';
	const FilterPriority = 1001;

	/**
	 * @var string
	 */
	protected $sPluginFile;

	/**
	 * @var boolean
	 */
	protected $fDoForceRunAutoUpdates = false;
	
	public function __construct( $insOptionPrefix = '' ) {
		parent::__construct( $this->constructStorageKey( $insOptionPrefix, self::Slug ) );
	}
	
	/**
	 * @param boolean $infDoForceRun
	 */
	public function setForceRunAutoUpdates( $infDoForceRun ) {
		$this->fDoForceRunAutoUpdates = $infDoForceRun;
	}

	/**
	 * @return boolean
	 */
	public function getForceRunAutoUpdates() {
		return apply_filters( 'icwp_force_autoupdate', $this->fDoForceRunAutoUpdates );
	}

	/**
	 * @param string $insPluginFile
	 */
	public function setThisPluginFile( $insPluginFile = '' ) {
		if ( empty( $insPluginFile ) ) {
			return;
		}
		$this->sPluginFile = $insPluginFile;
	}
	
	/**
	 */
	public function run() {

		if ( !$this->getCanRun() ) {
			return;
		}
		
		// When we force run auto updates we only want our filters to be applied.
		if ( $this->getForceRunAutoUpdates() ) {
			$this->removeAllAutoupdateFilters();
		}
		
		add_filter( 'allow_minor_auto_core_updates',	array( $this, 'autoupdate_core_minor' ), self::FilterPriority );
		add_filter( 'allow_major_auto_core_updates',	array( $this, 'autoupdate_core_major' ), self::FilterPriority );

		add_filter( 'auto_update_translation',			array( $this, 'autoupdate_translations' ), self::FilterPriority, 2 );
		add_filter( 'auto_update_plugin',				array( $this, 'autoupdate_plugins' ), self::FilterPriority, 2 );
		add_filter( 'auto_update_theme',				array( $this, 'autoupdate_themes' ), self::FilterPriority, 2 );

		if ( $this->getOption( 'enable_autoupdate_ignore_vcs' ) ) {
			add_filter( 'automatic_updates_is_vcs_checkout', array( $this, 'disable_for_vcs' ), 10, 2 );
		}

		if ( $this->getOption( 'enable_autoupdate_disable_all' ) ) {
			add_filter( 'automatic_updater_disabled', '__return_true', self::FilterPriority );
		}
		
		add_filter( 'auto_core_update_send_email',	array( $this, 'autoupdate_send_email' ),		self::FilterPriority, 1 ); //more parameter options here for later
		add_filter( 'auto_core_update_email',		array( $this, 'autoupdate_email_override' ),	self::FilterPriority, 1 ); //more parameter options here for later

		if ( $this->getForceRunAutoUpdates() ) {
			$this->force_run_autoupdates( ); //we'll redirect to the updates page for to show
		}
	}

	/**
	 * Will force-run the WordPress automatic updates process and then (maybe) redirect to the updates screen.
	 */
	public function force_run_autoupdates( $insRedirect = false ) {
		$lock_name = 'auto_updater.lock'; //ref: /wp-admin/includes/class-wp-upgrader.php
		delete_option( $lock_name );
		if ( !defined('DOING_CRON') ) {
			define( 'DOING_CRON', true ); // this prevents WP from disabling plugins pre-upgrade
		}
		
		// does the actual updating
		wp_maybe_auto_update();
		
		if ( !empty( $insRedirect ) ) {
			wp_redirect( network_admin_url( $insRedirect ) );
			exit();
		}
		return true;
	}
	
	/**
	 * This is a filter method designed to say whether a major core WordPress upgrade should be permitted,
	 * based on the plugin settings.
	 * 
	 * @param boolean $infUpdate
	 * @return boolean
	 */
	public function autoupdate_core_major( $infUpdate ) {
		if ( $this->getOption( 'autoupdate_core' ) == 'core_never' ) {
			return false;
		}
		else if ( $this->getOption( 'autoupdate_core' ) == 'core_major' ) {
			return true;
		}
		return $infUpdate;
	}
	
	/**
	 * This is a filter method designed to say whether a minor core WordPress upgrade should be permitted,
	 * based on the plugin settings.
	 * 
	 * @param boolean $infUpdate
	 * @return boolean
	 */
	public function autoupdate_core_minor( $infUpdate ) {
		if ( $this->getOption( 'autoupdate_core' ) == 'core_never' ) {
			return false;
		}
		else if ( $this->getOption( 'autoupdate_core' ) == 'core_minor' ) {
			return true;
		}
		return $infUpdate;
	}
	
	/**
	 * This is a filter method designed to say whether a WordPress translations upgrades should be permitted,
	 * based on the plugin settings.
	 * 
	 * @param boolean $infUpdate
	 * @param string $insSlug
	 * @return boolean
	 */
	public function autoupdate_translations( $infUpdate, $insSlug ) {
		if ( $this->getOption( 'enable_autoupdate_translations' ) ) {
			return true;
		}
		return $infUpdate;
	}
	
	/**
	 * This is a filter method designed to say whether WordPress plugin upgrades should be permitted,
	 * based on the plugin settings.
	 * 
	 * @param boolean $infUpdate
	 * @param boolean $insPluginSlug
	 * @return boolean
	 */
	public function autoupdate_plugins( $infUpdate, $insPluginSlug ) {

		if ( $insPluginSlug === $this->sPluginFile ) {
			return $this->getOption( 'autoupdate_plugin_self' );
		}

		if ( $this->getOption( 'enable_autoupdate_plugins' ) ) {
			return true;
		}

		$aAutoUpdateFiles = $this->getOption('auto_update_plugins');
		if ( !empty( $aAutoUpdateFiles ) && is_array($aAutoUpdateFiles) ) {
			if ( in_array( $insPluginSlug, $aAutoUpdateFiles ) ) {
				return true;
			}
		}

		return $infUpdate;
	}

	/**
	 * This is a filter method designed to say whether WordPress theme upgrades should be permitted,
	 * based on the plugin settings.
	 *
	 * @param boolean $infUpdate
	 * @param string $insThemeSlug
	 * @return boolean
	 */
	public function autoupdate_themes( $infUpdate, $insThemeSlug ) {

		if ( $this->getOption( 'enable_autoupdate_themes' ) ) {
			return true;
		}

		$aAutoUpdateFiles = $this->getOption('auto_update_themes');
		if ( !empty( $aAutoUpdateFiles ) && is_array($aAutoUpdateFiles) ) {
			if ( in_array( $insThemeSlug, $aAutoUpdateFiles ) ) {
				return true;
			}
		}
		
		return $infUpdate;
	}
	
	/**
	 * This is a filter method designed to say whether WordPress automatic upgrades should be permitted
	 * if a version control system is detected.
	 *
	 * @param boolean $checkout
	 * @param boolean $context
	 * @return boolean
	 */
	public function disable_for_vcs( $checkout, $context ) {
		return false;
	}
	
	/**
	 * A filter on whether or not a notification email is send after core upgrades are attempted.
	 * 
	 * @param boolean $infSendEmail
	 * @return boolean
	 */
	public function autoupdate_send_email( $infSendEmail ) {
		return $this->getOption( 'enable_upgrade_notification_email' );
	}
	
	/**
	 * A filter on the target email address to which to send upgrade notification emails.
	 * 
	 * @param array $inaEmailParams
	 * @return array
	 */
	public function autoupdate_email_override( $inaEmailParams ) {
		$sEmail = $this->getOption( 'override_email_address' );
		if ( !empty( $sEmail ) ) {
			$inaEmailParams['to'] = $sEmail;
		}
		return $inaEmailParams;
	}

	/**
	 * Removes all filters that have been added from auto-update related WordPress filters
	 */
	protected function removeAllAutoupdateFilters() {
		$aFilters = array(
			'allow_minor_auto_core_updates',
			'allow_major_auto_core_updates',
			'auto_update_translation',
			'auto_update_plugin',
			'auto_update_theme',
			'automatic_updates_is_vcs_checkout',
			'automatic_updater_disabled'
		);
		foreach( $aFilters as $sFilter ) {
			remove_all_filters( $sFilter );
		}
	}
}

endif;
