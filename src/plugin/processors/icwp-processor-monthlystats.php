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

require_once(dirname(__FILE__) . '/base/icwp-processor-basestats.php');

if ( !class_exists('ICWP_Processor_MonthlyStats_CP') ):

class ICWP_Processor_MonthlyStats_CP extends ICWP_Processor_BaseStats_CP {

	const Slug = 'monthlystats';

	/**
	 * Set this to true if the stat for this particular load is registered (prevent duplicates)
	 *
	 * @var bool
	 */
	protected static $fStatRegistered = false;

	public function __construct( $insOptionPrefix = '' ) {
		parent::__construct( $this->constructStorageKey( $insOptionPrefix, self::Slug ), self::Slug );
		$this->reset();
	}

	/**
	 * @return boolean
	 */
	public function run() {
		parent::run();
		if ( $this->getCanRun() ) {
			$this->doPageStats();
			self::$fStatRegistered = true;
		}
	}

	/**
	 * @return boolean
	 */
	protected function getCanRun() {
		if ( !$this->getOption( 'do_page_stats_monthly', false ) ){
			return false;
		}
		if ( self::$fStatRegistered ) {
			return false;
		}
		if ( $this->getDoIgnoreCurrentUser() ) {
			return false;
		}
		return true;
	}

	/**
	 * @return array
	 */
	public function getMonthlyTotals() {
		$sBaseQuery = "
			SELECT `month_id`, `year_id`, SUM(`count_total`) as monthly_total
			FROM
				`%s`
			GROUP BY `month_id`, `year_id`
			ORDER BY `year_id` ASC, `month_id` ASC, `day_id` ASC
		";
		$sQuery = sprintf( $sBaseQuery,
			$this->m_sTableName
		);
		return $this->selectCustomFromTable( $sQuery );
	}

	/**
	 *
	 */
	protected function setTodaysDate() {
		parent::setTodaysDate();
		$this->m_nDay = 0; //because it's monthly stats we don't care for the day.
	}

	/**
	 * This is hooked into a cron in the base class and overrides the parent method.
	 * 
	 * It'll delete everything older than 24hrs.
	 */
	public function cleanupDatabase() {
//		$nTimeStamp = time() - DAY_IN_SECONDS;
//		$this->deleteAllRowsOlderThan( $nTimeStamp );
	}
}

endif;