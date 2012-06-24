<div class="wrap">
	<style type="text/css">
		.well h3 {
			margin-bottom: 10px;
		}
	
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
		.reset-authentication input {
			float: left;
		}
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
			<h2>Worpit WordPress Admin Client Configuration</h2>
		</div>
		
		<div class="row">
			<div class="span12">
				<div class="well">
					<h3>The unqiue Worpit Authentication Key for this site is: <span class="the-key"><?php echo $wpv_key; ?></span></h3>
					
					<div class="assigned-state">
						<?php if ( $wpv_assigned === 'Y' ): ?>
							<h4 id="isAssigned">Assigned to Worpit account: <?php echo $wpv_assigned_to; ?></h4>
							
						<?php else: ?>
							<h4 id="isNotAssigned">Currently waiting assignment request from a Worpit account. [ <a href="http://bit.ly/LIjb8h" id="signupLinkWorpit" target="_blank">Don't have a Worpit account? Get It Free!</a> ]</h4>
							<p><strong>Important:</strong> if you don't intend to add this site now, disable this plugin to prevent this site being added to another Worpit account.</p>
						<?php endif; ?>
					</div>
					
				</div>
			</div>
		</div>
		
		<div class="row">
			<div class="span12">
				<div class="well">
					
					<div class="reset-authentication">
						<h3>Reset Worpit Authentication Key</h3>
						<p>In case you want to regenerate this key, for whatever reason, you may do so using the button below</p>
						<p><strong>Warning:</strong> Clicking this button <em>will disconnect this site if it has been assigned to a Worpit account</em>. <u>Not Recommended</u>.</p>
						<div>
							<form action="<?php echo $wpv_form_action; ?>" method="POST">
								<?php wp_nonce_field( $wpv_nonce_field ); ?>
								<input type="hidden" name="worpit_admin_form_submit" value="1" />
								<label>
									<input class="confirm-plugin-reset" type="checkbox" value="Y" style="margin-right:10px;" /> I'm sure I want to reset the Worpit plugin.
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
					<div class="row">
						<div class="span11">
							<h2>About Worpit</h2>
							<div>
								<p>Worpit is <strong>completely free</strong> when you want to admin up to 3 WordPress websites -
								<a href="http://bit.ly/LIjb8h" id="signupLinkWorpit" target="_blank">Sign Up for a Worpit WordPress admin account here</a>.</p>
							</div>
							<h3>Worpit Features [<a href="http://worpit.com/features/?src=worpitplugin" target="_blank">full details</a>]</h3>
						</div>
					</div>
					<div class="row">
						<div class="span5">
							<ul>
								<li>3 Free Sites For Life</li>
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