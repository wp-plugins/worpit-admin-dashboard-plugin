=== Worpit Plugin - Manage WordPress Better  ===
Contributors: paultgoodchild, dlgoodchild
Donate link: http://worpit.com/
Tags: worpit, manage, wordpress manage, wordpress admin, backup, restore, bulk
Requires at least: 3.2.0
Tested up to: 3.5
Stable tag: 1.3.0

== Description ==

[Worpit: Manage WordPress Better](http://worpit.com/?src=wpt_readme) lets you manage all your WordPress website from a single, fast, convenient dashboard.

With Worpit you can:

*	Manage WordPress websites from a single centralized Dashboard.
*	One-click update a plugin, a theme or even the WordPress Core.
*	Update all plugins, all themes, all WordPress cores across all sites, or just a selection at once.
*	Optimize all your WordPress sites without extra plugins: [clean up WordPress](http://worpit.com/2012/07/optimize-clean-up-wordpress-worpit/?src=wpt_readme) 
and [optimize the WordPress database](http://worpit.com/2012/07/how-to-optimize-wordpress-databases-with-worpit/?src=wpt_readme).
*	Add WordPress security options across all your websites at once. 
*	Install a brand new WordPress website automatically, anywhere you have cPanel web hosting
*	Install the Worpit WordPress plugin from WordPress.org, or have Worpit do it for you automatically
*	Log into your WordPress sites without remembering your WordPress login details.

No more logging in to each individual website to perform the same, repetitive tasks.  Do them in bulk, on all your sites at once.

== Frequently Asked Questions ==

= Is Worpit Free? =

Yes, you can try it for free with one WordPress site.

[You can sign up free here](http://worpitapp.com/?src=wpt_readme)

= Is Worpit secure? =

Yes! We take great care to ensure the integrity of the connection between Worpit and your website.

All sensitive data is encrypted on our system so it is never human-readable by anyone.

See the next question for more in-depth explanation.

= I want more to know more about Worpit plugin security? =

We take security seriously at Worpit - prevention is far better than cure and we trust when you see what steps we
take to ensure the integrity of your website you'll know we taking the biggest steps to secure your site.

*	Each new plugin install creates a unique secure code that you must supply correctly to add the site to your Worpit account.
*	When Worpit and your plugin are connected, Worpit creates a unique PIN code, encrypts with an MD5 hash and stores in
on your site. ONLY attempts to connect to your plugin that supply this correct PIN code will ever be able succeed.
*	We also take it a step FURTHER - each connection performs a unique hand-shake process (that you wont find in other similar products)
between Worpit and your WordPress websites to ensure that no-one else, anywhere, can spoof your WordPress site. The plugin will **always**
check to ensure that the connection has originated from worpit.com. If not, the connection is disregarded completely and immediately.

= Will the Worpit plugin slow down my site? =

Not a chance! We have the absolute SMALLEST plugin (compared to similar products of this type) around.

We install only the absolute necessary code, and when you need more, Worpit's unique "Action Pack" delivery
system sends just what your site needs.

= How does Worpit work? =

With the plugin installed and the connection setup to your own Worpit account, the Worpit system
will periodically ask your WordPress website for some information.  Currently we check:

*	WordPress Core update status
*	WordPress.org Plugins update status
*	WordPress.org Themes update status
*	Other server environment information that helps us to determine compatibility with the Worpit system.
This includes things like PHP version, HTTP server type and version. You can review all this captured
information from within Worpit and is useful as a handy reference.

We then take this information and display it to you on the Worpit dashboard - then it's over to you and what you want to do with it.

= If Worpit is free why is there a payment option? =

If want to manage more than 1 WordPress site, we feel contributing to 
the development of the Worpit product is fair.

= What is WorpDrive? =

WorpDrive is a new, [far more clever approach to WordPress backup and restore](http://worpdrive.com/?src=wpt_readme), 
and is a premium product available from with the Worpit control panel.

It doesn't use FTP, Amazon S3, or any of the traditional painful approaches to website backup, 
and you don't need to buy/rent any other 3rd party storage service.

WorpDrive is an ALL-IN-ONE backup and restore system for your WordPress website and is a bargain at twice the price.

= Is WorpDrive free? =

No. WorpDrive is available for a small monthly fee.

== Screenshots ==

1. At-a-Glance Summary making it easy to spot needed updates and other important actions.

2. Worpit keeps a log of all your actions so you can track what has been done, and what is pending.

3. You can update individual plugins, themes and the WordPress core, with convenient access to review changelogs.

4. Jump-list menus give you access to most common actions without changing your Worpit context.

5. Worpit provides 4 ways to install the plugin making it easy to get started.

== Changelog ==

= 1.3.0 =

* CHANGED: Plugin communication with Worpit App changed to help avoid security restrictions that impact direct access to PHP files.

= 1.2.3 =

* TWEAKED: Plugin's custom access rules.

= 1.2.2 =

* ADDED: a fix for whe na site changes its underlying file structure and the location of the plugin moves.

= 1.2.1 =

* Plugin now redirects to the Worpit settings page upon activation.
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
* Work around Maintenance Mode plugin so Worpit commands still work even in maintenance mode.

= 1.1.1 =

* Fix for handshaking features.

= 1.1.0 =

* Minimum required version to support [WorpDrive WordPress Backup and Recovery Service](http://worpdrive.com/?src=wpt_readme)
* Better stability features that also allow for better handling of errors and unexpected plugin output.

= 1.0.15 =

* Removes interference from the 'Secure WordPess' plugin when the Worpit plugin initialises due to a request from the Worpit App service.

= 1.0.14 =

* No functional change, just some wording on plugin.

= 1.0.13 =

* Latest stable release.

= 1.0 =

* Worpit - Manage WordPress Better Initial Release.

== Upgrade Notice ==

= 1.2.0 =

* Major plugin re-architecture to use HTTP GET instead of POST to receive directives from worpitapp.com
* Tested with WordPress 3.5
