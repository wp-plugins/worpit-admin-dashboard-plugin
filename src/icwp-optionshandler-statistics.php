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

require_once( dirname(__FILE__).ICWP_DS.'icwp-optionshandler-base.php' );

if ( !class_exists('ICWP_APP_FeatureHandler_Statistics_V1') ):

	class ICWP_APP_FeatureHandler_Statistics_V1 extends ICWP_APP_FeatureHandler_Base {

		/**
		 * @var ICWP_APP_Processor_Statistics
		 */
		protected $oFeatureProcessor;

		/**
		 * @return ICWP_APP_Processor_Statistics|null
		 */
		protected function loadFeatureProcessor() {
			if ( !isset( $this->oFeatureProcessor ) ) {
				require_once( $this->getController()->getPath_SourceFile( sprintf( 'icwp-processor-%s.php', $this->getFeatureSlug() ) ) );
				$this->oFeatureProcessor = new ICWP_APP_Processor_Statistics( $this );
			}
			return $this->oFeatureProcessor;
		}

		/**
		 * @return array
		 */
		public function retrieveDailyStats() {
			return $this->loadFeatureProcessor()->getDailyTotals();
		}

		/**
		 * @return array
		 */
		public function retrieveMonthlyStats() {
			return $this->loadFeatureProcessor()->getMonthlyTotals();
		}

		/**
		 * @return string
		 */
		public function getStatisticsTableName() {
			return $this->doPluginPrefix( $this->getOpt( 'statistics_table_name' ), '_' );
		}

		public function doPrePluginOptionsSave() {
			// Migrate from old system
			$aOldOptions = $this->loadWpFunctionsProcessor()->getOption( 'icwp_stats_system_options' );
			if ( !empty( $aOldOptions ) && is_array( $aOldOptions ) ) {
				if ( isset( $aOldOptions['enabled'] ) && $aOldOptions['enabled'] ) {
					$this->setIsMainFeatureEnabled( true );
				}
				$this->setOpt( 'enable_daily_statistics', ( isset( $aOldOptions['do_page_stats_daily'] ) && $aOldOptions['do_page_stats_daily'] ) ? 'Y' : 'N' );
				$this->setOpt( 'enable_monthly_statistics', ( isset( $aOldOptions['do_page_stats_monthly'] ) && $aOldOptions['do_page_stats_monthly'] ) ? 'Y' : 'N' );
				$this->setOpt( 'ignore_logged_in_user', ( isset( $aOldOptions['ignore_logged_in_user'] ) && $aOldOptions['ignore_logged_in_user'] ) ? 'Y' : 'N' );
				$this->setOpt( 'ignore_from_user_level', isset( $aOldOptions['ignore_from_user_level'] ) ? $aOldOptions['ignore_from_user_level']  : 11 );

				$oDb = $this->loadDbProcessor();

				$sDailyStatsTable = $oDb->getPrefix() . 'icwp_dailystats';
				if ( $oDb->getIfTableExists( $sDailyStatsTable ) ) {
					$oDb->doDropTable( $sDailyStatsTable );
				}

				$aMonthlyStatsTable = $oDb->getPrefix() . 'icwp_monthlystats';
				if ( $oDb->getIfTableExists( $aMonthlyStatsTable ) ) {
					$oDb->doDropTable( $aMonthlyStatsTable );
				}
				$this->loadWpFunctionsProcessor()->deleteOption( 'icwp_stats_system_options' );

				/**
				if ( $oDb->getIfTableExists( $sNewTable ) ) {

					$sBaseQuery = "INSERT INTO %s (page_id, uri, day_id, month_id, year_id, count_total, deleted_at)
						SELECT page_id, uri, day_id, month_id, year_id, count_total, deleted_at FROM %s
					";

					$sDailyStatsTable = $oDb->getPrefix() . 'icwp_dailystats';
					if ( $oDb->getIfTableExists( $sDailyStatsTable ) ) {
						$sDailyQuery = sprintf( $sBaseQuery, $sNewTable, $sDailyStatsTable );
						$oDb->doSql( $sDailyQuery );
						$oDb->doDropTable( $sDailyStatsTable );
					}

					$aMonthlyStatsTable = $oDb->getPrefix() . 'icwp_monthlystats';
					if ( $oDb->getIfTableExists( $aMonthlyStatsTable ) ) {
						$sMonthlyQuery = sprintf( $sBaseQuery, $sNewTable, $aMonthlyStatsTable );
						$oDb->doSql( $sMonthlyQuery );
						$oDb->doDropTable( $aMonthlyStatsTable );
					}

					// now we delete so we don't repeat this.
					$this->loadWpFunctionsProcessor()->deleteOption( 'icwp_stats_system_options' );
				}
				 */
			}
		}
	}

endif;

class ICWP_APP_FeatureHandler_Statistics extends ICWP_APP_FeatureHandler_Statistics_V1 { }