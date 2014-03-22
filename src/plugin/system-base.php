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

class ICWP_System_Base {

	/**
	 * @var string
	 */
	const Prefix = 'icwp_';

	/**
	 * @var string
	 */
	protected $sOptionsKey = 'system_options';

	/**
	 * @var array
	 */
	protected $aOptions;

	public function __construct() {
		$this->getSystemOptions();
	}

	/**
	 * Does all the setup of the individual processors
	 *
	 * @return bool
	 */
	public function run() { }

	/**
	 */
	public function loadSystemOptions() {
		$this->getSystemOptions();
	}

	/**
	 * @return array
	 */
	public function getSystemOptions() {
		if ( !isset( $this->aOptions ) ) {
			$this->aOptions = get_option( $this->getOptionsKey(), array() );
			$this->validateOptions();
		}
		return $this->aOptions;
	}

	/**
	 * Use this to update 1 or more options at a time.
	 *
	 * @param array $inaOptions
	 * @return boolean
	 */
	public function updateSystemOptions( $inaOptions = array() ) {
		$this->aOptions = array_merge( $this->getSystemOptions(), $inaOptions );
		return $this->saveSystemOptions();
	}

	/**
	 * Use this to update 1 or more options at a time.
	 *
	 * @param string $insKey
	 * @param mixed $inmValue
	 * @return boolean
	 */
	public function updateSystemOption( $insKey, $inmValue ) {
		$this->loadSystemOptions();
		if ( isset( $this->aOptions[$insKey] ) && $this->aOptions[$insKey] == $inmValue ) {
			return true;
		}
		return $this->updateSystemOptions( array( $insKey => $inmValue ) );
	}

	/**
	 * Use this to completely empty/clear the options
	 *
	 * @return boolean
	 */
	public function clearSystemOptions() {
		$this->aOptions = array();
		return $this->saveSystemOptions();
	}

	/**
	 * @return boolean
	 */
	protected function saveSystemOptions() {
		$this->validateOptions();
		return update_option( $this->getOptionsKey(), $this->aOptions );
	}

	/**
	 * @param string $insOptionKey
	 * @param mixed $insDefault
	 * @return mixed
	 */
	public function getOption( $insOptionKey, $insDefault = '' ) {
		if ( !isset( $this->aOptions ) || !is_array( $this->aOptions ) ) {
			$this->loadSystemOptions();
		}
		if ( !isset( $this->aOptions[$insOptionKey] ) ) {
			return $insDefault;
		}
		return $this->aOptions[$insOptionKey];
	}

	/**
	 * It's now just an alias.
	 *
	 * @param string $insOptionKey
	 * @param mixed $inmValue
	 * @return mixed
	 */
	public function setOption( $insOptionKey, $inmValue = false ) {
		$this->updateSystemOption( $insOptionKey, $inmValue );
	}

	/**
	 * @return boolean
	 */
	public function getIsSystemEnabled() {
		return $this->getOption( 'enabled', false );
	}

	/**
	 * @param bool $infEnabled
	 * @return boolean
	 */
	public function setIsSystemEnabled( $infEnabled = true ) {
		return $this->updateSystemOption( 'enabled', $infEnabled );
	}

	/**
	 * @return void
	 */
	protected function includeProcessors() { }

	/**
	 * @return void
	 */
	protected function validateOptions() {
		$aMinimumDefaults = array(
			'enabled'					=> false,
			'ignore_logged_in_user'		=> false,
			'ignore_from_user_level'	=> 11, //max is 10 so by default ignore no-one
		);
		if ( !is_array( $this->aOptions ) ) {
			$this->aOptions = array();
		}
		$this->aOptions = array_merge( $aMinimumDefaults, $this->aOptions );
	}

	protected function getOptionsKey() {
		return self::Prefix.$this->sOptionsKey;
	}
}