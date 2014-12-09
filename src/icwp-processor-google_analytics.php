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

if ( !class_exists('ICWP_APP_Processor_GoogleAnalytics_V1') ):

	class ICWP_APP_Processor_GoogleAnalytics_V1 extends ICWP_APP_Processor_Base {

		/**
		 */
		public function run() {
			add_action( $this->getWpHook(), array( $this, 'doPrintGoogleAnalytics' ), 100 );
		}

		/**
		 * @return void|string
		 */
		public function doPrintGoogleAnalytics() {

			if ( $this->getIfIgnoreUser() || strlen( $this->getOption( 'tracking_id' ) ) <= 0 ) {
				return;
			}
			echo ( $this->getIsOption( 'enable_universal_analytics', 'Y' ) ? $this->getAnalyticsCode_Universal() : $this->getAnalyticsCode() );
		}

		/**
		 * @return bool
		 */
		protected function getIfIgnoreUser() {
			$fIgnoreLoggedInUser = $this->getIsOption( 'ignore_logged_in_user', 'Y' );
			$nCurrentUserLevel = $this->loadWpFunctionsProcessor()->getCurrentUserLevel();
			if ( $fIgnoreLoggedInUser && $nCurrentUserLevel >= 0 ) { // logged in
				$nIgnoreFromUserLevel = $this->getOption( 'ignore_from_user_level', 11 );
				if ( $nCurrentUserLevel >= $nIgnoreFromUserLevel ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * @return string
		 */
		public function getAnalyticsCode() {
			$sRaw = "
				<!-- Google Analytics Tracking by iControlWP -->
				<script type=\"text/javascript\">//<![CDATA[
					var _gaq=_gaq||[];
					_gaq.push(['_setAccount','%s']);
					_gaq.push(['_trackPageview']);
					( function() {
						var ga=document.createElement('script');
						ga.type='text/javascript';
						ga.async=true;
						ga.src=('https:'==document.location.protocol?'https://ssl':'http://www')+'.google-analytics.com/ga.js';
						var s=document.getElementsByTagName('script')[0];
						s.parentNode.insertBefore(ga,s);
					})();
				 //]]></script>
			";
			return sprintf( $sRaw, $this->getOption( 'tracking_id' ) );
		}

		/**
		 * @return string
		 */
		public function getAnalyticsCode_Universal() {
			$sRaw = "
				<!-- Google Analytics Tracking by iControlWP -->
				<script type=\"text/javascript\">//<![CDATA[
				  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
				  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
				  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
				  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

				  ga('create', '%s', 'auto');
				  ga('require', 'displayfeatures');
				  ga('send', 'pageview');
				 //]]></script>
			";
			return sprintf( $sRaw, $this->getOption( 'tracking_id' ) );
		}

		/**
		 * @return string
		 */
		protected function getWpHook() {
			if ( $this->getIsOption( 'in_footer', 'Y' ) ) {
				return 'wp_print_footer_scripts';
			}
			return 'wp_head';
		}
	}

endif;

if ( !class_exists('ICWP_APP_Processor_GoogleAnalytics') ):
	class ICWP_APP_Processor_GoogleAnalytics extends ICWP_APP_Processor_GoogleAnalytics_V1 { }
endif;