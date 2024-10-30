=== Honeypot Toolkit ===
Contributors: foomagoo
Donate link: https://www.sterupdesign.com/donate/
Tags: honeypot, project honeypot, brute force protection, spam prevention, login monitor, 404 monitor, spamcop, ip blacklist, user enumeration
Requires at least: 4.6.0
Tested up to: 6.2
Stable tag: 4.4.4

== Description ==

This plugin allows you to automatically insert your Project Honeypot links into all of your pages and block IP addresses that are listed on the Http:BL list from Project Honeypot. There is an option to block IP addresses that have been blocked by Spamcop using their blacklist as well.
To prevent bots from using brute force attacks and scanning your site there is an option to block users that fail to login a set number of times or use blocked user names. You can also block IP addresses that generate a large number of 404 errors. This plugin will also prevent WordPress User Enumeration and automatically block anyone attempting it.

== Installation ==

1. Extract the downloaded Zip file.
2. Upload the 'honeypot-toolkit' directory to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Use the menu item called Honeypot Toolkit to get the plugin set up.

You should set up an account on the project honeypot website at https://www.projecthoneypot.org if you want to use Project Honeypot.

== Frequently Asked Questions ==

= Where do I get the script for my honeypot? =

You must sign up for an account on https://www.projecthoneypot.org. Then go to https://www.projecthoneypot.org/manage_honey_pots.php to set up your honeypot and follow the instructions.  After the script has been placed on your site enter the url of your script on the Honeypot Toolkit settings page.

== Screenshots ==

1. Settings page
2. Blocked list page
3. Activity page
4. Whitelist page

== Changelog ==

= 4.4.4 =
Fixed typo in 4.4.3.  Used _transient_timeout instead of _site_transient_timeout

= 4.4.3 =
Changing transients to use site transients for better compatibility with multisite installs
Added check for transients to ensure that they expire rather than living forever
Added check for empty array when no honeypot positions are selected

= 4.4.2 =
Added DNS_A argument to dns_get_record calls to only pull A records since that is all the plugin uses.
Made the logic a little more efficient for deciding if a DNS record was returned.

= 4.4.1 =
Added check to make sure honeypot link isn't included in post excerpt if the_content hook is used.

= 4.4 =
Changed the way activity count is updated to use the primary key so the database table will not get locked.

= 4.3.1 =
Fixed missing ajax save function for content honeypot.
Fixed check on settings page to make sure honeypot locations have been saved.

= 4.3 =
Added options to set the locations where the honeypot will appear.

= 4.2.2 =
Fixed PHP warning when checking for a temporary whitelist entry and one doesn't exist.

= 4.2.1 =
Fixed call to explode that was missing the delimiter

= 4.2 =
Changed how the server variables are handled. The variables can be a comma delimited list.
Added rel="nofollow" to honeypot links.

= 4.1.2 =
Fixed deprecated message for PHP 7.x

= 4.1.1 =
Fixed issue on multisite installs where the plugin would check for temporary whitelist entries in a database table prefixed with the current site DB prefix.  Changed $wpdb->prefix to $wpdb->base_prefix

= 4.1 =
Added functionality to temporarily whitelist an IP if it has passed the Project Honeypot and Spamcop blacklist checks. This prevents the same IP being checked multiple times while a user is visiting a site.
Fix for dropdown css on IP list pages.

= 4.0.9 =
Added the ability to enter a . in the band username field.
Added functionality to automatically whitelist the web servers IP address so it doesn't block itself while doing a health check.

= 4.0.8 =
Improved input validation and sanatization.
Added a checkbox to the IP lists so all entries can be selected.
Added functionality to submit the search query when the enter key is pressed in the search box.
Changed the way notes are stored so line breaks will not be stripped.

= 4.0.7 =
Fixing bug with login monitoring.  IP v6 addresses were not properly being blocked.
Added better notes when a user is blocked.

= 4.0.6 =
Updating scripts to use my new domain name for documentation links so plugins like wordfence don't alert users.
Updating readme to reflect compatibility with WP 5.1.

= 4.0.5 =
Fixed styling issue with jQuery UI dialog.
Changed IP links in the admin to go to domaintools.com since they can handle IPv6 addresses.

= 4.0.4 =
Changed from using wp_get_sites to get_sites to remove a deprecated message and stop using a deprecated function.
Changed functionality when updating the check interval for Project Honeypot and Spamcop lists.  Now it will reset the timeout when a new interval is set.

= 4.0.3 =
Improved functionality to check blocked IP addresses on the SPamcop and Project Honeypot lists.

= 4.0.2 =
Fixed typo to correct DB prefix in activate function

= 4.0.1 =
Made change to ensure the activate function is called when a new version is released.

= 4.0 =
Added support for blocking IPv6 addresses.
Added better support for blocking proxy addresses.
Changed validation functionality to use filter_var for IP addresses.

= 3.2.3 =
Added temporary patch for IP v6 addresses.

= 3.2.2 =
Fixed bug with transient set and get for blacklist check.

= 3.2.1 =
Fixed bug that prevented IPs on the blacklist from being removed if they weren't on the Spamcop or Project Honeypot lists anymore.
Fixed a bug that moved the dialog box above the top of the screen during an ajax call.

= 3.2 =
Changed the process to hide usernames so that it processes 100 at a time. This way it doesn't fail if there is a large number of users.
Hid the option to show IP lists on individual sites from the settings page if the site is not a multisite install.

= 3.1 =
Forced user nicenames to be md5 hashed when usernames are hidden regardless of whether they match the user login or not.

= 3.0 =
Added option to change an authors user nicename to an md5 hash to hide their real username.
Changed the plugin to be a network only plugin.  Now all IP lists are managed at the network level for multisite installs.

= 2.2 =
Fixed a bug that left details of the IP list entries escaped for MySQL when displaying them on the admin page.
Fixed a bug that prevents the user from selecting Project Honeypot or Spamcop Entry when editing an entry in the blocked list.

= 2.1 =
Moved the code to sanitize server variables for use in determining the visitors IP so that it will not throw an undefined index warning.

= 2.0 =
Added search functionality to search the different IP lists and make it easier to find an entry.
Fixed a bug that stopped the loading indicator from displaying when data was submitted.

= 1.2 =
Added indicator to show sorting direction in ip lists.
Added tabs to the settings page.

= 1.1 =
Added options to paging so you can go to any page in the list and change the type of records in the lists.

= 1.0 =
Added paging to the ip list pages.

= 0.2 =
Adding sanitization to the server keys used to prevent injection from request headers.
Ensuring that the IP being checked is an IP 4 address.
Fixed typo in the spamcop check function that checked the address of the visitor and not the address on the blocked list.

= 0.1 =
Initial version.

== Upgrade Notice ==

= 4.4.4 =
Fixed typo in 4.4.3.  Used _transient_timeout instead of _site_transient_timeout