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

require_once(dirname(__FILE__) . '/icwp-processor-basedb.php');

if ( !class_exists('ICWP_Processor_BaseStats_CP') ):

class ICWP_Processor_BaseStats_CP extends ICWP_Processor_BaseDb_CP {

	/**
	 * @var integer
	 */
	protected $m_nCurrentPageId;
	/**
	 * @var integer
	 */
	protected $m_nDay;
	/**
	 * @var integer
	 */
	protected $m_nMonth;
	/**
	 * @var integer
	 */
	protected $m_nYear;
	/**
	 * @var integer
	 */
	protected $m_sCurrentPageUri;

	/**
	 */
	public function run() {
		parent::run();
	}

	/**
	 * @return void
	 */
	public function doPageStats() {

		if ( defined('DOING_CRON') && DOING_CRON ) {
			return;
		}

		$this->prepStatData();

		if ( empty( $this->m_sCurrentPageUri ) || $this->m_nCurrentPageId <= 0 ) {
			return false;
		}

		//Does page entry already exist for today?
		$aCurrentStats = $this->getStatsCurrentPageToday();
		if ( count( $aCurrentStats ) == 1 ) {
			//increment counter
			$this->incrementTotalForCurrentPageToday();
		}
		else {
			//Add to DB
			$this->addNewStatForCurrentPageToday();
		}
	}

	/**
	 *
	 */
	protected function prepStatData() {
		$this->setTodaysDate();
		$this->setPageId();
		$this->setPageUri();
	}

	/**
	 * @return int
	 */
	protected function setPageId() {
		global $post;
		if ( empty( $post->ID ) ) {
			$this->m_nCurrentPageId = -1;
		}
		else {
			$this->m_nCurrentPageId = $post->ID;
		}
		return $this->m_nCurrentPageId;
	}

	/**
	 *
	 */
	protected function setTodaysDate() {
		$aParts = explode( ':', date( 'j:n:Y', strtotime('today midnight') - get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS ) );
		$this->m_nDay = $aParts[0];
		$this->m_nMonth = $aParts[1];
		$this->m_nYear = $aParts[2];
	}

	/**
	 *
	 */
	protected function setPageUri() {
		if ( !isset( $_SERVER['REQUEST_URI'] ) || empty( $_SERVER['REQUEST_URI'] ) ) {
			$this->m_sCurrentPageUri = false;
			return false;
		}
		$aParts = explode( '?', $_SERVER['REQUEST_URI'] );
		$this->m_sCurrentPageUri = $aParts[0];
		return $this->m_sCurrentPageUri;
	}

	/**
	 * @return mixed
	 */
	protected function getStatsCurrentPageToday() {
		return $this->getStatsForPageOnDay( $this->m_nCurrentPageId, $this->m_nDay, $this->m_nMonth, $this->m_nYear );
	}

	/**
	 * @param $innPageId
	 * @param $innDay
	 * @param $innMonth
	 * @param $innYear
	 * @return mixed
	 */
	protected function getStatsForPageOnDay( $innPageId, $innDay = 0, $innMonth = 0, $innYear = 0 ) {

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
			$innPageId,
			$innDay,
			$innMonth,
			$innYear
		);
		return $this->selectCustomFromTable( $sQuery );
	}

	/**
	 * @return mixed
	 */
	protected function incrementTotalForCurrentPageToday() {
		return $this->incrementTotalForPageToday( $this->m_nCurrentPageId );
	}

	/**
	 * @param $innPageId
	 * @return mixed
	 */
	protected function incrementTotalForPageToday( $innPageId ) {

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
			$innPageId,
			$this->m_nDay,
			$this->m_nMonth,
			$this->m_nYear
		);
		return $this->doSql( $sQuery );
	}

	/**
	 * @return mixed
	 */
	public function addNewStatForCurrentPageToday() {
		return $this->addNewStatForCurrentPage( $this->m_nDay, $this->m_nMonth, $this->m_nYear );
	}

	/**
	 * @param $innPageId
	 * @param $innDay
	 * @param $innMonth
	 * @param $innYear
	 * @return mixed
	 */
	protected function addNewStatForCurrentPage( $innDay = 0, $innMonth = 0, $innYear = 0 ) {

		$aData = array();
		$aData[ 'page_id' ]		= $this->m_nCurrentPageId;
		$aData[ 'uri' ]			= $this->m_sCurrentPageUri;
		$aData[ 'day_id' ]		= $innDay;
		$aData[ 'month_id' ]	= $innMonth;
		$aData[ 'year_id' ]		= $innYear;
		$aData[ 'count_total' ]	= 1;

		$mResult = $this->insertIntoTable( $aData );
		return $mResult;
	}

	/**
	 * @return mixed
	 */
	public function createTable() {
		$sSqlTables = $this->getTableCreateSql();
		$mResult = $this->doSql( $sSqlTables );
		return $mResult;
	}

	/**
	 * @return string
	 */
	protected function getTableCreateSql() {
		$sSql = "CREATE TABLE IF NOT EXISTS `%s` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`page_id` int(11) NOT NULL DEFAULT '0',
			`uri` varchar(255) NOT NULL DEFAULT '',
			`day_id` TINYINT(2) NOT NULL DEFAULT '0',
			`month_id` TINYINT(2) NOT NULL DEFAULT '0',
			`year_id` SMALLINT(4) NOT NULL DEFAULT '0',
			`count_total` int(15) NOT NULL DEFAULT '1',
			`deleted_at` TINYINT(1) NOT NULL DEFAULT '0',
 			PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		return sprintf( $sSql, $this->getTableName() );
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

	/**
	 * @param int $innMonth
	 * @return int
	 */
	protected function getPreviousMonthId( $innMonth = 0 ) {
		$nCompareMonth = ( $innMonth < 1 || $innMonth > 12 )? $this->m_nMonth : $innMonth;
		$nPrev = $nCompareMonth - 1;
		return ($nPrev == 0)? 12 : $nPrev;
	}
}

endif;