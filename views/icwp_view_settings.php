<?php
	include_once( dirname(__FILE__).ICWP_DS.'worpit_options_helper.php' );
?>
<div class="wrap">
	<div class="bootstrap-wpadmin">

		<div class="page-header">
			<a href="http://worpit.com/"><div class="icon32" id="worpit-icon"><br /></div></a>
			<h2><?php _hlt_e( 'Worpit Plugin Settings' ); ?></h2><?php _hlt_e( '' ); ?>
		</div>
		<?php
			printAllPluginOptionsForm( $icwp_aAllOptions, $icwp_var_prefix, 1 );
		?>
	
	</div><!-- /.bootstrap-wpadmin -->
</div><!-- /.wrap -->
