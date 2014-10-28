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

class ICWP_WhiteLabel extends ICWP_System_Base {

	public function __construct() {
		$this->sOptionsKey = 'whitelabel_system_options';
		parent::__construct();
	}

	public function run() {

		if ( !$this->getIsSystemEnabled() ) {
			return true;
		}
		add_filter( $this->getController()->doPluginPrefix( 'plugin_labels' ), array( $this, 'doRelabelPlugin' ) );
	}

	/**
	 * @param array $aPluginLabels
	 *
	 * @return array
	 */
	public function doRelabelPlugin( $aPluginLabels ) {

		$aPluginLabels = array_merge(
			$aPluginLabels,
			$this->getSystemOptions()
		);

		// these are the old white labelling keys which will be replaced upon final release of white labelling.
		if ( !empty( $aPluginLabels['service_name'] ) ) {
			$aPluginLabels['Name'] = $aPluginLabels['service_name'];
			$aPluginLabels['Title'] = $aPluginLabels['service_name'];
			$aPluginLabels['Author'] = $aPluginLabels['service_name'];
			$aPluginLabels['AuthorName'] = $aPluginLabels['service_name'];
		}
		if ( !empty( $aPluginLabels['tag_line'] ) ) {
			$aPluginLabels['Description'] = $aPluginLabels['tag_line'];
		}
		if ( !empty( $aPluginLabels['plugin_home_url'] ) ) {
			$aPluginLabels['PluginURI'] = $aPluginLabels['plugin_home_url'];
			$aPluginLabels['AuthorURI'] = $aPluginLabels['plugin_home_url'];
		}
		return $aPluginLabels;
	}
}

return new ICWP_WhiteLabel();