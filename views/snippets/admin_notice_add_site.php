
<form method="post" action="admin.php?page=worpit-admin">
	<?php echo $sNonce; ?>
	<p><strong>Warning:</strong> Now that you have installed the <?php echo $sServiceName; ?> plugin, you should now add it to your <?php echo $sServiceName; ?> account.
		<input type="hidden" name="icwp_admin_form_submit" value="1" />
		<input type="hidden" name="icwp_ack_plugin_notice" value="Y" />
		<input type="hidden" value="<?php echo $nCurrentUserId; ?>" name="icwp_user_id" id="_icwp_user_id">
		<input type="submit" value="Get your Authentication Key here" name="submit" class="button-primary">
	</p>
</form>