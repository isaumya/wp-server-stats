# WP Server Stats [![Version](https://img.shields.io/wordpress/plugin/v/wp-server-stats.svg?style=flat-square)](https://wordpress.org/plugins/wp-server-stats/) ![Downloads](https://img.shields.io/wordpress/plugin/dt/wp-server-stats.svg?style=flat-square) ![Rating](https://img.shields.io/wordpress/plugin/r/wp-server-stats.svg?style=flat-square)

![WP Server Stats Banner](https://i.imgur.com/JSonC5R.png)

Monitor your WordPress site the right way with most important server stats like database details, Memory Usage, PHP details, CPU load, Server Uptime & more.

**Contributors:** isaumya

**Author URI:** https://www.isaumya.com

**Plugin URI:** https://www.isaumya.com/portfolio-item/wp-server-stats/

**Donate link:** https://donate.isaumya.com/

**Requires at least:** 4.2

**Tested up to:** 5.2

**License:** MIT

## Description

WP Server Stats plugin will give you the ability to monitor your WordPress site at it's core level. With all most important server stats like allocated memory, memory usage, CPU usage etc. you can always identify if something wrong is going on with your site.

Also now you don't have to contact your host every single time for minor things. You can easy see the server stats at your WP admin dashboard and take curtail decision based on that, like if enabling some plugin is consuming a lot of memory or CPU etc.

> It took countless hours to code, design, test and include many useful server info that you like so much to show up in your WordPress dashboard. If you enjoy this plugin and understand the huge effort I put into this, please consider **[donating some amount](https://goo.gl/V41y3K) (no matter how small)** for keeping aliave the development of this plugin. Thank you again for using my plugin. Also if you love using this plugin, I would really appiciate if you take 2 minutes out of your busy schedule to **[share your review](https://wordpress.org/support/plugin/wp-server-stats/reviews/)** about this plugin.

Features of the plugin include:

* Shows server OS
* Shows server software
* Shows the server IP address
* Shows server port
* Shows server loacation
* Shows server hostname
* Shows server document root
* Shows if Memcached is enabled on your server or not
* If memcached is enabled it will show you a detailed information about your memcached installation, if provided right memcache host and port details in the **WP Server Stats** > **General Settings** page
* Shows total number of allowed CPU for yoru site
* Shows Realtime CPU usage percentage
* Shows Total RAM allocated
* Shows Real Time RAM Usage percentage
* Shows the database software installed on your site e.g. MySQL, MariaDB, Oracle etc.
* Shows the database version number
* Shows maximum number of connections allowed to your database
* Shows maximum packet size of your database
* Shows database disk usage
* Shows database index disk usage
* A seperate page to show up even more details about your database server
* Shows your PHP version number
* Shows your PHP max upload size limit
* Shows PHP max post size
* Shows PHP max execution time
* Shows if PHP safe mode is on or off
* Shows if PHP short tag is on or off
* Shows allowed PHP memory for your WordPress site
* Realtime Amount & Percentage of your PHP memory usage
* A seperate page to show up even more details about your install PHP & it's various modules
* Realtime PHP Memory, RAM Usage & CPU info bar changes color based on the load (you can change the colors from the WP Server Stats General Settings Page)
* Designed with flat colors (you can change the color scheme if you want)
* Realtime PHP Memory, RAM Usage & CPU usage info at the admin footer so that no matter what admin page you are, you can always see it
* Uses advanced WordPress Trainsient Caching mechanism to run the plugin super smooth without eating a lot of server resource. All the cache data will be auto expired on each week and then the plugin will re-cache the updated data again, to **ensure the least possible resource consumption by the plugin**
* Only shows to the Administrators. For Multisite, it will show the details to each site administrators, but not the network admins
* Option to change the realtime script refresh interval (default: 200ms), color scheme, memcache host and port details from the WP Server Stats - General Settings Page
* Automatically removes all the data added by this plugin to your WordPress database upon uninstallation of the plugin

## Read Before Installing

This plugin uses PHP `shell_exec()` function which is by default enabled by all good hosting companies. But a small percentage of hosting company disable `shell_exec()` by default. So, please contact your hosting company to make sure `shell_exec()` is enabled in your account before installing this plugin. Otherwise you will get an ERROR Code `EXEC096T` for every feature that uses `shell_exec()`.

## Very Important Note

This plugin has been developed and tested for Linux based server only so there is a very high chance that it might NOT work for Windows based servers. So I highly recommend this plugin to be used by those users who uses a Linux based server.
I currently has no plan to add Windows Server support as a very tiny amount of people still use Windows Server in this Linux age. I may add Windows support in future.

## ERROR Code List

**EXEC096T** - PHP `shell_exec()` function has not been enabled in your account, which this plugin highly needs to run properly. So contact your server host and ask them to enable PHP `shell_exec()` function for your account.

**IP096T** - Your server is not returning the IP properly. There is definately some issue with your server configuration. Please contact your host and tell then that PHP `gethostbyname( gethostname() )` is unable to get the server IP, ask them to look into thier server configuration and to fix the configuration issue. If you have a self hosted VPS or dedicated server, the reason is still the same. If you are unable to find the configuartion issue inside your server, I highly suggest you to hire a knowledgable server admin to look into your server. In most cases you should never get this error message.

## Languages

WP Server Stats is 100% compatible with translation and you can translate any text to whatever language you want. As this plugin doesn't comes with an in-built translation, I will suggest you to use plugin like [Say What?](https://wordpress.org/plugins/say-what/) to change the texts, you just have to use the text domain as `wp-server-stats` within the plugin to change the texts.

## Very Special Thanks

The list of people whom I wanna specially thanks without whom this plugin would have never been completed.

* Justin Catello from [BigScoots Hosting](https://www.isaumya.com/refer/bigscoots) - Looking for quality managed SSD hosting? Go with [BigScoot Hosting](http://www.bigscoots.com/portal/?affid=261) keeping your eye closed. They are that much good.
* [Pippin Williamson](https://twitter.com/pippinsplugins) from [Easy Digital Download](https://easydigitaldownloads.com/)
* [Justin Kimbrell](https://twitter.com/justin_kimbrell) for [FlipClock.js](http://flipclockjs.com/)
* Alex Rabe
* Vlad from [ip-api.com](http://ip-api.com/)
* [Lester Chan](https://twitter.com/gamerz)


## Support the Plugin

If you like this plugin please don't forget to write a review and if possible please [Donate some amount](http://donate.isaumya.com/) to keep the plugin and it's development alive.

## Screenshots

[![Dashboard - for people who have PHP `shell_exec()` function enabled & executable on their server](https://i.imgur.com/msml5MS.jpg)](https://i.imgur.com/msml5MS.jpg)

Dashboard - for people **who have** PHP `shell_exec()` function enabled & executable on their server

[![Dashboard - for people who does NOT have PHP `shell_exec()` function enabled or executable on their server](https://i.imgur.com/RAAoFLJ.png)](https://i.imgur.com/RAAoFLJ.png)

Dashboard - for people who **does NOT have** PHP `shell_exec()` function enabled or executable on their server

[![WP Server Stats Admin Settings Page](https://i.imgur.com/l9UDFwQ.jpg)](https://i.imgur.com/l9UDFwQ.jpg)

WP Server Stats Admin Settings Page (since v1.3.1)

[![Page to show up more in-depth details about your PHP installlation](https://i.imgur.com/pouUQz0.jpg)](https://i.imgur.com/pouUQz0.jpg)

Page to show up more in-depth details about your PHP installlation

[![Page to show up more in-depth details about your Database server](https://i.imgur.com/pBv5v5E.jpg)](https://i.imgur.com/pBv5v5E.jpg)

Page to show up more in-depth details about your Database server


## Installation

1. Go to Plugins > Add New
2. Search for WP Server Stats and Install it. 
3. Go to your admin dashboard and you will see the dashboard widget over there.
4. To change the settings of the WP Server Stats plugin, head over to **WP Server Stats** > **General Settings** menu in you WordPress's left vertical menu

**Please Note:** if you wanna test out the development/beta version of this plugin, feel free to install this github version, as I will do all the updates here and after the plugin is completely stable, then only I will push it to WordPress repo.

So, in WordPress remo the plugin version number will follow like this **X.X.X**, whereas in Github the version number with follow like this **X.X.X.X** where the last **X** denotes beta phase

## Changelog
For the actual plugin changelog, please checkout the [WordPress Plugin's Changelog section](https://wordpress.org/plugins/wp-server-stats/changelog/). It is hard to update the same thing in two seperate places.
