=== iControlWP - Take Control of WordPress  ===
Contributors: paultgoodchild, dlgoodchild
Donate link: http://www.icontrolwp.com/
Tags: manage, manage wordpress, wordpress admin, backup, restore, bulk, icontrolwp, managewp, worpit, automatic updates
Requires at least: 3.2.0
Tested up to: 4.1
Stable tag: 2.9.2.3

== Description ==

The [iControlWP Secure WordPress Manager](http://www.icontrolwp.com/?src=icwp_readme) is the only solution you need to
manage all your WordPress websites.

We're serious about helping you, so we neither charge you with silly pricing tiers like "Pro" and "Business", and we're certainly not free.

*Our Mission - Fewer WordPress Plugins; More Free Time*

With iControlWP you need fewer WordPress plugins on your sites because you can centrally manage much more from within your
control panel. For example:

*	You don't need an extra plugin for Google Analytics tracking codes.
*	You don't need an extra plugin for CloudFlare integration and management
*	You don't need an extra plugin to optimize your databases
*	You don't need an extra plugin to clean up old drafts, comments and post revisions
*	You don't need an extra plugin to manage WordPress automatic updates

With iControlWP you can:

*	Manage ALL WordPress websites from a single, centralized control panel.
*	One-click update WordPress Plugin (premium ones too!), Themes, and the WordPress Core.
*   Bulk update all plugins, themes and WordPress cores.
*   Very finely tune WordPress Automatic Updates for every plugin on every site.
*   Manage CloudFlare settings using our unique CloudFlare integration.
*   Insert Google Analytics Tracking code.
*	[Clean up WordPress](http://www.icontrolwp.com/2012/07/optimize-clean-up-wordpress-worpit/?src=wpt_readme)
and [Optimize WordPress databases](http://www.icontrolwp.com/2012/07/how-to-optimize-wordpress-databases-with-worpit/?src=wpt_readme)
*	Manage WordPress Security setting across all your sites.
*	Install new WordPress websites automatically, anywhere you have cPanel web hosting
*	Automatically log into your WordPress sites without remembering your WordPress login details.
*	Collect useful visitor site statistics and view them directly from within your control panel.


== Frequently Asked Questions ==

= Is iControlWP Free? =

No, but you can try it out using our unlimited-sites free trial.

[You can sign up free here](http://www.icontrolwp.com/?src=wpt_readme)

= Is iControlWP secure? =

Yes! We take great care to ensure the integrity of the connection between iControlWP and your website.

All sensitive data is encrypted on our system so it is never human-readable by anyone.

See the next question for more in-depth explanation.

= I want more to know more about iControlWP plugin security? =

We take security seriously at iControlWP - prevention is far better than cure and we trust when you see what steps we
take to ensure the integrity of your website you'll know we taking the biggest steps to secure your site.

*	Each new plugin install creates a unique secure code that you must supply correctly to add the site to your iControlWP account.
*	When iControlWP and your plugin are connected, iControlWP creates a unique PIN code, encrypts with an MD5 hash and stores in
on your site. ONLY attempts to connect to your plugin that supply this correct PIN code will ever be able succeed.
*	We also take it a step FURTHER - each connection performs a unique hand-shake process (that you wont find in other similar products)
between iControlWP and your WordPress websites to ensure that no-one else, anywhere, can spoof your WordPress site. The plugin will **always**
check to ensure that the connection has originated from iControlWP.com. If not, the connection is disregarded completely and immediately.

= Will the iControlWP plugin slow down my site? =

Not a chance! We have the absolute SMALLEST plugin (compared to similar products of this type) around.

We install only the absolute necessary code, and when you need more, iControlWP's unique "Action Pack" delivery
system sends just what your site needs.

= How does iControlWP work? =

With the plugin installed and the connection setup to your own iControlWP account, the iControlWP system
will periodically ask your WordPress website for some information.  Currently we check:

*	WordPress Core update status
*	WordPress.org Plugins update status
*	WordPress.org Themes update status
*	Other server environment information that helps us to determine compatibility with the iControlWP system.
This includes things like PHP version, HTTP server type and version. You can review all this captured
information from within iControlWP and is useful as a handy reference.

We then take this information and display it to you on the iControlWP dashboard - then it's over to you and what you want to do with it.

= What is WorpDrive? =

WorpDrive is a new, [far more clever approach to WordPress backup and restore](http://worpdrive.com/?src=wpt_readme), 
and is a premium product available from with the iControlWP control panel.

It doesn't use FTP, Amazon S3, or any of the traditional painful approaches to website backup, 
and you don't need to buy/rent any other 3rd party storage service.

WorpDrive is an ALL-IN-ONE backup and restore system for your WordPress website and is a bargain at twice the price.

= Is WorpDrive free? =

No. WorpDrive is available for a small monthly fee.

== Screenshots ==

1. At-a-Glance Summary tell you all your available updates split across plugin/theme names and site names with options to update all, or update individually.

2. See all your sites at a glance, which sites have upgrades available and where.

3. See a full summary of your site information, updates, WordPress Core version, Themes and much more.

4. Get a view of all your plugins and how each one is distributed across each site.

== Changelog ==

= 2.9.2.3 =
*Released: 11th December 2014*

* CHANGED:  Significant plugin refactoring to improve performance, security, and reliability of the iControlWP service.

= 2.8.4 =
*Released: 19th November 2014*

* IMPROVED: Compatibility with WordPress Simple Firewall user sessions and the Login-As feature.

= 2.8.3 =

* FIX:	    Temporarily disable automatic whitelist for wordfence.

= 2.8.2 =

* FIX:	    Quick-fix to get around fatal error with poor Jetpack coding.

= 2.8.1 =

* ADDED:	Option to set "DISALLOW_FILE_EDIT" from iControlWP App
* ADDED:	Option to set "FORCE_SSL_ADMIN" from iControlWP App
* ADDED:	Option to set [CloudFlare Flexible SSL](https://www.icontrolwp.com/2014/10/enabling-cloudflares-universal-flexible-ssl-wordpress-without-infinite-redirect-loops/) from within the iControlWP App
* ADDED:	Option to set Hide WordPress Version (WP Generator Meta tag) from iControlWP App
* ADDED:	Option to set Hide RSD Link from iControlWP App
* ADDED:	Option to set Hide Windows Live link from iControlWP App
* UPDATED:  White-listing IPs

= 2.7.13 =

* FIX:	    Error with automatic whitelisting in some cases.

= 2.7.12 =

* FIX:	    A few PHP notices, and update to WordPress 4.0 compatibility.

= 2.7.11 =

* CHANGE:	To avoid PHP strict warnings.

= 2.7.10 =

* ENHANCED:	Automatically adds to the Bad Behaviour IP white lists.

= 2.7.9 =

* CHANGE:	Improved compatibility with hosts that restrict certain methods of file includes.

= 2.7.8 =

* FIX:      WordPress 3.8.2 breaks automatic updates code. This update addresses that change.

= 2.7.7 =

* FIX:      for cron error code and google analytics js code

= 2.7.5 =

* CHANGED:	auto-updates system is now integrated directly into plugin instead of using a separate plugin.

= 2.7.4 =

* CHANGED:	tweak to white labelling and stats with more options.

= 2.7.3 =

* FIX:      for google analytics logic

= 2.7.2 =

* CHANGED:	Improved white labelling and options for google analytics.

= 2.7.0 =

* ADDED:	Google Analytics Integration.

= 2.6.4 =

* CHANGED:	some code refactoring, fixes, and improvements to the [new stats features](http://icwp.io/46)

= 2.6.2 =

* ADDED:	[Site Statistics Module](http://icwp.io/46)

= 2.5.2 =

* CHANGED:	Now ready for white labelling

= 2.5.1 =

* CHANGED:	Now uses WordPress wp_remote_get() by default to make its HTTP calls

= 2.5.0 =

* CHANGED:	Changed the URL of the App.

= 2.4.4 =

* ADDED:	Support for Elegant themes maintenance mode plugin.
* ADDED:	New Service WhiteList IPs.
* CHANGED:  Small fix to the remote site add feature.

= 2.4.2 =

* ADDED:	Verified works with WordPress 3.8 admin.
* CHANGED:	Improved IP white listing method with 3rd party plugins.

= 2.4.0 =

* ADDED:	Now works around the WP Maintenance plugin so you can add and manage the site.
* ADDED:	Support for the new automatic [WordPress Background Updates features in iControlWP](http://www.icontrolwp.com/2013/11/manage-wordpress-automatic-background-updates-icontrolwp/).

= 2.3.10 =

* ADDED:	New IP address to IP address pool for the service.

= 2.3.8 =

* ADDED:	Improved support for Maintenance Mode plugins while linking your site to iControlWP.

= 2.3.7 =

* ADDED:	Support for All In One WP Security plugin.

= 2.3.6 =

* IMPROVE:	W3 Total Cache compatibility.

= 2.3.5 =

* FIX:		WPMS notices.

= 2.3.4 =

* ADDED:	Full support for WP Engine.

= 2.3.3 =

* ADDED: Support for automatically adding whitelisted IP addresses for the WordPress Simple Firewall plugin.

= 2.3.2 =

* ADDED: Direct support for WordPress Firewall 2 plugin by automatically whitelist iControlWP IPs.
* ADDED: Support for the Ultimate Maintenance Mode plugin.

= 2.2 =

* ADDED: Ability to add a site to an iControlWP account from within the WordPress site itself
* ADDED: Support for the underConstruction plugin to ensure we by-pass any redirection.

= 2.1 =

* ADDED: support for initial plugin white labelling options
* ADDED: login-as redirect options and support for WPMS login as
* ADDED: Automatic Better WP Security IP Whitelisting for the iControlWP service
* ADDED: Automatic Wordfence IP Whitelisting for the iControlWP service

= 2.0 =

* NAME CHANGE: Was Worpit, now iControlWP.com
* ADDED: support for logging in as any user, not just admin.
* FIX: preventing the plugin from disconnecting from the service in some cases.

= 1.3.1 =

* FIX: Improved security

= 1.3.0 =

* CHANGED: Plugin communication with iControlWP App changed to help avoid security restrictions that impact direct access to PHP files.

= 1.2.3 =

* TWEAKED: Plugin's custom access rules.

= 1.2.2 =

* ADDED: a fix for whe na site changes its underlying file structure and the location of the plugin moves.

= 1.2.1 =

* Plugin now redirects to the iControlWP settings page upon activation.
* FIX: a bug with the code in the plugin option to mask WP version.

= 1.2.0 =

* Plugin re-architecture to use HTTP GET instead of POST to receive directives from worpitapp.com
* Tested with WordPress 3.5

= 1.1.3 =

* Adds custom options for setting various security related WordPress settings.
* Now easier to find the plugin URL when adding a site.

= 1.1.2 =

* Adds a .htaccess to the plugin root folder to cater for people who don't have their own .htaccess to prevent directory listing
* Fixes compatibility with other plugins who have the same function names in some cases.
* Work around Maintenance Mode plugin so iControlWP commands still work even in maintenance mode.

= 1.1.1 =

* Fix for handshaking features.

= 1.1.0 =

* Minimum required version to support [WorpDrive WordPress Backup and Recovery Service](http://worpdrive.com/?src=wpt_readme)
* Better stability features that also allow for better handling of errors and unexpected plugin output.

= 1.0.15 =

* Removes interference from the 'Secure WordPess' plugin when the iControlWP plugin initialises due to a request from the iControlWP service.

= 1.0.14 =

* No functional change, just some wording on plugin.

= 1.0.13 =

* Latest stable release.

= 1.0 =

* Worpit - Manage WordPress Better Initial Release.

== Upgrade Notice ==

Now lets you perform automatic WordPress updates from inside iControlWP
