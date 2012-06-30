<?php

class Controllers_Transport extends Controllers_Base {

	public function index() {
		$this->success( array() );
	}
	
	/**
	 * As a means of debugging and helping Worpit function across many more environments
	 * we may from time to time do a query on the following information.
	 *
	 * This functionality is protected by our authentication and handshaking system.
	 * No other domain than worpitapp.com can make such requests
	 */
	public function query() {
		worpitAuthenticate( $_POST );
		worpitVerifyPackageRequest( $_POST );
		
		$aData = array(
			'_SERVER'				=> $_SERVER,
			'_ENV'					=> $_ENV,
			'ini_get_all'			=> @ini_get_all(),
			'extensions_loaded'		=> @get_loaded_extensions(),
			'php_version'			=> @phpversion(),
			'has_exec'				=> worpitFunctionExists( 'exec' )? 1: 0
		);
		
		$this->success( $aData );
	}
	
	/**
	 * Core package execution.
	 */
	public function execute() {
		worpitAuthenticate( $_POST );
		worpitVerifyPackageRequest( $_POST );

		$sTempDir = createTempDir( dirname(__FILE__), 'pkg_' );
	
		if ( $sTempDir === false ) {
			$nVal = removeTempDir( $sTempDir, &$this->m_aOutput );
			$this->fail( 'Failed to create temporary directory.' );
		}
		
		foreach ( $_FILES as $sKey => $aUpload ) {
			if ( $aUpload['error'] == UPLOAD_ERR_OK ) {
				move_uploaded_file( $aUpload['tmp_name'], $sTempDir.'/'.$aUpload['name'] );
				chmod( $sTempDir.'/'.$aUpload['name'], 0644 );
			}
		}
		
		/**
		 * @since 1.0.3
		 */
		$_POST['use_serialize'] = '1';
		
		/**
		 * @since 1.0.4
		 */
		$_POST['prevent_auto_run'] = '1';

		$sWritableRequestData = "<?php \n";
		foreach ( $_POST as $sKey => $sValue ) {
			if ( in_array( $sKey, array( 'key', 'pin', 'action' ) ) ) {
				continue;
			}
			$sWritableRequestData .= "define( 'REQUEST_".strtoupper( $sKey )."', \"".$sValue."\" );"."\n";
		}

		$sPackageConstants = $this->getPackageConstants();
		foreach ( $sPackageConstants as $sKey => $sValue ) {
			$sWritableRequestData .= "define( 'OPTION_".strtoupper( $sKey )."', \"".$sValue."\" );"."\n";
		}

		if ( !file_put_contents( $sTempDir.'/request_data.php', $sWritableRequestData."\n" ) ) {
			$nVal = removeTempDir( $sTempDir, &$aRemoveOutput );
			$this->logMerge( $aRemoveOutput );
			$this->fail( 'Failed to create dynamic request data config file.' );
		}
		else {
			chmod( $sTempDir.'/request_data.php', 0644 );
		}
		
		include_once( $sTempDir.DS.'installer.php' );
		$oInstall = new Installer();
		$aInstallerResponse = $oInstall->run();
		
		$this->log( $aInstallerResponse );

		$aRemoveOutput = array();
		$nVal = removeTempDir( $sTempDir, &$aRemoveOutput );
		$this->logMerge( $aRemoveOutput );
		
		if ( $aInstallerResponse['success'] != true ) {
			$this->fail( 'Failed to execute target: '.($aInstallerResponse['success']? 1: 0) );
		}

		$aData = isset( $aInstallerResponse['data'] )? $aInstallerResponse['data']: '';
		$this->success( $aData );
	}
	
	/**
	 * Worpit's autologin secure implementation using temporary tokens only configurable by the
	 * Worpit package delivery system.
	 */
	public function login() {
		global $wp_version;
		
		$oWpHelper = new Helper_WordPress();

		if ( !isset( $_GET['token'] ) ) {
			//header( "Location: $location", true, $status);
			die( 'WorpitError: Invalid request' );
		}
		
		if ( $_GET['token'] != $oWpHelper->getTransient( 'worpit_login_token' ) ) {
			die( 'WorpitError: Invalid token' );
		}
		
		if ( version_compare( $wp_version, '3.1', '>=' ) && !isset( $_POST['username'] ) ) {
			$aUserRecords = get_users( 'role=administrator' );
			if ( count( $aUserRecords ) == 0 ) {
				$this->fail( 'Failed to find an administrator' );
			}
			
			$oUser = $aUserRecords[0];
			/*
			foreach ( $aUserRecords as $aUser ) {
				//echo '<li>' . $user->user_email . '</li>';
				break;
			}
			*/
		}
		else {
			if ( version_compare( $wp_version, '3.2.2', '<=' ) ) {
				$oUser = get_userdatabylogin( $_POST['username'] );
			}
			else {
				//get_userdata
				$oUser = get_user_by( 'login', $_POST['username'] );
			}
		}
				
		wp_set_current_user( $oUser->ID );
		
		// TODO: Handle multisite
		if ( !defined( 'COOKIEHASH' ) ) {
			wp_cookie_constants();
		}
		wp_set_auth_cookie( $oUser->ID );
		
		$oWpHelper->deleteTransient( 'worpit_login_token' );
		
		wp_safe_redirect( admin_url( '' ) );
		
		// for this session disable the send_frame_options_header
		// set some session variables so WP thinks we're logged in.
		// which means, if you have the key, the pin, and the email, then you can login...still fairly secure really.
		
		// use a cookie that applies this all the time?
		//remove_action( 'login_init', 'send_frame_options_header', 10, 0 );
		//remove_action( 'admin_init', 'send_frame_options_header', 10, 0 );
	}
}