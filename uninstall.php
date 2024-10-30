<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

if (is_multisite()) {
	$networkSites = get_sites();
	if (sizeof($networkSites) > 0) {
		$originalBlogID = get_current_blog_id();
		foreach($networkSites as $site) {
			switch_to_blog($site->blog_id);
			HT_delete_site_data();
		}
		switch_to_blog($originalBlogID);
	}
} else {
	HT_delete_site_data();
}

delete_site_option("ht_only_allow_whitelist");
delete_site_option("ht_login_mon");
delete_site_option("ht_login_limit");
delete_site_option("ht_login_time_span");
delete_site_option("ht_login_block_time");
delete_site_option("ht_show_login_count");
delete_site_option("ht_404_mon");
delete_site_option("ht_404_limit");
delete_site_option("ht_404_time_span");
delete_site_option("ht_404_block_time");
delete_site_option("ht_response_code");
delete_site_option("ht_banned_usernames");
delete_site_option('ht_ph_api_key');
delete_site_option('ht_ph_bl_max_days');
delete_site_option('ht_ph_check_ip_interval');
delete_site_option('ht_bl_threat_score');
delete_site_option('ht_use_project_honeypot');
delete_site_option('ht_use_spamcop');
delete_site_option('ht_ph_check_ip_interval');
delete_site_option('ht_hide_usernames');
delete_site_option('ht_site_level_lists');

function HT_delete_site_data() {
	global $wpdb;
	$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."ht_ip_list");
	$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."ht_activity");

	delete_option('ht_honeypot_path');
	delete_option('ht_use_custom_honeypot');
	delete_option('ht_use_body_open_honeypot');
	delete_option('ht_use_menu_honeypot');
	delete_option('ht_use_search_form_honeypot');
	delete_option('ht_use_footer_honeypot');
}
?>