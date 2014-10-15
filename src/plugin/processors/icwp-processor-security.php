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

require_once(dirname(__FILE__) . '/base/icwp-processor-base.php');

if ( !class_exists('ICWP_Processor_Security_CP') ):

	class ICWP_Processor_Security_CP extends ICWP_Processor_Base_CP {

		const Slug = 'security';

		public function __construct( $insOptionPrefix = '' ) {
			parent::__construct( $this->constructStorageKey( $insOptionPrefix, self::Slug ), self::Slug );
			$this->reset();
		}

		/**
		 */
		public function run() {
			parent::run();
			if ( !$this->getCanRun() ) {

				add_filter( 'user_has_cap', array( $this, 'disableFileEditing' ), 0, 3 );

				if ( $this->getIsOption( 'force_ssl_login', 'Y' ) && function_exists( 'force_ssl_login' ) ) {
					if ( !defined( 'FORCE_SSL_LOGIN' ) ) {
						define( 'FORCE_SSL_LOGIN', true );
					}
					force_ssl_login( true );
				}

				if ( $this->getIsOption( 'force_ssl_admin', 'Y' ) && function_exists( 'force_ssl_admin' ) ) {
					if ( !defined( 'FORCE_SSL_ADMIN' ) ) {
						define( 'FORCE_SSL_ADMIN', true );
					}
					force_ssl_admin( true );
				}

				if ( $this->getIsOption( 'hide_wordpress_generator_tag', 'Y' ) ) {
					remove_action( 'wp_head', 'wp_generator' );
				}

			}
		}

		/**
		 * @param array $aAllCaps
		 * @param array $aCap
		 * @param array $aArgs
		 *
		 * @return array
		 */
		public function disableFileEditing( $aAllCaps, $aCap, $aArgs ) {

			$aEditCapabilities = array( 'edit_themes', 'edit_plugins', 'edit_files' );
			$sRequestedCapability = $aArgs[0];

			if ( !in_array( $sRequestedCapability, $aEditCapabilities ) ) {
				return $aAllCaps;
			}
			$aAllCaps[ $sRequestedCapability ] = false;
			return $aAllCaps;
		}

	}

endif;