<?php
$sServiceName = $wpv_label_data['service_name'];
$sUrlServiceHome = $wpv_label_data['plugin_home_url'];
$sUrlServiceHomeHelp = 'http://icwp.io/help';
$sUrlServiceHomeFeatures = 'http://icwp.io/features';

$sUrlPlugin_TwitterBootstrap = 'http://icwp.io/pluginbootstrap';
$sUrlPlugin_WpPlugins = 'http://icwp.io/wpplugins';

$fWhitelabelled = ($sServiceName != 'iControlWP');

function printOptionsPageHeader( $insServiceName, $insUrl, $insSection = '' ) {

	if ( $insServiceName == 'iControlWP' ) {
		$sIconLink = 'http://icwp.io/2k';
		$sTitleLink = 'http://icwp.io/3f';
	}
	else {
		$sIconLink = $insUrl;
		$sTitleLink = $insUrl;
	}

	$sLinkedIcwp = sprintf( '<a href="%s" target="_blank">%s</a>', $sTitleLink, $insServiceName );
	echo '<div class="page-header">';
	echo sprintf( '<h2><a id="pluginlogo_32" class="header-icon32" href="%s" target="_blank"></a>', $sIconLink );
	$sBaseTitle = sprintf( '%s Client Configuration', $sLinkedIcwp );
	if ( !empty($insSection) ) {
		echo sprintf( '%s :: %s', $insSection, $sBaseTitle );
	}
	else {
		echo $sBaseTitle;
	}
	echo '</h2></div>';
}
?>
<style>
	#pluginlogo_32 {
		background: url( "<?php echo $wpv_label_data['icon_url_32x32']; ?>" ) no-repeat 0px 3px transparent;
	}
</style>
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
		a#signupLinkIcwp {
			font-size: smaller;
    		font-weight: normal;
    		text-decoration: underline;
		}
		.assigned-state {
		}
		.assigned-state #isAssigned {
			color: #00A500;
			background: url("<?php echo $wpv_image_url; ?>pinvoke/tick.png") no-repeat 0 50% transparent;
			padding-left: 25px;
			margin-bottom: 15px;
		}
		.assigned-state #isNotAssigned {
			background: url("<?php echo $wpv_image_url; ?>pinvoke/status-amber.png") no-repeat 0 1px transparent;
			padding-left: 25px;
			margin-bottom: 15px;
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
		function icwp_formAddSiteSubmit() {
			var $elemSubmit = jQuery( "button[name=icwp_add_remotely_submit]" );
			$elemSubmit.html( "Please wait, attempting to add site - please do not reload this page." );
			$elemSubmit.attr( "disabled", "disabled" );
			
			var form = jQuery( "#icwpform-remote-add-site" ).submit();
		}
	</script>
	
	<div class="bootstrap-wpadmin">
	
		<?php echo printOptionsPageHeader( $sServiceName, $sUrlServiceHome ); ?>

		<div class="row">
			<div class="span12">
				<div class="well">
					<?php
						if ( empty($wpv_key) ) {
							echo '<h3>You need to generate your Access Key - reset your key using the red button below.</h3>';
						}
					?>
					<div class="assigned-state">
						<?php if ( $wpv_assigned === 'Y' ): ?>
							<h3 id="isAssigned"><?php echo sprintf( 'Currently connected to %s account.%s', "<u>$sServiceName</u>", ($fWhitelabelled? '' : " ($wpv_assigned_to)") ); ?></h3>

						<?php else: ?>
							<h3>The unique <?php echo $sServiceName; ?> Access Key for this site is: <span class="the-key"><?php echo $wpv_key; ?></span></h3>

							<h4 id="isNotAssigned">Currently waiting for connection from a <?php echo $sServiceName; ?> account. [ <a href="<?php echo $sUrlServiceHome; ?>" id="signupLinkIcwp" target="_blank">Don't have a <?php echo $sServiceName; ?> account? Get it today!</a> ]</h4>
							<p><strong>Important:</strong> if you don't plan to add this site now, disable this plugin to prevent this site from being added to another <?php echo $sServiceName; ?> account.</p>
						<?php endif; ?>
					</div>
					
				</div>
			</div>
		</div>
		<?php if ( !$wpv_is_linked ) : ?>
		<div class="row">
			<div class="span12">
				<div class="well">
					<h3>Remotely add site to <?php echo $sServiceName; ?> account</h3>
					<p>You may add your site to your <?php echo $sServiceName; ?> from here, or from within your <?php echo $sServiceName; ?> Dashboard. Both methods are supported and secure.</p>
					<p>Note: If this doesn't work, your web host probably has restrictions on outgoing web connections. Please try adding this site from you <?php echo $sServiceName; ?> dashboard.</p>
					<form action="<?php echo $wpv_form_action; ?>" method="POST" name="icwpform-remote-add-site" id="form-remote-add-site" class="">
						<?php wp_nonce_field( $wpv_nonce_field ); ?>
						<input type="hidden" name="icwp_admin_form_submit" value="1" />
						<input type="hidden" name="icwp_admin_form_submit_add_remotely" value="1" />
						<fieldset>
							<legend style="margin-bottom: 8px;">Remote Add Site</legend>
							<label for="_account_auth_key"><?php echo $sServiceName; ?> Unique Account Authentication Key:</label>
							<input name="account_auth_key" type="text" class="span6" id="_account_auth_key" />
							<label for="_account_email_address"><?php echo $sServiceName; ?> Account Email Address:</label>
							<input name="account_email_address" type="text" class="span6" id="_account_email_address"/>
						</fieldset>
						<button class="btn" name="icwp_add_remotely_submit" type="submit" onclick="icwp_formAddSiteSubmit()" >Add Site</button>
					</form>
				</div>
			</div>
		</div>
		<?php endif; ?>
		
		<div class="row">
			<div class="span12">
				<div class="well">
					
					<div class="enable-handshake-authentication">
						<h3>Allow Secure Plugin Hand-Shaking</h3>
						<p>Normally, this option is not turned on because some websites do not support it.</p>
						<p>If you do turn it on, it will greatly increase your security - the plugin will "phone home" to the <?php echo $sServiceName; ?> Dashboard with every connection to ensure the request originated from <?php echo $sServiceName; ?>.</p>
						<p><strong>Warning:</strong> Do not enable this option until you have synchronized your site with your <?php echo $sServiceName; ?> account.</p>
						<div class="<?php echo ( $wpv_can_handshake !== 'Y' )? 'cant-handshake' : ''; ?>">
							<form action="<?php echo ( $wpv_can_handshake === 'Y' )? $wpv_form_action : ''; ?>" method="POST" name="form-hand-shaking" id="form-hand-shaking">
								<?php wp_nonce_field( $wpv_nonce_field ); ?>
								<input type="hidden" name="icwp_admin_form_submit" value="1" />
								<input type="hidden" name="icwp_admin_form_submit_handshake" value="1" />
								<label>
									<input
									type="checkbox"
									name="icwp_admin_handshake_enabled"
									value="Y"
									class=""
									id="icwp_admin_handshake_enabled"
									<?php echo ( $wpv_handshake_enabled === 'Y' )? ' checked="checked"' : ''; ?>
									<?php echo ( $wpv_can_handshake !== 'Y' )? ' disabled="disabled"' : ''; ?>
									/> If this box is checked, plugin handshaking will be enabled. If you have problems syncing with <?php echo $sServiceName; ?>, disable this option.
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
						<h3>Reset <?php echo $sServiceName; ?> Access Key</h3>
						<p>You can break the connection with <?php echo $sServiceName; ?> and regenerate a new access key, using the button below</p>
						<p><strong>Warning:</strong> Clicking this button <em>will disconnect this site if it has been added to a <?php echo $sServiceName; ?> account</em>. <u>Not Recommended</u>.</p>
						<div>
							<form action="<?php echo $wpv_form_action; ?>" method="POST" name="form-reset-auth" id="form-reset-auth">
								<?php wp_nonce_field( $wpv_nonce_field ); ?>
								<input type="hidden" name="icwp_admin_form_submit" value="1" />
								<input type="hidden" name="icwp_admin_form_submit_resetplugin" value="1" />
								<label>
									<input class="confirm-plugin-reset" type="checkbox" value="Y" style="margin-right:10px;" />I'm sure I want to reset the <?php echo $sServiceName; ?> plugin.
								</label>
								<button class="btn btn-danger" disabled="disabled" name="submit_reset" type="submit">Reset Plugin</button>
							</form>
						</div>
					</div>
					
				</div>
			</div>
		</div>

		<?php if ( !$fWhitelabelled ) : ?>

		<div class="row">
			<div class="span12">
				<div class="well">
					
					<div class="send-debug">
						<h3>Send <?php echo $sServiceName; ?> Site Debug</h3>
						<p>No two WordPress sites are created equal. The sheer variations of configurations are mind-blowing, so writing <?php echo $sServiceName; ?> to work for everyone is not
						trivial.</p>
						<p>So if your site is having issues with <?php echo $sServiceName; ?>, don't fret. You can help us out by sending us some information about your configuration using
						the buttons below.</p>
						<p>We <strong>wont collect sensitive information</strong> about you or any passwords etc. We're only interested in information about the plugins you're
						using, your WordPress version, your PHP and server configuration. Further, you will be able to review what will be sent before you send it.</p>
						<div>
							<form action="<?php echo $wpv_form_action; ?>" method="POST" name="form-send-debug" id="form-send-debug">
								<?php wp_nonce_field( $wpv_nonce_field ); ?>
								<input type="hidden" name="icwp_admin_form_submit" value="1" />
								<input type="hidden" name="icwp_admin_form_submit_debug" value="1" />
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
							<h2>About <?php echo $sServiceName; ?></h2>
							<div>
								<p><?php echo $sServiceName; ?> is <strong>completely free</strong> to get started with an unlimited sites 30 day trial -
								<a href="<?php echo $sUrlServiceHome; ?>" id="signupLinkIcwp" target="_blank">Sign Up for a <?php echo $sServiceName; ?> account here</a>.</p>
							</div>
							<h3><?php echo $sServiceName; ?> Features [<a href="<?php echo $sUrlServiceHomeFeatures; ?>" target="_blank">full details</a>]</h3>
						</div>
					</div>
					<div class="row">
						<div class="span5">
							<ul>
								<li>Free to get started with with unlimited 30 day trial</li>
								<li>Manage all your WordPress sites in 1 place</li>
								<li>One-click Updates for WordPress.org Plugins, Themes and WordPress Core</li>
								<li>One-Click login to admin each WordPress website</li>
								<li>True pay-as-you-go pricing.</li>
							</ul>
						</div>
						<div class="span6">
							<ul>
								<li>Fully Automated WordPress Installer Tool!</li>
								<li>Complete <?php echo $sServiceName; ?> Dashboard Access - no standard/pro/business tiers</li>
								<li>Access to all future <?php echo $sServiceName; ?> Dashboard updates!</li>
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
							<h3>Do you like the <?php echo $sServiceName; ?> system?</h3>
							<p>Help <u>spread the word</u> or check out what else we do ...</p>
						</div>
						<div class="span4">
							<a href="https://twitter.com/share" class="twitter-share-button" data-url="<?php echo $sUrlServiceHome; ?>" data-text="Get <?php echo $sServiceName; ?> #WordPress Admin Free Trial today!" data-via="<?php echo $sServiceName; ?>" data-size="large">Tweet</a><script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
						</div>
					</div>
						
						<div class="span5">
							<ul>
								<li><a href="<?php echo $sUrlServiceHomeHelp; ?>" target="_blank"><strong>See <?php echo $sServiceName; ?> Help &amp; Support page</strong></a>.</li>
								<li><a href="http://wordpress.org/extend/plugins/worpit-admin-dashboard-plugin/" target="_blank">Give <?php echo $sServiceName; ?> a 5 star rating on WordPress.org!</a></li>
							</ul>
						</div>
					
					<div class="row">
						<div class="span6">
							<ul>
								<li><a href="<?php echo $sUrlPlugin_TwitterBootstrap; ?>" target="_blank"><strong>Twitter Bootstrap CSS Plugin</strong></a></li>
								<li><a href="<?php echo $sUrlPlugin_WpPlugins; ?>" target="_blank">Check out all our WordPress Plugins</a></li>
							</ul>
						</div>
					</div>
				</div><!-- / well -->
			</div><!-- / span12 -->
		</div>
		<?php endif; ?>
		
	</div>
</div>