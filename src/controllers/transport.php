<?php

class Worpit_Controllers_Transport extends Worpit_Controllers_Base {

	/**
	 * @return void
	 */
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
		
		$sTempDir = worpitCreateTempDir( dirname(__FILE__), '.pkg_' );
		
		$aData = array(
			'_SERVER'				=> $_SERVER,
			'_ENV'					=> $_ENV,
			'ini_get_all'			=> @ini_get_all(),
			'extensions_loaded'		=> @get_loaded_extensions(),
			'php_version'			=> @phpversion(),
			'has_exec'				=> worpitFunctionExists( 'exec' )? 1: 0,
			'can_mkdir'				=> $sTempDir !== false? 1: 0,
			'package_constants'		=> $this->getPackageConstants()
		);
		
		if ( $sTempDir !== false ) {
			worpitRemoveTempDir( $sTempDir );
		}
		
		$this->success( $aData );
	}

	/**
	 * Alternative method to executing a package.
	 * This will take priority over the execute in the near future.
	 * The reason for this is because some host do not allow uploading of .php files
	 */
	public function retrieve() {
		worpitAuthenticate( $_POST );
		worpitVerifyPackageRequest( $_POST );
		
		if ( !function_exists( 'download_url' ) ) {
			return $this->fail( 'The download_url function is not available' );
		}
		
		if ( !function_exists( 'is_wp_error' ) ) {
			return $this->fail( 'The is_wp_error function is not available' );
		}
		
		$sUrl = ICWP_RETRIEVE_URL.$_GET['package_id'].'/'.worpitGetOption( 'key' ).'/'.worpitGetOption( 'pin' );
		$sTmpFile = download_url( $sUrl );
		
		if ( is_wp_error( $sTmpFile ) ) {
			if ( !is_object( $sTmpFile ) && is_file( $sTmpFile ) ) {
				@icwpFsDeleteFile( $sTmpFile );
			}
			return $this->fail( sprintf( 'The package could not be downloaded from "%s": %s', $sUrl, is_object( $sTmpFile )? $sTmpFile->get_error_message(): '#not-an-object#' ) );
		}

		$sNewFile = dirname( __FILE__ ).WORPIT_DS.basename( $sTmpFile );
		$sFileToInclude = icwpFsMoveFile( $sTmpFile, $sNewFile )? $sNewFile : $sTmpFile;

		include_once( $sFileToInclude );
		if ( !class_exists( 'Worpit_Package_Installer', false ) ) {
			return $this->fail( 'Worpit_Package_Installer does not exist: '.$sTmpFile );
		}
		@icwpFsDeleteFile( $sFileToInclude );

		$oInstall = new Worpit_Package_Installer();
		$aInstallerResponse = $oInstall->run();
		
		$this->log( $aInstallerResponse );
		
		if ( !$aInstallerResponse['success'] ) {
			return $this->fail( 'Package failed.' );
		}
		
		$aData = isset( $aInstallerResponse['data'] )? $aInstallerResponse['data']: '';
		return $this->success( $aData );
	}
	
	/**
	 * Core package execution.
	 */
	public function execute() {
		worpitAuthenticate( $_POST );
		worpitVerifyPackageRequest( $_POST );

		/**
		 * @since 1.0.14
		 */
		$_POST['rel_package_dir'] = '';
		$_POST['abs_package_dir'] = '';
		
		$sTempDir = false;
		if ( !isset( $_POST['force_use_eval'] ) ) {
			$sTempDir = worpitCreateTempDir( dirname(__FILE__), 'pkg_' );
			$_POST['rel_package_dir'] = str_replace( dirname(__FILE__), '', $sTempDir );
			$_POST['abs_package_dir'] = $sTempDir;
		}
		
		if ( $sTempDir === false ) {
			$fAllowUploads = (bool)ini_get( 'file_uploads' );
			
			if ( $fAllowUploads ) {
				$aFileContents = array();
				foreach ( $_FILES as $sKey => $aUpload ) {
					if ( $aUpload['error'] == UPLOAD_ERR_OK ) {
						if ( !is_readable( $aUpload['tmp_name'] ) ) {
							$this->fail( 'Unable to create temp directory. Uploaded file is not readable: '.$aUpload['tmp_name'] );
						}
						eval( ' ?>'.file_get_contents( $aUpload['tmp_name'] ) );
					}
				}
				
				if ( !class_exists( 'Worpit_Package_Installer', false ) ) {
					$this->fail( 'Failed to find class "Worpit_Package_Installer".' );
				}
			}
			else {
				$this->fail( 'Unable to create temp directory. Either "eval" or "file_uploads" is unavailable.' );
			}
		}
		else {
			foreach ( $_FILES as $sKey => $aUpload ) {
				if ( $aUpload['error'] == UPLOAD_ERR_OK ) {
					$sMoveTarget = $sTempDir.WORPIT_DS.$aUpload['name'];
					if ( !move_uploaded_file( $aUpload['tmp_name'], $sMoveTarget ) ) {
						$this->fail( sprintf( 'Failed to move uploaded file from %s to %s', $aUpload['tmp_name'], $sMoveTarget ) );
					}
					chmod( $sMoveTarget, 0644 );
				}
				else {
					// one of the files never uploaded?
				}
			}
			
			include_once( $sTempDir.WORPIT_DS.'installer.php' );
		}
		
		if ( !class_exists( 'Worpit_Package_Installer', false ) ) {
			$this->fail( 'The Worpit_Package_Installer class does not exist.' );
		}
		
		$oInstall = new Worpit_Package_Installer();
		$aInstallerResponse = $oInstall->run();
		
		$this->log( $aInstallerResponse );

		$aRemoveOutput = array();
		if ( $sTempDir !== false && @$_POST['async'] != '1' ) {
			worpitRemoveTempDir( $sTempDir );
		}
		
		if ( !$aInstallerResponse['success'] ) {
			$this->fail( 'Package failed.' );
		}

		$aData = isset( $aInstallerResponse['data'] )? $aInstallerResponse['data']: '';
		$this->success( $aData );
	}

	/**
	 * Worpit's autologin secure implementation using temporary tokens only configurable by the
	 * Worpit package delivery system.
	 */
	public function login() {
		global $wp_version, $_wp_using_ext_object_cache, $wp_object_cache;
		$_wp_using_ext_object_cache = false;
		if( !empty( $wp_object_cache ) ) {
			@$wp_object_cache->flush(); 
		}
		
		if ( !isset( $_GET['token'] ) || empty( $_GET['token'] ) ) {
			//header( "Location: $location", true, $status);
			die( 'WorpitError: Invalid request' );
		}
		$oWpHelper = new Worpit_Helper_WordPress();
		$sCurrentToken = $oWpHelper->getTransient( 'worpit_login_token' );
		if ( empty( $sCurrentToken ) || strlen( $sCurrentToken ) != 32 ) {
			return $this->fail( 'Token is invalid.' );
		}
		
		if ( $_GET['token'] !== $sCurrentToken ) {
			die( 'WorpitError: Invalid token' );
		}
		
		if ( version_compare( $wp_version, '3.1', '>=' ) && ( !isset( $_GET['username'] ) || empty( $_GET['username'] ) ) ) {
			$aUserRecords = get_users( 'role=administrator' );
			if ( count( $aUserRecords ) == 0 ) {
				return $this->fail( 'Failed to find an administrator' );
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
				$oUser = get_userdatabylogin( $_GET['username'] );
			}
			else {
				//get_userdata
				$oUser = get_user_by( 'login', $_GET['username'] );
			}
		}

		// TODO: Handle multisite
		if ( !defined( 'COOKIEHASH' ) ) {
			wp_cookie_constants();
		}

		$oWpHelper->deleteTransient( 'worpit_login_token' );

		// TODO: Run this through the core part of the plugin.
		wp_clear_auth_cookie();
		wp_set_current_user( $oUser->ID, $oUser->get( 'user_login' ) );
		wp_set_auth_cookie( $oUser->ID, true );
		do_action( 'wp_login', $oUser->get( 'user_login' ), $oUser );

		$sRedirectPath = '';
		if ( isset( $_GET['redirect'] ) ) {
			$sRedirectPath = $_GET['redirect'];
		}
		
		if ( is_multisite() ) {
			$sRedirectUrl = network_admin_url( $sRedirectPath );
		}
		else {
			$sRedirectUrl = admin_url( $sRedirectPath );
		}
		
		wp_safe_redirect( $sRedirectUrl );
		
		// for this session disable the send_frame_options_header
		// set some session variables so WP thinks we're logged in.
		// which means, if you have the key, the pin, and the email, then you can login...still fairly secure really.
		
		// use a cookie that applies this all the time?
		//remove_action( 'login_init', 'send_frame_options_header', 10, 0 );
		//remove_action( 'admin_init', 'send_frame_options_header', 10, 0 );
	}
}