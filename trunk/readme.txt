=== Plugin Execution Timer ===
Contributors: carlos.ramirez
Tags: performance, plugin, execution, timer
Requires at least: 5.0
Tested up to: 5.8
Requires PHP: 7.0
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Logs the execution time of other plugins during website frontend and admin UI rendering.

== Description ==

Plugin Execution Timer is a simple plugin that logs the execution time of other plugins during website frontend and admin UI rendering. It stores this information in a text file named `plugins_load_time.txt` located in the website's root directory. You can view the contents of the file through an admin menu option in the WordPress dashboard.

Features:
* Measures and logs the execution time of active plugins.
* Determines whether the plugins are running on the frontend or admin UI.
* Provides an admin menu option to view the log data.

== Installation ==

1. Upload the `plugin-execution-timer` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. A new menu item named "Plugin Execution Timer" will be available in your admin dashboard. Click on it to view the contents of the `plugins_load_time.txt` file.

== Frequently Asked Questions ==

= What are this plugin's possible uses? =

Sometimes a wordpress website load time is been degrade because the adding of new plugins. Use the Plugin Execution Timer to check which one(s) are the responsible candidates.

= Is this plugin safe to use on a live website? =

This plugin is for debugging purposes only. It should be installed, activated, used to measure the plugins load and execution times, and then deactivated to avoid degrade the performance of the live website.

== Screenshots ==

1. Admin menu option to view the log data.

== Changelog ==

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.0 =
Initial release.
