<div class="wrap">
	<style type="text/css">
		.well h3 { margin-bottom: 10px; }
		span.the-key {
			background-color: #FFFFFF;
		    border: 1px solid #AAAAAA;
		    border-radius: 4px 4px 4px 4px;
		    font-family: "courier new",sans-serif;
		    letter-spacing: 1px;
		    margin-left: 10px;
		    padding: 5px 8px;
		}
		a#signupLinkWorpit {
			font-size: smaller;
    		font-weight: normal;
    		text-decoration: underline;
		}
		.assigned-state h4 {
			padding-left: 25px;
			margin-bottom: 15px;
		}
		.assigned-state h4#isAssigned {
			color: #00A500;
			background: url("<?php echo $wpv_image_url; ?>pinvoke/tick.png") no-repeat 0 1px transparent;
		}
		.assigned-state h4#isNotAssigned {
			background: url("<?php echo $wpv_image_url; ?>pinvoke/status-amber.png") no-repeat 0 1px transparent;
		}
		.reset-authentication input,
		.enable-handshake-authentication input {
			float: left;
			margin-right: 4px !important;
		}
		.cant-handshake { opacity: 0.5; }
	</style>
	
	<script type="text/javascript">
		jQuery( document ).ready(
			function () {
				jQuery( 'input.confirm-plugin-reset' ).on( 'click',
					function() {
						var $oThis = jQuery( this );
						if ( $oThis.is( ':checked' ) ) {
							jQuery( 'button[name=submit_reset]' ).removeAttr( 'disabled' );
						}
						else {
							jQuery( 'button[name=submit_reset]' ).attr( 'disabled', 'disabled' );
						}
					}
				);
			}
		);
	</script>

	<div class="bootstrap-wpadmin">
		<div class="page-header">
			<a href="http://worpit.com/"><div class="icon32" id="worpit-icon"><br /></div></a>
			<h2>Worpit Client Configuration :: Manage WordPress Better</h2>
		</div>
		
		<div class="row">
			<div class="span12">
				<div class="well">
					<?php
						if ( empty($wpv_key) ) {
							echo '<h3>You need to generate your Access Key - reset your key using the red button below.</h3>';
						} else {
							echo '<h3>The unqiue Worpit Access Key for this site is: <span class="the-key">'.$wpv_key.'</span></h3>';
						}
					?>
					<div class="assigned-state">
						<?php if ( $wpv_assigned === 'Y' ): ?>
							<h4 id="isAssigned">Currently connected to Worpit account: <?php echo $wpv_assigned_to; ?></h4>
							
						<?php else: ?>
							<h4 id="isNotAssigned">Currently waiting for connection from a Worpit account. [ <a href="http://bit.ly/LIjb8h" id="signupLinkWorpit" target="_blank">Don't have a Worpit account? Get It Free!</a> ]</h4>
							<p><strong>Important:</strong> if you don't plan to add this site now, disable this plugin to prevent this site from being added to another Worpit account.</p>
						<?php endif; ?>
					</div>
					
				</div>
			</div>
		</div>
		
		<div class="row">
			<div class="span12">
				<div class="well">
					
					<div class="enable-handshake-authentication">
						<h3>Allow Plugin Hand-Shaking</h3>
						<p>Normally, this option is not turned on because some websites do not work well with it.</p>
						<p>If you do turn it on, it will increase your plugin security - the plugin will "phone home" to Worpit App with every connection to see that the request came from Worpit App.</p>
						<p><strong>Warning:</strong> Do not enable this option until you have synchronized your site with your Worpit account.</p>
						<div class="<?php echo ( $wpv_can_handshake !== 'Y' )? 'cant-handshake' : ''; ?>">
							<form action="<?php echo ( $wpv_can_handshake === 'Y' )? $wpv_form_action : ''; ?>" method="POST" name="form-hand-shaking" id="form-hand-shaking">
								<?php wp_nonce_field( $wpv_nonce_field ); ?>
								<input type="hidden" name="worpit_admin_form_submit" value="1" />
								<input type="hidden" name="worpit_admin_form_submit_handshake" value="1" />
								<label>
									<input
									type="checkbox"
									name="worpit_admin_handshake_enabled"
									value="Y"
									class=""
									id="worpit_admin_handshake_enabled"
									<?php echo ( $wpv_handshake_enabled === 'Y' )? ' checked="checked"' : ''; ?>
									<?php echo ( $wpv_can_handshake !== 'Y' )? ' disabled="disabled"' : ''; ?>
									/> If this box is checked, plugin handshaking will be enabled. If you have problems syncing with Worpit, disable this option.
								</label>
								<button class="btn btn-warning" name="submit" type="submit" <?php echo ( $wpv_can_handshake !== 'Y' )? 'disabled="disabled"' : ''; ?>>Change Option</button>
							</form>
						</div>
					</div>
					
				</div>
			</div>
		</div>
		
		<div class="row">
			<div class="span12">
				<div class="well">
					
					<div class="reset-authentication" name="">
						<h3>Reset Worpit Access Key</h3>
						<p>You can break the connection with Worpit and regenerate a new access key, using the button below</p>
						<p><strong>Warning:</strong> Clicking this button <em>will disconnect this site if it has been added to a Worpit account</em>. <u>Not Recommended</u>.</p>
						<div>
							<form action="<?php echo $wpv_form_action; ?>" method="POST" name="form-reset-auth" id="form-reset-auth">
								<?php wp_nonce_field( $wpv_nonce_field ); ?>
								<input type="hidden" name="worpit_admin_form_submit" value="1" />
								<input type="hidden" name="worpit_admin_form_submit_resetplugin" value="1" />
								<label>
									<input class="confirm-plugin-reset" type="checkbox" value="Y" style="margin-right:10px;" />I'm sure I want to reset the Worpit plugin.
								</label>
								<button class="btn btn-danger" disabled="disabled" name="submit_reset" type="submit">Reset Plugin</button>
							</form>
						</div>
					</div>
					
				</div>
			</div>
		</div>
		
		<div class="row">
			<div class="span12">
				<div class="well">
					
					<div class="send-debug">
						<h3>Send Worpit Debug Information</h3>
						<p>No two WordPress sites are created equal. The sheer variations of configurations are mind-blowing, so writing Worpit to work for everyone is not
						trivial.</p>
						<p>So if your site is having issues with Worpit, don't fret. You can help us out by sending us some information about your configuration using
						the buttons below.</p>
						<p>We <strong>wont collect sensitive information</strong> about you or any passwords etc. We're only interested in information about the plugins you're
						using, your WordPress version, your PHP and server configuration. Further, you will be able to review what will be sent before you send it.</p>
						<div>
							<form action="<?php echo $wpv_form_action; ?>" method="POST" name="form-send-debug" id="form-send-debug">
								<?php wp_nonce_field( $wpv_nonce_field ); ?>
								<input type="hidden" name="worpit_admin_form_submit" value="1" />
								<input type="hidden" name="worpit_admin_form_submit_debug" value="1" />
								<button class="btn btn-inverse" name="submit_gather" type="submit" style="margin-right:8px;">Gather Information</button>
								
								<?php if ( !$wpv_debug_file_url ): ?>
									<button class="btn btn-info" name="view_information" type="submit" style="margin-right:8px;" disabled="disabled">View Information</button>
								<?php else: ?>
									<a href="<?php echo $wpv_debug_file_url; ?>" class="btn btn-info" name="view_information" type="submit" style="margin-right:8px;" target="_blank">View Information</a>
								<?php endif; ?>
								
								<button class="btn btn-success" name="submit_information" type="submit" style="margin-right:8px;" <?php if ( !$wpv_debug_file_url ): ?>disabled="disabled"<?php endif; ?>>Send Debug Information</button>
							</form>
						</div>
					</div>
					
				</div>
			</div>
		</div>
		
		<div class="row">
			<div class="span12">
				<div class="well">
					<div class="row">
						<div class="span11">
							<h2>About Worpit</h2>
							<div>
								<p>Worpit is <strong>completely free</strong> to get started with 1 site and after you signup, you can get a free 3 site trial when you tweet about it -
								<a href="http://bit.ly/LIjb8h" id="signupLinkWorpit" target="_blank">Sign Up for a Worpit WordPress admin account here</a>.</p>
							</div>
							<h3>Worpit Features [<a href="http://worpit.com/features/?src=worpitplugin" target="_blank">full details</a>]</h3>
						</div>
					</div>
					<div class="row">
						<div class="span5">
							<ul>
								<li>Free to get started with one site (optional 3 site trial)</li>
								<li>Manage all your WordPress sites in 1 place</li>
								<li>One-click Updates for WordPress.org Plugins, Themes and WordPress Core</li>
								<li>One-Click login to admin each WordPress website</li>
								<li>Price Lock - the price you pay now, you always pay.</li>
							</ul>
						</div>
						<div class="span6">
							<ul>
								<li>Fully Automated WordPress Installer Tool!</li>
								<li>Complete Worpit Dashboard Access - no standard/pro/business tiers</li>
								<li>Quality Premium WordPress addons only available inside Worpit (coming soon!)</li>
								<li>Access to all future Worpit Dashboard updates!</li>
								<li>Smooth scaling based on your needs</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<div class="row">
			<div class="span12">
				<div class="well">
					<div class="row">
						<div class="span6">
							<h3>Do you like the Worpit system?</h3>
							<p>Help <u>spread the word</u> or check out what else we do ...</p>
						</div>
						<div class="span4">
							<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://worpit.com/?src=worpitplugintweet" data-text="Get Worpit #WordPress Admin Free today!" data-via="Worpit" data-size="large" data-hashtags="worpit">Tweet</a><script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
						</div>
					</div>
						
						<div class="span5">
							<ul>
								<li><a href="http://bit.ly/Mqr4ly" target="_blank"><strong>See Worpit Help &amp; Support page</strong></a>.</li>
								<li><a href="http://wordpress.org/extend/plugins/worpit-admin-dashboard-plugin/" target="_blank">Give Worpit a 5 star rating on WordPress.org!</a></li>
								<!-- <li><a href="http://bit.ly/owxOjJ">Get Quality Wordpress Web Hosting</a></li>  -->
							</ul>
						</div>
					
					<div class="row">
						<div class="span6">
							<ul>
								<li><a href="http://bit.ly/H3tiAu" target="_blank"><strong> Twitter Bootstrap CSS Plugin</strong></a></li>
								<li><a href="http://bit.ly/Lxdugp" target="_blank">Check out Content By Country WordPress Plugin</a></li>
							</ul>
						</div>
					</div>
				</div><!-- / well -->
			</div><!-- / span12 -->
		</div>
		
	</div>
</div>