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

class ICWP_Stats extends ICWP_System_Base {

	public function __construct() {
		$this->sOptionsKey = 'stats_system_options';
		parent::__construct();
	}

	public function setupDatabases() {
		$oDailyStats = $this->getDailyStatsProcessor();
		$oDailyStats->createTable();
		$oMonthlyStats = $this->getMonthlyStatsProcessor();
		$oMonthlyStats->createTable();
	}

	/**
	 * Does all the setup of the individual stats processors
	 *
	 * @return bool
	 */
	public function run() {

		if ( !$this->getIsSystemEnabled() ) {
			return false;
		}

		$oDailyStats = $this->getDailyStatsProcessor();
		$oDailyStats->run();

		$oMonthlyStats = $this->getMonthlyStatsProcessor();
		$oMonthlyStats->run();

		return true;
	}

	/**
	 * @return array
	 */
	public function retrieveDailyStats() {
		$oStats = $this->getDailyStatsProcessor();
		return $oStats->getDailyTotals();
	}

	/**
	 * @return array
	 */
	public function retrieveMonthlyStats() {
		$oStats = $this->getMonthlyStatsProcessor();
		return $oStats->getMonthlyTotals();
	}

	/**
	 * @param bool $infEnabled
	 * @return bool
	 */
	public function setIsEnabledDailyStats( $infEnabled = true ) {
		return $this->updateSystemOptions(
			array(
				'do_page_stats_daily' => $infEnabled
			)
		);
	}

	/**
	 * @param bool $infEnabled
	 * @return bool
	 */
	public function setIsEnabledMonthlyStats( $infEnabled = true ) {
		return $this->updateSystemOptions(
			array(
				'do_page_stats_monthly' => $infEnabled
			)
		);
	}

	/**
	 * @return void
	 */
	protected function includeProcessors() {
		require_once( dirname(__FILE__).'/processors/icwp-processor-dailystats.php' );
		require_once( dirname(__FILE__).'/processors/icwp-processor-monthlystats.php' );
	}

	/**
	 * @return void
	 */
	protected function validateOptions() {
		$aMinimumDefaults = array(
			'enabled'					=> false,
			'do_page_stats_daily'		=> false,
			'do_page_stats_monthly'		=> false,
			'ignore_logged_in_user'			=> false,
			'ignore_from_user_level'		=> 11, //max is 10 so by default ignore no-one
		);
		$this->aOptions = array_merge( $aMinimumDefaults, $this->aOptions );
	}

	/**
	 * @return ICWP_Processor_DailyStats_CP
	 */
	protected function getDailyStatsProcessor() {
		$this->includeProcessors();
		$oStats = new ICWP_Processor_DailyStats_CP( self::Prefix );
		$oStats->setOptions( $this->getSystemOptions() );
		return $oStats;
	}

	/**
	 * @return ICWP_Processor_MonthlyStats_CP
	 */
	protected function getMonthlyStatsProcessor() {
		$this->includeProcessors();
		$oStats = new ICWP_Processor_MonthlyStats_CP( self::Prefix );
		$oStats->setOptions( $this->getSystemOptions() );
		return $oStats;
	}
}

return new ICWP_Stats();