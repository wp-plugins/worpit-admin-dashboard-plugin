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

require_once( dirname(__FILE__).ICWP_DS.'icwp-optionshandler-base.php' );

if ( !class_exists('ICWP_APP_FeatureHandler_Compatibility') ):

	class ICWP_APP_FeatureHandler_Compatibility extends ICWP_APP_FeatureHandler_Base {

		/**
		 * @var ICWP_APP_Processor_Compatibility
		 */
		protected $oFeatureProcessor;

		/**
		 * @return ICWP_APP_Processor_Compatibility|null
		 */
		protected function loadFeatureProcessor() {
			if ( !isset( $this->oFeatureProcessor ) ) {
				require_once( $this->getController()->getPath_SourceFile( sprintf( 'icwp-processor-%s.php', $this->getFeatureSlug() ) ) );
				$this->oFeatureProcessor = new ICWP_APP_Processor_Compatibility( $this );
			}
			return $this->oFeatureProcessor;
		}

		public function doPrePluginOptionsSave() { }
	}

endif;