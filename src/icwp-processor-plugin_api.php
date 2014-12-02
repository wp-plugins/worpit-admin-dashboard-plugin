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

require_once( dirname(__FILE__).ICWP_DS.'icwp-processor-base.php' );

if ( !class_exists('ICWP_APP_Processor_Plugin_Api') ):

	/**
	 * Class ICWP_APP_Processor_Plugin_Api
	 */
	class ICWP_APP_Processor_Plugin_Api extends ICWP_APP_Processor_Base {

		/**
		 * @var ICWP_APP_FeatureHandler_Plugin
		 */
		protected $oFeatureOptions;

		/**
		 * @param ICWP_APP_FeatureHandler_Plugin $oFeatureOptions
		 */
		public function __construct( ICWP_APP_FeatureHandler_Plugin $oFeatureOptions ) {
			parent::__construct( $oFeatureOptions );
		}

		/**
		 * @return ICWP_APP_FeatureHandler_Plugin
		 */
		protected function getFeatureOptions() {
			return $this->oFeatureOptions;
		}

		/**
		 * @return stdClass
		 */
		public function run() {
			$oDp = $this->loadDataProcessor();

			$sApiMethod = $oDp->FetchGet( 'm', 'index' );
			if ( !preg_match( '/[A-Z0-9_]+/i', $sApiMethod ) ) {
				$sApiMethod = 'index';
			}

			$oResponse = $this->getStandardResponse();
			$oResponse->method = $sApiMethod;

			// Should we preApiCheck login?
			if ( $sApiMethod == 'login' ) {
				return $this->doLogin( $oResponse );
			}

			$this->preApiCheck( $oResponse );
			if ( !$oResponse->success ) {
				return $oResponse;
			}

			$this->doHandshakeVerify( $oResponse );
			if ( !$oResponse->success ) {
				if ( $oResponse->code == 9991 ) {
					$this->getFeatureOptions()->setCanHandshake();
				}
				return $oResponse;
			}

			$this->doWpEngine();
			@set_time_limit( $oDp->FetchRequest( 'timeout', false, 60 ) );

			$aOldVersionMethods = array(
				'index',
				'query',
//				'retrieve',
//				'execute',
			);

			if ( in_array( $sApiMethod, $aOldVersionMethods ) ) {
				define( 'WORPIT_DIRECT_API', 1 );
				include_once( dirname(__FILE__).'/transport.php' );
				die();
			}

			if ( $sApiMethod == 'retrieve' ) {
				$this->doRetrieve( $oResponse );
			}
			if ( $sApiMethod == 'execute' ) {
				$this->doExecute( $oResponse );
			}

			return $oResponse;
		}

		/**
		 * @param stdClass $oResponse
		 *
		 * @return stdClass
		 */
		protected function preApiCheck( $oResponse ) {
			$oDp = $this->loadDataProcessor();

			if ( !$this->getFeatureOptions()->getIsSiteLinked() ) {
				$sErrorMessage = 'NotAssigned';
				return $this->setErrorResponse(
					$oResponse,
					$sErrorMessage,
					9999
				);
			}

			$sKey = $this->getOption( 'key' );
			$sRequestKey = trim( $oDp->FetchRequest( 'key', false ) );
			if ( empty( $sRequestKey ) ) {
				$sErrorMessage = 'EmptyRequestKey';
				return $this->setErrorResponse(
					$oResponse,
					$sErrorMessage,
					9995
				);
			}
			if ( $sRequestKey != $sKey ) {
				$sErrorMessage = 'InvalidKey';
				return $this->setErrorResponse(
					$oResponse,
					$sErrorMessage,
					9998
				);
			}

			$sPin = $this->getOption( 'pin' );
			$sRequestPin = trim( $oDp->FetchRequest( 'pin', false ) );
			if ( empty( $sRequestPin ) ) {
				$sErrorMessage = 'EmptyRequestPin';
				return $this->setErrorResponse(
					$oResponse,
					$sErrorMessage,
					9994
				);
			}
			if ( md5( $sRequestPin ) != $sPin ) {
				$sErrorMessage = 'InvalidPin';
				return $this->setErrorResponse(
					$oResponse,
					$sErrorMessage,
					9997
				);
			}

			return $oResponse;
		}

		/**
		 * @param stdClass $oResponse
		 *
		 * @return stdClass
		 */
		protected function doHandshakeVerify( stdClass $oResponse ) {
			if( !$this->getFeatureOptions()->getCanHandshake() ) {
				return $oResponse;
			}
			$oDp = $this->loadDataProcessor();
			$sVerificationCode = $oDp->FetchRequest( 'verification_code', false );
			$sPackageName = $oDp->FetchRequest( 'package_name', false );
			$sPin = $oDp->FetchRequest( 'pin', false );

			if ( empty( $sVerificationCode ) || empty( $sPackageName ) || empty( $sPin ) ) {
				return $this->setErrorResponse(
					$oResponse,
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
					$oResponse,
					sprintf( 'Package Handshaking Failed against URL "%s" with an empty response.', $sHandshakeVerifyUrl ),
					9991
				);
			}

			$oJsonResponse = $this->loadDataProcessor()->doJsonDecode( trim( $sResponse ) );
			if ( !is_object( $oJsonResponse ) || !isset( $oJsonResponse->success ) || $oJsonResponse->success !== true ) {
				return $this->setErrorResponse(
					$oResponse,
					sprintf( 'Package Handshaking Failed against URL "%s" with response: "%s".', $sHandshakeVerifyUrl, print_r( $oJsonResponse,true ) ),
					9992
				);
			}

			return $this->setSuccessResponse( $oResponse ); //just to be sure we proceed thereafter
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
			$oDp = $this->loadDataProcessor();
			$oWp = $this->loadWpFunctionsProcessor();
			$sWpUser = $oDp->FetchPost( 'wpadmin_user' );
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
		 * @param stdClass $oResponse
		 *
		 * @return stdClass
		 */
		protected function doRetrieve( $oResponse ) {
			$oDp = $this->loadDataProcessor();
			$oFs = $this->loadFileSystemProcessor();

			if ( !function_exists( 'download_url' ) ) {
				return $this->setErrorResponse(
					$oResponse,
					sprintf( 'Function "%s" does not exit.', 'download_url' )
					-1 //TODO: Set a code
				);
			}

			if ( !function_exists( 'is_wp_error' ) ) {
				return $this->setErrorResponse(
					$oResponse,
					sprintf( 'Function "%s" does not exit.', 'is_wp_error' ),
					-1 //TODO: Set a code
				);
			}

			$sPackageId = $oDp->FetchGet( 'package_id' );
			if ( empty( $sPackageId ) ) {
				return $this->setErrorResponse(
					$oResponse,
					'Package ID to retrieve is empty.',
					-1 //TODO: Set a code
				);
			}

			// We can do this because we've assumed at this point we've validated the communication with iControlWP
			$sRetrieveBaseUrl = $oDp->FetchRequest( 'package_retrieve_url', false, $this->getOption( 'package_retrieve_url' ) );
//			$sRetrieveUrl = 'http://staging.worpitapp.com/system/package/retrieve/';
			$sPackageRetrieveUrl = sprintf(
				'%s/%s/%s/%s',
				rtrim( $sRetrieveBaseUrl, '/' ),
				$sPackageId,
				$this->getOption( 'key' ),
				$this->getOption( 'pin' )
			);
			$sRetrievedTmpFile = download_url( $sPackageRetrieveUrl );

			if ( is_wp_error( $sRetrievedTmpFile ) ) {
				$sMessage = sprintf(
					'The package could not be downloaded from "%s" with error: %s',
					$sPackageRetrieveUrl,
					$sRetrievedTmpFile->get_error_message()
				);
				return $this->setErrorResponse(
					$oResponse,
					$sMessage,
					-1 //TODO: Set a code
				);
			}

			$sNewFile = $this->getController()->getPath_Temp( basename( $sRetrievedTmpFile ) );
			$sFileToInclude = $oFs->move( $sRetrievedTmpFile, $sNewFile ) ? $sNewFile : $sRetrievedTmpFile;

			$this->runInstaller( $oResponse, $sFileToInclude );
			return $oResponse;
		}

		/**
		 * @param stdClass $oResponse
		 *
		 * @return stdClass
		 */
		protected function doExecute( $oResponse ) {

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
				$this->setErrorResponse(
					$oResponse,
					'No longer support EVAL() methods.',
					9800
				);
			}

			// TODO:
			//https://yoast.com/smarter-upload-handling-wp-plugins/
			//wp_handle_upload()
			foreach ( $_FILES as $sKey => $aUpload ) {
				if ( $aUpload['error'] == UPLOAD_ERR_OK ) {
					$sMoveTarget = $sTempDir.WORPIT_DS.$aUpload['name'];
					if ( !move_uploaded_file( $aUpload['tmp_name'], $sMoveTarget ) ) {
						$this->setErrorResponse(
							$oResponse,
							sprintf( 'Failed to move uploaded file from %s to %s', $aUpload['tmp_name'], $sMoveTarget ),
							9801
						);
					}
					chmod( $sMoveTarget, 0644 );
				}
				else {
					$this->setErrorResponse(
						$oResponse,
						'One of the uploaded files could not be copied to the temp dir.',
						9802
					);
				}
			}

			$sFileToInclude = $sTempDir . WORPIT_DS . 'installer.php';
			$this->runInstaller( $oResponse, $sFileToInclude );
			$oFs->deleteDir( $sTempDir );

			return $oResponse;
		}

		/**
		 * @param string $sInstallerFileToInclude
		 * @param stdClass $oResponse
		 *
		 * @return stdClass
		 */
		private function runInstaller( $oResponse, $sInstallerFileToInclude ) {

			include_once( $sInstallerFileToInclude );
			$oFs = $this->loadFileSystemProcessor();
			$oFs->deleteFile( $sInstallerFileToInclude );

			if ( !class_exists( 'Worpit_Package_Installer', false ) ) {
				$sErrorMessage = sprintf( 'Worpit_Package_Installer does not exist in file: "%s".', $sInstallerFileToInclude );
				return $this->setErrorResponse(
					$oResponse,
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

				$this->setErrorResponse(
					$oResponse,
					sprintf( 'Package Execution FAILED with error message: "%s"', $sInstallerExecutionMessage ),
					-1 //TODO: Set a code
				);
			}
			else {

				$this->setSuccessResponse(
					$oResponse,
					sprintf( 'Package Execution SUCCEEDED with message: "%s".', $sInstallerExecutionMessage ),
					0,
					isset( $aInstallerResponse['data'] )? $aInstallerResponse['data']: ''
				);
			}

			return $oResponse;
		}

		/**
		 * @param stdClass $oResponse
		 *
		 * @return stdClass
		 */
		protected function doLogin( $oResponse ) {
			$oWp = $this->loadWpFunctionsProcessor();
			$oDp = $this->loadDataProcessor();
			$oWp->doBustCache();

			// If there's an error with login, we die.
			$oResponse->die = true;

			$sRequestToken = $oDp->FetchRequest( 'token', false, '' );
			if ( empty( $sRequestToken ) ) {
				$sErrorMessage = 'No valid Login Token was sent.';
				return $this->setErrorResponse(
					$oResponse,
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
					$oResponse,
					$sErrorMessage,
					-1 //TODO: Set a code
				);
			}

			if ( $sStoredToken !== $sRequestToken ) {
				$sErrorMessage = 'Login Tokens do not match.';
				return $this->setErrorResponse(
					$oResponse,
					$sErrorMessage,
					-1 //TODO: Set a code
				);
			}

			$sUsername = $oDp->FetchRequest( 'username', false, '' );
			$oUser = $oWp->getUserByUsername( $sUsername );
			if ( empty( $sUsername ) || empty( $oUser ) ) {
				$aUserRecords = version_compare( $oWp->getWordpressVersion(), '3.1', '>=' ) ? get_users( 'role=administrator' ) : array();
				if ( empty( $aUserRecords[0] ) ) {
					$sErrorMessage = 'Failed to find an administrator user.';
					return $this->setErrorResponse(
						$oResponse,
						$sErrorMessage,
						-1 //TODO: Set a code
					);
				}
				$oUser = $aUserRecords[0];
			}

			if ( !defined( 'COOKIEHASH' ) ) {
				wp_cookie_constants();
			}

			$fLoginSuccess = $oWp->setUserLoggedIn( $oUser->get( 'user_login' ) );
			if ( !$fLoginSuccess ) {
				return $this->setErrorResponse(
					$oResponse,
					sprintf( 'There was a problem logging you in as "%s".', $oUser->get( 'user_login' ) ),
					-1 //TODO: Set a code
				);
			}

			$sRedirectPath = $oDp->FetchGet( 'redirect', '' );
			if ( strlen( $sRedirectPath ) == 0 ) {
				$oWp->redirectToAdmin();
			}
			else {
				$oWp->doRedirect( $sRedirectPath );
			}
			die();
		}

		/**
		 * @param stdClass $oResponse
		 * @param string $sErrorMessage
		 * @param int $nErrorCode
		 * @param mixed $mErrorData
		 *
		 * @return stdClass
		 */
		protected function setErrorResponse( stdClass $oResponse, $sErrorMessage = '', $nErrorCode = -1, $mErrorData = '' ) {
			$oResponse->success = false;
			$oResponse->error_message = $sErrorMessage;
			$oResponse->code = $nErrorCode;
			$oResponse->data = $mErrorData;
			return $oResponse;
		}

		/**
		 * @param stdClass $oResponse
		 * @param string $sMessage
		 * @param int $nSuccessCode
		 * @param mixed $mData
		 *
		 * @return stdClass
		 */
		protected function setSuccessResponse( stdClass $oResponse, $sMessage = '', $nSuccessCode = 0, $mData = '' ) {
			$oResponse->success = true;
			$oResponse->message = $sMessage;
			$oResponse->code = $nSuccessCode;
			$oResponse->data = $mData;
			return $oResponse;
		}

		/**
		 * @return stdClass
		 */
		protected function getStandardResponse() {
			$oResponse = new stdClass();
			$oResponse->error_message = '';
			$oResponse->message = '';
			$oResponse->success = true;
			$oResponse->code = 0;
			$oResponse->data = null;
			$oResponse->method = '';
			$oResponse->die = false;
			return $oResponse;
		}
	}

endif;
