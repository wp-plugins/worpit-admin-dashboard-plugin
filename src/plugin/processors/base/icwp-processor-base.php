<?php
/**
 * Copyright (c) 2013 iControlWP <support@icontrolwp.com>
 * All rights reserved.
 * 
 * Version: 2013-08-27-B
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
 *
 */

if ( !class_exists('ICWP_Processor_Base_CP') ):

class ICWP_Processor_Base_CP {
	
	const PcreDelimiter = '/';
	const LOG_MESSAGE_LEVEL_INFO = 0;
	const LOG_MESSAGE_LEVEL_WARNING = 1;
	const LOG_MESSAGE_LEVEL_CRITICAL = 2;

	const LOG_CATEGORY_DEFAULT = 0;
	const LOG_CATEGORY_FIREWALL = 1;
	const LOG_CATEGORY_LOGINPROTECT = 2;

	/**
	 * @var string
	 */
	protected $m_sStorageKey;

	/**
	 * @var boolean
	 */
	protected $m_fNeedSave;

	/**
	 * @var array
	 */
	protected $m_aLog;
	/**
	 * @var array
	 */
	protected $m_aLogMessages;
	
	/**
	 * @var integer
	 */
	protected $m_nRequestIp;

	/**
	 * @var boolean
	 */
	protected $m_fLoggingEnabled;
	
	/**
	 * @var ICWP_EmailProcessor
	 */
	protected $m_oEmailHandler;
	
	/**
	 * @var array
	 */
	protected $aOptions;
	
	public function __construct( $insStorageKey ) {
		$this->m_sStorageKey = $insStorageKey;
		$this->m_fNeedSave = true;
		$this->reset();
	}

	/**
	 * Resets the object values to be re-used anew
	 */
	public function reset() {
		$this->m_nRequestIp = $this->getVisitorIpAddress();
		$this->resetLog();
	}
	
	/**
	 * Override to set what this processor does when it's "run"
	 */
	public function run() { }

	/**
	 * @return boolean
	 */
	protected function getCanRun() {
		if ( $this->getDoIgnoreCurrentUser() ) {
			return false;
		}
		return true;
	}
	
	/**
	 * Ensure that when we save the object later, it doesn't save unnecessary data.
	 */
	public function doPreStore() {
		unset( $this->m_oEmailHandler );
	}

	/**
	 * @return boolean
	 */
	public function store() {
		$this->doPreStore();
		if ( $this->getNeedSave() ) {
			$this->setNeedSave( false );
			return update_option( $this->m_sStorageKey, $this );
		}
		return true;
	}

	/**
	 * @return boolean
	 */
	public function deleteStore() {
		return delete_option( $this->m_sStorageKey );
	}
	
	/**
	 * @return boolean
	 */
	public function getNeedSave() {
		return $this->m_fNeedSave;
	}
	
	/**
	 * @param boolean $infNeedSave
	 */
	public function setNeedSave( $infNeedSave = true ) {
		$this->m_fNeedSave = $infNeedSave;
	}

	/**
	 * @param array $inaOptions
	 */
	public function setOptions( &$inaOptions ) {
		$this->aOptions = $inaOptions;
	}

	/**
	 * Resets the log
	 */
	public function resetLog() {
		$this->m_aLogMessages = array();
	}
	
	/**
	 * @param boolean $infEnableLogging
	 */
	public function setLogging( $infEnableLogging = true ) {
		$this->m_fLoggingEnabled = $infEnableLogging;
	}
	
	/**
	 * Builds and returns the full log.
	 * 
	 * @return array (associative)
	 */
	public function getLogData() {
		
		if ( $this->m_fLoggingEnabled  ) {
			$this->m_aLog = array(
				'messages'			=> serialize( $this->m_aLogMessages ),
			);
		}
		else {
			$this->m_aLog = false;
		}
		
		return $this->m_aLog;
	}
	
	/**
	 * @param string $insLogMessage
	 * @param int $insMessageType
	 */
	public function writeLog( $insLogMessage = '', $insMessageType = self::LOG_MESSAGE_LEVEL_INFO ) {
		if ( !is_array( $this->m_aLogMessages ) ) {
			$this->resetLog();
		}
		$this->m_aLogMessages[] = array( $insMessageType, $insLogMessage );
	}
	/**
	 * @param string $insLogMessage
	 */
	public function logInfo( $insLogMessage ) {
		$this->writeLog( $insLogMessage, self::LOG_MESSAGE_LEVEL_INFO );
	}
	/**
	 * @param string $insLogMessage
	 */
	public function logWarning( $insLogMessage ) {
		$this->writeLog( $insLogMessage, self::LOG_MESSAGE_LEVEL_WARNING );
	}
	/**
	 * @param string $insLogMessage
	 */
	public function logCritical( $insLogMessage ) {
		$this->writeLog( $insLogMessage, self::LOG_MESSAGE_LEVEL_CRITICAL );
	}

	/**
	 * Cloudflare compatible.
	 *
	 * @param bool $infAsLong
	 * @return integer - visitor IP Address as IP2Long
	 */
	public function getVisitorIpAddress( $infAsLong = true ) {
		require_once(dirname(__FILE__) . '/icwp-processor-data.php');
		return ICWP_Processor_Data_CP::GetVisitorIpAddress( $infAsLong );
	}

	/**
	 * @param array $inaIpList
	 * @param string $innIpAddress
	 * @param string $outsLabel
	 * @return boolean
	 */
	public function isIpOnlist( $inaIpList, $innIpAddress = '', &$outsLabel = '' ) {

		if ( empty( $innIpAddress ) || !isset( $inaIpList['ips'] ) ) {
			return false;
		}
	
		$outsLabel = '';
		foreach( $inaIpList['ips'] as $mWhitelistAddress ) {
			
			$aIps = $this->parseIpAddress( $mWhitelistAddress );
			if ( count( $aIps ) === 1 ) { //not a range
				if ( $innIpAddress == $aIps[0] ) {
					$outsLabel = $inaIpList['meta'][ md5( $mWhitelistAddress ) ];
					return true;
				}
			}
			else if ( count( $aIps ) == 2 ) {
				if ( $aIps[0] <= $innIpAddress && $innIpAddress <= $aIps[1] ) {
					$outsLabel = $inaIpList['meta'][ md5( $mWhitelistAddress ) ];
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * @param string $insIpAddress	- an IP or IP address range in LONG format.
	 * @return array				- with 1 ip address, or 2 addresses if it is a range.
	 */
	protected function parseIpAddress( $insIpAddress ) {
		
		$aIps = array();
		
		if ( empty($insIpAddress) ) {
			return $aIps;
		}
		
		// offset=1 in the case that it's a range and the first number is negative on 32-bit systems
		$mPos = strpos( $insIpAddress, '-', 1 );

		if ( $mPos === false ) { //plain IP address
			$aIps[] = $insIpAddress;
		}
		else {
			//we remove the first character in case this is '-'
			$aParts = array( substr( $insIpAddress, 0, 1 ), substr( $insIpAddress, 1 ) );
			list( $sStart, $sEnd ) = explode( '-', $aParts[1], 2 );
			$aIps[] = $aParts[0].$sStart;
			$aIps[] = $sEnd;
		}
		return $aIps;
	}
	
	/**
	 * We force PHP to pass by reference in case of older versions of PHP (?)
	 * 
	 * @param ICWP_EmailProcessor $inoEmailHandler
	 */
	public function setEmailHandler( &$inoEmailHandler ) {
		$this->m_oEmailHandler = $inoEmailHandler;
	}
	
	/**
	 * @param string $insEmailSubject	- message subject
	 * @param array $inaMessage			- message content
	 * @return boolean					- message sending success (remember that if throttled, returns true)
	 */
	public function sendEmail( $insEmailSubject, $inaMessage ) {
		return $this->m_oEmailHandler->sendEmail( $insEmailSubject, $inaMessage );
	}
	
	/**
	 * @param string $insEmailAddress	- message recipient
	 * @param string $insEmailSubject	- message subject
	 * @param array $inaMessage			- message content
	 * @return boolean					- message sending success (remember that if throttled, returns true)
	 */
	public function sendEmailTo( $insEmailAddress, $insEmailSubject, $inaMessage ) {
		return $this->m_oEmailHandler->sendEmailTo( $insEmailAddress, $insEmailSubject, $inaMessage );
	}

	/**
	 * @param string $insOptionKey
	 * @param mixed $insDefault
	 * @return mixed
	 */
	protected function getOption( $insOptionKey, $insDefault = '' ) {
		if ( !isset( $this->aOptions ) || !is_array( $this->aOptions ) || !isset( $this->aOptions[$insOptionKey] ) ) {
			return $insDefault;
		}
		return $this->aOptions[$insOptionKey];
	}

	/**
	 * Checks the $inaData contains valid key values as laid out in $inaChecks
	 *
	 * @param array $inaData
	 * @param array $inaChecks
	 * @return boolean
	 */
	protected function validateParameters( $inaData, $inaChecks ) {
	
		if ( !is_array( $inaData ) ) {
			return false;
		}
	
		foreach( $inaChecks as $sCheck ) {
			if ( !array_key_exists( $sCheck, $inaData ) || empty( $inaData[ $sCheck ] ) ) {
				return false;
			}
		}
		return true;
	}

	protected function constructStorageKey( $insPrefix = '', $insSlug = '' ) {
		$sTemplate = '%s%s_processor';
		return sprintf($sTemplate, $insPrefix, $insSlug );
	}

	/**
	 * Override this to provide custom cleanup.
	 */
	public function deleteAndCleanUp() {
		$this->deleteStore();
	}

	/**
	 * @return bool
	 */
	protected function getIsUserLoggedIn() {
		return function_exists('is_user_logged_in')? is_user_logged_in() : false;
	}

	/**
	 * @return integer
	 */
	protected function getCurrentUser() {
		return function_exists('wp_get_current_user')? wp_get_current_user() : null;
	}

	/**
	 * @return integer
	 */
	protected function getCurrentUserLevel() {
		$oUser = $this->getCurrentUser();
		return ( is_object($oUser) && ($oUser instanceof WP_User) )? $oUser->user_level : -1;
	}

	/**
	 * By default, if no settings are set, returns false.
	 *
	 * @return bool
	 */
	protected function getDoIgnoreCurrentUser() {
		$fIgnoreLoggedInUser = $this->getOption( 'ignore_logged_in_user', false );
		if ( $fIgnoreLoggedInUser && $this->getIsUserLoggedIn() ) {
			$nIgnoreFromUserLevel = $this->getOption( 'ignore_from_user_level', 11 );
			if ( $this->getCurrentUserLevel() >= $nIgnoreFromUserLevel ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return bool
	 */
	protected function getIsCurrentUserAdmin() {
		return $this->getIsUserLoggedIn() && current_user_can( 'manage_options' );
	}
}

endif;