<?php
class HT_Template {
	var $HT;

	function __construct($HT) {
		$this->HT = $HT;
	}

	function admin_css() {
		wp_enqueue_style('HT-admin');
	}
	
	function common_js() {
		require_once($this->HT->absPath . "/tpl/common_js.php");
	}
	
	function settings_page_js() {
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_script('jquery-ui-tabs');
		require_once($this->HT->absPath . "/tpl/settings_page_js.php");
	}
	
	function settings_page() {
		if ( current_user_can( 'activate_plugins' ) ) {
			$htSettings = array(
				'only_allow_whitelist' => get_site_option('ht_only_allow_whitelist', '0'),
				'login_mon' => get_site_option('ht_login_mon', '0'),
				'login_limit' => get_site_option('ht_login_limit', '0'),
				'login_time_span' => get_site_option('ht_login_time_span', '3600'),
				'login_block_time' => get_site_option('ht_login_block_time', '86400'),
				'show_login_count' => get_site_option('ht_show_login_count', '0'),
				'404_mon' => get_site_option('ht_404_mon', '0'),
				'404_limit' => get_site_option('ht_404_limit', '10'),
				'404_time_span' => get_site_option('ht_404_time_span', '3600'),
				'404_block_time' => get_site_option('ht_404_block_time', '86400'),
				'response_code' => get_site_option('ht_response_code', '503'),
				'banned_usernames' => join("\n", get_site_option('ht_banned_usernames', array())),
				'honeypot_path' => get_option('ht_honeypot_path'),
				'use_custom_honeypot' => get_option('ht_use_custom_honeypot'),
				'use_body_open_honeypot' => get_option('ht_use_body_open_honeypot'),
				'use_menu_honeypot' => get_option('ht_use_menu_honeypot'),
				'use_search_form_honeypot' => get_option('ht_use_search_form_honeypot'),
				'use_footer_honeypot' => get_option('ht_use_footer_honeypot'),
				'use_the_content_honeypot' => get_option('ht_use_the_content_honeypot'),
				'ph_api_key' => get_site_option('ht_ph_api_key'),
				'ph_bl_max_days' => get_site_option('ht_ph_bl_max_days', '255'),
				'ph_bl_threat_score' => get_site_option('ht_ph_bl_threat_score', '10'),
				'ph_check_ip_interval' => get_site_option('ht_ph_check_ip_interval', '14'),
				'use_project_honeypot' => get_site_option('ht_use_project_honeypot'),
				'use_spamcop' => get_site_option('ht_use_spamcop'),
				'hide_usernames' => get_site_option('ht_hide_usernames'),
				'site_level_lists' => get_site_option('ht_site_level_lists')
			);
			require_once($this->HT->absPath . "/tpl/settings.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}

	function single_settings_page_js() {
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_script('jquery-ui-tabs');
		require_once($this->HT->absPath . "/tpl/single_settings_page_js.php");
	}
	
	function single_settings_page() {
		if ( current_user_can( 'activate_plugins' ) ) {
			$htSettings = array(
				'honeypot_path' => get_option('ht_honeypot_path'),
				'use_custom_honeypot' => get_option('ht_use_custom_honeypot'),
				'use_body_open_honeypot' => get_option('ht_use_body_open_honeypot'),
				'use_menu_honeypot' => get_option('ht_use_menu_honeypot'),
				'use_search_form_honeypot' => get_option('ht_use_search_form_honeypot'),
				'use_footer_honeypot' => get_option('ht_use_footer_honeypot'),
				'use_the_content_honeypot' => get_option('ht_use_the_content_honeypot')
			);
			require_once($this->HT->absPath . "/tpl/single_settings.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}

	function whitelist_page_js() {
		global $wpdb;
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-dialog');
		require_once($this->HT->absPath . "/tpl/whitelist_page_js.php");
	}
	
	function whitelist_page() {
		if ( current_user_can( 'activate_plugins' ) ) {
			require_once($this->HT->absPath . "/tpl/whitelist.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}

	function blocked_list_page_js() {
		global $wpdb;
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-dialog');
		require_once($this->HT->absPath . "/tpl/blocked_list_page_js.php");
	}
	
	function blocked_list_page() {
		if ( current_user_can( 'activate_plugins' ) ) {
			require_once($this->HT->absPath . "/tpl/blockedList.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}

	function activity_list_page_js() {
		global $wpdb;
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-dialog');
		require_once($this->HT->absPath . "/tpl/activity_list_page_js.php");
	}
	
	function activity_list_page() {
		if ( current_user_can( 'activate_plugins' ) ) {
			require_once($this->HT->absPath . "/tpl/activityList.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}
}
?>