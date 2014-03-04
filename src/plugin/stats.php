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

class ICWP_Stats {

	/**
	 * @var string
	 */
	const Stats_Key_Options = 'icwp_stats_system_options';

	/**
	 * @var string
	 */
	const Prefix = 'icwp_';

	/**
	 * @var array
	 */
	protected $aOptions;

	public function __construct() {
		$this->getStatsSystemOptions();
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

		if ( !$this->getIsStatsSystemEnabled() ) {
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
	 * @return array
	 */
	public function getStatsSystemOptions() {
		if ( !isset( $this->aOptions ) ) {
			$this->aOptions = get_option( self::Stats_Key_Options, array() );
			$this->validateOptions();
		}
		return $this->aOptions;
	}

	/**
	 * Use this to update 1 or more options at a time.
	 *
	 * @param array $inaOptions
	 * @return boolean
	 */
	public function updateStatsSystemOptions( $inaOptions = array() ) {
		$this->aOptions = array_merge( $this->getStatsSystemOptions(), $inaOptions );
		return $this->saveStatsSystemOptions();
	}

	/**
	 * Use this to completely empty/clear the options
	 *
	 * @return boolean
	 */
	public function clearStatsSystemOptions() {
		$this->aOptions = array();
		return $this->saveStatsSystemOptions();
	}

	/**
	 * @return boolean
	 */
	protected function saveStatsSystemOptions() {
		$this->validateOptions();
		return update_option( self::Stats_Key_Options, $this->aOptions );
	}

	/**
	 * @return boolean
	 */
	public function getIsStatsSystemEnabled() {
		$aOptions = $this->getStatsSystemOptions();
		return $aOptions['enabled'];
	}

	/**
	 * @param bool $infEnabled
	 * @return boolean
	 */
	public function setIsStatsSystemEnabled( $infEnabled = true ) {
		return $this->updateStatsSystemOptions(
			array(
				'enabled' => $infEnabled,
				'do_page_stats_daily' => $infEnabled,
				'do_page_stats_monthly' => $infEnabled
			)
		);
	}

	/**
	 * @param bool $infEnabled
	 * @return bool
	 */
	public function setIsEnabledDailyStats( $infEnabled = true ) {
		return $this->updateStatsSystemOptions(
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
		return $this->updateStatsSystemOptions(
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
			'do_page_stats_monthly'		=> false
		);
		$this->aOptions = array_merge( $aMinimumDefaults, $this->aOptions );
	}

	/**
	 * @return ICWP_Processor_DailyStats_CP
	 */
	protected function getDailyStatsProcessor() {
		$this->includeProcessors();
		$oStats = new ICWP_Processor_DailyStats_CP( self::Prefix );
		$oStats->setOptions( $this->getStatsSystemOptions() );
		return $oStats;
	}

	/**
	 * @return ICWP_Processor_MonthlyStats_CP
	 */
	protected function getMonthlyStatsProcessor() {
		$this->includeProcessors();
		$oStats = new ICWP_Processor_MonthlyStats_CP( self::Prefix );
		$oStats->setOptions( $this->getStatsSystemOptions() );
		return $oStats;
	}
}

return new ICWP_Stats();