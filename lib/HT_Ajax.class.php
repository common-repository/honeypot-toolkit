<?php
class HT_Ajax {
	var $HT;

	function __construct($HT) {
		$this->HT = $HT;
	}

	function remove_ip() {
		global $wpdb;
		if (!$this->HT->verify_nonce($_POST['HT_nonce'])) {
			die();
		}
		
		$result = array('success'=>0, 'message'=>'');
		if (isset($_POST['activity_ids']) && is_array($_POST['activity_ids'])) {
			foreach($_POST['activity_ids'] as $activityID) {
				$activityID = sanitize_text_field($activityID);
				if (is_numeric($activityID)) {
					if ($wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_activity WHERE activity_id = %d", $activityID)) !== false) {
						$result['success'] = 1;
						$result['message'] = 'The IP range was successfully removed';
					}
				}
			}
		} else if (isset($_POST['ip_list_ids']) && is_array($_POST['ip_list_ids'])) {
			foreach($_POST['ip_list_ids'] as $ipListId) {
				$ipListId = sanitize_text_field($ipListId);
				if (is_numeric($ipListId)) {
					if ($wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $ipListId)) !== false) {
						$result['success'] = 1;
						$result['message'] = 'The IP range was successfully removed';
					}
				}
			}
		}
		print json_encode($result);
		die();
	}
	
	function blacklist_ip() {
		global $wpdb;
		
		if (!$this->HT->verify_nonce($_POST['HT_nonce'])) {
			die();
		}
		
		if (isset($_POST['activity_ids'])) {
			if (is_array($_POST['activity_ids'])) {
				$result = array('success'=>0, 'message'=>'');
				foreach($_POST['activity_ids'] as $activityID) {
					$activityID = sanitize_text_field($activityID);
					if (is_numeric($activityID)) {
						$activityDetails = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."ht_activity WHERE activity_id = %d", $activityID), ARRAY_A);
						if (is_array($activityDetails)) {
							$ipNumber = $this->HT->calculate_ip_number($activityDetails['ip_address']);
							if ($ipNumber > 0) {
								$blockedID = $wpdb->get_var($wpdb->prepare("SELECT ip_id FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_number_start = %d AND ip_number_end = %d", $ipNumber, $ipNumber));
							} else if ($ipNumber == -1) {
								$blockedID = $wpdb->get_var($wpdb->prepare("SELECT ip_id FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_address_start = %s", $activityDetails['ip_address']));
							} else {
								$blockedID = 0;
							}

							if (is_numeric($blockedID)) {
								$blockedNotes = $wpdb->get_var($wpdb->prepare("SELECT notes FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $blockedID));
								$wpdb->update($wpdb->base_prefix."ht_ip_list", array("offense_level"=>15, "notes"=>$blockedNotes."\n".$activityDetails['notes']), array("ip_id"=>$blockedID));
								$result['success'] = 1;
								$result['message'] = "That IP has already been blocked.  If it wasn't on the blocked list it has been added.";
							} else {
								$wpdb->insert($wpdb->base_prefix."ht_ip_list", array("ip_address_start"=>$activityDetails['ip_address'], "ip_address_end"=>$activityDetails['ip_address'], "ip_number_start"=>$ipNumber, "ip_number_end"=>$ipNumber, "offense_level"=>15, "insert_time"=>time(), "notes"=>$activityDetails['notes']));
								$result['success'] = 1;
								$result['message'] = "The IP was successfully added to the blocked list.";
							}

							if ($result['success'] == 1) {
								$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_activity WHERE activity_id = %d", $activityID));
							}
						}
					}
				}
				print json_encode($result);
			}
		} else if (isset($_POST['ip_list_ids'])) {
			if (is_array($_POST['ip_list_ids'])) {
				$result = array('success'=>0, 'message'=>'');
				foreach($_POST['ip_list_ids'] as $ipListID) {
					$ipListID = sanitize_text_field($ipListID);
					if (is_numeric($ipListID)) {
						$ipListID = $wpdb->get_var($wpdb->prepare("SELECT ip_id FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $ipListID));
						if (is_numeric($ipListID)) {
							$wpdb->update($wpdb->base_prefix."ht_ip_list", array("offense_level"=>15), array("ip_id"=>$ipListID));
							$result['success'] = 1;
							$result['message'] = "The IP was successfully added to the blacklist.";
						}
					}
				}
				print json_encode($result);
			}
		} else if (isset($_POST['new_ip_address_start'])) {
			$result = array('success'=>0, 'message'=>'');
			
			if (filter_var($_POST['new_ip_address_start'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($_POST['new_ip_address_start'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
				$ipNumberStart = $this->HT->calculate_ip_number(sanitize_text_field($_POST['new_ip_address_start']));
				$newIPAddressStart = sanitize_text_field($_POST['new_ip_address_start']);
			} else {
				$ipNumberStart = 0;
				$newIPAddressStart = 0;
			}
			
			if (filter_var($_POST['new_ip_address_end'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($_POST['new_ip_address_end'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
				$ipNumberEnd = $this->HT->calculate_ip_number(sanitize_text_field($_POST['new_ip_address_end']));
				$newIPAddressEnd = sanitize_text_field($_POST['new_ip_address_end']);
			} else {
				$ipNumberEnd = 0;
				$newIPAddressEnd = 0;
			}

			$ipNotes = sanitize_textarea_field($_POST['new_ip_notes']);
			$ipOffenseLevel = (in_array($_POST['new_ip_offense_level'], array(1,10,15)))? (int)$_POST['new_ip_offense_level'] : 15;

			if ($ipNumberStart == 0) {
				$result['message'] = 'The starting IP address of the range you submitted is invalid.';
				$result['success'] = 0;
			} else if ($ipNumberEnd == 0) {
				$result['message'] = 'The ending IP address of the range you submitted is invalid.';
				$result['success'] = 0;
			} else if (filter_var($_POST['new_ip_address_start'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $_POST['new_ip_address_start'] != $_POST['new_ip_address_end']) {
				$result['message'] = 'Ranges are not supported for IPv6 addresses.  The starting and ending addresses must match when using IPv6.';
				$result['success'] = 0;
			} else if ($ipNumberStart > 0 && $ipNumberStart > $ipNumberEnd) {
				$result['message'] = 'The starting IP address must be smaller than or equal to the ending IP address to be a valid IP range.';
				$result['success'] = 0;
			} else {
				if ($ipNumberStart > 0) {
					$blacklistID = $wpdb->get_var($wpdb->prepare("SELECT ip_id FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = %d AND ip_number_start = %d AND ip_number_end = %d", $ipOffenseLevel, $ipNumberStart, $ipNumberEnd));
				} else if ($ipNumberStart == -1) {
					$blacklistID = $wpdb->get_var($wpdb->prepare("SELECT ip_id FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = %d AND ip_address_start = %s", $ipOffenseLevel, $newIPAddressStart));
				}
				
				if (is_numeric($blacklistID)) {
					$wpdb->update($wpdb->base_prefix."ht_ip_list", array("offense_level"=>$ipOffenseLevel, 'notes'=>$ipNotes), array("ip_id"=>$blacklistID));
					$result['success'] = 1;
					$result['message'] = "The IP range was successfully added to the blocked list.";
				} else {
					if ($ipNumberStart > 0) {
						$blacklistExists = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = %d AND ip_number_start <= %d AND ip_number_end >= %d", $ipOffenseLevel, $ipNumberStart, $ipNumberEnd));
					} else {
						$blacklistExists = 0;
					}
					
					if (is_numeric($blacklistExists) && $blacklistExists > 0) {
						$result['success'] = 1;
						$result['message'] = "The IP range is already covered in another blacklist entry.";
					} else {

						if ($ipNumberStart > 0) {
							$blacklistRanges = $wpdb->get_results($wpdb->prepare("SELECT ip_id, ip_address_start, ip_address_end, ip_number_start, ip_number_end FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = %d AND (ip_number_start <= %d AND ip_number_end >= %d) AND ip_number_end <= %d", $ipOffenseLevel, $ipNumberStart, $ipNumberStart, $ipNumberEnd), ARRAY_A);
					
							$bestRange = array('ip_address_start'=>$newIPAddressStart, 'ip_address_end'=>$newIPAddressEnd, 'ip_number_start'=>$ipNumberStart, 'ip_number_end'=>$ipNumberEnd);
							if (is_array($blacklistRanges)) {
								foreach($blacklistRanges as $blacklistRange) {
									if ($blacklistRange['ip_number_start'] < $bestRange['ip_number_start']) {
										$bestRange['ip_number_start'] = $blacklistRange['ip_number_start'];
										$bestRange['ip_address_start'] = $blacklistRange['ip_address_start'];
									}
									$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $blacklistRange['ip_id']));
								}
							}

							$blacklistRanges = $wpdb->get_results($wpdb->prepare("SELECT ip_id, ip_address_start, ip_address_end, ip_number_start, ip_number_end FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = %d AND (ip_number_start <= %d AND ip_number_end >= %d) AND ip_number_start >= %d", $ipOffenseLevel, $ipNumberEnd, $ipNumberEnd, $ipNumberStart), ARRAY_A);

							if (is_array($blacklistRanges)) {
								foreach($blacklistRanges as $blacklistRange) {
									if ($blacklistRange['ip_number_end'] > $bestRange['ip_number_end']) {
										$bestRange['ip_number_end'] = $blacklistRange['ip_number_end'];
										$bestRange['ip_address_end'] = $blacklistRange['ip_address_end'];
									}
									$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $blacklistRange['ip_id']));
								}
							}
						} else {
							$bestRange = array('ip_address_start'=>$newIPAddressStart, 'ip_address_end'=>$newIPAddressEnd, 'ip_number_start'=>$ipNumberStart, 'ip_number_end'=>$ipNumberEnd);
						}
						
						$wpdb->insert($wpdb->base_prefix."ht_ip_list", array("ip_address_start"=>$bestRange['ip_address_start'], "ip_address_end"=>$bestRange['ip_address_end'], "ip_number_start"=>$bestRange['ip_number_start'], "ip_number_end"=>$bestRange['ip_number_end'], "offense_level"=>$ipOffenseLevel, "insert_time"=>time(), 'notes'=>$ipNotes));
						$result['success'] = 1;
						$result['message'] = "The IP range was successfully added to the blocked list.";
					}
				}
				if ($result['success'] == 1 && $ipNumberStart > 0) {
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = %d AND ip_number_start > %d AND ip_number_end < %d", $ipOffenseLevel, $ipNumberStart, $ipNumberEnd));
				}
			}
			
			print json_encode($result);
		}
		die();
	}

	function whitelist_ip() {
		global $wpdb;
		if (!$this->HT->verify_nonce($_POST['HT_nonce'])) {
			die();
		}
		
		if (isset($_POST['activity_ids'])) {
			if (is_array($_POST['activity_ids'])) {
				$result = array('success'=>0, 'message'=>'');
				foreach($_POST['activity_ids'] as $activityID) {
					$activityID = sanitize_text_field($activityID);
					$activityDetails = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."ht_activity WHERE activity_id = %d", $activityID), ARRAY_A);
					if (is_array($activityDetails)) {
						$ipNumber = $this->HT->calculate_ip_number($activityDetails['ip_address']);
						if ($ipNumber > 0) {
							$whitelistedID = $wpdb->get_var($wpdb->prepare("SELECT ip_id FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_number_start = %d AND ip_number_end = %d", $ipNumber, $ipNumber));
						} else if ($ipNumber == -1) {
							$whitelistedID = $wpdb->get_var($wpdb->prepare("SELECT ip_id FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_address_start = %s", $activityDetails['ip_address']));
						}

						if (is_numeric($whitelistedID)) {
							$whitelistNotes = $wpdb->get_var($wpdb->prepare("SELECT notes FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $whitelistedID));
							$wpdb->update($wpdb->base_prefix."ht_ip_list", array("offense_level"=>0, "notes"=>$whitelistNotes."\n".$activityDetails['notes']), array("ip_id"=>$whitelistedID));
							$result['success'] = 1;
							$result['message'] = "The IP was successfully added to the whitelist.";
						} else {
							$wpdb->insert($wpdb->base_prefix."ht_ip_list", array("ip_address_start"=>$activityDetails['ip_address'], "ip_address_end"=>$activityDetails['ip_address'], "ip_number_start"=>$ipNumber, "ip_number_end"=>$ipNumber, "offense_level"=>0, "insert_time"=>time(), "notes"=>$activityDetails['notes']));
							$result['success'] = 1;
							$result['message'] = "The IP was successfully added to the whitelist.";
						}

						if ($result['success'] == 1) {
							$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_activity WHERE activity_id = %d", $activityID));
						}
					}
				}
				print json_encode($result);
			}
		} else if (isset($_POST['ip_list_ids'])) {
			if (is_array($_POST['ip_list_ids'])) {
				$result = array('success'=>0, 'message'=>'');
				foreach($_POST['ip_list_ids'] as $ipListID) {
					$ipListID = sanitize_text_field($ipListID);
					$ipListID = $wpdb->get_var($wpdb->prepare("SELECT ip_id FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $ipListID));
					if (is_numeric($ipListID)) {
						$wpdb->update($wpdb->base_prefix."ht_ip_list", array("offense_level"=>0), array("ip_id"=>$ipListID));
						$result['success'] = 1;
						$result['message'] = "The IP was successfully added to the whitelist.";
					}
				}
				print json_encode($result);
			}
		} else if (isset($_POST['new_ip_address_start'])) {
			$result = array('success'=>0, 'message'=>'');
			if (filter_var($_POST['new_ip_address_start'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($_POST['new_ip_address_start'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
				$ipNumberStart = $this->HT->calculate_ip_number(sanitize_text_field($_POST['new_ip_address_start']));
				$newIPAddressStart = sanitize_text_field($_POST['new_ip_address_start']);
			} else {
				$ipNumberStart = 0;
				$newIPAddressStart = 0;
			}
			
			if (filter_var($_POST['new_ip_address_end'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($_POST['new_ip_address_end'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
				$ipNumberEnd = $this->HT->calculate_ip_number(sanitize_text_field($_POST['new_ip_address_end']));
				$newIPAddressEnd = sanitize_text_field($_POST['new_ip_address_end']);
			} else {
				$ipNumberEnd = 0;
				$newIPAddressEnd = 0;
			}
			
			$ipNotes = sanitize_textarea_field($_POST['new_ip_notes']);
			
			if ($ipNumberStart == 0) {
				$result['message'] = 'The starting IP address of the range you submitted is invalid.';
				$result['success'] = 0;
			} else if ($ipNumberEnd == 0) {
				$result['message'] = 'The ending IP address of the range you submitted is invalid.';
				$result['success'] = 0;
			} else if (filter_var($_POST['new_ip_address_start'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $_POST['new_ip_address_start'] != $_POST['new_ip_address_end']) {
				$result['message'] = 'Ranges are not supported for IPv6 addresses.  The starting and ending addresses must match when using IPv6.';
				$result['success'] = 0;
			} else if ($ipNumberStart > 0 && $ipNumberStart > $ipNumberEnd) {
				$result['message'] = 'The starting IP address must be smaller than or equal to the ending IP address to be a valid IP range.';
				$result['success'] = 0;
			} else {
				if ($ipNumberStart > 0) {
					$whitelistID = $wpdb->get_var($wpdb->prepare("SELECT ip_id FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_number_start = %d AND ip_number_end = %d", $ipNumberStart, $ipNumberEnd));
				} else if ($ipNumberStart == -1) {
					$whitelistID = $wpdb->get_var($wpdb->prepare("SELECT ip_id FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_address_start = %s", $newIPAddressStart));
				}
				
				if (is_numeric($whitelistID)) {
					$wpdb->update($wpdb->base_prefix."ht_ip_list", array("offense_level"=>0, 'notes'=>$ipNotes), array("ip_id"=>$whitelistID));
					$result['success'] = 1;
					$result['message'] = "The IP range was successfully added to the whitelist.";
				} else {
					if ($ipNumberStart > 0) {
						$whitelistExists = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = 0 AND ip_number_start <= %d AND ip_number_end >= %d", $ipNumberStart, $ipNumberEnd));
					} else {
						$whitelistExists = 0;
					}
					
					if (is_numeric($whitelistExists) && $whitelistExists > 0) {
						$result['success'] = 1;
						$result['message'] = "The IP range is already covered in another whitelist entry.";
					} else {

						if ($ipNumberStart > 0) {
							$whitelistRanges = $wpdb->get_results($wpdb->prepare("SELECT ip_id, ip_address_start, ip_address_end, ip_number_start, ip_number_end FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = 0 AND (ip_number_start <= %d AND ip_number_end >= %d) AND ip_number_end <= %d", $ipNumberStart, $ipNumberStart, $ipNumberEnd), ARRAY_A);
					
							$bestRange = array('ip_address_start'=>$newIPAddressStart, 'ip_address_end'=>$newIPAddressEnd, 'ip_number_start'=>$ipNumberStart, 'ip_number_end'=>$ipNumberEnd);
							if (is_array($whitelistRanges)) {
								foreach($whitelistRanges as $whitelistRange) {
									if ($whitelistRange['ip_number_start'] < $bestRange['ip_number_start']) {
										$bestRange['ip_number_start'] = $whitelistRange['ip_number_start'];
										$bestRange['ip_address_start'] = $whitelistRange['ip_address_start'];
									}
									$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $whitelistRange['ip_id']));
								}
							}

							$whitelistRanges = $wpdb->get_results($wpdb->prepare("SELECT ip_id, ip_address_start, ip_address_end, ip_number_start, ip_number_end FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = 0 AND (ip_number_start <= %d AND ip_number_end >= %d) AND ip_number_start >= %d", $ipNumberEnd, $ipNumberEnd, $ipNumberStart), ARRAY_A);

							if (is_array($whitelistRanges)) {
								foreach($whitelistRanges as $whitelistRange) {
									if ($whitelistRange['ip_number_end'] > $bestRange['ip_number_end']) {
										$bestRange['ip_number_end'] = $whitelistRange['ip_number_end'];
										$bestRange['ip_address_end'] = $whitelistRange['ip_address_end'];
									}
									$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $whitelistRange['ip_id']));
								}
							}
						} else {
							$bestRange = array('ip_address_start'=>$newIPAddressStart, 'ip_address_end'=>$newIPAddressEnd, 'ip_number_start'=>$ipNumberStart, 'ip_number_end'=>$ipNumberEnd);
						}
						
						$wpdb->insert($wpdb->base_prefix."ht_ip_list", array("ip_address_start"=>$bestRange['ip_address_start'], "ip_address_end"=>$bestRange['ip_address_end'], "ip_number_start"=>$bestRange['ip_number_start'], "ip_number_end"=>$bestRange['ip_number_end'], "offense_level"=>0, "insert_time"=>time(), 'notes'=>$ipNotes));
						$result['success'] = 1;
						$result['message'] = "The IP range was successfully added to the whitelist.";
					}
				}
				if ($result['success'] == 1 && $ipNumberStart > 0) {
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = 0 AND ip_number_start > %d AND ip_number_end < %d", $ipNumberStart, $ipNumberEnd));
				}
			}
			
			print json_encode($result);
		}
		die();
	}
	
	function save_single_settings() {
		if (!$this->HT->verify_nonce($_POST['HT_nonce'])) {
			die();
		}
		if (isset($_POST['ht-honeypot-path']) && preg_match($this->HT->validationRegex['ht-honeypot-path'], $_POST['ht-honeypot-path'])) {
			update_option('ht_honeypot_path', sanitize_text_field($_POST['ht-honeypot-path']));
		} else {
			update_option('ht_honeypot_path', "");
		}

		if (isset($_POST['ht-use-custom-honeypot']) && $_POST['ht-use-custom-honeypot'] == 'true') {
			update_option('ht_use_custom_honeypot', "1");
		} else {
			update_option('ht_use_custom_honeypot', "0");
		}

		if (isset($_POST['ht-use-body-open-honeypot']) && $_POST['ht-use-body-open-honeypot'] == 'true') {
			update_option('ht_use_body_open_honeypot', "1");
		} else {
			update_option('ht_use_body_open_honeypot', "0");
		}

		if (isset($_POST['ht-use-menu-honeypot']) && $_POST['ht-use-menu-honeypot'] == 'true') {
			update_option('ht_use_menu_honeypot', "1");
		} else {
			update_option('ht_use_menu_honeypot', "0");
		}

		if (isset($_POST['ht-use-search-form-honeypot']) && $_POST['ht-use-search-form-honeypot'] == 'true') {
			update_option('ht_use_search_form_honeypot', "1");
		} else {
			update_option('ht_use_search_form_honeypot', "0");
		}

		if (isset($_POST['ht-use-footer-honeypot']) && $_POST['ht-use-footer-honeypot'] == 'true') {
			update_option('ht_use_footer_honeypot', "1");
		} else {
			update_option('ht_use_footer_honeypot', "0");
		}

		if (isset($_POST['ht-use-the-content-honeypot']) && $_POST['ht-use-the-content-honeypot'] == 'true') {
			update_option('ht_use_the_content_honeypot', "1");
		} else {
			update_option('ht_use_the_content_honeypot', "0");
		}

		print json_encode(array('success'=>1, 'message'=>'Settings were successfully saved'));
		die();
	}
	
	function save_settings() {
		if (!$this->HT->verify_nonce($_POST['HT_nonce'])) {
			die();
		}
		
		if (isset($_POST['ht-use-spamcop']) && $_POST['ht-use-spamcop'] == 'true') {
			update_site_option('ht_use_spamcop', "1");
		} else {
			update_site_option('ht_use_spamcop', "0");
		}
		
		if (isset($_POST['ht-use-project-honeypot']) && $_POST['ht-use-project-honeypot'] == 'true') {
			update_site_option('ht_use_project_honeypot', "1");
		} else {
			update_site_option('ht_use_project_honeypot', "0");
		}
		
		if (isset($_POST['ht-ph-api-key']) && preg_match($this->HT->validationRegex['ht-ph-api-key'], $_POST['ht-ph-api-key'])) {
			update_site_option('ht_ph_api_key', sanitize_text_field($_POST['ht-ph-api-key']));
		}
		
		if (isset($_POST['ht-ph-bl-days']) && is_numeric($_POST['ht-ph-bl-days'])) {
			update_site_option('ht_ph_bl_days', sanitize_text_field($_POST['ht-ph-bl-days']));
		}
		
		if (isset($_POST['ht-ph-bl-threat-score']) && is_numeric($_POST['ht-ph-bl-threat-score'])) {
			update_site_option('ht_ph_bl_threat_score', sanitize_text_field($_POST['ht-ph-bl-threat-score']));
		}

		if (isset($_POST['ht-ph-check-ip-interval']) && is_numeric($_POST['ht-ph-check-ip-interval']) && get_site_option('ht_ph_check_ip_interval', 14) != sanitize_text_field($_POST['ht-ph-check-ip-interval'])) {
			update_site_option('ht_ph_check_ip_interval', sanitize_text_field($_POST['ht-ph-check-ip-interval']));
			delete_transient('HT_spam_records_checked');
			set_transient('HT_spam_records_checked', 1, (get_site_option('ht_ph_check_ip_interval', 14) * 86400));
		}
		
		if (isset($_POST['ht-only-allow-whitelist']) && in_array($_POST['ht-only-allow-whitelist'], array("1", "0"))) {
			update_site_option('ht_only_allow_whitelist', sanitize_text_field($_POST['ht-only-allow-whitelist']));
		} else {
			update_site_option('ht_only_allow_whitelist', "0");
		}
		
		if (isset($_POST['ht-login-mon']) && $_POST['ht-login-mon'] == 'true') {
			update_site_option('ht_login_mon', "1");
		} else {
			update_site_option('ht_login_mon', "0");
		}
		
		if (isset($_POST['ht-login-limit']) && is_numeric($_POST['ht-login-limit'])) {
			update_site_option('ht_login_limit', sanitize_text_field($_POST['ht-login-limit']));
		} else {
			update_site_option('ht_login_limit', "3");
		}

		if (isset($_POST['ht-login-time-span']) && is_numeric($_POST['ht-login-time-span'])) {
			update_site_option('ht_login_time_span', sanitize_text_field($_POST['ht-login-time-span']));
		} else {
			update_site_option('ht_login_time_span', "3600");
		}

		if (isset($_POST['ht-login-block-time']) && is_numeric($_POST['ht-login-block-time'])) {
			update_site_option('ht_login_block_time', sanitize_text_field($_POST['ht-login-block-time']));
		} else {
			update_site_option('ht_login_block_time', "86400");
		}

		if (isset($_POST['ht-show-login-count']) && in_array($_POST['ht-show-login-count'], array('1', '0'))) {
			update_site_option('ht_show_login_count', sanitize_text_field($_POST['ht-show-login-count']));
		} else {
			update_site_option('ht_show_login_count', '0');
		}

		if (isset($_POST['ht-404-mon']) && $_POST['ht-404-mon'] == 'true') {
			update_site_option('ht_404_mon', "1");
		} else {
			update_site_option('ht_404_mon', "0");
		}
		
		if (isset($_POST['ht-404-limit']) && is_numeric($_POST['ht-404-limit'])) {
			update_site_option('ht_404_limit', sanitize_text_field($_POST['ht-404-limit']));
		} else {
			update_site_option('ht_404_limit', "10");
		}

		if (isset($_POST['ht-404-time-span']) && is_numeric($_POST['ht-404-time-span'])) {
			update_site_option('ht_404_time_span', sanitize_text_field($_POST['ht-404-time-span']));
		} else {
			update_site_option('ht_404_time_span', "3600");
		}

		if (isset($_POST['ht-404-block-time']) && is_numeric($_POST['ht-404-block-time'])) {
			update_site_option('ht_404_block_time', sanitize_text_field($_POST['ht-404-block-time']));
		} else {
			update_site_option('ht_404_block_time', "86400");
		}
		
		if (isset($_POST['ht-response-code']) && is_numeric($_POST['ht-response-code'])) {
			update_site_option('ht_response_code', sanitize_text_field($_POST['ht-response-code']));
		} else {
			update_site_option('ht_response_code', "503");
		}

		if (isset($_POST['ht-banned-usernames']) && preg_match($this->HT->validationRegex['ht-banned-usernames'], $_POST['ht-banned-usernames'])) {
			$bannedUsernames = preg_replace("/\\r\\n/", "\n", sanitize_textarea_field($_POST['ht-banned-usernames']));
			$bannedUsernames = explode("\n", $bannedUsernames);
			if (!is_array($bannedUsernames)) {
				$bannedUsernames = array();
			}
			foreach ($bannedUsernames as $key=>$username) {
				if ($username == '') {
					unset($bannedUsernames[$key]);
				}
			}

			update_site_option('ht_banned_usernames', $bannedUsernames);
		}

		if (!is_multisite()) {
			if (isset($_POST['ht-honeypot-path']) && preg_match($this->HT->validationRegex['ht-honeypot-path'], $_POST['ht-honeypot-path'])) {
				update_option('ht_honeypot_path', sanitize_text_field($_POST['ht-honeypot-path']));
			} else {
				update_option('ht_honeypot_path', "");
			}

			if (isset($_POST['ht-use-custom-honeypot']) && $_POST['ht-use-custom-honeypot'] == 'true') {
				update_option('ht_use_custom_honeypot', "1");
			} else {
				update_option('ht_use_custom_honeypot', "0");
			}

			if (isset($_POST['ht-use-body-open-honeypot']) && $_POST['ht-use-body-open-honeypot'] == 'true') {
				update_option('ht_use_body_open_honeypot', "1");
			} else {
				update_option('ht_use_body_open_honeypot', "0");
			}

			if (isset($_POST['ht-use-menu-honeypot']) && $_POST['ht-use-menu-honeypot'] == 'true') {
				update_option('ht_use_menu_honeypot', "1");
			} else {
				update_option('ht_use_menu_honeypot', "0");
			}

			if (isset($_POST['ht-use-search-form-honeypot']) && $_POST['ht-use-search-form-honeypot'] == 'true') {
				update_option('ht_use_search_form_honeypot', "1");
			} else {
				update_option('ht_use_search_form_honeypot', "0");
			}

			if (isset($_POST['ht-use-footer-honeypot']) && $_POST['ht-use-footer-honeypot'] == 'true') {
				update_option('ht_use_footer_honeypot', "1");
			} else {
				update_option('ht_use_footer_honeypot', "0");
			}

			if (isset($_POST['ht-use-the-content-honeypot']) && $_POST['ht-use-the-content-honeypot'] == 'true') {
				update_option('ht_use_the_content_honeypot', "1");
			} else {
				update_option('ht_use_the_content_honeypot', "0");
			}
		}


		$hideUsers = 0;
		$resetUsers = 0;
		$userMessage = '';
		if (isset($_POST['ht-hide-usernames']) && $_POST['ht-hide-usernames'] == 'true') {
			if (get_site_option('ht_hide_usernames') != '1') {
				update_site_option('ht_hide_usernames', '1');
				$hideUsers = 1;
				$userMessage = "<br />The users will now be processed to hide them.";
			}
		} else {
			if (get_site_option('ht_hide_usernames') != '0') {
				update_site_option('ht_hide_usernames', '0');
				$resetUsers = 1;
				$userMessage = "<br />The users will now be processed to set them back to the default.";
			}
			#$this->HT->reset_usernames();
		}

		if (isset($_POST['ht-site-level-lists']) && $_POST['ht-site-level-lists'] == 'true') {
			update_site_option('ht_site_level_lists', '1');
		} else {
			update_site_option('ht_site_level_lists', '0');
		}
		
		print json_encode(array('success'=>1, 'message'=>'Settings were successfully saved.'.$userMessage, 'reset-users'=>$resetUsers, 'hide-users'=>$hideUsers));
		die();
	}

	function process_usernames() {
		global $wpdb;
		if (!$this->HT->verify_nonce($_POST['HT_nonce'])) {
			die();
		}
		
		$returnVals = array('success'=>0, 'msg'=>'', 'total'=>0, 'offset'=>0);
		if (is_numeric($_POST['user-offset'])) {
			$returnVals['offset'] = sanitize_text_field($_POST['user-offset']);
		}

		$userLimit = 100;
		if (is_numeric($_POST['user-limit'])) {
			$userLimit = sanitize_text_field($_POST['user-limit']);
		}

		if ($_POST['user-action'] == 'hide') {
			$this->HT->generate_random_usernames($returnVals['offset'], $userLimit);
		} else if ($_POST['user-action'] == 'reset') {
			$this->HT->reset_usernames($returnVals['offset'], $userLimit);
		}

		$returnVals['success'] = 1;

		$query = "SELECT COUNT(*) AS num_users FROM ".$wpdb->base_prefix."users";
		$returnVals['total'] = $wpdb->get_var($query);
		print json_encode($returnVals);
		die();
	}
	
	function retrieve_entry_details() {
		global $wpdb;
		$result = array('success'=>0, 'message'=>'');
		if (!$this->HT->verify_nonce($_POST['HT_nonce'])) {
			die();
		}
		
		if (isset($_POST['whitelist_id'])) {
			if (is_numeric($_POST['whitelist_id'])) {
				$ipListDetails = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", sanitize_text_field($_POST['whitelist_id'])), ARRAY_A);
				if (is_array($ipListDetails)) {
					$result['success'] = 1;
					$result['edit-ip-address-start'] = $ipListDetails['ip_address_start'];
					$result['edit-ip-address-end'] = $ipListDetails['ip_address_end'];
					$result['edit-ip-notes'] = stripslashes($ipListDetails['notes']);
					$result['whitelist-id'] = $ipListDetails['ip_id'];
				} else {
					$result['success'] = 0;
					$result['message'] = 'The whitelist entry was not found in the database.';
				}
			} else {
				$result['success'] = 0;
				$result['message'] = 'The whitelist entry was not found in the database.';
			}
		} else if (isset($_POST['blocked_id'])) {
			if (is_numeric($_POST['blocked_id'])) {
				$ipListDetails = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", sanitize_text_field($_POST['blocked_id'])), ARRAY_A);
				if (is_array($ipListDetails)) {
					$result['success'] = 1;
					$result['edit-ip-address-start'] = $ipListDetails['ip_address_start'];
					$result['edit-ip-address-end'] = $ipListDetails['ip_address_end'];
					$result['edit-ip-notes'] = stripslashes($ipListDetails['notes']);
					$result['edit-offense-level'] = $ipListDetails['offense_level'];
					$result['blocked-id'] = $ipListDetails['ip_id'];
				} else {
					$result['success'] = 0;
					$result['message'] = 'The blocked list entry was not found in the database.';
				}
			} else {
				$result['success'] = 0;
				$result['message'] = 'The blocked list entry was not found in the database.';
			}
		}
		
		print json_encode($result);
		die();
	}

	function edit_ip_list_entry() {
		global $wpdb;
		$result = array('success'=>0, 'message'=>'');
		if (!$this->HT->verify_nonce($_POST['HT_nonce'])) {
			die();
		}
		
		if (isset($_POST['edit_whitelist_id'])) {
			$editWhitelistID = sanitize_text_field($_POST['edit_whitelist_id']);
			if (is_numeric($editWhitelistID) && $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $editWhitelistID)) == 1) {
				if (filter_var($_POST['edit_ip_address_start'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($_POST['edit_ip_address_start'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
					$ipNumberStart = $this->HT->calculate_ip_number(sanitize_text_field($_POST['edit_ip_address_start']));
					$editIPAddressStart = sanitize_text_field($_POST['edit_ip_address_start']);
				} else {
					$ipNumberStart = 0;
					$editIPAddressStart = 0;
				}
				
				if (filter_var($_POST['edit_ip_address_end'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($_POST['edit_ip_address_end'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
					$ipNumberEnd = $this->HT->calculate_ip_number(sanitize_text_field($_POST['edit_ip_address_end']));
					$editIPAddressEnd = sanitize_text_field($_POST['edit_ip_address_end']);
				} else {
					$ipNumberEnd = 0;
					$editIPAddressEnd = 0;
				}
				
				$ipNotes = sanitize_textarea_field($_POST['edit_ip_notes']);
				
				if ($ipNumberStart == 0) {
					$result['message'] = 'The starting IP address of the range you submitted is invalid.';
					$result['success'] = 0;
				} else if ($ipNumberEnd == 0) {
					$result['message'] = 'The ending IP address of the range you submitted is invalid.';
					$result['success'] = 0;
				} else if (filter_var($_POST['edit_ip_address_start'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $_POST['edit_ip_address_start'] != $_POST['edit_ip_address_end']) {
					$result['message'] = 'Ranges are not supported for IPv6 addresses.  The starting and ending addresses must match when using IPv6.';
					$result['success'] = 0;
				} else if ($ipNumberStart > 0 && $ipNumberStart > $ipNumberEnd) {
					$result['message'] = 'The starting IP address must be smaller than or equal to the ending IP address to be a valid IP range.';
					$result['success'] = 0;
				} else {

					if ($ipNumberStart > 0) {
						$whitelistRanges = $wpdb->get_results($wpdb->prepare("SELECT ip_id, ip_address_start, ip_address_end, ip_number_start, ip_number_end FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id != %d AND offense_level = 0 AND (ip_number_start <= %d AND ip_number_end >= %d) AND ip_number_end <= %d", $editWhitelistID, $ipNumberStart, $ipNumberStart, $ipNumberEnd), ARRAY_A);
					
						$bestRange = array('ip_address_start'=>$editIPAddressStart, 'ip_address_end'=>$editIPAddressEnd, 'ip_number_start'=>$ipNumberStart, 'ip_number_end'=>$ipNumberEnd);
						if (is_array($whitelistRanges)) {
							foreach($whitelistRanges as $whitelistRange) {
								if ($whitelistRange['ip_number_start'] < $bestRange['ip_number_start']) {
									$bestRange['ip_number_start'] = $whitelistRange['ip_number_start'];
									$bestRange['ip_address_start'] = $whitelistRange['ip_address_start'];
								}
								$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $whitelistRange['ip_id']));
							}
						}

						$whitelistRanges = $wpdb->get_results($wpdb->prepare("SELECT ip_id, ip_address_start, ip_address_end, ip_number_start, ip_number_end FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id != %d AND offense_level = 0 AND (ip_number_start <= %d AND ip_number_end >= %d) AND ip_number_start >= %d", $editWhitelistID, $ipNumberEnd, $ipNumberEnd, $ipNumberStart), ARRAY_A);

						if (is_array($whitelistRanges)) {
							foreach($whitelistRanges as $whitelistRange) {
								if ($whitelistRange['ip_number_end'] > $bestRange['ip_number_end']) {
									$bestRange['ip_number_end'] = $whitelistRange['ip_number_end'];
									$bestRange['ip_address_end'] = $whitelistRange['ip_address_end'];
								}
								$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $whitelistRange['ip_id']));
							}
						}
					} else {
						$bestRange = array('ip_address_start'=>$editIPAddressStart, 'ip_address_end'=>$editIPAddressEnd, 'ip_number_start'=>$ipNumberStart, 'ip_number_end'=>$ipNumberEnd);
					}
					
					$wpdb->update($wpdb->base_prefix."ht_ip_list", array("ip_address_start"=>$bestRange['ip_address_start'], "ip_address_end"=>$bestRange['ip_address_end'], "ip_number_start"=>$bestRange['ip_number_start'], "ip_number_end"=>$bestRange['ip_number_end'], "offense_level"=>0, "insert_time"=>time(), 'notes'=>$ipNotes), array('ip_id'=>$editWhitelistID));
					$result['success'] = 1;
					$result['message'] = "The whitelist entry was successfully updated.";

					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id != %d AND offense_level = 0 AND ip_number_start > %d AND ip_number_end < %d", $editWhitelistID, $bestRange['ip_number_start'], $bestRange['ip_number_end']));
				}
			} else {
				$result['success'] = 0;
				$result['message'] = 'The whitelist entry was not found in the database.';
			}
		} else if (isset($_POST['edit_blocked_id'])) {
			$editBlockedID = sanitize_text_field($_POST['edit_blocked_id']);
			if (is_numeric($editBlockedID) && $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $editBlockedID)) == 1) {
				if (filter_var($_POST['edit_ip_address_start'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($_POST['edit_ip_address_start'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
					$ipNumberStart = $this->HT->calculate_ip_number(sanitize_text_field($_POST['edit_ip_address_start']));
					$editIPAddressStart = sanitize_text_field($_POST['edit_ip_address_start']);
				} else {
					$ipNumberStart = 0;
					$editIPAddressStart = 0;
				}
				
				if (filter_var($_POST['edit_ip_address_end'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($_POST['edit_ip_address_end'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
					$ipNumberEnd = $this->HT->calculate_ip_number(sanitize_text_field($_POST['edit_ip_address_end']));
					$editIPAddressEnd = sanitize_text_field($_POST['edit_ip_address_end']);
				} else {
					$ipNumberEnd = 0;
					$editIPAddressEnd = 0;
				}
				
				$ipNotes = sanitize_textarea_field($_POST['edit_ip_notes']);
				$ipOffenseLevel = (in_array($_POST['edit_offense_level'], array(1,10,11,12,15)))? (int)$_POST['edit_offense_level'] : 15;
				
				if ($ipNumberStart == 0) {
					$result['message'] = 'The starting IP address of the range you submitted is invalid.';
					$result['success'] = 0;
				} else if ($ipNumberEnd == 0) {
					$result['message'] = 'The ending IP address of the range you submitted is invalid.';
					$result['success'] = 0;
				} else if (filter_var($_POST['edit_ip_address_start'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $_POST['edit_ip_address_start'] != $_POST['edit_ip_address_end']) {
					$result['message'] = 'Ranges are not supported for IPv6 addresses.  The starting and ending addresses must match when using IPv6.';
					$result['success'] = 0;
				} else if ($ipNumberStart > 0 && $ipNumberStart > $ipNumberEnd) {
					$result['message'] = 'The starting IP address must be smaller than or equal to the ending IP address to be a valid IP range.';
					$result['success'] = 0;
				} else {

					if ($ipNumberStart > 0) {
						$blockedRanges = $wpdb->get_results($wpdb->prepare("SELECT ip_id, ip_address_start, ip_address_end, ip_number_start, ip_number_end FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id != %d AND offense_level = %d AND (ip_number_start <= %d AND ip_number_end >= %d) AND ip_number_end <= %d", $editBlockedID, $ipOffenseLevel, $ipNumberStart, $ipNumberStart, $ipNumberEnd), ARRAY_A);
					
						$bestRange = array('ip_address_start'=>$editIPAddressStart, 'ip_address_end'=>$editIPAddressEnd, 'ip_number_start'=>$ipNumberStart, 'ip_number_end'=>$ipNumberEnd);
						if (is_array($blockedRanges)) {
							foreach($blockedRanges as $blockedRange) {
								if ($blockedRange['ip_number_start'] < $bestRange['ip_number_start']) {
									$bestRange['ip_number_start'] = $blockedRange['ip_number_start'];
									$bestRange['ip_address_start'] = $blockedRange['ip_address_start'];
								}
								$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $blockedRange['ip_id']));
							}
						}

						$blockedRanges = $wpdb->get_results($wpdb->prepare("SELECT ip_id, ip_address_start, ip_address_end, ip_number_start, ip_number_end FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id != %d AND offense_level = %d AND (ip_number_start <= %d AND ip_number_end >= %d) AND ip_number_start >= %d", $editBlockedID, $ipOffenseLevel, $ipNumberEnd, $ipNumberEnd, $ipNumberStart), ARRAY_A);

						if (is_array($blockedRanges)) {
							foreach($blockedRanges as $blockedRange) {
								if ($blockedRange['ip_number_end'] > $bestRange['ip_number_end']) {
									$bestRange['ip_number_end'] = $blockedRange['ip_number_end'];
									$bestRange['ip_address_end'] = $blockedRange['ip_address_end'];
								}
								$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id = %d", $blockedRange['ip_id']));
							}
						}
					} else {
						$bestRange = array('ip_address_start'=>$editIPAddressStart, 'ip_address_end'=>$editIPAddressEnd, 'ip_number_start'=>$ipNumberStart, 'ip_number_end'=>$ipNumberEnd);
					}
					
					$wpdb->update($wpdb->base_prefix."ht_ip_list", array("ip_address_start"=>$bestRange['ip_address_start'], "ip_address_end"=>$bestRange['ip_address_end'], "ip_number_start"=>$bestRange['ip_number_start'], "ip_number_end"=>$bestRange['ip_number_end'], "offense_level"=>$ipOffenseLevel, "insert_time"=>time(), 'notes'=>$ipNotes), array('ip_id'=>$editBlockedID));
					$result['success'] = 1;
					$result['message'] = "The blocked list entry was successfully updated.";

					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."ht_ip_list WHERE ip_id != %d AND offense_level = %d AND ip_number_start > %d AND ip_number_end < %d", $editBlockedID, $ipOffenseLevel, $bestRange['ip_number_start'], $bestRange['ip_number_end']));
				}
			} else {
				$result['success'] = 0;
				$result['message'] = 'The blocked list entry was not found in the database.';
			}
		}
		
		print json_encode($result);
		die();
	}

	function retrieve_whitelist() {
		global $wpdb;
		if (!$this->HT->verify_nonce($_POST['HT_nonce'])) {
			die();
		}
		
		$result = array('success'=>0, 'whitelist'=>array());
		if ( current_user_can( 'activate_plugins' ) ) {
			$sortBy = 'ip_number_start';
			if (isset($_POST['sort'])) {
				switch($_POST['sort']) {
					case 'ip_end':
						$sortBy = 'ip_number_end';
						break;
					case 'activity':
						$sortBy = 'activity_count';
						break;
					case 'notes':
						$sortBy = 'notes';
						break;
				}
			}
			$searchQuery = '';
			if (isset($_POST['search_q'])) {
				$searchQuery = $wpdb->prepare(" AND (ip_address_start LIKE %s OR ip_address_end LIKE %s OR notes LIKE %s)", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%");
			}
			
			$selectOffset = 0;
			$offsetLimit = "";
			if (isset($_POST['current_page']) && is_numeric($_POST['current_page'])) {
				$selectOffset = sanitize_text_field($_POST['current_page']);
				if ($selectOffset > 0) {
					$selectOffset = ($selectOffset * 10);
				}
				$offsetLimit = $wpdb->prepare(" LIMIT %d, 10", $selectOffset);
			}

			$sortDirection = 'ASC';
			if (isset($_POST['direction']) && $_POST['direction'] == 'd') {
				$sortDirection = 'DESC';
			}
			
			$sql = "SELECT ip_id, ip_address_start, ip_address_end, notes, activity_count FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = 0 ".$searchQuery." ORDER BY ".$sortBy." ".$sortDirection.$offsetLimit;
			$whitelistItems = $wpdb->get_results($sql, ARRAY_A);
			
			foreach($whitelistItems as $key=>$item) {
				$whitelistItems[$key]['notes'] = nl2br(strip_tags($item['notes']));
			}
			$result['whitelist'] = stripslashes_deep($whitelistItems);
		}
		print json_encode($result);
		die();
	}

	function retrieve_blocked_list() {
		global $wpdb;
		if (!$this->HT->verify_nonce($_POST['HT_nonce'])) {
			die();
		}
		
		$result = array('success'=>0, 'blockedList'=>array(), 'record_type_counts'=>array(
			array('level'=>1, 'text'=>'404'),
			array('level'=>10, 'text'=>'Login'),
			array('level'=>11, 'text'=>'Project Honeypot'),
			array('level'=>12, 'text'=>'Spamcop'),
			array('level'=>15, 'text'=>'Permanent')
		));
		if (current_user_can('activate_plugins')) {
			$sortBy = 'ip_number_start';
			if (isset($_POST['sort'])) {
				switch($_POST['sort']) {
					case 'ip_end':
						$sortBy = 'ip_number_end';
						break;
					case 'offense':
						$sortBy = 'offense_level';
						break;
					case 'time':
						$sortBy = 'insert_time';
						break;
					case 'count':
						$sortBy = 'activity_count';
						break;
					case 'notes':
						$sortBy = 'notes';
						break;
				}
			}
			
			$searchQuery = '';
			if (isset($_POST['search_q'])) {
				$searchQuery = $wpdb->prepare(" AND (ip_address_start LIKE %s OR ip_address_end LIKE %s OR notes LIKE %s)", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%");
			}


			$selectOffset = 0;
			$offsetLimit = "";
			if (isset($_POST['current_page']) && is_numeric($_POST['current_page'])) {
				$selectOffset = sanitize_text_field($_POST['current_page']);
				if ($selectOffset > 0) {
					$selectOffset = ($selectOffset * 10);
				}
				$offsetLimit = $wpdb->prepare(" LIMIT %d, 10", $selectOffset);
			}
			
			$sortDirection = 'ASC';
			if (isset($_POST['direction']) && $_POST['direction'] == 'd') {
				$sortDirection = 'DESC';
			}

			$recordTypeSelect = (isset($_POST['record_type']) && is_numeric($_POST['record_type']))? $wpdb->prepare("offense_level = %d", sanitize_text_field($_POST['record_type'])) : "offense_level > 3";
			
			$sql = "SELECT ip_id, ip_address_start, ip_address_end, insert_time, offense_level, notes, activity_count FROM ".$wpdb->base_prefix."ht_ip_list WHERE ".$recordTypeSelect.$searchQuery." ORDER BY ".$sortBy." ".$sortDirection.$offsetLimit;
			$blockedItems = $wpdb->get_results($sql, ARRAY_A);
			foreach($blockedItems as $key=>$blockedItem) {
				$blockedItems[$key]['insert_time'] = get_date_from_gmt(date('Y-m-d H:i:s', $blockedItem['insert_time']), 'Y-m-d H:i:s');
			}

			$sql = "SELECT offense_level, count(*) as count FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level > 0".$searchQuery." GROUP BY offense_level";
			$recordTypeRes = $wpdb->get_results($sql, ARRAY_A);
			
			foreach($recordTypeRes as $type) {
				foreach($result['record_type_counts'] as $key=>$record) {
					if ($record['level'] == $type['offense_level']) {
						$result['record_type_counts'][$key]['text'] = $result['record_type_counts'][$key]['text'].' ('.$type['count'].')';
					}
				}
			}

			$totalRows = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level > 3".$searchQuery);
			$result['record_type_counts'][] = array('level'=>'', 'text'=>'ALL ('.$totalRows.')');
			
			foreach($blockedItems as $key=>$item) {
				$blockedItems[$key]['notes'] = nl2br(strip_tags($item['notes']));
			}

			$result['success'] = 1;
			$result['blockedList'] = stripslashes_deep($blockedItems);
		}
		print json_encode($result);
		die();
	}

	function retrieve_activity_list() {
		global $wpdb;
		if (!$this->HT->verify_nonce($_POST['HT_nonce'])) {
			die();
		}
		
		$result = array('success'=>0, 'activityList'=>array(), 'record_type_counts'=>array(
			array('level'=>'login', 'text'=>'Login'),
			array('level'=>'404', 'text'=>'404')
		));
		if (current_user_can('activate_plugins')) {
			$sortBy = 'ip_address';
			if (isset($_REQUEST['sort'])) {
				switch($_REQUEST['sort']) {
					case 'time':
						$sortBy = 'last_activity';
						break;
					case 'count':
						$sortBy = 'activity_count';
						break;
					case 'type':
						$sortBy = 'activity_type';
						break;
					case 'notes':
						$sortBy = 'notes';
						break;
				}
			}
			
			$selectOffset = 0;
			$offsetLimit = "";
			if (isset($_POST['current_page']) && is_numeric($_POST['current_page'])) {
				$selectOffset = sanitize_text_field($_POST['current_page']);
				if ($selectOffset > 0) {
					$selectOffset = ($selectOffset * 10);
				}
				$offsetLimit = $wpdb->prepare(" LIMIT %d, 10", $selectOffset);
			}
			
			$sortDirection = 'ASC';
			if (isset($_REQUEST['direction']) && $_REQUEST['direction'] == 'd') {
				$sortDirection = 'DESC';
			}
			
			$recordTypeSelect = (isset($_POST['record_type']) && in_array($_POST['record_type'], array('login', '404')))? $wpdb->prepare("WHERE activity_type = %s", sanitize_text_field($_POST['record_type'])) : '';
			
			$searchQuery = '';
			if (isset($_POST['search_q'])) {
				$searchQuery = $wpdb->prepare(" (ip_address LIKE %s OR activity_type LIKE %s OR notes LIKE %s)", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%");
			}
			
			$sql = "SELECT activity_id, ip_address, last_activity, activity_count, activity_type, notes FROM ".$wpdb->base_prefix."ht_activity ".$recordTypeSelect.(($recordTypeSelect == '')? "WHERE":"AND").$searchQuery." ORDER BY ".$sortBy." ".$sortDirection.$offsetLimit;
			$activityItems = $wpdb->get_results($sql, ARRAY_A);
			foreach($activityItems as $key=>$activityItem) {
				$activityItems[$key]['last_activity'] = get_date_from_gmt(date('Y-m-d H:i:s', $activityItem['last_activity']), 'Y-m-d H:i:s');
			}
			
			$sql = "SELECT activity_type, count(*) as count FROM ".$wpdb->base_prefix."ht_activity WHERE ".$searchQuery." GROUP BY activity_type";
			$recordTypeRes = $wpdb->get_results($sql, ARRAY_A);
			
			foreach($recordTypeRes as $type) {
				foreach($result['record_type_counts'] as $key=>$record) {
					if ($record['level'] == $type['activity_type']) {
						$result['record_type_counts'][$key]['text'] = $result['record_type_counts'][$key]['text'].' ('.$type['count'].')';
					}
				}
			}

			$totalRows = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->base_prefix."ht_activity WHERE ".$searchQuery);
			$result['record_type_counts'][] = array('level'=>'', 'text'=>'ALL ('.$totalRows.')');
			
			foreach($activityItems as $key=>$item) {
				$activityItems[$key]['notes'] = nl2br(strip_tags($item['notes']));
			}
			
			$result['success'] = 1;
			$result['activityList'] = stripslashes_deep($activityItems);
		}
		print json_encode($result);
		die();
	}

	function retrieve_page_limit() {
		global $wpdb;
		if (!$this->HT->verify_nonce($_POST['HT_nonce'])) {
			die();
		}
		
		$result = array('success'=>0, 'pageLimit'=>0);
		if ($_POST['list_type'] == 'blocked') {
			$searchQuery = (isset($_POST['search_q']))? $wpdb->prepare(" AND (ip_address_start LIKE %s OR ip_address_end LIKE %s OR notes LIKE %s)", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%") : "";
			$recordTypeSelect = (isset($_POST['record_type']) && is_numeric($_POST['record_type']))? $wpdb->prepare("offense_level = %d", sanitize_text_field($_POST['record_type'])) : "offense_level > 3";
			$totalRows = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->base_prefix."ht_ip_list WHERE ".$recordTypeSelect.$searchQuery);
			$result['success'] = 1;
		} else if ($_POST['list_type'] == 'whitelist') {
			$searchQuery = (isset($_POST['search_q']))? $wpdb->prepare(" AND (ip_address_start LIKE %s OR ip_address_end LIKE %s OR notes LIKE %s)", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%") : "";
			$totalRows = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->base_prefix."ht_ip_list WHERE offense_level = 0".$searchQuery);
			$result['success'] = 1;
		} else if ($_POST['list_type'] == 'activity') {
			$recordTypeSelect = (isset($_POST['record_type']) && in_array($_POST['record_type'], array('login', '404')))? $wpdb->prepare("WHERE activity_type = %s", sanitize_text_field($_POST['record_type'])) : '';
			
			$searchQuery = ((isset($_POST['search_q']))? (($recordTypeSelect == '')? "WHERE":" AND").$wpdb->prepare(" (ip_address LIKE %s OR activity_type LIKE %s OR notes LIKE %s)", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%", "%".$wpdb->esc_like(sanitize_text_field($_POST['search_q']))."%"):"");
			
			$totalRows = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->base_prefix."ht_activity ".$recordTypeSelect.$searchQuery);
			$result['success'] = 1;
		} else {
			$totalRows = 0;
		}

		if ($totalRows == 0 || $totalRows%10 > 0) {
			$result['pageLimit'] = floor($totalRows / 10);
		} else {
			$result['pageLimit'] = floor(($totalRows-1) / 10);
		}
		print json_encode($result);
		die();
	}
}
?>