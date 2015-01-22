<?php

if ( class_exists( 'ICWP_APP_Foundation', false ) ) {
	return;
}

class ICWP_APP_Foundation {

	/**
	 * @return ICWP_APP_DataProcessor
	 */
	static public function loadDataProcessor() {
		if ( ! class_exists( 'ICWP_APP_DataProcessor', false ) ) {
			require_once( dirname(__FILE__).ICWP_DS.'icwp-data.php' );
		}
		return ICWP_APP_DataProcessor::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpFilesystem
	 */
	static public function loadFileSystemProcessor() {
		if ( ! class_exists( 'ICWP_APP_WpFilesystem', false ) ) {
			require_once( dirname(__FILE__).ICWP_DS.'icwp-wpfilesystem.php' );
		}
		return ICWP_APP_WpFilesystem::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpFunctions
	 */
	static public function loadWpFunctionsProcessor() {
		if ( ! class_exists( 'ICWP_APP_WpFunctions', false ) ) {
			require_once( dirname(__FILE__).ICWP_DS.'icwp-wpfunctions.php' );
		}
		return ICWP_APP_WpFunctions::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpDb
	 */
	static public function loadDbProcessor() {
		if ( ! class_exists( 'ICWP_APP_WpDb', false ) ) {
			require_once( dirname(__FILE__).ICWP_DS.'icwp-wpdb.php' );
		}
		return ICWP_APP_WpDb::GetInstance();
	}

	/**
	 * @return ICWP_APP_YamlProcessor
	 */
	static public function loadYamlProcessor() {
		if ( ! class_exists( 'ICWP_APP_YamlProcessor', false ) ) {
			require_once( dirname(__FILE__).ICWP_DS.'icwp-yaml.php' );
		}
		return ICWP_APP_YamlProcessor::GetInstance();
	}

	/**
	 * @return ICWP_Stats_APP
	 */
	public function loadStatsProcessor() {
		require_once( dirname(__FILE__).ICWP_DS.'icwp-wpsf-stats.php' );
	}
}