<?php
/*
Plugin Name: Honeypot Toolkit
Plugin URI: https://www.sterupdesign.com/dev/wordpress/plugins/honeypot-toolkit/
Description: Automates the placement of honeypot links for Project Honeypot. Also blocks IP Addresses who have a bad rating on Project Honeypot and Spamcop. Monitors bad logins and 404 errors.
Version: 4.4.4
Author: Jeff Sterup
Author URI: https://www.sterupdesign.com
License: GPL2
Network: true
*/

require_once(WP_PLUGIN_DIR . "/" . plugin_basename(dirname(__FILE__)) . "/lib/HoneypotToolkit.class.php");

HoneypotToolkit::check_block_list();

$HoneypotToolkit = new HoneypotToolkit(__FILE__);
?>