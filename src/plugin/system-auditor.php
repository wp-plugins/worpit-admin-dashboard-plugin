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

class ICWP_Auditor extends ICWP_System_Base {

	public function __construct() {
		$this->sOptionsKey = 'auditor_system_options';
		parent::__construct();
	}

	/**
	 * Does all the setup of the individual processors
	 *
	 * @return bool
	 */
	public function run() {
		if ( !$this->getIsSystemEnabled() ) {
			return false;
		}
		$oProcessor = $this->getAuditorProcessor();
		$oProcessor->run();
		return true;
	}

	/**
	 * @param string $insTrackingId
	 * @return bool
	 */
	public function setTrackingId( $insTrackingId ) {
		return $this->updateSystemOptions(
			array(
				'tracking_id' => $insTrackingId
			)
		);
	}

	/**
	 * @return void
	 */
	protected function includeProcessors() {
		require_once( dirname(__FILE__).'/processors/icwp-processor-auditor.php' );
	}

	/**
	 * @return void
	 */
	protected function validateOptions() {
		$aMinimumDefaults = array(
			'enabled'						=> false,
			'ignore_logged_in_user'			=> false,
			'ignore_from_user_level'		=> 11, //max is 10 so by default ignore no-one
		);
		$this->aOptions = array_merge( $aMinimumDefaults, $this->aOptions );
	}

	/**
	 * @return ICWP_Processor_GoogleAnalytics_CP
	 */
	protected function getAuditorProcessor() {
		$this->includeProcessors();
		$oProcessor = new ICWP_Processor_Auditor_CP( self::Prefix );
		$oProcessor->setOptions( $this->getSystemOptions() );
		return $oProcessor;
	}
}

return new ICWP_Auditor();

/*
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
		add_action( 'shutdown', array( &$this, 'onShutdown' ) );
		if ( !defined( 'SAVEQUERIES' ) ){
			define( 'SAVEQUERIES', true );
		}

		/**
		 * We don't want to have to call add_action continuously, as that's repetitive, so we make
		 * a predictable loop (i.e. we know what way the function name will be written)
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
	public function onWpLogin( $insUserLogin ) {
		$this->logAction(
			'wp_login',
			'User "'.$insUserLogin.'" logged in.',
			func_get_args()
		);
	}

	/**
	 * @link http://codex.wordpress.org/Plugin_API/Action_Reference/delete_user
	public function onDeleteUser( $insUserId ) {

	}

	/**
	 * The parameters are subject to change! They are just an initial sketch, maybe you can
	 * think more about this.
	protected function logAction( $insAction, $insText, $inaArgs ) {
		$this->m_aActions[] = array_merge( func_get_args(), array( time() ) );
	}
}
*/