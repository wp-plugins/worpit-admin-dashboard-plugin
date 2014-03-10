<?php
/**
 * Copyright (c) 2013 iControlWP <support@icontrolwp.com>
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

if ( !class_exists('ICWP_Processor_GoogleAnalytics_CP') ):

class ICWP_Processor_GoogleAnalytics_CP extends ICWP_Processor_Base_CP {

	const Slug = 'googleanalytics';

	public function __construct( $insOptionPrefix = '' ) {
		parent::__construct( $this->constructStorageKey( $insOptionPrefix, self::Slug ), self::Slug );
		$this->reset();
	}

	/**
	 */
	public function run() {
		parent::run();
		if ( $this->getOption( 'do_insert_google_analytics', false ) ) {
			add_action( $this->getWpHook(), array($this, 'printGoogleAnalytics' ), 100 );
		}
	}

	/**
	 * @param bool $infPrint
	 * @return void|string
	 */
	public function printGoogleAnalytics( $infPrint = true ) {
		if ( !$this->canPrintAnalytics() ) {
			return '';
		}

		$sCode = $this->getAnalyticsCode();
		if ( $infPrint ) {
			echo $sCode;
		}
		return $sCode;
	}

	/**
	 * Based on various settings will determine whether the code may be printed
	 *
	 * @return boolean
	 */
	protected function canPrintAnalytics() {
		$sId = $this->getOption('tracking_id');
		if ( empty( $sId ) ) {
			return false;
		}

		$fIgnoreLoggedInUser = $this->getOption('ignore_logged_in_user', false);
		if ( $fIgnoreLoggedInUser && $this->getIsUserLoggedIn() ) {
			return false;
		}

		$nIgnoreFromUserLevel = $this->getOption( 'ignore_from_user_level', 11 );
		if ( $this->getCurrentUserLevel() >= $nIgnoreFromUserLevel ) {
			return false;
		}

		return true;
	}

	/**
	 * @return string
	 */
	protected function getAnalyticsCode() {
		$sRaw = "<!-- Google Analytics Tracking by iControlWP --><script>
			var _gaq=_gaq||[];
			_gaq.push(['_setAccount','%s']);
			_gaq.push(['_trackPageview']);
			(function(){var ga=document.createElement('script');ga.type='text/javascript';ga.async=true;ga.src=('https:'==document.location.protocol?'https://ssl':'http://www')+'.google-analytics.com/ga.js';var s=document.getElementsByTagName('script')[0];s.parentNode.insertBefore(ga,s);})();
		</script>";
		return preg_replace( '/[\n\r\s]+/', ' ', sprintf( $sRaw, $this->getOption('tracking_id') ) );
	}

	protected function getWpHook() {
		if ( $this->getOption('in_footer') ) {
			return 'wp_print_footer_scripts';
		}
		return 'wp_head';
	}
}

endif;