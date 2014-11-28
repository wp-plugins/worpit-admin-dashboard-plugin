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

			$oResponse = new stdClass();
			$oResponse->message = '';
			$oResponse->success = true;
			$oResponse->code = 0;
			$oResponse->data = null;
			$sApiMethod = $oDp->FetchGet( 'm', 'index' );
			if ( !preg_match( '/[A-Z0-9_]+/i', $sApiMethod ) ) {
				$sApiMethod = 'index';
			}
			$oResponse->method = $sApiMethod;

			// Should we preApiCheck login?
			if ( $sApiMethod == 'login' ) {
				$this->doLogin( $oResponse );
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
				$oResponse->success = false;
				$oResponse->code = 9999;
				$oResponse->message = 'NotAssigned';
				return $oResponse;
			}

			$sKey = $this->getOption( 'key' );
			$sRequestKey = trim( $oDp->FetchRequest( 'key', false ) );
			if ( empty( $sRequestKey ) ) {
				$oResponse->success = false;
				$oResponse->code = 9995;
				$oResponse->message = 'EmptyRequestKey';
			}
			if ( $sRequestKey != $sKey ) {
				$oResponse->success = false;
				$oResponse->code = 9998;
				$oResponse->message = 'InvalidKey:'.$sRequestKey;
			}

			$sPin = $this->getOption( 'pin' );
			$sRequestPin = trim( $oDp->FetchRequest( 'pin', false ) );
			if ( empty( $sRequestPin ) ) {
				$oResponse->success = false;
				$oResponse->code = 9994;
				$oResponse->message = 'EmptyRequestPin';
			}
			if ( md5( $sRequestPin ) != $sPin ) {
				$oResponse->success = false;
				$oResponse->code = 9997;
				$oResponse->message = 'InvalidPin:'.$sRequestPin;
				return $oResponse;
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
				$oResponse->success = false;
				$oResponse->code = 9990;
				$oResponse->message = 'Either the Verification Code, Package Name, or PIN were empty. Could not Handshake.';
				return $oResponse;
			}

			$sHandshakeVerifyBaseUrl = $this->getOption( 'handshake_verify_url' );
			// We can do this because we've assumed at this point we've validated the communication with iControlWP
//			$sHandshakeVerifyBaseUrl = 'http://staging.worpitapp.com/system/package/retrieve/';
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
				$oResponse->success = false;
				$oResponse->code = 9991; //this code is use to re-initiate Handshaking verification test
				$oResponse->message = sprintf( 'Package Handshaking Failed against URL "%s" with an empty response.', $sHandshakeVerifyUrl );
				return $oResponse;
			}

			$oJsonResponse = $this->loadDataProcessor()->doJsonDecode( trim( $sResponse ) );
			if ( !is_object( $oJsonResponse ) || !isset( $oJsonResponse->success ) || $oJsonResponse->success !== true ) {
				$oResponse->success = false;
				$oResponse->code = 9992;
				$oResponse->message = sprintf( 'Package Handshaking Failed against URL "%s" with response: "%s".', $sHandshakeVerifyUrl, print_r( $oJsonResponse,true ) );
				return $oResponse;
			}

			$oResponse->success = true; //just to be sure we proceed
			return $oResponse;
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
		 * @return void
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
				$sWpUser = is_a( $oUser, 'WP_User' ) ? $oUser->get( 'user_login' ) : '';
			}

			$oWp->setUserLoggedIn( empty( $sWpUser ) ? 'admin' : $sWpUser );
			return true;
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
				$oResponse->success = false;
				$oResponse->message = sprintf( 'Function "%s" does not exit.', 'download_url' );
				//TODO: Set a code
				$oResponse->code = -1;
				return $oResponse;
			}

			if ( !function_exists( 'is_wp_error' ) ) {
				$oResponse->success = false;
				$oResponse->message = sprintf( 'Function "%s" does not exit.', 'is_wp_error' );
				//TODO: Set a code
				$oResponse->code = -1;
				return $oResponse;
			}

			$sPackageId = $oDp->FetchGet( 'package_id' );
			if ( empty( $sPackageId ) ) {
				$oResponse->success = false;
				$oResponse->message = 'Package ID to retrieve is empty.';
				//TODO: Set a code
				$oResponse->code = -1;
				return $oResponse;
			}

			// We can do this because we've assumed at this point we've validated the communication with iControlWP
			$sRetrieveBaseUrl = $oDp->FetchRequest( 'package_retrieve_url', $this->getOption( 'package_retrieve_url' ) );
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
				$oResponse->success = false;
				$oResponse->message = sprintf(
					'The package could not be downloaded from "%s" with error: %s',
					$sPackageRetrieveUrl,
					$sRetrievedTmpFile->get_error_message()
				);
				//TODO: Set a code
				$oResponse->code = -1;
				return $oResponse;
			}

			$sNewFile = $this->getController()->getPath_Temp( basename( $sRetrievedTmpFile ) );
			$sFileToInclude = $oFs->move( $sRetrievedTmpFile, $sNewFile ) ? $sNewFile : $sRetrievedTmpFile;

			$oResponse = $this->runInstaller( $sFileToInclude, $oResponse );
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
				$this->fail( 'No longer support EVAL().' );
			}

			foreach ( $_FILES as $sKey => $aUpload ) {
				if ( $aUpload['error'] == UPLOAD_ERR_OK ) {
					$sMoveTarget = $sTempDir.WORPIT_DS.$aUpload['name'];
					if ( !move_uploaded_file( $aUpload['tmp_name'], $sMoveTarget ) ) {
						$this->fail( sprintf( 'Failed to move uploaded file from %s to %s', $aUpload['tmp_name'], $sMoveTarget ) );
					}
					chmod( $sMoveTarget, 0644 );
				}
				else {
					$this->fail( 'One of the uploaded files could not be copied to the temp dir.' );
				}
			}

			$sFileToInclude = $sTempDir . WORPIT_DS . 'installer.php';
			$oResponse = $this->runInstaller( $sFileToInclude, $oResponse );
			$oFs->deleteDir( $sTempDir );

			return $oResponse;
		}

		/**
		 * @param string $sInstallerFileToInclude
		 * @param stdClass $oResponse
		 *
		 * @return stdClass
		 */
		private function runInstaller( $sInstallerFileToInclude, $oResponse ) {

			include_once( $sInstallerFileToInclude );
			$oFs = $this->loadFileSystemProcessor();
			$oFs->deleteFile( $sInstallerFileToInclude );

			if ( !class_exists( 'Worpit_Package_Installer', false ) ) {
				$oResponse->success = false;
				$oResponse->message = sprintf( 'Worpit_Package_Installer does not exist in file: "%s".', $sInstallerFileToInclude );
				//TODO: Set a code
				$oResponse->code = -1;
				return $oResponse;
			}

			$oInstall = new Worpit_Package_Installer();
			$aInstallerResponse = $oInstall->run();

			$sInstallerExecutionMessage = !empty( $aInstallerResponse[ 'message' ] ) ? $aInstallerResponse[ 'message' ] : 'No message';

			// TODO
//			$this->log( $aInstallerResponse );

			if ( !$aInstallerResponse['success'] ) {
				$oResponse->success = false;
				$oResponse->message = sprintf( 'Package Execution FAILED with error message: "%s"', $sInstallerExecutionMessage );
				//TODO: Set a code
				$oResponse->code = -1;
				return $oResponse;
			}

			$aData = isset( $aInstallerResponse['data'] )? $aInstallerResponse['data']: '';

			$oResponse->success = true;
			$oResponse->message = sprintf( 'Package Execution SUCCEEDED with message: "%s".', $sInstallerExecutionMessage );
			$oResponse->data = $aData;
			//TODO: Set a code
			$oResponse->code = 0;

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

			$sRequestToken = $oDp->FetchRequest( 'token', false, '' );
			if ( empty( $sRequestToken ) ) {
				$oResponse->success = false;
				$oResponse->message = 'No valid Login Token was sent';
				//TODO: Set a code
				$oResponse->code = -1;
				return $oResponse;
			}

			$sLoginTokenKey = 'worpit_login_token';
			$sStoredToken = $oWp->getTransient( $sLoginTokenKey );
			$oWp->deleteTransient( $sLoginTokenKey ); // One chance per token
			if ( empty( $sStoredToken ) || strlen( $sStoredToken ) != 32 ) {
				$oResponse->success = false;
				$oResponse->message = 'Login Token is not present or is not of the correct format.';
				//TODO: Set a code
				$oResponse->code = -1;
				return $oResponse;
			}

			if ( $sStoredToken !== $sRequestToken ) {
				$oResponse->success = false;
				$oResponse->message = 'Login Tokens do not match';
				//TODO: Set a code
				$oResponse->code = -1;
				return $oResponse;
			}

			$sUsername = $oDp->FetchRequest( 'username', false, '' );
			$oUser = $oWp->getUserByUsername( $sUsername );
			if ( empty( $sUsername ) || empty( $oUser ) ) {
				$aUserRecords = version_compare( $oWp->getWordpressVersion(), '3.1', '>=' ) ? get_users( 'role=administrator' ) : array();
				if ( empty( $aUserRecords[0] ) ) {
					$oResponse->success = false;
					$oResponse->message = 'Failed to find an administrator user';
					//TODO: Set a code
					$oResponse->code = -1;
					return $oResponse;
				}
				$oUser = $aUserRecords[0];
			}

			if ( !defined( 'COOKIEHASH' ) ) {
				wp_cookie_constants();
			}

			wp_clear_auth_cookie();
			wp_set_current_user( $oUser->ID, $oUser->get( 'user_login' ) );
			wp_set_auth_cookie( $oUser->ID, true );
			do_action( 'wp_login', $oUser->get( 'user_login' ), $oUser );

			$sRedirectPath = $oDp->FetchGet( 'redirect', '' );
			if ( strlen( $sRedirectPath ) == 0 ) {
				$oWp->redirectToAdmin();
			}
			else {
				$oWp->doRedirect( $sRedirectPath );
			}
			die();
		}
	}

endif;
