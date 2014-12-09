<?php
/**
 * Copyright (c) 2014 iControlWP <support@icontrolwp.com>
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

require_once( dirname(__FILE__).ICWP_DS.'icwp-processor-basedb.php' );

if ( !class_exists('ICWP_APP_Processor_Statistics_V1') ):

	class ICWP_APP_Processor_Statistics_V1 extends ICWP_APP_BaseDbProcessor {

		/**
		 * @var integer
		 */
		protected $nCurrentPageId;
		/**
		 * @var integer
		 */
		protected $nDay;

		/**
		 * @var integer
		 */
		protected $nMonth;

		/**
		 * @var integer
		 */
		protected $nYear;

		/**
		 * @var string
		 */
		protected $sCurrentPageUri;

		/**
		 * @var string
		 */
		protected $sStatsMode = 'daily';

		/**
		 * Set this to true if the stat for this particular load is registered (prevent duplicates)
		 *
		 * @var bool
		 */
		protected static $fStatRegistered = false;

		/**
		 * @param ICWP_APP_FeatureHandler_Base $oFeatureOptions
		 */
		public function __construct( $oFeatureOptions ) {
			parent::__construct( $oFeatureOptions, $oFeatureOptions->getStatisticsTableName() );
		}

		/**
		 * @return bool
		 */
		public function run() {
			if ( self::$fStatRegistered || strlen( $this->getPageUri() ) == 0 || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
				return true;
			}

			add_action( $this->getController()->doPluginPrefix( 'plugin_shutdown' ), array( $this, 'doStats' ) );
			self::$fStatRegistered = true;
			return true;
		}

		public function doStats() {

			if ( $this->getPageId() < 0 || $this->getIfIgnoreUser() ) {
				return;
			}

			if ( $this->getIsOption( 'enable_daily_statistics', 'Y' ) ) {
				$this->setStatsMode( 'daily' );
				$this->doPageStats();
			}
			if ( $this->getIsOption( 'enable_monthly_statistics', 'Y' ) ) {
				$this->setStatsMode( 'monthly' );
				$this->doPageStats();
			}
		}

		/**
		 * @return bool
		 */
		protected function getIfIgnoreUser() {
			$fIgnoreLoggedInUser = $this->getIsOption( 'ignore_logged_in_user', 'Y' );
			$nCurrentUserLevel = $this->loadWpFunctionsProcessor()->getCurrentUserLevel();
			if ( $fIgnoreLoggedInUser && $nCurrentUserLevel >= 0 ) { // logged in
				$nIgnoreFromUserLevel = $this->getOption( 'ignore_from_user_level', 11 );
				if ( $nCurrentUserLevel >= $nIgnoreFromUserLevel ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * @return string
		 */
		protected function getStatsMode() {
			return $this->sStatsMode;
		}

		/**
		 * @param $sMode
		 */
		protected function setStatsMode( $sMode ) {
			$this->sStatsMode = $sMode;
		}

		/**
		 * @param int $nLimit
		 *
		 * @return array|bool
		 */
		public function getDailyTotals( $nLimit = 31 ) {
				$sBaseQuery = "
				SELECT `day_id`, `month_id`, SUM(`count_total`) as day_total
				FROM
					`%s`
				WHERE
					`day_id` != '0'
				GROUP BY `year_id`, `month_id`, `day_id`
				ORDER BY `year_id` DESC, `month_id` DESC, `day_id` DESC
				LIMIT %s
			";
			$sQuery = sprintf(
				$sBaseQuery,
				$this->getTableName(),
				$nLimit
			);
			return $this->selectCustom( $sQuery );
		}

		/**
		 * @return array
		 */
		public function getMonthlyTotals() {
			$sBaseQuery = "
				SELECT `month_id`, `year_id`, SUM(`count_total`) as monthly_total
				FROM
					`%s`
				WHERE
					`day_id` = '0'
				GROUP BY `year_id`, `month_id`, `day_id`
				ORDER BY `year_id` DESC, `month_id` DESC, `day_id` DESC
			";
			$sQuery = sprintf(
				$sBaseQuery,
				$this->getTableName()
			);
			return $this->selectCustom( $sQuery );
		}

		/**
		 * @return void
		 */
		protected function doPageStats() {

			//Does page entry already exist for today?
			$aCurrentStats = $this->getStatsCurrentPageToday();
			if ( count( $aCurrentStats ) == 1 ) {
				//increment counter
				$mResult = $this->incrementTotalForCurrentPageToday();
			}
			else {
				//Add to DB
				$mResult = $this->addNewStatForCurrentPageToday();
			}
			return $mResult;
		}

		/**
		 * @return array|bool
		 */
		protected function getStatsCurrentPageToday() {
			return $this->getStatsForPageOnDay( $this->getPageId(), $this->getDay(), $this->getMonth(), $this->getYear() );
		}

		/**
		 * @param int $nPageId
		 * @param int $nDay
		 * @param int $nMonth
		 * @param int $nYear
		 *
		 * @return array|bool
		 */
		protected function getStatsForPageOnDay( $nPageId, $nDay = 0, $nMonth = 0, $nYear = 0 ) {

			$sBaseQuery = "
				SELECT *
					FROM `%s`
				WHERE
					`page_id`		= '%s'
					AND `day_id`		= '%s'
					AND `month_id`		= '%s'
					AND `year_id`		= '%s'
					AND `deleted_at`	= '0'
			";
			$sQuery = sprintf( $sBaseQuery,
				$this->getTableName(),
				$nPageId,
				$nDay,
				$nMonth,
				$nYear
			);
			return $this->selectCustom( $sQuery );
		}

		/**
		 * @return bool|int
		 */
		protected function incrementTotalForCurrentPageToday() {
			return $this->incrementTotalForPageToday( $this->getPageId() );
		}

		/**
		 * @param int $nPageId
		 *
		 * @return bool|int
		 */
		protected function incrementTotalForPageToday( $nPageId ) {

			$sQuery = "
				UPDATE `%s`
					SET `count_total`	= `count_total` + 1
				WHERE
					`page_id`		= '%s'
					AND `day_id`	= '%s'
					AND `month_id`	= '%s'
					AND `year_id`	= '%s'
			";
			$sQuery = sprintf( $sQuery,
				$this->getTableName(),
				$nPageId,
				$this->getDay(),
				$this->getMonth(),
				$this->getYear()
			);
			return $this->doSql( $sQuery );
		}

		/**
		 * Creates a new stat entry in the table for the current page for TODAY.
		 *
		 * @return mixed
		 */
		public function addNewStatForCurrentPageToday() {
			return $this->addNewStatForCurrentPage( $this->getDay(), $this->getMonth(), $this->getYear() );
		}

		/**
		 * Creates a new stat entry in the table for the current page on the specified day.
		 *
		 * @param $nDay
		 * @param $nMonth
		 * @param $nYear
		 *
		 * @return mixed
		 */
		protected function addNewStatForCurrentPage( $nDay = 0, $nMonth = 0, $nYear = 0 ) {
			return $this->addNewStatForPage(
				$this->getPageId(),
				$this->getPageUri(),
				$nDay,
				$nMonth,
				$nYear
			);
		}

		/**
		 * @param int $nPageId
		 * @param string $sUri
		 * @param int $nDay
		 * @param int $nMonth
		 * @param int $nYear
		 *
		 * @return bool|int
		 */
		protected function addNewStatForPage( $nPageId, $sUri, $nDay = 0, $nMonth = 0, $nYear = 0 ) {

			$aData = array();
			$aData[ 'page_id' ]		= $nPageId;
			$aData[ 'uri' ]			= $sUri;
			$aData[ 'day_id' ]		= $nDay;
			$aData[ 'month_id' ]	= $nMonth;
			$aData[ 'year_id' ]		= $nYear;
			$aData[ 'count_total' ]	= 1;
			$aData[ 'created_at' ]	= $this->time();

			$mResult = $this->insertData( $aData );
			return $mResult;
		}

		/**
		 * @return int
		 */
		protected function getDay() {
			if ( $this->getStatsMode() == 'monthly' ) {
				return 0;
			}
			if ( !isset( $this->nDay ) ) {
				$this->setDateParts();
			}
			return $this->nDay;
		}

		/**
		 * @return int
		 */
		protected function getMonth() {
			if ( !isset( $this->nMonth ) ) {
				$this->setDateParts();
			}
			return $this->nMonth;
		}

		/**
		 * @return int
		 */
		protected function getYear() {
			if ( !isset( $this->nYear ) ) {
				$this->setDateParts();
			}
			return $this->nYear;
		}

		/**
		 * @return string
		 */
		protected function getPageUri() {
			if ( !isset( $this->sCurrentPageUri ) ) {
				$aParts = explode( '?', $this->loadDataProcessor()->FetchServer( 'REQUEST_URI', '' ) );
				$this->sCurrentPageUri = $aParts[0];
			}
			return $this->sCurrentPageUri;
		}

		/**
		 * @return int
		 */
		protected function getPageId() {
			if ( !isset( $this->nCurrentPageId ) ) {
				$this->nCurrentPageId = $this->loadWpFunctionsProcessor()->getCurrentPostId();
			}
			return $this->nCurrentPageId;
		}

		/**
		 *
		 */
		private function setDateParts() {
			$aParts = explode( ':', date( 'j:n:Y', strtotime('today midnight') - get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS ) );
			$this->nDay = $aParts[0];
			$this->nMonth = $aParts[1];
			$this->nYear = $aParts[2];
		}

		/**
		 * @return string
		 */
		protected function getCreateTableSql() {
			$sSql = "CREATE TABLE IF NOT EXISTS `%s` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`page_id` INT(11) NOT NULL DEFAULT '0',
				`uri` varchar(255) NOT NULL DEFAULT '',
				`day_id` TINYINT(2) NOT NULL DEFAULT '0',
				`month_id` TINYINT(2) NOT NULL DEFAULT '0',
				`year_id` SMALLINT(4) NOT NULL DEFAULT '0',
				`count_total` INT(15) NOT NULL DEFAULT '1',
				`created_at` INT(15) NOT NULL DEFAULT '0',
				`deleted_at` INT(15) NOT NULL DEFAULT '0',
	            PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
			return sprintf( $sSql, $this->getTableName() );
		}

		protected function getTableColumnsByDefinition() {
			return $this->getOption( 'statistics_table_columns' );
		}

		/**
		 * This is hooked into a cron in the base class and overrides the parent method.
		 *
		 * It'll delete everything older than 24hrs.
		 */
		public function cleanupDatabase() {
			if ( !$this->getTableExists() ) {
				return;
			}

			$sQuery = "
				DELETE from `%s`
				WHERE
					`day_id`			!= '0'
					AND `created_at`	< '%s'
			";
			$sQuery = sprintf( $sQuery,
				$this->getTableName(),
				( $this->time() - 31 * DAY_IN_SECONDS )
			);
			$this->doSql( $sQuery );
		}

		/**
		 * @param int $nMonth
		 *
		 * @return int
		 */
		protected function getPreviousMonthId( $nMonth = 0 ) {
			$nCompareMonth = ( $nMonth < 1 || $nMonth > 12 )? $this->getMonth() : $nMonth;
			$nPrev = $nCompareMonth - 1;
			return ($nPrev == 0)? 12 : $nPrev;
		}
	}

endif;

if ( !class_exists('ICWP_APP_Processor_Statistics') ):
	class ICWP_APP_Processor_Statistics extends ICWP_APP_Processor_Statistics_V1 { }
endif;