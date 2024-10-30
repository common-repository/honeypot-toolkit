<?php
require_once('HT_Template.class.php');
require_once('HT_Ajax.class.php');

class HoneypotToolkit {
	var $absPath, $urlPath, $nonce, $pluginDirPath, $tpl, $ajax, $validationRegex, $mainPluginFile, $remoteIPList;
	function __construct($mainFile) {
		$this->mainPluginFile = plugin_basename(dirname($mainFile)) . DIRECTORY_SEPARATOR . "honeypot-toolkit.php";
		$this->pluginDirPath = $this->get_plugin_dir();
		$this->absPath = $this->pluginDirPath . DIRECTORY_SEPARATOR . plugin_basename(dirname($mainFile));
		$this->urlPath = plugins_url("", $mainFile);
		$this->tpl = new HT_Template($this);
		$this->ajax = new HT_Ajax($this);
		$this->validationRegex = array(
			'new-ip-address-start'=>'/^[0-9a-fA-F\:\.]{1,50}$/',
			'new-ip-address-end'=>'/^[0-9a-fA-F\:\.]{1,50}$/',
			'new-ip-notes'=>'/^[\s|\S]{0,4000}$/',
			'edit-ip-address-start'=>'/^[0-9a-fA-F\:\.]{1,50}$/',
			'edit-ip-address-end'=>'/^[0-9a-fA-F\:\.]{1,50}$/',
			'edit-ip-notes'=>'/^[\s|\S]{0,4000}$/',
			'ht-honeypot-path'=>'/^(((https?):((\/\/)|(\\\\))|\/)+[\w\d:#\[\]\*@%\/;$()~_?\+-=\\\.&]*|)$/',
			'ht-ph-api-key'=>'/^[A-Za-z0-9]*$/',
			'ht-banned-usernames'=>'/^[A-Za-z0-9\s\.]*$/',
			'default'=>'/^.*$/'
		);
		$GLOBALS['ht_whitelist_count_incremented'] = array();
		$this->add_hooks($mainFile);

		##Create list of visitors IP Addresses
		$this->remoteIPList = array();
		foreach(array('HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR') as $serverKey) {
			if (isset($_SERVER[$serverKey])) {
				if (preg_match('/,/', $_SERVER[$serverKey])) {
					$splitIP = explode(',', $_SERVER[$serverKey]);
					foreach($splitIP as $remoteIP) {
						$remoteIP = sanitize_text_field(trim($remoteIP));
						if (!in_array($remoteIP, $this->remoteIPList)) {
							$this->remoteIPList[] = $remoteIP;
						}
					}
				} else {
					if (!in_array($_SERVER[$serverKey], $this->remoteIPList)) {
						$this->remoteIPList[] = sanitize_text_field($_SERVER[$serverKey]);
					}
				}
			}
		}
	}

	function get_plugin_dir() {
		return preg_replace('/\\' . DIRECTORY_SEPARATOR . 'honeypot-toolkit\\' . DIRECTORY_SEPARATOR . 'lib$/', '', dirname(__FILE__));
	}
	
	function add_hooks($mainFile) {
		register_activation_hook($mainFile,array($this, 'activate'));
		
		if (!is_admin() && get_site_option('ht_use_project_honeypot') == '1' && get_option('ht_honeypot_path') != '') {
			$this->set_honeypot_position();
		}

		add_action('plugins_loaded', array($this, 'check_version'));
		
		add_action('user_register', array($this, 'filter_user_nicename'));
		add_filter('update_user_metadata', array($this, 'filter_user_meta'), 10, 4);
		add_filter('wp_pre_insert_user_data', array($this, 'filter_user_data'), 10, 3);
		
		add_filter('init', array($this, 'init'));

		add_action('admin_init', array($this, 'register_admin_style'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_style'));
		if (is_multisite()) {
			add_action('network_admin_menu', array($this, 'admin_menu'), 9);
			add_action('admin_menu', array($this, 'single_admin_menu'), 9);
		} else {
			add_action('admin_menu', array($this, 'admin_menu'), 9);
		}
		add_action('admin_notices', array($this, 'add_ajax_notices'));
		add_action('network_admin_notices', array($this, 'add_ajax_notices'));
		
		add_action('shutdown', array($this, 'check_for_404'));
		add_filter('authenticate', array($this, 'check_banned_users'), 1, 2);
		add_action('wp_login_failed', array($this, 'login_failed'), 1, 1);
		add_action('wp_login', array($this, 'user_logged_in'));
		add_filter('wp_login_errors', array($this, 'show_bad_login_count'), 10, 1);
		
		//Ajax functions
		add_action('wp_ajax_HT_blacklist_ip', array($this->ajax, 'blacklist_ip'));
		add_action('wp_ajax_HT_whitelist_ip', array($this->ajax, 'whitelist_ip'));
		add_action('wp_ajax_HT_save_settings', array($this->ajax, 'save_settings'));
		add_action('wp_ajax_HT_save_single_settings', array($this->ajax, 'save_single_settings'));
		add_action('wp_ajax_HT_remove_ip', array($this->ajax, 'remove_ip'));
		add_action('wp_ajax_HT_retrieve_entry_details', array($this->ajax, 'retrieve_entry_details'));
		add_action('wp_ajax_HT_edit_ip_entry', array($this->ajax, 'edit_ip_list_entry'));
		add_action('wp_ajax_HT_retrieve_whitelist', array($this->ajax, 'retrieve_whitelist'));
		add_action('wp_ajax_HT_retrieve_blocked_list', array($this->ajax, 'retrieve_blocked_list'));
		add_action('wp_ajax_HT_retrieve_activity_list', array($this->ajax, 'retrieve_activity_list'));
		add_action('wp_ajax_HT_retrieve_page_limit', array($this->ajax, 'retrieve_page_limit'));
		add_action('wp_ajax_HT_process_usernames', array($this->ajax, 'process_usernames'));
	}

	function set_honeypot_position() {
		$positions = array();
		
		##All positions are defaulted on except the custom honeypot
		if (get_option('ht_use_custom_honeypot') == '1') {
			$positions[] = 'custom';
		}

		if (get_option('ht_use_body_open_honeypot') !== '0') {
			$positions[] = 'body';
		}

		if (get_option('ht_use_menu_honeypot') !== '0') {
			$positions[] = 'menu';
		}

		if (get_option('ht_use_search_form_honeypot') !== '0') {
			$positions[] = 'search';
		}

		if (get_option('ht_use_footer_honeypot') !== '0') {
			$positions[] = 'footer';
		}

		if (get_option('ht_use_the_content_honeypot') !== '0') {
			$positions[] = 'content';
		}

		if (count($positions) > 0) {
			$positionKey = array_rand($positions, 1);
		
			switch($positions[$positionKey]) {
				case 'menu':
					add_filter('wp_nav_menu', array($this, 'insert_honeypot_link'), 99, 1);
					add_filter('wp_page_menu', array($this, 'insert_honeypot_link'), 99, 1);
					break;
				case 'footer':
					add_action('wp_footer', array($this, 'print_honeypot_link'), 99, 1);
					break;
				case 'custom':
					add_action('ht_custom_honeypot', array($this, 'print_honeypot_link'));
					break;
				case 'search':
					add_filter('get_search_form', array($this, 'insert_honeypot_link'), 99, 1);
					break;
				case 'body':
					add_action('wp_body_open', array($this, 'print_honeypot_link'), 99, 1);
					break;
				case 'content':
					add_filter('the_content', array($this, 'insert_honeypot_link'), 99, 1);
					break;
			}
		}
	}
	
	function init() {
		##Create nonce
		$this->nonce = wp_create_nonce(plugin_basename(__FILE__));
	}

	function verify_nonce($nonce) {
		return wp_verify_nonce( $nonce, plugin_basename(__FILE__) );
	}
	
	function reset_usernames($userOffset, $limit) {
		global $wpdb;
		$allUsers = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->base_prefix."users LIMIT %d, %d", $userOffset, $limit), ARRAY_A);
		foreach ($allUsers as $userRes) {
			$user = get_userdata($userRes['ID']);
			wp_update_user(array('ID'=>$user->ID, 'user_nicename'=>$user->user_login));
		}
	}
	
	function generate_random_usernames($userOffset, $limit) {
		global $wpdb;
		$allUsers = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->base_prefix."users LIMIT %d, %d", $userOffset, $limit), ARRAY_A);
		foreach ($allUsers as $userRes) {
			$user = get_userdata($userRes['ID']);
			if ($user !== false) {
				$userMeta = get_user_meta($user->ID);
				$nickName = '';
				if (isset($userMeta['nickname'][0]) && strtolower($userMeta['nickname'][0]) == strtolower($user->user_login)) {
					$nickNameArr = str_split($userMeta['nickname'][0]);
					for ($i=0; $i<count($nickNameArr); $i++) {
						$nickNameArr[$i] = '&#'.ord($nickNameArr[$i]).';';
					}
					$nickName = join($nickNameArr);
					update_user_meta($user->ID, 'nickname', $nickName);
				}
				
				wp_update_user(array('ID'=>$user->ID, 'user_nicename'=>md5($user->ID)));
				
				if (strtolower($user->display_name) == strtolower($user->user_login)) {
					$displayNameArr = str_split($user->display_name);
					for ($i=0; $i<count($displayNameArr); $i++) {
						$displayNameArr[$i] = '&#'.ord($displayNameArr[$i]).';';
					}
					$displayName = join($displayNameArr);
					
					wp_update_user(array('ID'=>$user->ID, 'display_name'=>$displayName));
				}
			}
		}
	}

	function filter_user_nicename($userID) {
		if (get_site_option('ht_hide_usernames') == '1') {
			$hashedID = md5($userID);
			wp_update_user(array('ID'=>$userID, 'user_nicename'=>$hashedID));
		}
	}

	function filter_user_meta($check, $userID, $metaKey, $metaValue) {
		if (get_site_option('ht_hide_usernames') == '1' && $metaKey == 'nickname') {
			$user = get_userdata($userID);
			if ($user !== false && strtolower($user->user_login) == strtolower($metaValue)) {
				$nickNameArr = str_split($metaValue);
				for ($i=0; $i<count($nickNameArr); $i++) {
					$nickNameArr[$i] = '&#'.ord($nickNameArr[$i]).';';
				}
				update_user_meta($user->ID, 'nickname', join($nickNameArr));
				return false;
			}
		}
		return $check;
	}

	function filter_user_data($userData, $update, $userID) {
		global $wpdb;
		if (get_site_option('ht_hide_usernames') == '1') {
			if (!is_null($userID)) {
				$user = get_userdata($userID);
				if ($user !== false) {
					if (strtolower($userData['user_nicename']) == strtolower($user->user_login)) {
						$userData['user_nicename'] = md5($userID);
					}
					
					if (strtolower($user->user_login) == strtolower($userData['display_name'])) {
						$displayNameArr = str_split($userData['display_name']);
						for ($i=0; $i<count($displayNameArr); $i++) {
							$displayNameArr[$i] = '&#'.ord($displayNameArr[$i]).';';
						}
						$userData['display_name'] = join($displayNameArr);
					}
				}
			}
		}
		return $userData;
	}
	
	
	function check_for_404() {
		global $wpdb;
		foreach($this->remoteIPList as $remoteAddress) {
			$ipNumber = HoneypotToolkit::calculate_ip_number($remoteAddress);
			if (!HoneypotToolkit::check_whitelist($ipNumber, $remoteAddress)) {
				if (is_404() && get_site_option('ht_404_mon', "0") == "1") {
					$ht404Limit = get_site_option('ht_404_limit', "10");
					$ht404TimeSpan = get_site_option('ht_404_time_span', "3600");
					
					$deleteActivityQuery = "DELETE FROM ".$wpdb->base_prefix."ht_activity WHERE last_activity < %d AND activity_type='404'";
					$wpdb->query($wpdb->prepare($deleteActivityQuery, time()-$ht404TimeSpan));
					
					$storedActivity = $wpdb->get_row($wpdb->prepare("SELECT activity_id, activity_count, notes FROM ".$wpdb->base_prefix."ht_activity WHERE ip_address=%s AND activity_type='404'", $remoteAddress), ARRAY_A);
					$currentCount = 0;
					$activityNotes = (isset($_SERVER['REQUEST_SCHEME']))? htmlentities($_SERVER['REQUEST_SCHEME']).'://':'';
					$activityNotes .= (isset($_SERVER['SERVER_NAME']))? htmlentities($_SERVER['SERVER_NAME']):((isset($_SERVER['HTTP_HOST']))? htmlentities($_SERVER['HTTP_HOST']):'');
					$activityNotes .= htmlentities($_SERVER['REQUEST_URI']);
					if (is_array($storedActivity)) {
						$activityNotes = (preg_match('/'.preg_quote($activityNotes, '/').'/', $storedActivity['notes']))? $storedActivity['notes'] : $storedActivity['notes']."\n".$activityNotes;
						$wpdb->update($wpdb->base_prefix."ht_activity", array("activity_count"=>$storedActivity['activity_count']+1, "last_activity"=>time(), "notes"=>$activityNotes), array("activity_id"=>$storedActivity['activity_id'], "activity_type"=>"404"));
						$currentCount = $storedActivity['activity_count']+1;
					} else {
						$wpdb->insert($wpdb->base_prefix."ht_activity", array("ip_address"=>$remoteAddress, "activity_count"=>1, "activity_type"=>"404", "last_activity"=>time(), "notes"=>$activityNotes));
						$currentCount = 1;
					}

					if ($currentCount >= $ht404Limit) {
						$wpdb->insert($wpdb->base_prefix."ht_ip_list", array("ip_address_start"=>$remoteAddress, "ip_address_end"=>$remoteAddress, "ip_number_start"=>$ipNumber, "ip_number_end"=>$ipNumber, "offense_level"=>1, "insert_time"=>time(), "notes"=>$activityNotes, "activity_count"=>1));
						$deleteActivityQuery = "DELETE FROM ".$wpdb->base_prefix."ht_activity WHERE ip_address=%s";
						$wpdb->query($wpdb->prepare($deleteActivityQuery, $remoteAddress));
					}
				}
			}
		}

		$this->remove_blocked_ips();
	}
	
	function remove_blocked_ips() {
		global $wpdb;
		##Check for blocked ips to be removed
		if (get_site_option('_site_transient_timeout_HT_delete_blocked_ips') == '' || false === get_site_transient('HT_delete_blocked_ips')) {
			$ht404BlockTime = get_site_option('ht_404_block_time', "86400");
			$htLoginBlockTime = get_site_option('ht_login_block_time', "86400");
				
			$deleteBlockedIPQuery = "DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE (insert_time <= %d AND offense_level = 1) OR (insert_time <= %d AND offense_level = 10)";
			$wpdb->query($wpdb->prepare($deleteBlockedIPQuery, time()-$ht404BlockTime, time()-$htLoginBlockTime));
			set_site_transient('HT_delete_blocked_ips', 1, 1800);
		}

		##Check Project Honeypot and Spamcop records
		if (get_site_option('_site_transient_timeout_HT_spam_records_checked') == '' || false === get_site_transient('HT_spam_records_checked')) {
			$htPHAPIKey = get_site_option('ht_ph_api_key');
			$htPHMaxDays = get_site_option('ht_ph_bl_max_days', '255');
			$htPHThreatLevel = get_site_option('ht_ph_bl_threat_score', '10');
			$htPHCheckIPInterval = get_site_option('ht_ph_check_ip_interval', '14');
			$htUseProjectHoneypot = get_site_option('ht_use_project_honeypot', '0');
			$htUseSpamcop = get_site_option('ht_use_spamcop', '0');

			if ($htUseProjectHoneypot == '1') {
				$honeypotRecordQuery = "SELECT ip_id, ip_address_start FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = 11 ORDER BY insert_time";
				$allHoneypotRecords = $wpdb->get_results($honeypotRecordQuery, ARRAY_A);
				foreach($allHoneypotRecords as $honeypotRecord) {
					$recordUpdated = 0;
					$dnsData = @dns_get_record($htPHAPIKey . "." . implode(".", array_reverse(explode(".", $honeypotRecord['ip_address_start']))) . ".dnsbl.httpbl.org", DNS_A);
					if (is_array($dnsData) && count($dnsData) > 0) {
						foreach($dnsData as $dnsRecord) {
							if (isset($dnsRecord['type']) && $dnsRecord['type'] == 'A' && isset($dnsRecord['ip'])) {
								$ipThreatScore = explode(".", $dnsRecord['ip']);
								if ($ipThreatScore[1] <= $htPHMaxDays && $ipThreatScore[2] >= $htPHThreatLevel && $ipThreatScore[3] > 0) {
									$wpdb->update($wpdb->base_prefix."ht_ip_list", array("insert_time"=>time(), "notes"=>"PROJECT HONEYPOT: ".$dnsRecord['ip']), array("ip_id"=>$honeypotRecord['ip_id']));
									$recordUpdated = 1;
								}
							}
						}
					}

					if ($recordUpdated == 0) {
						$deleteSpamRecordQuery = "DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id=%d";
						$wpdb->query($wpdb->prepare($deleteSpamRecordQuery, $honeypotRecord['ip_id']));
					}
				}
			}

			if ($htUseSpamcop == '1') {
				$spamcopRecordQuery = "SELECT ip_id, ip_address_start FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = 12 ORDER BY insert_time";
				$allSpamcopRecords = $wpdb->get_results($spamcopRecordQuery, ARRAY_A);
				foreach($allSpamcopRecords as $spamcopRecord) {
					$recordUpdated = 0;
					$dnsData = @dns_get_record(implode(".", array_reverse(explode(".", $spamcopRecord['ip_address_start']))) . ".bl.spamcop.net", DNS_A);
					if (is_array($dnsData) && count($dnsData) > 0) {
						foreach($dnsData as $dnsRecord) {
							if (isset($dnsRecord['type']) && $dnsRecord['type'] == 'A' && $dnsRecord['ip'] == '127.0.0.2') {
								$wpdb->update($wpdb->base_prefix."ht_ip_list", array("insert_time"=>time(), "notes"=>"SPAMCOP ENTRY"), array("ip_id"=>$spamcopRecord['ip_id']));
								$recordUpdated = 1;
							}
						}
					}

					if ($recordUpdated == 0) {
						$deleteSpamRecordQuery = "DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id=%d";
						$wpdb->query($wpdb->prepare($deleteSpamRecordQuery, $spamcopRecord['ip_id']));
					}
				}
			}
			set_site_transient('HT_spam_records_checked', 1, ($htPHCheckIPInterval * 86400));
		}
	}
	
	function show_bad_login_count($errors) {
		global $wpdb;
		if (get_site_option('ht_show_login_count') == '1') {
			$htLoginLimit = get_site_option('ht_login_limit', "3");
			$activityCount = $wpdb->get_row($wpdb->prepare("SELECT activity_id, activity_count FROM ".$wpdb->base_prefix."ht_activity WHERE ip_address=%s AND activity_type='login'", $_SERVER['REMOTE_ADDR']), ARRAY_A);
			if (is_array($activityCount)) {
				$errors->add('ht_login_attempts', '<strong>Login attempts left:</strong> '.($htLoginLimit-$activityCount['activity_count']));
			} else {
				$errors->add('ht_login_attempts', '<strong>Login attempts left:</strong> '.$htLoginLimit);
			}
		}
		return $errors;
	}
	
	function check_banned_users($user, $username) {
		global $wpdb;
		foreach($this->remoteIPList as $remoteAddress) {
			$ipNumber = $this->calculate_ip_number($remoteAddress);
			if ($ipNumber != 0 && !$this->check_whitelist($ipNumber, $remoteAddress) && get_site_option('ht_login_mon', "0") == "1") {
				$bannedUsernames = get_site_option('ht_banned_usernames', array());
				if (get_site_option('ht_hide_usernames') == '1') {
					$nicenameCount = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM ".$wpdb->base_prefix."users WHERE user_nicename=%s", sanitize_text_field($username)));
				} else {
					$nicenameCount = 0;
				}

				if (in_array($username, $bannedUsernames) || $nicenameCount != 0) {
					$activityNotes = 'Site: '.(isset($_SERVER['REQUEST_SCHEME']))? htmlentities($_SERVER['REQUEST_SCHEME']).'://':'';
					$activityNotes .= (isset($_SERVER['SERVER_NAME']))? htmlentities($_SERVER['SERVER_NAME']):((isset($_SERVER['HTTP_HOST']))? htmlentities($_SERVER['HTTP_HOST']):'');
					$activityNotes .= "\nUser: ".htmlentities(strip_tags($username))."\n";
					
					$wpdb->insert($wpdb->base_prefix."ht_ip_list", array("ip_address_start"=>$remoteAddress, "ip_address_end"=>$remoteAddress, "ip_number_start"=>$ipNumber, "ip_number_end"=>$ipNumber, "offense_level"=>10, "insert_time"=>time(), "notes"=>$activityNotes, "activity_count"=>1));
					$deleteActivityQuery = "DELETE FROM ".$wpdb->base_prefix."ht_activity WHERE ip_address=%s";
					$wpdb->query($wpdb->prepare($deleteActivityQuery, $remoteAddress));
					$htResponseCode = get_site_option('ht_response_code', "503");
					http_response_code($htResponseCode);
					die();
				}
			}
		}
		return $user;
	}

	function login_failed($username) {
		global $wpdb;
		foreach($this->remoteIPList as $remoteAddress) {
			$ipNumber = $this->calculate_ip_number($remoteAddress);
			if ($ipNumber != 0 && !$this->check_whitelist($ipNumber, $remoteAddress) && get_site_option('ht_login_mon', "0") == "1") {
				$htLoginLimit = get_site_option('ht_login_limit', "3");
				$htLoginTimeSpan = get_site_option('ht_login_time_span', "86400");
				
				$activityNotes = 'Site: '.((isset($_SERVER['REQUEST_SCHEME']))? htmlentities($_SERVER['REQUEST_SCHEME']).'://':'');
				$activityNotes .= (isset($_SERVER['SERVER_NAME']))? htmlentities($_SERVER['SERVER_NAME']):((isset($_SERVER['HTTP_HOST']))? htmlentities($_SERVER['HTTP_HOST']):'');
				$activityNotes .= "\nUser: ".htmlentities(strip_tags($username))."\n";
				$deleteActivityQuery = "DELETE FROM ".$wpdb->base_prefix."ht_activity WHERE last_activity < %d AND activity_type='login'";
				$wpdb->query($wpdb->prepare($deleteActivityQuery, time()-$htLoginTimeSpan));

				$storedActivity = $wpdb->get_row($wpdb->prepare("SELECT activity_id, activity_count, notes FROM ".$wpdb->base_prefix."ht_activity WHERE ip_address=%s AND activity_type='login'", $remoteAddress), ARRAY_A);
				$currentCount = 0;
				if (is_array($storedActivity)) {
					$activityNotes = (preg_match('/'.preg_quote($activityNotes, '/').'/', $storedActivity['notes']))? $storedActivity['notes'] : $storedActivity['notes']."\n".$activityNotes;
					$wpdb->update($wpdb->base_prefix."ht_activity", array("activity_count"=>$storedActivity['activity_count']+1, "last_activity"=>time(), "notes"=>$activityNotes), array("activity_id"=>$storedActivity['activity_id']));
					$currentCount = $storedActivity['activity_count']+1;
				} else {
					$wpdb->insert($wpdb->base_prefix."ht_activity", array("ip_address"=>$remoteAddress, "activity_count"=>1, "activity_type"=>"login", "last_activity"=>time(), "notes"=>$activityNotes));
					$currentCount = 1;
				}

				if ($currentCount >= $htLoginLimit) {
					$wpdb->insert($wpdb->base_prefix."ht_ip_list", array("ip_address_start"=>$remoteAddress, "ip_address_end"=>$remoteAddress, "ip_number_start"=>$ipNumber, "ip_number_end"=>$ipNumber, "offense_level"=>10, "insert_time"=>time(), "notes"=>$activityNotes, "activity_count"=>$currentCount));
					$deleteActivityQuery = "DELETE FROM ".$wpdb->base_prefix."ht_activity WHERE ip_address=%s";
					$wpdb->query($wpdb->prepare($deleteActivityQuery, $remoteAddress));
					$htResponseCode = get_site_option('ht_response_code', "503");
					http_response_code($htResponseCode);
					die();
				}
			}
		}
	}

	function user_logged_in() {
		global $wpdb;
		foreach($this->remoteIPList as $remoteAddress) {
			$deleteActivityQuery = "DELETE FROM ".$wpdb->base_prefix."ht_activity WHERE ip_address = %s AND activity_type='login'";
			$wpdb->query($wpdb->prepare($deleteActivityQuery, $remoteAddress));
		}
	}
	
	
	function add_ajax_notices() {
		?>
		<div id="HT-ui-notices" title="Basic dialog">
			<div id="HT-ajax-notices-container">
			</div>
		</div>
		<?php
	}
		
	function register_admin_style() {
		wp_register_style('font-awesome-4.7.0', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
		wp_register_style('HT-admin', $this->urlPath . '/css/admin.css');
	}
	
	function enqueue_admin_style() {
		wp_enqueue_style('font-awesome-4.7.0');
	}
	
	function single_admin_menu() {
		$pluginPage=add_menu_page( 'Honeypot Toolkit', 'Honeypot Toolkit', 'administrator', 'Honeypot_Toolkit', array($this->tpl, 'single_settings_page'), $this->urlPath.'/img/honeypot-icon.png');
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'admin_css'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'common_js'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'single_settings_page_js'));
		
		$pluginPage=add_submenu_page('Honeypot_Toolkit', 'Settings', 'Settings', 'administrator', 'Honeypot_Toolkit', array($this->tpl, 'single_settings_page'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'admin_css'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'common_js'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'single_settings_page_js'));
		
		if (get_site_option('ht_site_level_lists') == '1') {
			$pluginPage=add_submenu_page('Honeypot_Toolkit', 'Blocked List', 'Blocked List', 'administrator', 'HT_blocked_list', array($this->tpl, 'blocked_list_page'));
			add_action('admin_head-'.$pluginPage, array($this->tpl, 'admin_css'));
			add_action('admin_head-'.$pluginPage, array($this->tpl, 'common_js'));
			add_action('admin_head-'.$pluginPage, array($this->tpl, 'blocked_list_page_js'));
			
			$pluginPage=add_submenu_page('Honeypot_Toolkit', 'Activity List', 'Activity List', 'administrator', 'HT_activity_list', array($this->tpl, 'activity_list_page'));
			add_action('admin_head-'.$pluginPage, array($this->tpl, 'admin_css'));
			add_action('admin_head-'.$pluginPage, array($this->tpl, 'common_js'));
			add_action('admin_head-'.$pluginPage, array($this->tpl, 'activity_list_page_js'));
			
			$pluginPage=add_submenu_page('Honeypot_Toolkit', 'Whitelist', 'Whitelist', 'administrator', 'HT_whitelist', array($this->tpl, 'whitelist_page'));
			add_action('admin_head-'.$pluginPage, array($this->tpl, 'admin_css'));
			add_action('admin_head-'.$pluginPage, array($this->tpl, 'common_js'));
			add_action('admin_head-'.$pluginPage, array($this->tpl, 'whitelist_page_js'));
		}
	}
	
	function admin_menu() {
		$pluginPage=add_menu_page( 'Honeypot Toolkit', 'Honeypot Toolkit', 'administrator', 'Honeypot_Toolkit', array($this->tpl, 'settings_page'), $this->urlPath.'/img/honeypot-icon.png');
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'admin_css'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'common_js'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'settings_page_js'));
		
		$pluginPage=add_submenu_page('Honeypot_Toolkit', 'Settings', 'Settings', 'administrator', 'Honeypot_Toolkit', array($this->tpl, 'settings_page'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'admin_css'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'common_js'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'settings_page_js'));
		
		$pluginPage=add_submenu_page('Honeypot_Toolkit', 'Blocked List', 'Blocked List', 'administrator', 'HT_blocked_list', array($this->tpl, 'blocked_list_page'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'admin_css'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'common_js'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'blocked_list_page_js'));
		
		$pluginPage=add_submenu_page('Honeypot_Toolkit', 'Activity List', 'Activity List', 'administrator', 'HT_activity_list', array($this->tpl, 'activity_list_page'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'admin_css'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'common_js'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'activity_list_page_js'));
		
		$pluginPage=add_submenu_page('Honeypot_Toolkit', 'Whitelist', 'Whitelist', 'administrator', 'HT_whitelist', array($this->tpl, 'whitelist_page'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'admin_css'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'common_js'));
		add_action('admin_head-'.$pluginPage, array($this->tpl, 'whitelist_page_js'));
	}
	
	function activate() {
		global $wpdb;
		$IPTableSQL = "CREATE TABLE ".$wpdb->base_prefix."ht_ip_list (
			ip_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			ip_address_start varchar(50) NOT NULL DEFAULT '',
			ip_address_end varchar(50) NOT NULL DEFAULT '',
			ip_number_start bigint(20) NOT NULL DEFAULT 0,
			ip_number_end bigint(20) NOT NULL DEFAULT 0,
			insert_time int(12) NOT NULL DEFAULT 0,
			offense_level tinyint UNSIGNED NOT NULL DEFAULT 1,
			notes longtext NOT NULL DEFAULT '',
			activity_count bigint(20) NOT NULL DEFAULT 0,
			PRIMARY KEY (ip_id),
			KEY ht_address_idx (ip_number_start, ip_number_end, insert_time, offense_level)
		);";
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->base_prefix."ht_ip_list'") != $wpdb->base_prefix."ht_ip_list") {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($IPTableSQL);
		}

		$showColumnSql = "SHOW COLUMNS FROM ".$wpdb->base_prefix."ht_ip_list";
		$showColumnResults = $wpdb->get_results($showColumnSql);
		$updatedColumns = array(
			'ip_address_start' => array(0, "varchar(50)", "NOT NULL", "DEFAULT ''"),
			'ip_address_end' => array(0, "varchar(50)", "NOT NULL", "DEFAULT ''")
		);

		foreach ($showColumnResults as $column) {
			if (array_key_exists($column->Field, $updatedColumns) && $column->Type != $updatedColumns[$column->Field][1]) {
				$updatedColumns[$column->Field][0] = 1;
			}
		}

		foreach ($updatedColumns as $column=>$value) {
			if ($value[0] == 1) {
				$updateColumnSql = "ALTER TABLE ".$wpdb->base_prefix."ht_ip_list MODIFY " . $column . " " . $value[1] . " " . $value[2] . " " . $value[3] . ";";
				$updateColumnResult = $wpdb->query($updateColumnSql);
			}
		}

		$activityTableSQL = "CREATE TABLE ".$wpdb->base_prefix."ht_activity (
			activity_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			ip_address varchar(50) NOT NULL DEFAULT '',
			last_activity int(12) NOT NULL DEFAULT 0,
			activity_count int(3) NOT NULL DEFAULT 1,
			activity_type varchar(25) NOT NULL DEFAULT '',
			notes longtext NOT NULL default '',
			PRIMARY KEY (activity_id),
			KEY ht_activity_idx (ip_address, activity_type)
		);";
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->base_prefix."ht_activity'") != $wpdb->base_prefix."ht_activity") {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($activityTableSQL);
		}

		$showColumnSql = "SHOW COLUMNS FROM ".$wpdb->base_prefix."ht_activity";
		$showColumnResults = $wpdb->get_results($showColumnSql);
		$updatedColumns = array(
			'ip_address' => array(0, "varchar(50)", "NOT NULL", "DEFAULT ''")
		);

		foreach ($showColumnResults as $column) {
			if (array_key_exists($column->Field, $updatedColumns) && $column->Type != $updatedColumns[$column->Field][1]) {
				$updatedColumns[$column->Field][0] = 1;
			}
		}

		foreach ($updatedColumns as $column=>$value) {
			if ($value[0] == 1) {
				$updateColumnSql = "ALTER TABLE ".$wpdb->base_prefix."ht_activity MODIFY " . $column . " " . $value[1] . " " . $value[2] . " " . $value[3] . ";";
				$updateColumnResult = $wpdb->query($updateColumnSql);
			}
		}


		##Combine all ip lists into one to use site wide if each site has it's own list and make sure the plugin is active sitewide.
		if (is_multisite()) {
			$sitewidePlugins = get_site_option('active_sitewide_plugins', array());
		
			if (!array_key_exists($this->mainPluginFile, $sitewidePlugins)) {
				$sitewidePlugins[$this->mainPluginFile] = time();
				update_site_option('active_sitewide_plugins', $sitewidePlugins);
			}

			$networkSites = get_sites();
			if (sizeof($networkSites) > 0) {
				$originalBlogID = get_current_blog_id();
				foreach($networkSites as $site) {
					switch_to_blog($site->blog_id);
					if ($wpdb->prefix != $wpdb->base_prefix) {
						$blacklistEntries = $wpdb->get_results("SELECT ip_id, ip_address_start, ip_address_end, ip_number_start, ip_number_end, offense_level, notes FROM ".$wpdb->prefix."ht_ip_list WHERE offense_level > 3", ARRAY_A);
					
						foreach($blacklistEntries as $entry) {
							$blacklistExists = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = %d AND ip_number_start <= %d AND ip_number_end >= %d", $entry['offense_level'], $entry['ip_number_start'], $entry['ip_number_end']));
						
							if (is_numeric($blacklistExists) && $blacklistExists == 0) {
								$ipNotes = $entry['notes'];
								
								$blacklistRanges = $wpdb->get_results($wpdb->prepare("SELECT ip_id, ip_address_start, ip_address_end, ip_number_start, ip_number_end, notes FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = %d AND (ip_number_start <= %d AND ip_number_end >= %d) AND ip_number_end <= %d", $entry['offense_level'], $entry['ip_number_start'], $entry['ip_number_start'], $entry['ip_number_end']), ARRAY_A);
							
								$bestRange = array('ip_address_start'=>$entry['ip_address_start'], 'ip_address_end'=>$entry['ip_address_end'], 'ip_number_start'=>$entry['ip_number_start'], 'ip_number_end'=>$entry['ip_number_end']);
								if (is_array($blacklistRanges)) {
									foreach($blacklistRanges as $blacklistRange) {
										if ($blacklistRange['ip_number_start'] < $bestRange['ip_number_start']) {
											$bestRange['ip_number_start'] = $blacklistRange['ip_number_start'];
											$bestRange['ip_address_start'] = $blacklistRange['ip_address_start'];
											$ipNotes .= $blacklistRange['notes'];
										}
										$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $blacklistRange['ip_id']));
									}
								}

								$blacklistRanges = $wpdb->get_results($wpdb->prepare("SELECT ip_id, ip_address_start, ip_address_end, ip_number_start, ip_number_end FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = %d AND (ip_number_start <= %d AND ip_number_end >= %d) AND ip_number_start >= %d", $entry['offense_level'], $entry['ip_number_end'], $entry['ip_number_end'], $entry['ip_number_start']), ARRAY_A);

								if (is_array($blacklistRanges)) {
									foreach($blacklistRanges as $blacklistRange) {
										if ($blacklistRange['ip_number_end'] > $bestRange['ip_number_end']) {
											$bestRange['ip_number_end'] = $blacklistRange['ip_number_end'];
											$bestRange['ip_address_end'] = $blacklistRange['ip_address_end'];
											$ipNotes .= $blacklistRange['notes'];
										}
										$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $blacklistRange['ip_id']));
									}
								}
								
								$wpdb->insert($wpdb->base_prefix."ht_ip_list", array("ip_address_start"=>$bestRange['ip_address_start'], "ip_address_end"=>$bestRange['ip_address_end'], "ip_number_start"=>$bestRange['ip_number_start'], "ip_number_end"=>$bestRange['ip_number_end'], "offense_level"=>$entry['offense_level'], "insert_time"=>time(), 'notes'=>$ipNotes));
							}
						}

						$whitelistEntries = $wpdb->get_results("SELECT ip_id, ip_address_start, ip_address_end, ip_number_start, ip_number_end, offense_level, notes FROM ".$wpdb->prefix."ht_ip_list WHERE offense_level = 0", ARRAY_A);
						
						foreach($whitelistEntries as $entry) {
							$whitelistExists = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = 0 AND ip_number_start <= %d AND ip_number_end >= %d", $ipNumberStart, $ipNumberEnd));
						
							if (is_numeric($whitelistExists) && $whitelistExists == 0) {

								$ipNotes = $entry['notes'];
								
								$whitelistRanges = $wpdb->get_results($wpdb->prepare("SELECT ip_id, ip_address_start, ip_address_end, ip_number_start, ip_number_end, notes FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = 0 AND (ip_number_start <= %d AND ip_number_end >= %d) AND ip_number_end <= %d", $entry['ip_number_start'], $entry['ip_number_start'], $entry['ip_number_end']), ARRAY_A);
							
								$bestRange = array('ip_address_start'=>$entry['ip_address_start'], 'ip_address_end'=>$entry['ip_address_end'], 'ip_number_start'=>$entry['ip_number_start'], 'ip_number_end'=>$entry['ip_number_end']);
								if (is_array($whitelistRanges)) {
									foreach($whitelistRanges as $whitelistRange) {
										if ($whitelistRange['ip_number_start'] < $bestRange['ip_number_start']) {
											$bestRange['ip_number_start'] = $whitelistRange['ip_number_start'];
											$bestRange['ip_address_start'] = $whitelistRange['ip_address_start'];
											$ipNotes .= $whitelistRange['notes'];
										}
										$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $whitelistRange['ip_id']));
									}
								}

								$whitelistRanges = $wpdb->get_results($wpdb->prepare("SELECT ip_id, ip_address_start, ip_address_end, ip_number_start, ip_number_end FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = 0 AND (ip_number_start <= %d AND ip_number_end >= %d) AND ip_number_start >= %d", $entry['ip_number_end'], $entry['ip_number_end'], $entry['ip_number_start']), ARRAY_A);

								if (is_array($whitelistRanges)) {
									foreach($whitelistRanges as $whitelistRange) {
										if ($whitelistRange['ip_number_end'] > $bestRange['ip_number_end']) {
											$bestRange['ip_number_end'] = $whitelistRange['ip_number_end'];
											$bestRange['ip_address_end'] = $whitelistRange['ip_address_end'];
											$ipNotes .= $whitelistRange['notes'];
										}
										$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $whitelistRange['ip_id']));
									}
								}
								
								$wpdb->insert($wpdb->base_prefix."ht_ip_list", array("ip_address_start"=>$bestRange['ip_address_start'], "ip_address_end"=>$bestRange['ip_address_end'], "ip_number_start"=>$bestRange['ip_number_start'], "ip_number_end"=>$bestRange['ip_number_end'], "offense_level"=>0, "insert_time"=>time(), 'notes'=>$ipNotes));
							}
						}
						$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."ht_ip_list");
						$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."ht_activity");

						delete_option("ht_only_allow_whitelist");
						delete_option("ht_login_mon");
						delete_option("ht_login_limit");
						delete_option("ht_login_time_span");
						delete_option("ht_login_block_time");
						delete_option("ht_show_login_count");
						delete_option("ht_404_mon");
						delete_option("ht_404_limit");
						delete_option("ht_404_time_span");
						delete_option("ht_404_block_time");
						delete_option("ht_response_code");
						delete_option("ht_banned_usernames");
						delete_option('ht_ph_api_key');
						delete_option('ht_ph_bl_max_days');
						delete_option('ht_ph_check_ip_interval');
						delete_option('ht_bl_threat_score');
						delete_option('ht_use_project_honeypot');
						delete_option('ht_use_spamcop');
						delete_option('ht_ph_check_ip_interval');
					}
				}


				switch_to_blog($originalBlogID);
			}
		}
		
		
		if (get_site_option('ht_only_allow_whitelist') == "") {
			update_site_option('ht_only_allow_whitelist', "0");
		}

		if (get_site_option('ht_login_mon') == "") {
			update_site_option('ht_login_mon', "0");
		}
		
		if (get_site_option('ht_login_limit') == "") {
			update_site_option('ht_login_limit', "3");
		}

		if (get_site_option('ht_login_time_span') == "") {
			update_site_option('ht_login_time_span', "3600");
		}

		if (get_site_option('ht_login_block_time') == "") {
			update_site_option('ht_login_block_time', "86400");
		}

		if (get_site_option('ht_show_login_count') == "") {
			update_site_option('ht_show_login_count', "0");
		}

		if (get_site_option('ht_404_mon') == "") {
			update_site_option('ht_404_mon', "0");
		}
		
		if (get_site_option('ht_404_limit') == "") {
			update_site_option('ht_404_limit', "10");
		}

		if (get_site_option('ht_404_time_span') == "") {
			update_site_option('ht_404_time_span', "3600");
		}

		if (get_site_option('ht_404_block_time') == "") {
			update_site_option('ht_404_block_time', "86400");
		}

		if (get_site_option('ht_response_code') == "") {
			update_site_option('ht_response_code', "503");
		}

		if (get_site_option('ht_ph_bl_max_days') == "") {
			update_site_option('ht_ph_bl_max_days', "255");
		}

		if (get_site_option('ht_ph_bl_threat_score') == "") {
			update_site_option('ht_ph_bl_threat_score', "10");
		}

		if (get_site_option('ht_use_project_honeypot') == "") {
			update_site_option('ht_use_project_honeypot', "0");
		}

		if (get_site_option('ht_use_spamcop') == "") {
			update_site_option('ht_use_spamcop', "0");
		}

		if (get_site_option('ht_hide_usernames') == "") {
			update_site_option('ht_hide_usernames', "0");
		}

		if (get_site_option('ht_site_level_lists') == "") {
			update_site_option('ht_site_level_lists', "0");
		}

		update_site_option('ht_plugin_version', "4.4.4");
	}

	function check_version() {
		if (get_site_option('ht_plugin_version') != "4.4.4") {
			$this->activate();
		}
	}
	
	function gen_honeypot_link() {
		$honeypotLink = '';
		$honeypotPath = get_option('ht_honeypot_path', '/');
		$linkText = $this->gen_random_string(mt_rand(5,32));
		switch(mt_rand(1,5)) {
			default:
			case 1:
				$honeypotLink = '<div style="display: none;"><a rel="nofollow" href="'.$honeypotPath.'" title="'.$linkText.'">'.$linkText.'</a></div>';
				break;
			case 2:
				$honeypotLink = '<a rel="nofollow" href="'.$honeypotPath.'" style="display: none;" title="'.$linkText.'">'.$linkText.'</a>';
				break;
			case 3:
				$honeypotLink = '<a rel="nofollow" href="'.$honeypotPath.'" style="display: none;" title="'.$linkText.'"><!-- '.$linkText.' --></a>';
				break;
			case 4:
				$honeypotLink = '<!-- <a rel="nofollow" href="'.$honeypotPath.'" title="'.$linkText.'">'.$linkText.'</a> -->';
				break;
			case 5:
				$honeypotLink = '<a rel="nofollow" href="'.$honeypotPath.'" style="display: none;" title="'.$linkText.'"></a>';
				break;
		}
		return $honeypotLink;
	}

	function gen_random_string($length) {
		$string = '';
		for ($i=0; $i<$length; $i++) {
			$char = '';
			switch(mt_rand(1,5)) {
				case 2:
					$char = chr(mt_rand(65,79));
					break;
				case 4:
					$char = chr(mt_rand(80,90));
					break;
				case 1:
					$char = chr(mt_rand(109,122));
					break;
				case 3:
					$char = chr(mt_rand(97,108));
					break;
				case 5:
					$char = ' ';
					break;
			}
			$string .= $char;
		}
		return $string;
	}
	
	function insert_honeypot_link($content) {
		if (!doing_action('get_the_excerpt')) {
			$content .= $this->gen_honeypot_link();
		}
		return $content;
	}

	function print_honeypot_link() {
		print $this->gen_honeypot_link();
	}

	public static function check_whitelist($ipNumber, $ipAddress) {
		global $wpdb;
		if ($ipAddress != '' && isset($_SERVER['SERVER_ADDR']) && $ipAddress == $_SERVER['SERVER_ADDR']) {
			##The web server is talking to itself
			return true;
		} else if ($ipNumber > 0) {
			$ipID = $wpdb->get_var($wpdb->prepare("SELECT ip_id FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_number_start <= %d AND ip_number_end >= %d AND offense_level = 0", $ipNumber, $ipNumber));
			if ($ipID !== null && is_numeric($ipID)) {
				if (!isset($GLOBALS['ht_whitelist_count_incremented'][$ipAddress])) {
					$incrementActivityQuery = "UPDATE ".$wpdb->base_prefix."ht_ip_list SET activity_count=activity_count+1 WHERE ip_id=%d";
					$incrementActivityRes = $wpdb->query($wpdb->prepare($incrementActivityQuery, $ipID));
					$GLOBALS['ht_whitelist_count_incremented'][$ipAddress] = 1;
				}
				return true;
			}
		} else if ($ipNumber == -1) {
			$ipID = $wpdb->get_var($wpdb->prepare("SELECT ip_id FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_address_start = %s AND offense_level = 0", $ipAddress));
			if ($ipID !== null && is_numeric($ipID)) {
				if (!isset($GLOBALS['ht_whitelist_count_incremented'][$ipAddress])) {
					$incrementActivityQuery = "UPDATE ".$wpdb->base_prefix."ht_ip_list SET activity_count=activity_count+1 WHERE ip_id=%d";
					$incrementActivityRes = $wpdb->query($wpdb->prepare($incrementActivityQuery, $ipID));
					$GLOBALS['ht_whitelist_count_incremented'][$ipAddress] = 1;
				}
				return true;
			}
		}
		return false;
	}
	
	public static function check_block_list() {
		global $wpdb;
		$ht404BlockTime = get_site_option('ht_404_block_time', "86400");
		$htLoginBlockTime = get_site_option('ht_login_block_time', "86400");
		$htOnlyAllowWhitelist = get_site_option('ht_only_allow_whitelist', '0');
		$htResponseCode = get_site_option('ht_response_code', "503");
		$htPHAPIKey = get_site_option('ht_ph_api_key');
		$htPHMaxDays = get_site_option('ht_ph_bl_max_days', '255');
		$htPHThreatLevel = get_site_option('ht_ph_bl_threat_score', '10');
		$htUseProjectHoneypot = get_site_option('ht_use_project_honeypot', '0');
		$htUseSpamcop = get_site_option('ht_use_spamcop', '0');

		##Delete expired temporary whitelist
		if (get_site_option('_site_transient_timeout_HT_clean_temp_whitelist') == '' || false === get_site_transient('HT_clean_temp_whitelist')) {
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = 3 AND insert_time <= %d", time()-3600));
			set_site_transient('HT_clean_temp_whitelist', 1, 86400);
		}
		
		##Create list of visitors IP Addresses
		$remoteIPList = array();
		foreach(array('HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR') as $serverKey) {
			if (isset($_SERVER[$serverKey])) {
				if (preg_match('/,/', $_SERVER[$serverKey])) {
					$splitIP = explode(',', $_SERVER[$serverKey]);
					foreach($splitIP as $remoteIP) {
						$remoteIP = sanitize_text_field(trim($remoteIP));
						if (!in_array($remoteIP, $remoteIPList)) {
							$remoteIPList[] = $remoteIP;
						}
					}
				} else {
					if (!in_array($_SERVER[$serverKey], $remoteIPList)) {
						$remoteIPList[] = sanitize_text_field($_SERVER[$serverKey]);
					}
				}
			}
		}

		foreach($remoteIPList as $remoteAddress) {
			$ipNumber = HoneypotToolkit::calculate_ip_number($remoteAddress);
			if ($ipNumber != 0 && !HoneypotToolkit::check_whitelist($ipNumber, $remoteAddress)) {
				if ($htOnlyAllowWhitelist == '1' || ($ipNumber > 0 && $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_number_start <= %d AND ip_number_end >= %d AND (offense_level >= 11 OR (insert_time > %d AND offense_level = 1) OR (insert_time > %d AND offense_level = 10))", $ipNumber, $ipNumber, time()-$ht404BlockTime, time()-$htLoginBlockTime)) > 0) || ($ipNumber == -1 && $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_address_start = %s AND (offense_level >= 11 OR (insert_time > %d AND offense_level = 1) OR (insert_time > %d AND offense_level = 10))", $remoteAddress, time()-$ht404BlockTime, time()-$htLoginBlockTime)) > 0)) {
					if ($ipNumber > 0) {
						$ipID = $wpdb->get_var($wpdb->prepare("SELECT ip_id FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_number_start <= %d AND ip_number_end >= %d", $ipNumber, $ipNumber));
						if ($ipID !== null && is_numeric($ipID)) {
							$incrementActivityQuery = "UPDATE ".$wpdb->base_prefix."ht_ip_list SET insert_time=".time().", activity_count=activity_count+1 WHERE ip_id = %d";
							$incrementActivityRes = $wpdb->query($wpdb->prepare($incrementActivityQuery, $ipID));
						}
					} else if ($ipNumber == -1) {
						$ipID = $wpdb->get_var($wpdb->prepare("SELECT ip_id FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_address_start = %s", $remoteAddress));
						if ($ipID !== null && is_numeric($ipID)) {
							$incrementActivityQuery = "UPDATE ".$wpdb->base_prefix."ht_ip_list SET insert_time=".time().", activity_count=activity_count+1 WHERE ip_id=%d";
							$incrementActivityRes = $wpdb->query($wpdb->prepare($incrementActivityQuery, $ipID));
						}
					}
					http_response_code($htResponseCode);
					die();
				} else if ($ipNumber != -1) {
					$tmpWhitelistEntry = $wpdb->get_results($wpdb->prepare("SELECT ip_id, insert_time FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = 3 AND ip_address_start = %s", $remoteAddress), ARRAY_A);
					if (!isset($tmpWhitelistEntry[0]) || $tmpWhitelistEntry[0]['insert_time'] <= time()-3600) {
						if (isset($tmpWhitelistEntry[0])) {
							$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = 3 AND ip_address_start = %s", $remoteAddress));
						}

						if ($htUseProjectHoneypot == '1' && $htPHAPIKey != '') {
							$dnsData = @dns_get_record($htPHAPIKey . "." . implode(".", array_reverse(explode(".", $remoteAddress))) . ".dnsbl.httpbl.org", DNS_A);
							if (is_array($dnsData) && count($dnsData) > 0) {
								foreach($dnsData as $dnsRecord) {
									if (isset($dnsRecord['type']) && $dnsRecord['type'] == 'A' && isset($dnsRecord['ip'])) {
										$ipThreatScore = explode(".", $dnsRecord['ip']);
										if ($ipThreatScore[1] <= $htPHMaxDays && $ipThreatScore[2] >= $htPHThreatLevel && $ipThreatScore[3] > 0) {
											$wpdb->insert($wpdb->base_prefix."ht_ip_list", array("ip_address_start"=>$remoteAddress, "ip_address_end"=>$remoteAddress, "ip_number_start"=>$ipNumber, "ip_number_end"=>$ipNumber, "offense_level"=>11, "insert_time"=>time(), "notes"=>"PROJECT HONEYPOT: ".$dnsRecord['ip'], "activity_count"=>1));
											$deleteActivityQuery = "DELETE FROM ".$wpdb->base_prefix."ht_activity WHERE ip_address=%s";
											$wpdb->query($wpdb->prepare($deleteActivityQuery, $remoteAddress));
											http_response_code($htResponseCode);
											die();
										}
									}
								}
							}
						}

						if ($htUseSpamcop == '1') {
							$dnsData = @dns_get_record(implode(".", array_reverse(explode(".", $remoteAddress))) . ".bl.spamcop.net", DNS_A);
							if (is_array($dnsData) && count($dnsData) > 0) {
								foreach($dnsData as $dnsRecord) {
									if (isset($dnsRecord['type']) && $dnsRecord['type'] == 'A' && $dnsRecord['ip'] == '127.0.0.2') {
										$wpdb->insert($wpdb->base_prefix."ht_ip_list", array("ip_address_start"=>$remoteAddress, "ip_address_end"=>$remoteAddress, "ip_number_start"=>$ipNumber, "ip_number_end"=>$ipNumber, "offense_level"=>12, "insert_time"=>time(), "notes"=>"SPAMCOP ENTRY", "activity_count"=>1));
										$deleteActivityQuery = "DELETE FROM ".$wpdb->base_prefix."ht_activity WHERE ip_address=%s";
										$wpdb->query($wpdb->prepare($deleteActivityQuery, $remoteAddress));
										http_response_code($htResponseCode);
										die();
									}
								}
							}
						}

						$wpdb->insert($wpdb->base_prefix."ht_ip_list", array("ip_address_start"=>$remoteAddress, "ip_address_end"=>$remoteAddress, "ip_number_start"=>$ipNumber, "ip_number_end"=>$ipNumber, "offense_level"=>3, "insert_time"=>time(), "notes"=>"Temporary whitelist"));
					}
				}
			}
		}
	}
	
	public static function calculate_ip_number($ipAddress) {
		if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			return ip2long($ipAddress);
		} else if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			return -1;
		} else {
			return 0;
		}
	}

}
?>