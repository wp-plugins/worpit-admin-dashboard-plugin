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

class ICWP_Compatibility extends ICWP_System_Base {

	/**
	 * @var ICWP_Processor_Compatibility_CP
	 */
	protected $oProcessor;

	public function __construct() {
		$this->sOptionsKey = 'compatibility_system_options';
		parent::__construct();
	}

	public function run() {

		if ( !$this->getIsSystemEnabled() ) {
			return false;
		}
		$oCompProcessor = $this->getCompatibilityProcessor();
		$oCompProcessor->run();
		return true;
	}

	/**
	 * @return ICWP_Processor_Compatibility_CP
	 */
	public function & getCompatibilityProcessor() {
		if ( !is_null( $this->oProcessor ) ) {
			return $this->oProcessor;
		}
		$this->includeProcessors();
		$this->oProcessor = new ICWP_Processor_Compatibility_CP( self::Prefix );
		$this->oProcessor->setOptions( $this->getSystemOptions() );
		return $this->oProcessor;

	}
	/**
	 * @return void
	 */
	protected function includeProcessors() {
		require_once( dirname(__FILE__).'/processors/icwp-processor-compatibility.php' );
	}
}

return new ICWP_Compatibility();