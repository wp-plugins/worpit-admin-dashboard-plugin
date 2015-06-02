<?php

if ( !class_exists( 'ICWP_APP_Processor_Plugin_Api', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base.php' );

	/**
	 * Class ICWP_APP_Processor_Plugin_Api
	 */
	class ICWP_APP_Processor_Plugin_Api extends ICWP_APP_Processor_Base {

		/**
		 * @var stdClass
		 */
		protected static $oActionResponse;

		/**
		 * @var ICWP_APP_FeatureHandler_Plugin
		 */
		protected $oFeatureOptions;

		/**
		 * @return stdClass
		 */
		public function run() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();

			$sApiMethod = $oFO->fetchIcwpRequestParam( 'm', 'index' );
			if ( !preg_match( '/[A-Z0-9_]+/i', $sApiMethod ) ) {
				$sApiMethod = 'index';
			}

			$oResponse = $this->getStandardResponse();
			$oResponse->method = $sApiMethod;

			// Should we preApiCheck login?
			if ( $sApiMethod == 'login' ) {
				return $this->doLogin();
			}

			$this->doHandshakeVerify();
			if ( !$oResponse->success ) {
				if ( $oResponse->code == 9991 ) {
					$oFO->setCanHandshake(); //recheck ability to handshake
				}
				return $oResponse;
			}

			$this->preApiCheck();
			if ( !$oResponse->success ) {
				if ( !$this->doAttemptSiteReassign()->success ) {
					return $oResponse;
				}
			}
			$this->doWpEngine();
			@set_time_limit( $oFO->fetchIcwpRequestParam( 'timeout', 60 ) );

			switch( $sApiMethod ) {

				case 'index':
					$this->doIndex();
					break;
				case 'retrieve':
					$this->doRetrieve();
					break;
				case 'execute':
					$this->doExecute();
					break;
				case 'internal':
//			    	$this->doInternal();
					break;
			}

			return $oResponse;
		}

		/**
		 * @return stdClass
		 */
		protected function doIndex() {
			return $this->setSuccessResponse( 'Plugin Index' ); //just to be sure we proceed thereafter
		}

		/**
		 * @return stdClass
		 */
		protected function preApiCheck() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oResponse = $this->getStandardResponse();

			if ( !$oFO->getIsSiteLinked() ) {
				$sErrorMessage = 'NotAssigned';
				return $this->setErrorResponse(
					$sErrorMessage,
					9999
				);
			}

			$sKey = $oFO->getPluginAuthKey();
			$sRequestKey = trim( $oFO->fetchIcwpRequestParam( 'key', false ) );
			if ( empty( $sRequestKey ) ) {
				$sErrorMessage = 'EmptyRequestKey';
				return $this->setErrorResponse(
					$sErrorMessage,
					9995
				);
			}
			if ( $sRequestKey != $sKey ) {
				$sErrorMessage = 'InvalidKey';
				return $this->setErrorResponse(
					$sErrorMessage,
					9998
				);
			}

			$sPin = $oFO->getPluginPin();
			$sRequestPin = trim( $oFO->fetchIcwpRequestParam( 'pin', false ) );
			if ( empty( $sRequestPin ) ) {
				$sErrorMessage = 'EmptyRequestPin';
				return $this->setErrorResponse(
					$sErrorMessage,
					9994
				);
			}
			if ( md5( $sRequestPin ) != $sPin ) {
				$sErrorMessage = 'InvalidPin';
				return $this->setErrorResponse(
					$sErrorMessage,
					9997
				);
			}

			return $oResponse;
		}

		/**
		 * Attempts to relink/reassign a site upon API failure, with certain pre-conditions
		 *
		 * 1) The method is "retrieve"
		 * 2) The site CAN Handshake (it will check this)
		 * 3) The handshake is verified for this package
		 *
		 * @return stdClass
		 */
		protected function doAttemptSiteReassign() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();

			$oResponse = $this->getStandardResponse();
			if ( !isset( $oResponse->method ) || $oResponse->method != 'retrieve' ) {
				return $this->setErrorResponse(
					sprintf( 'Attempting Site Reassign Failed: %s.', 'Site action method is not "retrieve"' ),
					9806
				);
			}

			// We first verify fully if we CAN handshake
			if ( !$oFO->getCanHandshake( true ) ) {
				return $this->setErrorResponse(
					sprintf( 'Attempting Site Reassign Failed: %s.', 'Site cannot handshake' ),
					9801
				);
			}
			$oResponse = $this->doHandshakeVerify();
			if ( !$oResponse->success ) {
				return $this->setErrorResponse(
					sprintf( 'Attempting Site Reassign Failed: %s.', 'Handshake verify failed' ),
					9802
				);
			}

			$sRequestedAcc = urldecode( $oFO->fetchIcwpRequestParam( 'accname' ) );
			if ( empty( $sRequestedAcc ) || !is_email( $sRequestedAcc ) ) {
				return $this->setErrorResponse(
					sprintf( 'Attempting Site Reassign Failed: %s.', 'Request account empty or invalid' ),
					9803
				);
			}

			$sRequestedKey = $oFO->fetchIcwpRequestParam( 'key', '' );
			if ( empty( $sRequestedKey ) || strlen( $sRequestedKey ) != 24 ) {
				return $this->setErrorResponse(
					sprintf( 'Attempting Site Reassign Failed: %s.', 'Auth Key not of the correct format' ),
					9804
				);
			}

			$sRequestPin = $oFO->fetchIcwpRequestParam( 'pin', '' );
			if ( empty( $sRequestPin ) ) {
				return $this->setErrorResponse(
					sprintf( 'Attempting Site Reassign Failed: %s.', 'PIN empty' ),
					9805
				);
			}

			$oFO->setOpt( 'key', $sRequestedKey );
			$oFO->setPluginAssigned( $sRequestedAcc );
			$oFO->setPluginPin( $sRequestPin );
			$oFO->savePluginOptions();

			return $this->setSuccessResponse(
				'Attempting Site Reassign Succeeded.',
				9800
			);
		}

		/**
		 * @return stdClass
		 */
		protected function doHandshakeVerify() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oDp = $this->loadDataProcessor();
			$oResponse = $this->getStandardResponse();

			if( !$oFO->getCanHandshake() ) {
				$oResponse->handshake = 'unsupported';
				return $oResponse;
			}
			$oResponse->handshake = 'failed';

			$sVerificationCode = $oFO->fetchIcwpRequestParam( 'verification_code', false );
			if ( $oDp->getCanOpensslSign() ) {
				$sSignature = base64_decode( $oFO->fetchIcwpRequestParam( 'opensig', '' ) );
				$sPublicKey = $this->getOption( 'icwp_public_key', '' );
				if ( !empty( $sSignature ) && !empty( $sPublicKey ) ) {
					$oResponse->openssl_verify = openssl_verify( $sVerificationCode, $sSignature, base64_decode( $sPublicKey ) );
					if ( $oResponse->openssl_verify === 1 ) {
						$oResponse->handshake = 'openssl';
						return $this->setSuccessResponse(); //just to be sure we proceed thereafter
					}
				}
			}

			$sPackageName = $oFO->fetchIcwpRequestParam( 'package_name', false );
			$sPin = $oFO->fetchIcwpRequestParam( 'pin', false );

			if ( empty( $sVerificationCode ) || empty( $sPackageName ) || empty( $sPin ) ) {
				return $this->setErrorResponse(
					'Either the Verification Code, Package Name, or PIN were empty. Could not Handshake.',
					9990
				);
			}

			$sHandshakeVerifyBaseUrl = $this->getOption( 'handshake_verify_url' );
			// We can do this because we've assumed at this point we've validated the communication with iControlWP
			$sHandshakeVerifyUrl = sprintf(
				'%s/%s/%s/%s',
				rtrim( $sHandshakeVerifyBaseUrl, '/' ),
				$sVerificationCode,
				$sPackageName,
				$sPin
			);

			$oFs = $this->loadFileSystemProcessor();
			$sResponse = $oFs->getUrlContent( $sHandshakeVerifyUrl );
			if ( empty( $sResponse ) ) {
				return $this->setErrorResponse(
					sprintf( 'Package Handshaking Failed against URL "%s" with an empty response.', $sHandshakeVerifyUrl ),
					9991
				);
			}

			$oJsonResponse = $oDp->doJsonDecode( trim( $sResponse ) );
			if ( !is_object( $oJsonResponse ) || !isset( $oJsonResponse->success ) || $oJsonResponse->success !== true ) {
				return $this->setErrorResponse(
					sprintf( 'Package Handshaking Failed against URL "%s" with response: "%s".', $sHandshakeVerifyUrl, print_r( $oJsonResponse,true ) ),
					9992
				);
			}

			$oResponse->handshake = 'url';
			return $this->setSuccessResponse(); //just to be sure we proceed thereafter
		}

		/**
		 *
		 */
		protected function doWpEngine() {
			if ( @getenv( 'IS_WPE' ) == '1' && class_exists( 'WpeCommon', false ) && $this->setAuthorizedUser() ) {
				$oWpEngineCommon = WpeCommon::instance();
				$oWpEngineCommon->set_wpe_auth_cookie();
			}
		}

		/**
		 * @return bool
		 */
		protected function setAuthorizedUser() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oWp = $this->loadWpFunctionsProcessor();
			$sWpUser = $oFO->fetchIcwpRequestParam( 'wpadmin_user' );
			if ( empty( $sWpUser ) ) {

				if ( version_compare( $oWp->getWordpressVersion(), '3.1', '>=' ) ) {
					$aUserRecords = get_users( 'role=administrator' );
					if ( is_array( $aUserRecords ) && count( $aUserRecords ) ) {
						$oUser = $aUserRecords[0];
					}
				}
				else {
					$oUser = $oWp->getUserById( 1 );
				}
				$sWpUser = ( !empty( $oUser ) && is_a( $oUser, 'WP_User' ) ) ? $oUser->get( 'user_login' ) : '';
			}

			return $oWp->setUserLoggedIn( empty( $sWpUser ) ? 'admin' : $sWpUser );
		}

		/**
		 * @return stdClass
		 */
		protected function doInternal() {
			include_once( 'plugin_internalapi.php' );
			$oInternalApi = new ICWP_APP_Processor_Plugin_InternalApi( $this->getFeatureOptions() );
			return $oInternalApi->run();
		}

		/**
		 * @return stdClass
		 */
		protected function doRetrieve() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oFs = $this->loadFileSystemProcessor();
			$oResponse = $this->getStandardResponse();

			if ( !function_exists( 'download_url' ) ) {
				return $this->setErrorResponse(
					sprintf( 'Function "%s" does not exit.', 'download_url' )
					-1 //TODO: Set a code
				);
			}

			if ( !function_exists( 'is_wp_error' ) ) {
				return $this->setErrorResponse(
					sprintf( 'Function "%s" does not exit.', 'is_wp_error' ),
					-1 //TODO: Set a code
				);
			}

			$sPackageId = $oFO->fetchIcwpRequestParam( 'package_id' );
			if ( empty( $sPackageId ) ) {
				return $this->setErrorResponse(
					'Package ID to retrieve is empty.',
					-1 //TODO: Set a code
				);
			}

			// We can do this because we've assumed at this point we've validated the communication with iControlWP
			$sRetrieveBaseUrl = $oFO->fetchIcwpRequestParam( 'package_retrieve_url', $this->getOption( 'package_retrieve_url' ) );
//			$sRetrieveUrl = 'http://staging.worpitapp.com/system/package/retrieve/';
			$sPackageRetrieveUrl = sprintf(
				'%s/%s/%s/%s',
				rtrim( $sRetrieveBaseUrl, '/' ),
				$sPackageId,
				$oFO->getPluginAuthKey(),
				$oFO->getPluginPin()
			);
			$sRetrievedTmpFile = download_url( $sPackageRetrieveUrl );

			if ( is_wp_error( $sRetrievedTmpFile ) ) {
				$sMessage = sprintf(
					'The package could not be downloaded from "%s" with error: %s',
					$sPackageRetrieveUrl,
					$sRetrievedTmpFile->get_error_message()
				);
				return $this->setErrorResponse(
					$sMessage,
					-1 //TODO: Set a code
				);
			}

			$sNewFile = $this->getController()->getPath_Temp( basename( $sRetrievedTmpFile ) );
//			if ( is_null( $sNewFile ) ) {
//				return $this->setErrorResponse(
//					'Could not create temporary folder to store package',
//					-1 //TODO: Set a code
//				);
//			}
			$sFileToInclude = $sRetrievedTmpFile;
			if ( !is_null( $sNewFile ) && $oFs->move( $sRetrievedTmpFile, $sNewFile ) ) { //we try to move it to our plugin tmp folder.
				$sFileToInclude = $sNewFile;
			}

			$this->runInstaller( $sFileToInclude );
			return $oResponse;
		}

		/**
		 * @return stdClass
		 */
		protected function doExecute() {
			$oFs = $this->loadFileSystemProcessor();

			/**
			 * @since 1.0.14
			 */
			$_POST['rel_package_dir'] = '';
			$_POST['abs_package_dir'] = '';

			$sTempDir = $oFs->getTempDir( $this->getController()->getPath_Temp(), 'pkg_' );
			if ( !isset( $_POST['force_use_eval'] ) ) {
				$_POST['rel_package_dir'] = str_replace( dirname(__FILE__), '', $sTempDir );
				$_POST['abs_package_dir'] = $sTempDir;
			}
			else {
				return $this->setErrorResponse(
					'No longer support EVAL() methods.',
					9800
				);
			}

			// TODO:
			//https://yoast.com/smarter-upload-handling-wp-plugins/
			//wp_handle_upload()
			foreach ( $_FILES as $sKey => $aUpload ) {
				if ( $aUpload['error'] == UPLOAD_ERR_OK ) {
					$sMoveTarget = $sTempDir.ICWP_DS.$aUpload['name'];
					if ( !move_uploaded_file( $aUpload['tmp_name'], $sMoveTarget ) ) {
						return $this->setErrorResponse(
							sprintf( 'Failed to move uploaded file from %s to %s', $aUpload['tmp_name'], $sMoveTarget ),
							9801
						);
					}
					chmod( $sMoveTarget, 0644 );
				}
				else {
					return $this->setErrorResponse(
						'One of the uploaded files could not be copied to the temp dir.',
						9802
					);
				}
			}

			$sFileToInclude = $sTempDir.ICWP_DS.'installer.php';
			$this->runInstaller( $sFileToInclude );
			$oFs->deleteDir( $sTempDir );

			return $this->getStandardResponse();
		}

		/**
		 * @param string $sInstallerFileToInclude
		 *
		 * @return stdClass
		 */
		private function runInstaller( $sInstallerFileToInclude ) {
			$oFs = $this->loadFileSystemProcessor();

			$bIncludeSuccess = include_once( $sInstallerFileToInclude );
			$oFs->deleteFile( $sInstallerFileToInclude );

			if ( !$bIncludeSuccess ) {
				return $this->setErrorResponse(
					'PHP failed to include the Installer file for execution'
				);
			}

			if ( !class_exists( 'Worpit_Package_Installer', false ) ) {
				$sErrorMessage = sprintf( 'Worpit_Package_Installer class does not exist after including file: "%s".', $sInstallerFileToInclude );
				return $this->setErrorResponse(
					$sErrorMessage,
					-1 //TODO: Set a code
				);
			}

			$oInstall = new Worpit_Package_Installer();
			$aInstallerResponse = $oInstall->run();
			$sInstallerExecutionMessage = !empty( $aInstallerResponse[ 'message' ] ) ? $aInstallerResponse[ 'message' ] : 'No message';

			// TODO
//			$this->log( $aInstallerResponse );

			if ( !$aInstallerResponse['success'] ) {
				return $this->setErrorResponse(
					sprintf( 'Package Execution FAILED with error message: "%s"', $sInstallerExecutionMessage ),
					-1 //TODO: Set a code
				);
			}
			else {

				return $this->setSuccessResponse(
					sprintf( 'Package Execution SUCCEEDED with message: "%s".', $sInstallerExecutionMessage ),
					0,
					isset( $aInstallerResponse['data'] )? $aInstallerResponse['data']: ''
				);
			}
		}

		/**
		 * @return stdClass
		 */
		protected function doLogin() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oWp = $this->loadWpFunctionsProcessor();
			$oWp->doBustCache();

			$oResponse = $this->getStandardResponse();
			// If there's an error with login, we die.
			$oResponse->die = true;

			$sRequestToken = $oFO->fetchIcwpRequestParam( 'token', '' );
			if ( empty( $sRequestToken ) ) {
				$sErrorMessage = 'No valid Login Token was sent.';
				return $this->setErrorResponse(
					$sErrorMessage,
					-1 //TODO: Set a code
				);
			}

			$sLoginTokenKey = 'worpit_login_token';
			$sStoredToken = $oWp->getTransient( $sLoginTokenKey );
			$oWp->deleteTransient( $sLoginTokenKey ); // One chance per token
			if ( empty( $sStoredToken ) || strlen( $sStoredToken ) != 32 ) {
				$sErrorMessage = 'Login Token is not present or is not of the correct format.';
				return $this->setErrorResponse(
					$sErrorMessage,
					-1 //TODO: Set a code
				);
			}

			if ( $sStoredToken !== $sRequestToken ) {
				$sErrorMessage = 'Login Tokens do not match.';
				return $this->setErrorResponse(
					$sErrorMessage,
					-1 //TODO: Set a code
				);
			}

			$sUsername = $oFO->fetchIcwpRequestParam( 'username', '' );
			$oUser = $oWp->getUserByUsername( $sUsername );
			if ( empty( $sUsername ) || empty( $oUser ) ) {
				$aUserRecords = version_compare( $oWp->getWordpressVersion(), '3.1', '>=' ) ? get_users( 'role=administrator' ) : array();
				if ( empty( $aUserRecords[0] ) ) {
					$sErrorMessage = 'Failed to find an administrator user.';
					return $this->setErrorResponse(
						$sErrorMessage,
						-1 //TODO: Set a code
					);
				}
				$oUser = $aUserRecords[0];
			}

			if ( !defined( 'COOKIEHASH' ) ) {
				wp_cookie_constants();
			}

			$bLoginSuccess = $oWp->setUserLoggedIn( $oUser->get( 'user_login' ) );
			if ( !$bLoginSuccess ) {
				return $this->setErrorResponse(
					sprintf( 'There was a problem logging you in as "%s".', $oUser->get( 'user_login' ) ),
					-1 //TODO: Set a code
				);
			}

			$sRedirectPath = $oFO->fetchIcwpRequestParam( 'redirect', '' );
			if ( strlen( $sRedirectPath ) == 0 ) {
				$oWp->redirectToAdmin();
			}
			else {
				$oWp->doRedirect( $sRedirectPath );
			}
			die();
		}

		/**
		 * @param string $sErrorMessage
		 * @param int $nErrorCode
		 * @param mixed $mErrorData
		 *
		 * @return stdClass
		 */
		protected function setErrorResponse( $sErrorMessage = '', $nErrorCode = -1, $mErrorData = '' ) {
			$oResponse = $this->getStandardResponse();
			$oResponse->success = false;
			$oResponse->error_message .= ' '.$sErrorMessage;
			$oResponse->code = $nErrorCode;
			$oResponse->data = $mErrorData;
			return $oResponse;
		}

		/**
		 * @param string $sMessage
		 * @param int $nSuccessCode
		 * @param mixed $mData
		 *
		 * @return stdClass
		 */
		protected function setSuccessResponse( $sMessage = '', $nSuccessCode = 0, $mData = null ) {
			$oResponse = $this->getStandardResponse();
			$oResponse->success = true;
			$oResponse->message = $sMessage;
			$oResponse->code = $nSuccessCode;
			$oResponse->data = is_null( $mData ) ? array( 'success' => 1 ) : $mData;
			return $oResponse;
		}

		/**
		 * @return stdClass
		 */
		static protected function getStandardResponse() {
			if ( is_null( self::$oActionResponse ) ) {
				$oResponse = new stdClass();
				$oResponse->error_message = '';
				$oResponse->message = '';
				$oResponse->success = true;
				$oResponse->code = 0;
				$oResponse->data = null;
				$oResponse->method = '';
				$oResponse->die = false;
				$oResponse->action_object = null;
				$oResponse->handshake = 'none';
				self::$oActionResponse = $oResponse;
			}
			return self::$oActionResponse;
		}
	}

endif;
