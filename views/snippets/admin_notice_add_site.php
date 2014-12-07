<style>
	div p.icwp-authentication-notice {
		line-height: 32px;
		padding: 2px 0 10px;
	}
	span.the-key {
		background-color: #ffffff;
		border: 1px solid #aaaaaa;
		border-radius: 4px;
		font-family: "courier new",sans-serif;
		font-size: 19px;
		letter-spacing: 1px;
		margin-left: 10px;
		padding: 5px 8px;
	}
</style>
<p class="icwp-authentication-notice">Now that you've installed the <?php echo $sServiceName; ?> plugin, you need to connect this site to your <?php echo $sServiceName; ?> account.
	<br />Use the following Authentication Key when prompted <span class="the-key"><?php echo $sAuthKey; ?></span>
</p>