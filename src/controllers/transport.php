<?php

class Worpit_Controllers_Transport extends Worpit_Controllers_Base {

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
		
		/**
		 * @since 1.0.8
		 */
		if ( isset( $_POST['using_ftp'] ) ) {
			$sAbsTarget = dirname(__FILE__).WORPIT_DS.$_POST['temp_dir_name'];
			if ( !is_dir( $sAbsTarget ) ) {
				$this->fail( 'Expected directory "'.$sAbsTarget.'" does not exist.' );
			}
			
			if ( !is_file( $sAbsTarget.WORPIT_DS.'installer.php' ) ) {
				$this->fail( 'An installer was not found for the package located at "'.$sAbsTarget.'".' );
			}
			
			if ( is_file( $sAbsTarget.WORPIT_DS.'request_data.php' ) && is_writable( $sAbsTarget.WORPIT_DS.'request_data.php' ) ) {
				file_put_contents( $sAbsTarget.WORPIT_DS.'request_data.php', $this->getWritableRequestData( $_POST ) );
			}
			
			include_once( $sAbsTarget.WORPIT_DS.'installer.php' );
		}
		else {
			$sTempDir = false;
			if ( !isset( $_POST['force_use_eval'] ) ) {
				$sTempDir = worpitCreateTempDir( dirname(__FILE__), '.pkg_' );
				$_POST['rel_package_dir'] = str_replace( dirname(__FILE__), '', $sTempDir );
				$_POST['abs_package_dir'] = $sTempDir;
			}
			
			$sWritableRequestData = $this->getWritableRequestData( $_POST );

			if ( $sTempDir === false ) {
				$fAllowUploads = (bool)ini_get( 'file_uploads' );
				
				if ( $fAllowUploads && isset( $_REQUEST['eval_order'] ) ) {
					$aFileContents = array();
					foreach ( $_FILES as $sKey => $aUpload ) {
						if ( $aUpload['error'] == UPLOAD_ERR_OK ) {
							if ( !is_readable( $aUpload['tmp_name'] ) ) {
								$this->fail( 'Unable to create temp directory. Uploaded file is not readable: '.$aUpload['tmp_name'] );
							}
							$aFileContents[$aUpload['name']] = file_get_contents( $aUpload['tmp_name'] );
						}
					}
										
					eval( ' ?>'.$sWritableRequestData );
					
					$aEvalOrder = explode( ',', $_REQUEST['eval_order'] );
					
					foreach( $aEvalOrder as $sEvalFile ) {
						if ( isset( $aFileContents[$sEvalFile] ) ) {
							eval( ' ?>'.$aFileContents[$sEvalFile] );
						}
						else {
							$this->fail( 'Missing required file: '.$sEvalFile.' from uploaded files: '.implode( ',', array_keys( $aFileContents ) ) );
						}
					}
					
					if ( !class_exists( 'Worpit_Package_Installer', false ) ) {
						$this->fail( 'Failed to find class "Worpit_Package_Installer".' );
					}
				}
				else {
					$this->fail( 'Unable to create temp directory. Either "eval" or "file_uploads" is unavailable, or "eval_order" was not provided.' );
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
				
				if ( !file_put_contents( $sTempDir.WORPIT_DS.'request_data.php', $sWritableRequestData."\n" ) ) {
					$nVal = worpitRemoveTempDir( $sTempDir );
					$this->logMerge( $aRemoveOutput );
					$this->fail( 'Failed to create dynamic request data config file.' );
				}
				else {
					chmod( $sTempDir.WORPIT_DS.'request_data.php', 0644 );
				}
				
				include_once( $sTempDir.WORPIT_DS.'installer.php' );
			}
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
		global $wp_version;
		
		$oWpHelper = new Worpit_Helper_WordPress();

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