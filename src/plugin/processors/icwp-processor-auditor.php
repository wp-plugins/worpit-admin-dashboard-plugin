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

require_once(dirname(__FILE__) . '/base/icwp-processor-basedb.php');

if ( !class_exists('ICWP_Processor_Auditor_CP') ):

class ICWP_Processor_Auditor_CP extends ICWP_Processor_BaseDb_CP {

	const Slug = 'auditor';

	protected $aActionOptionsMap;

	public function __construct( $insOptionPrefix = '' ) {
		parent::__construct( $this->constructStorageKey( $insOptionPrefix, self::Slug ), self::Slug );
		$this->reset();
	}

	/**
	 */
	public function run() {
		parent::run();
		if ( $this->getCanRun() ) {
			$this->setActionsOptionsMap();

			foreach( $this->aActionOptionsMap as $sOption => $aArgs )  {
				list( $sHook ) = $aArgs;
				if ( $this->getOption( $sOption ) ) {
					add_action( $sHook, array($this, 'audit_'.$sHook), 10 );
				}
			}
		}
	}

	public function audit_delete_user() {
		$oUser = $this->getCurrentUser();
		//log event data in db.
	}

	/**
	 * Based on various settings will determine whether the code may be printed
	 *
	 * @return boolean
	 */
	protected function getCanRun() {
		if ( $this->getDoIgnoreCurrentUser() ) {
			return false;
		}
		return true;
	}

	protected function setActionsOptionsMap() {
		$this->aActionOptionsMap = array(
			'do_user_delete' => array( 'delete_user' )
		);
	}
}

endif;