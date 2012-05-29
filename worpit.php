<?php
/*
Plugin Name: Worpit
Plugin URI: http://worpit.com/
Description: Worpit WordPress Plugin for Advanced WordPress Admin, Worpit.com
Version: trunk
Author: Worpit
Author URI: http://worpit.com/
*/

/**
 * Copyright (c) 2011 Worpit <helpdesk@worpit.com>
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
class Worpit_Plugin {
	
	public function __construct() {
		
		if ( is_admin() ) {
			add_action( 'admin_init', array( &$this, 'printAdminNotices') );
		}
	}

	public function printAdminNotices() {
	
		if ( current_user_can( 'manage_options' ) ) {
			echo '
			<div id="worpit_message" class="updated">
				<style>
					#worpit_message p { font-weight:bold; }
					#worpit_message a { text-decoration:underline; }
				</style>
				<p>Sorry, but the Worpit plugin is in active development and not yet finished. You can expect Worpit to be released early June 2012.
				<br/>For now, to remove this message you would be better to deactivate the plugin.
				For the latest information, please go to <a href="http://worpit.com/?src=worpitplugin" target="_blank">worpit.com</a>.</p>
			</div>
		';
		}
	
	}//printAdminNotice

}//Worpit_Plugin
	
new Worpit_Plugin();
