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

			$aOldVersionMethods = array(
				'index',
				'query',
				'retrieve',
				'execute'
			);

			if ( in_array( $sApiMethod, $aOldVersionMethods ) ) {
				define( 'WORPIT_DIRECT_API', 1 );
				include_once( dirname(__FILE__).'/transport.php' );
				die();
			}

			$oResponse = new stdClass();
			$oResponse->status = '';
			$oResponse->success = false;
			$oResponse->code = 0;

			if ( $sApiMethod == 'login' ) {
				$this->doLogin( $oResponse );
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
