<?php

if ( class_exists( 'ICWP_APP_Foundation', false ) ) {
	return;
}

class ICWP_APP_Foundation {

	/**
	 * @return ICWP_APP_DataProcessor
	 */
	static public function loadDataProcessor() {
		require_once( 'icwp-data-processor.php' );
		return ICWP_APP_DataProcessor::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpFilesystem
	 */
	static public function loadFileSystemProcessor() {
		require_once( 'icwp-wpfilesystem.php' );
		return ICWP_APP_WpFilesystem::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpFunctions
	 */
	static public function loadWpFunctionsProcessor() {
		require_once( 'icwp-wpfunctions.php' );
		return ICWP_APP_WpFunctions::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpDb
	 */
	static public function loadDbProcessor() {
		require_once( 'icwp-wpdb.php' );
		return ICWP_APP_WpDb::GetInstance();
	}

	/**
	 * @return ICWP_APP_YamlProcessor
	 */
	static public function loadYamlProcessor() {
		require_once( 'icwp-processor-yaml.php' );
		return ICWP_APP_YamlProcessor::GetInstance();
	}

	/**
	 * @return ICWP_Stats_APP
	 */
	public function loadStatsProcessor() {
		require_once( 'icwp-wpsf-stats.php' );
	}
}