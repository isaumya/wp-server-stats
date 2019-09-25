=== WP Server Health Stats ===
Contributors: isaumya
Author URI: https://www.isaumya.com
Plugin URI: https://www.isaumya.com/portfolio-item/wp-server-stats/
Donate link: http://donate.isaumya.com/
Tags: dashboard, widget, server, health, server health, stats, information, admin, isaumya
Requires at least: 4.2
Tested up to: 5.3

Stable Tag: 1.6.8

License: GNU Version 2 or Any Later Version

Monitor your WP site the right way with most important stats like Database, PHP details, PHP Memory, RAM Usage, CPU load, Server Uptime & more.

== Description ==

WP Server Health Stats plugin will give you the ability to monitor your WordPress site at its core level. With all-important server health stats like allocated PHP memory, memory usage, realtime RAM usage, CPU usage etc. you can always identify if something wrong is going on with your site.

Now you don't have to contact your host every single time for minor things. You can easily see the server health stats in your WP admin dashboard and make critical decisions based on that, like if enabling some plugin is consuming a lot of memory or CPU etc.

> It took many countless hours to code, design, test and include useful server info to show up in your WordPress dashboard. If you enjoy this plugin and understand the huge effort I put into this, please consider **[donating some amount](https://donate.isaumya.com/) (no matter how small)** to keep alive the development of this plugin. Thank you again for using my plugin. If you love using this plugin, I would really appreciate it if you took 2 minutes out of your busy schedule to **[share your review](https://wordpress.org/support/plugin/wp-server-stats/reviews/)** about this plugin.

=== Q: After installing this plugin my server load has increased tremendously. How to fix this? ===

 >This plugin uses Ajax to call the server after an interval of time to get the latest data about the server, giving you the real-time update. By default, this happens after every 200ms. But if you are using a low-end server with fewer resources, calling the server after each 200ms can increase your server load drastically. You can easily solve this too. On the left-hand side menu of WP Admin panel, you will see a settings panel for **WP Server Stats** under that you will find another submenu called **General Settings**. Within general settings, the first option is to change the interval time. Change it to how much higher your want like 2000ms (2 sec) or maybe 20000ms (20 sec) depending on your need. Once you save the settings the plugin will call the server only after the interval you have set, reducing your server load back to how it was without this plugin.
But again, generally speaking, if you are in a decent to good hosting, you won't face this problem at all, as this a very lightweight plugin.

Features of the plugin include:

* Purge WP Server Stats Cache (Which you can disable also. Check the FAQ section for more details.)
* Shows server OS
* Shows server software
* Shows the server IP address
* Shows server port
* Shows server location
* Shows server hostname
* Shows server document root
* Shows if Memcached is enabled on your server or not
* If Memcached is enabled it will show you detailed information about your Memcached installation if provided appropriate Memcached host and port details in the **WP Server Stats** > **General Settings** page
* Shows total number of allowed CPU for your site
* Shows Real-Time CPU usage percentage
* Shows Total RAM allocated
* Shows Real-Time RAM Usage percentage
* Shows the database software installed on your site e.g. MySQL, MariaDB, Oracle etc.
* Shows the database version number
* Shows maximum number of connections allowed to your database
* Shows maximum packet size of your database
* Shows database disk usage
* Shows database index disk usage
* A separate page to show up even more details about your database server
* Shows your PHP version number
* Shows your PHP max upload size limit
* Shows PHP max post size
* Shows PHP max execution time
* Shows if PHP safe mode is on or off
* Shows if PHP short tag is on or off
* Shows allowed PHP memory for your WordPress site
* Real-Time Amount & Percentage of your PHP memory usage
* A separate page to show up even more details about your installed PHP & its various modules
* Real-Time PHP Memory, RAM Usage & CPU info bar changes colour based on the load (you can change the colours from the WP Server Stats General Settings Page)
* Designed with flat colours (you can change the colour scheme if you want)
* Realtime PHP Memory, RAM Usage & CPU usage info at the admin footer so that no matter what admin page you are, you can always see it
* Uses advanced WordPress Transient Caching mechanism to run the plugin super smooth without eating a lot of server resources. All the cache data will be auto expired on each week and then the plugin will re-cache the updated data again, to **ensure the least possible resource consumption by the plugin**
* Only shows to the Administrators. For Multisite, it will show the details to each site administrators, but not the network admins
* Option to change the realtime script refresh interval (default: 200ms), colour scheme, Memcached host and port details from the WP Server Stats - General Settings Page
* Automatically removes all the data added by this plugin to your WordPress database upon uninstallation of the plugin

**READ BEFORE INSTALLING**

This plugin uses PHP `shell_exec()` function which is by default enabled by all good hosting companies. But a small percentage of hosting company disable `shell_exec()` by default. So, please contact your hosting company to make sure `shell_exec()` is enabled in your account before installing this plugin. Otherwise, you will get an ERROR Code `EXEC096T` for every feature that uses `shell_exec()`.

> **Now this plugin is also hosted in [Github](https://github.com/isaumya/wp-server-stats). But the Github repo will be used for the development of the plugin. So, from now on you can also report the bugs in [Github Issue Tracker](https://github.com/isaumya/wp-server-stats/issues) if you want.**

**Very Important Note**

This plugin has been developed and tested on Linux based servers only so there is a very high chance that it might NOT work for Windows-based servers. So, I highly recommend this plugin to be used by those users who use a Linux based server.
I currently have no plan to add Windows Server support as a very tiny amount of people still use Windows Server in this Linux age. I may add Windows support in future.

**ERROR Code List**

**EXEC096T** - PHP `shell_exec()` function has not been enabled in your account, which this plugin needs to run properly. Contact your server host and ask them to enable PHP `shell_exec()` function for your account.

**IP096T** - Your server is not returning the IP properly. There is definitely some issue with your server configuration. Please contact your host and tell them that PHP `gethostbyname( gethostname() )` is unable to get the server IP, ask them to look into their server configuration and to fix the configuration issue. If you have a self-hosted VPS or dedicated server, the reason is still the same. If you are unable to find the configuration issue inside your server, I highly suggest you hire a knowledgeable server admin to look into your server. In most cases, you should never get this error message.

**Languages**

WP Server Stats is 100% compatible with translation and you can translate any text to whatever language you want. As this plugin doesn't come with an inbuilt translation, I will suggest you to use a plugin like [Say What?](https://wordpress.org/plugins/say-what/) to change the text, you just have to use the text domain as `wp-server-stats` within the plugin to change the text.

**Very Special Thanks**

The list of people whom I especially want to thank without whom this plugin would have never been completed.

* Justin Catello from [BigScoots Hosting](https://www.isaumya.com/refer/bigscoots) - Looking for quality managed SSD hosting? Go with [BigScoot Hosting](http://www.bigscoots.com/portal/?affid=261) keeping your eye closed. They are that much good.
* [Pippin Williamson](https://twitter.com/pippinsplugins) from [Easy Digital Download](https://easydigitaldownloads.com/)
* [Justin Kimbrell](https://twitter.com/justin_kimbrell) for [FlipClock.js](http://flipclockjs.com/)
* Alex Rabe
* Vlad from [ip-api.com](http://ip-api.com/)
* [Lester Chan](https://twitter.com/gamerz)


**Support the Plugin**

If you like this plugin please don't forget to write a review and if possible please [Donate some amount](http://donate.isaumya.com/) to keep the plugin and it's development alive.

== Screenshots ==

1. Dashboard - for people who have PHP `shell_exec()` function enabled & executable on their server
2. Dashboard - for people who do NOT have PHP `shell_exec()` function enabled or executable on their server
3. Admin settings page to change a various aspect of the plugin (since v1.3.1)
4. Page to show more in-depth details about your PHP installation
5. Page to show more in-depth details about your Database server


== Installation ==

1. Go to Plugins > Add New
2. Search for WP Server Stats and Install it
3. Go to your admin dashboard and you will see the dashboard widget there.
4. To change the settings of the WP Server Stats plugin, head over to **WP Server Stats** > **General Settings** menu in your WordPress's left vertical menu

== Frequently Asked Questions ==

= Do I need any special configuration in my server/hosting account? =

No. This plugin uses PHP `shell_exec()` function which is by default enabled by all good hosting companies. But a small percentage of hosting company disable `shell_exec()` by default. So, please contact your hosting company to make sure `shell_exec()` is enabled in your account before installing this plugin. Otherwise, you will get an ERROR Code `EXEC096T` for every feature that uses `shell_exec()`.

= How to Disable showing "Purge Cache - WP Server Stats" from the WP Admin Bar? =

If you want to disable showing "Purge Cache - WP Server Stats" from WP Admin Bar, simply open your `wp-config.php` file and just above all the database details mentioned, add the following line:
`define('WPSERVERSTATS_ADMINBAR_DISABLE', true);`
That's it. Save your `wp-config.php` file, go back to your WordPress Dashboard and you will see that the WP Server Stats Purge Cache option is now gone.
If you are still seeing that option, please clear all kind of cache you have on your website. Enjoy the update. Thanks to [@djquadrocks](https://profiles.wordpress.org/djquadrocks/) for the idea.

= What are the different ERROR Codes thrown by this plugin? =

**ERROR Code List**

**EXEC096T** - PHP `shell_exec()` function has not been enabled in your account, which this plugin highly needs to run properly. So contact your server host and ask them to enable PHP `shell_exec()` function for your account.

**IP096T** - Your server is not returning the IP properly. There is definitely some issue with your server configuration. Please contact your host and tell then that PHP `gethostbyname( gethostname() )` is unable to get the server IP, ask them to look into their server configuration and to fix the configuration issue. If you have a self-hosted VPS or dedicated server, the reason is still the same. If you are unable to find the configuration issue inside your server, I highly suggest you hire a knowledgeable server admin to look into your server. In most cases, you should never get this error message.

== Changelog ==

= 1.6.8, September 25, 2019 =
* Made sure that the real-time RAM usage data is more accurate across all systems. Special thanks to [@razvanfatu](https://github.com/razvanfatu).

= 1.6.6 & 1.6.7, May 13, 2019 =
* Added the ability to disable showing the "Purge Cache - WP Server Stats" on the WP Admin Bar as per user demand. Check the FAQ section for more details about how to do it. Special thanks to [@djquadrocks](https://profiles.wordpress.org/djquadrocks/).

= 1.6.5, December 20, 2018 =
* Added the ability to purge the WP Server Stats Cache from the admin bar. Added as per users demand.

= 1.6.4, April 14, 2018 =

* Rolling back to using `MemFree` instead of going to look for `MemAvailable` value despite you have that available in your server or not. As per the [issue posted here](https://wordpress.org/support/topic/ram-unknown-negative-value/) - it is no longer possible to trust the `MemAvailable` parameter value as it could be higher than `MemTotal` and hence breaking the entire Real-time RAM checking feature.

= 1.6.3, April 6, 2018 =

* If your current server environment provides the support for `MemAvailable` in the `/proc/meminfo` file then the plugin will take that data for a more accurate measure of RAM available, otherwise it will take the `MemFree` value.

= 1.6.2, April 6, 2018 =

* As it turns out `MemAvailable` is not available for all the servers. It is only available in some distros like Ubuntu. That is why I'm again falling it back to `MemFree` as that is widely available across all systems and distros and will work flawlessly with all server.

= 1.6.1, April 5, 2018 =

* Started using `MemAvailable` instead of `MemFree` in the program to accurately know the availability of free RAM on the server. Special thanks to [@inspired888](https://profiles.wordpress.org/inspired888) for reporting the issue.

= 1.6.0, February 11, 2018 =

* Fixed a major calculation issue with RAM
* Now shows Real-Time Free RAM in the dashboard stats

= 1.5.8, November 24, 2017 =

* Completely removing all kind of Session dependency. Upgrade to v1.5.8 if you are currently using any old version of the plugin.

= 1.5.7, November 24, 2017 =

* Critical Bug Fix - Fixed the bug which was preventing the loopback requests in WP v4.9 and also causing an issue with the new file editor. If you are using WP v4.9, upgrade to v1.5.7 immediately.

= 1.5.6, November 5, 2017 =

* Minor Calculation bugfix

= 1.5.5, November 5, 2017 =

* Fixed a major calculation issue with v1.5.4. If you are running v1.5.4, please update to v1.5.5 immediately.
* Now the actual RAM usage also shows up in the dashboard section

= 1.5.4, November 5, 2017 =

* Now shows the total RAM available
* Added real-time RAM usage bar & percentage at the footer

= 1.5.3, August 30, 2017 =

* Added a check if the session has already been started before starting it.

= 1.5.0, 1.5.1, 1.5.2, December 14 & 15, 2016 =

* Permanently fixed the welcome notice keep showing issue for good. Now no matter what kind of server you are installing this plugin and what kind of cache architecture it has, once you dismiss the welcome notice, it will never show up again unless you delete and reinstall the plugin.

= 1.4.9, December 2, 2016 =

* Hopefully fixing the welcome notice dismissal issue

= 1.4.8, November 27, 2016 =

* Updated the donation link
* Fixed some issues with uninstallation calls
* Fixed a responsive design issue

= 1.4.7, November 17, 2016 =
* Ability to use IP-API Pro Key for high traffic websites

= 1.4.6, November 2, 2016 =
* Fixed a PHP warning

= 1.4.5, October 23, 2016 =
* Fixed the compatibility issue with the register_uninstall_hook

= 1.4.3 & 1.4.4, October 14, 2016 =
* Making sure the welcome notice doesn't show up after clicking on the closing button
* Making sure the notice transient gets deleted upon uninstallation

= 1.4.1 & 1.4.2, October 14, 2016 =

* Some minor bugfix and old PHP version compatibility add
* Fixed some wired issue with the readme file

= 1.4.0, October 14, 2016 =

* Added ability to show up server OS
* Added ability to show up server software
* Added ability to show up a server port
* Added ability to show up server document root
* Added ability to show up if Memcached is enabled on your server or not
* Added ability to show up a detailed page with more info about the memcached installation, if memcached is enabled and configured properly within the WP Server Stats General Settings page
* Added ability to show up the database software installed on your site e.g. MySQL, MariaDB, Oracle etc.
* Added ability to show up the database version number
* Added ability to show up the maximum number of connections allowed to your database
* Added ability to show up maximum packet size of your database
* Added ability to show up database disk usage
* Added ability to show up database index disk usage
* Added ability to show up a separate page with more details about your database server
* Added ability to show up your PHP max upload size limit
* Added ability to show up PHP max post size
* Added ability to show up PHP max execution time
* Added ability to show up if PHP safe mode is on or off
* Added ability to show up if PHP short tag is on or off
* Added ability to show up allowed PHP memory for your WordPress site
* Added ability to show up a separate page to with more details about your install PHP & its various modules
* Added advanced WordPress Transient Caching mechanism to run the plugin super smooth without eating a lot of server resource. All the cache data will be auto expired on each week and then the plugin will re-cache the updated data again, to ensure the least possible resource consumption by the plugin
* Option to change the realtime script refresh interval (default: 200ms), colour scheme, memcache host and port details from the WP Server Stats - General Settings Page
* Automatically removes all the data added by this plugin to your WordPress database upon uninstallation of the plugin

= 1.3.2, October 9, 2016 =

* Minor bug fix

= 1.3.1, October 8, 2016 =

* Major release with a bunch of code improvement
* Now the plugin has it's own admin settings menu found inside Settings > WP Server Stats, from where users can easily tweak the various aspect of the plugin
* Support WordPress's native colour picker inside WP Admin Menu
* Option to change script refresh interval along with various colour options
* Most major release since the first release of the plugin
* This plugin is now added in [Github Repo](https://github.com/isaumya/wp-server-stats) for more streamlined development

= 1.2.1, June 24, 2016 =

* Major bug fix with the new shell execution logic improvement

= 1.2.0, June 24, 2016 =

* Major bug fix with the new shell execution logic improvement

= 1.1.9, June 24, 2016 =

* Code quality improvement
* Shell execution logic improvement
* New clean notice for people who don't have `shell_exec()` enabled/executable on their server
* Various minor bug fix
* New screenshot added for non `shel_exec()` enabled people

= 1.1.8, June 23, 2016 =

* Code quality improvement
* Fixed Bug: The Real-Time CPU Load bar & Uptime clock is showing even if PHP `shell_exec()` isn't enabled. Now it just shows `ERROR EXEC096T` - which means PHP `shell_exec()` isn't enabled to execute that feature
* Fixed Bug: Real-Time Memory Usage stuck at 0% if PHP `shell_exec()` is disabled.
* Few other minor bug fixes

= 1.1.7, June 22, 2016 =

* Major Update Pushed
* Memory usage information is now shown realtime memory usage and memory usage percentage
* Total allocated PHP memory now shows up to PB (Petabyte)
* Code cleanup, optimization & minor bug fix

= 1.1.5, May 16, 2016 =

* Instead of showing LOC096T Error Code, now the plugin will show a proper detailed message in case there is any problem fetching the location details.

= 1.1.4, January 23, 2016 =

* Fixed a minor PHP Bug

= 1.1.2 & 1.1.3, January 3, 2016 =

* Bug Fix with FlipClock.js
* Minor bug fix

= 1.1.1, December 31, 2015 =

* Unnecessary console log removed

= 1.1.0, December 31, 2015 =

* FlickClick Responsiveness issue fixed cross-browser
* Some PHP notices has been resolved
* Minor Bug Fix

= 1.0.6, December 05, 2015 =

* Fixed Firefox CSS issue with FlipClock. Props to [@ArtProjectGroup](https://profiles.wordpress.org/artprojectgroup/)

= 1.0.5, October 28, 2015 =

* Fixed a minor bug with calling a hook

= 1.0.4, October 27, 2015 =

* Updated readme file

= 1.0.3, October 27, 2015 =

* Updated readme file

= 1.0.2, October 27, 2015 =

* Updated readme file

= 1.0.1, October 27, 2015 =

* Some minor bug fix

= 1.0.0, October 27, 2015 =

* First official release!