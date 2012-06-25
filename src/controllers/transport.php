<?php

class Controllers_Transport extends Controllers_Base {

	public function index() {

	}
	
	/**
	 * As a means of debugging and helping Worpit function across many more environments
	 * we may from time to time do a query on the following information.
	 *
	 * This functionality is protected by our authentication and handshaking system.
	 * No other domain than worpitapp.com can make such requests
	 */
	public function query() {
		worpitAuthenticate( $_REQUEST );
		
		worpitVerifyPackageRequest( $_REQUEST );
		
		$aData = array(
			'_SERVER'				=> $_SERVER,
			'_ENV'					=> $_ENV,
			'ini_get_all'			=> ini_get_all(),
			'extensions_loaded'		=> get_loaded_extensions(),
			'php_version'			=> phpversion(),
			'has_exec'				=> function_exists( 'exec' )? 1: 0
		);
		
		$this->success( $aData );
	}
	
	/**
	 * Core package execution.
	 */
	public function execute() {
		worpitAuthenticate( $_REQUEST );
		
		worpitVerifyPackageRequest( $_REQUEST );

		$sTempDir = createTempDir( dirname(__FILE__), 'pkg_' );
	
		if ( $sTempDir === false ) {
			$nVal = removeTempDir( $sTempDir, &$this->m_aOutput );
			$this->fail( 'Failed to create temporary directory.' );
		}
		
		foreach ( $_FILES as $sKey => $aUpload ) {
			if ( $aUpload['error'] == UPLOAD_ERR_OK ) {
				move_uploaded_file( $aUpload['tmp_name'], $sTempDir.'/'.$aUpload['name'] );
				chmod( $sTempDir.'/'.$aUpload['name'], 0755 );
			}
		}

		$sWritableRequestData = "<?php \n";
		foreach ( $_REQUEST as $sKey => $sValue ) {
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
			chmod( $sTempDir.'/request_data.php', 0755 );
		}

		// this is one way, if it fails we may need to do a web call!
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			//$sCmd = 'D:/Applications/xampp_1.7.3/php/php.exe --no-header --no-chdir '.$sTempDir.DS.'installer.php';
			$sCmd = 'php.exe --no-header --no-chdir '.$sTempDir.DS.'installer.php';
		}
		else {
			$sCmd = 'php-cli --no-header --no-chdir '.$sTempDir.DS.'installer.php';
		}
		
		$fHasExec = function_exists( 'exec' );
		$fTriedExec = false;
		$nReturn = null;
		$sInstallerResponse = null;
		
		if ( $fHasExec && !defined( 'REQUEST_USE_ALTERNATIVE' ) ) {
			exec( $sCmd, &$aInstallerOutput, &$nReturn );
			$this->m_aOutput = array_merge( $this->m_aOutput, $aInstallerOutput );
			
			$sInstallerResponse = trim( implode( '', $aInstallerOutput ) );
			$aInstallerResponse = @json_decode( $sInstallerResponse, true );
			
			$fTriedExec = true;
		}
		
		if ( ( $fTriedExec && $nReturn !== 0 ) || !$fHasExec || defined( 'REQUEST_USE_ALTERNATIVE' ) ) {
			// @see http://codex.wordpress.org/Function_Reference/plugins_url
			//$sInstallerUrl = OPTION_PLUGIN_URL;
			
			$sInstallerUrl = plugins_url( 'controllers/' , dirname(__FILE__) );
			$sInstallerUrl = str_replace( 'https://', 'http://', $sInstallerUrl );
			
			$aTempParts = explode( 'src/controllers/', $sTempDir, 2 );
			if ( count( $aTempParts ) < 2 ) {
				$this->log( $sInstallerResponse );
				$this->logMerge( array( $sInstallerResponse, $nReturn, $sTempDir, $sInstallerUrl ) );
				$this->fail( 'Failed to construct installer request url from sTempDir ('.$sTempDir.')', $nReturn );
			}
			
			$sInstallerUrl .= $aTempParts[1].'/installer.php';
			
			$sInstallerResponse = @file_get_contents( $sInstallerUrl );
			$aInstallerResponse = @json_decode( $sInstallerResponse, true );
			
			$this->log( $sInstallerResponse );
			$nReturn = 0;
		}
		
		$aRemoveOutput = array();
		$nVal = removeTempDir( $sTempDir, &$aRemoveOutput );
		$this->logMerge( $aRemoveOutput );
		
		if ( $nReturn !== 0 || $aInstallerResponse['success'] !== true ) {
			$this->fail( 'Failed to execute target: '.$aInstallerResponse['success'], $nReturn );
		}

		$aData = isset( $aInstallerResponse['data'] )? $aInstallerResponse['data']: array();
		$aData['output'] = isset( $aInstallerResponse['output'] )? $aInstallerResponse['output']: array();
		$this->success( $aData );
	}
	
	/**
	 * Worpit's autologin secure implementation using temporary tokens only configurable by the
	 * Worpit package delivery system.
	 */
	public function login() {
		global $wp_version;
		
		$oWpHelper = new Helper_WordPress();

		if ( !isset( $_REQUEST['token'] ) ) {
			//header( "Location: $location", true, $status);
			die( 'error' );
		}
		
		if ( $_REQUEST['token'] != $oWpHelper->getTransient( 'worpit_login_token' ) ) {
			die( 'error: invalid token' );
		}
		
		if ( version_compare( $wp_version, '3.1', '>=' ) && !isset( $_REQUEST['username'] ) ) {
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
				$oUser = get_userdatabylogin( $_REQUEST['username'] );
			}
			else {
				//get_userdata
				$oUser = get_user_by( 'login', $_REQUEST['username'] );
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