<?php

/**
 * Copyright (c) 2013 iControlWP <support@icontrolwp.com>
 * All rights reserved.
 *
 * "iControlWP" (previously "Worpit") is distributed under the GNU General Public License, Version 2,
 * June 1991. Copyright (C) 1989, 1991 Free Software Foundation, Inc., 51 Franklin
 * St, Fifth Floor, Boston, MA 02110, USA
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

require_once( dirname(__FILE__).'/system-base.php' );

class ICWP_Auditor extends ICWP_System_Base {

	public function __construct() {
		$this->sOptionsKey = 'auditor_system_options';
		parent::__construct();
	}

	/**
	 * Does all the setup of the individual processors
	 *
	 * @return bool
	 */
	public function run() {
		if ( !$this->getIsSystemEnabled() ) {
			return false;
		}
		$oProcessor = $this->getAuditorProcessor();
		$oProcessor->run();
		return true;
	}

	/**
	 * @param string $insTrackingId
	 * @return bool
	 */
	public function setTrackingId( $insTrackingId ) {
		return $this->updateSystemOptions(
			array(
				'tracking_id' => $insTrackingId
			)
		);
	}

	/**
	 * @return void
	 */
	protected function includeProcessors() {
		require_once( dirname(__FILE__).'/processors/icwp-processor-auditor.php' );
	}

	/**
	 * @return void
	 */
	protected function validateOptions() {
		$aMinimumDefaults = array(
			'enabled'						=> false,
			'ignore_logged_in_user'			=> false,
			'ignore_from_user_level'		=> 11, //max is 10 so by default ignore no-one
		);
		$this->aOptions = array_merge( $aMinimumDefaults, $this->aOptions );
	}

	/**
	 * @return ICWP_Processor_GoogleAnalytics_CP
	 */
	protected function getAuditorProcessor() {
		$this->includeProcessors();
		$oProcessor = new ICWP_Processor_Auditor_CP( self::Prefix );
		$oProcessor->setOptions( $this->getSystemOptions() );
		return $oProcessor;
	}
}

return new ICWP_GoogleAnalytics();