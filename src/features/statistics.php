<?php
/**
 * Copyright (c) 2015 iControlWP <support@icontrolwp.com>
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

require_once( 'base.php' );

if ( !class_exists( 'ICWP_APP_FeatureHandler_Statistics_V1', false ) ):

	class ICWP_APP_FeatureHandler_Statistics_V1 extends ICWP_APP_FeatureHandler_Base {

		/**
		 * @return string
		 */
		protected function getProcessorClassName() {
			return 'ICWP_APP_Processor_Statistics';
		}

		/**
		 * @return array
		 */
		public function retrieveDailyStats() {
			/** @var ICWP_APP_Processor_Statistics $oFp */
			$oFp = $this->getProcessor();
			return $oFp->getDailyTotals();
		}

		/**
		 * @return array
		 */
		public function retrieveMonthlyStats() {
			/** @var ICWP_APP_Processor_Statistics $oFp */
			$oFp = $this->getProcessor();
			return $oFp->getMonthlyTotals();
		}

		/**
		 * @return string
		 */
		public function getStatisticsTableName() {
			return $this->doPluginPrefix( $this->getOpt( 'statistics_table_name' ), '_' );
		}

		public function doPrePluginOptionsSave() {

			$sOptionValue = $this->getIsMainFeatureEnabled() ? 'Y' : 'N';
			$this->setOpt( 'enable_daily_statistics', $sOptionValue );
			$this->setOpt( 'enable_monthly_statistics', $sOptionValue );

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

			}
		}
	}

endif;

class ICWP_APP_FeatureHandler_Statistics extends ICWP_APP_FeatureHandler_Statistics_V1 { }