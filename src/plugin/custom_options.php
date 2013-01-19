<?php

/**
 * Copyright (c) 2012 Worpit <helpdesk@worpit.com>
 * All rights reserved.
 *
 * "Worpit" is distributed under the GNU General Public License, Version 2,
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
 *
 */
class Worpit_Plugin_Custom_Options {
	
	protected $m_aCustomOptions;
	
	public function __construct( &$inaOptions ) {
		$this->m_aCustomOptions = $inaOptions;
		$this->implementCustomOptions();
	}
	
	protected function implementCustomOptions() {
	
		//Hide WordPress version
		if ( $this->m_aCustomOptions['sec_hide_wp_version'] == 'Y' ) {
			remove_action('wp_head', 'wp_generator');
		}
		
		//Set random version on script and style query
		if ( $this->m_aCustomOptions['sec_set_random_script_version'] == 'Y' ) {
			add_filter( 'script_loader_src', array( $this, 'replaceScriptVersion'), 15, 1 );
			add_filter( 'style_loader_src', array( $this, 'replaceScriptVersion'), 15, 1 );
		}
	
		//Remove WLmanifest link
		if ( $this->m_aCustomOptions['sec_hide_wlmanifest_link'] == 'Y' ) {
			remove_action( 'wp_head', 'wlwmanifest_link' );
		}
		
		//Remove RSD link
		if ( $this->m_aCustomOptions['sec_hide_rsd_link'] == 'Y' ) {
			remove_action( 'wp_head', 'rsd_link' );
		}
	
	}
	
	public function replaceScriptVersion( $insSrcUrl ) {
		
		global $wp_version;
		
		//not displaying the WordPress version anyway
		if ( substr_count( $insSrcUrl, '?ver='.$wp_version ) == 0 ) {
			return $insSrcUrl;
		}
		
		$sVersion = empty( $this->m_aCustomOptions['sec_random_script_version'] )? rand(): $this->m_aCustomOptions['sec_random_script_version'];
		
		return str_replace( '?ver='.$wp_version, '?ver='.$sVersion, $insSrcUrl );
	}
	
}