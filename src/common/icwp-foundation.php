<?php

if ( !class_exists( 'ICWP_APP_Foundation', false ) ) :

	class ICWP_APP_Foundation {

		/**
		 * @var ICWP_APP_DataProcessor
		 */
		private static $oDp;
		/**
		 * @var ICWP_APP_WpFilesystem
		 */
		private static $oFs;
		/**
		 * @var ICWP_APP_WpFilesystem
		 */
		private static $oWp;
		/**
		 * @var ICWP_APP_YamlProcessor
		 */
		private static $oYaml;

		/**
		 * @return ICWP_APP_DataProcessor
		 */
		static public function loadDataProcessor() {
			if ( !isset( self::$oDp ) ) {
				require_once( dirname(__FILE__).ICWP_DS.'icwp-data.php' );
				self::$oDp = ICWP_APP_DataProcessor::GetInstance();
			}
			return self::$oDp;
		}

		/**
		 * @return ICWP_APP_WpFilesystem
		 */
		static public function loadFileSystemProcessor() {
			if ( !isset( self::$oFs ) ) {
				require_once( dirname(__FILE__).ICWP_DS.'icwp-wpfilesystem.php' );
				self::$oFs = ICWP_APP_WpFilesystem::GetInstance();
			}
			return self::$oFs;
		}

		/**
		 * @return ICWP_APP_WpFunctions
		 */
		static public function loadWpFunctionsProcessor() {
			if ( !isset( self::$oWp ) ) {
				require_once( dirname(__FILE__).ICWP_DS.'icwp-wpfunctions.php' );
				self::$oWp = ICWP_APP_WpFunctions::GetInstance();
			}
			return self::$oWp;
		}

		/**
		 * @return ICWP_APP_WpDb
		 */
		static public function loadDbProcessor() {
			return self::loadWpFunctionsProcessor()->loadDbProcessor();
		}

		/**
		 * @return ICWP_APP_YamlProcessor
		 */
		static public function loadYamlProcessor() {
			if ( !isset( self::$oYaml ) ) {
				require_once( dirname(__FILE__).ICWP_DS.'icwp-yaml.php' );
				self::$oYaml = ICWP_APP_YamlProcessor::GetInstance();
			}
			return self::$oYaml;
		}

		/**
		 * @return ICWP_Stats_APP
		 */
		public function loadStatsProcessor() {
			require_once( dirname(__FILE__).ICWP_DS.'icwp-stats.php' );
		}
	}

endif;
