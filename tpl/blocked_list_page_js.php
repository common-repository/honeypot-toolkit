<script type="text/javascript" language="javascript">
	HT_pagedSettings['currentSortCol'] = 'ip-start';
	HT_pagedSettings['pageLimit'] = 0;
	HT_pagedSettings['listType'] = 'blocked';
	jQuery(function() {
		jQuery('.perform-bulk-action').click(function() {
			var blockedIDs = new Array();
			jQuery('.blocked-id:checked').each(function() {
				blockedIDs.push(this.value);
			});
			var actionType = jQuery('.ht-bulk-action').val();
			
			if (blockedIDs.length == 0) {
				HT_display_ui_dialog('Submission Error', 'You must select at least one row to perform the action on.');
			} else {
				if (actionType == 'blacklist') {
					var postVars = { 'ip_list_ids': blockedIDs, HT_nonce: '<?php print $this->HT->nonce; ?>' };
					HT_submit_ajax('HT_blacklist_ip', postVars, '.HT-content-wrap', function() {fill_ip_list();});
				} else if (actionType == 'whitelist') {
					var postVars = { 'ip_list_ids': blockedIDs, HT_nonce: '<?php print $this->HT->nonce; ?>' };
					HT_submit_ajax('HT_whitelist_ip', postVars, '.HT-content-wrap', function() {fill_ip_list();});
				} else if (actionType == 'remove') {
					var postVars = { 'ip_list_ids': blockedIDs, HT_nonce: '<?php print $this->HT->nonce; ?>' };
					HT_submit_ajax('HT_remove_ip', postVars, '.HT-content-wrap', function() {fill_ip_list();});
				}
			}

		});

		jQuery('.add-ip-button').click(function() {
			if (HT_validate_form_inputs('.blocked-item.new')) {
				var newIPStart = jQuery(this).closest('.ip-list-item').find('.new-ip-address-start').val();
				var newIPEnd = jQuery(this).closest('.ip-list-item').find('.new-ip-address-end').val();
				var newIPOffenseLevel = jQuery(this).closest('.ip-list-item').find('.new-ip-offense-level').val();
				var newIPNotes = jQuery(this).closest('.ip-list-item').find('.new-ip-notes').val();
				var postVars = { 'new_ip_address_start': newIPStart, 'new_ip_address_end': newIPEnd, 'new_ip_offense_level': newIPOffenseLevel, 'new_ip_notes': newIPNotes, HT_nonce: '<?php print $this->HT->nonce; ?>' };
				HT_submit_ajax('HT_blacklist_ip', postVars, '.HT-content-wrap', function() {
					fill_ip_list();
				});
			}
		});

		jQuery('.ip-list-item.header a').click(function() {
			HT_pagedSettings['currentSortCol'] = 'ip-start';
			if (jQuery(this).hasClass('ip-start')) {
				HT_pagedSettings['currentSortCol'] = 'ip-start';
			} else if (jQuery(this).hasClass('ip-end')) {
				HT_pagedSettings['currentSortCol'] = 'ip-end';
			} else if (jQuery(this).hasClass('time')) {
				HT_pagedSettings['currentSortCol'] = 'time';
			} else if (jQuery(this).hasClass('offense')) {
				HT_pagedSettings['currentSortCol'] = 'offense';
			} else if (jQuery(this).hasClass('count')) {
				HT_pagedSettings['currentSortCol'] = 'count';
			} else if (jQuery(this).hasClass('notes')) {
				HT_pagedSettings['currentSortCol'] = 'notes';
			}

			if (HT_pagedSettings['currentPage'] != 'all') {
				HT_pagedSettings['currentPage'] = 0;
			}
			
			if (jQuery(this).hasClass('desc')) {
				jQuery('.ip-list-item.header a').removeClass('asc desc');
				HT_pagedSettings['currentSortDir'] = 'a';
				jQuery(this).addClass('asc');
			} else {
				jQuery('.ip-list-item.header a').removeClass('asc desc');
				HT_pagedSettings['currentSortDir'] = 'd';
				jQuery(this).addClass('desc');
			}

			fill_ip_list();
			return false;
		});


		HT_toggle_loading('.HT-content-wrap');
		jQuery('.ip-list-item.header a.'+HT_pagedSettings['currentSortCol']).click();
	});
	
	function attach_handlers() {
		jQuery('.edit-blocked-list-entry').click(function() {
			HT_toggle_loading('.HT-content-wrap');
			var blockedID = jQuery(this).closest('.ip-list-item').find('.blocked-id').val();
			var postVars = { 'blocked_id': blockedID, HT_nonce: '<?php print $this->HT->nonce; ?>' };
			jQuery.post(encodeURI(ajaxurl + '?action=HT_retrieve_entry_details'), postVars, function (result) {
				HT_toggle_loading('.HT-content-wrap');
				var parsedResult = {};
				try {
					parsedResult = jQuery.parseJSON(result);
				} catch(err) {
					parsedResult = {'success': '0', 'message': 'There was a problem retrieving the details for that item.'};
				}
				
				if (parsedResult['success'] == '1') {
					HT_display_ui_edit_form('Edit blocked list entry', parsedResult, function() {
						if (HT_validate_form_inputs('#HT-ajax-edit-form-container')) {
							var editBlockedID = jQuery('#HT-ajax-edit-form-container .blocked-id').val();
							var newIPStart = jQuery('#HT-ajax-edit-form-container .edit-ip-address-start').val();
							var newIPEnd = jQuery('#HT-ajax-edit-form-container .edit-ip-address-end').val();
							var newOffenseLevel = jQuery('#HT-ajax-edit-form-container .edit-offense-level').val();
							var newIPNotes = jQuery('#HT-ajax-edit-form-container .edit-ip-notes').val();
							var postVars = { 'edit_blocked_id': editBlockedID, 'edit_ip_address_start': newIPStart, 'edit_ip_address_end': newIPEnd, 'edit_ip_notes': newIPNotes, 'edit_offense_level': newOffenseLevel, HT_nonce: '<?php print $this->HT->nonce; ?>' };
							jQuery('#HT-ui-edit-form').dialog("close");
							HT_submit_ajax('HT_edit_ip_entry', postVars, '.HT-content-wrap', function() {
								fill_ip_list();
							});
						}
					});
				} else {
					HT_display_ui_dialog('Error', parsedResult['message']);
				}
			});
		});
	}

	function fill_ip_list() {
		var blockLevels = {'1': '404', '10': 'Login', '11': 'Project Honeypot', '12': 'Spamcop', '15': 'Permanent'};
		HT_toggle_loading('.HT-content-wrap');
		HT_set_button_state();
		var postVars = {'search_q': jQuery('#search-ip-list-q').val(), 'record_type': HT_pagedSettings['recordType'], 'sort': HT_pagedSettings['currentSortCol'], 'direction': HT_pagedSettings['currentSortDir'], 'current_page': HT_pagedSettings['currentPage'], HT_nonce: '<?php print $this->HT->nonce; ?>'};
		jQuery.post(encodeURI(ajaxurl + '?action=HT_retrieve_blocked_list'), postVars, function (result) {
			try {
				parsedResult = jQuery.parseJSON(result);
			} catch(err) {
				parsedResult = {'success': '0', 'message': 'There was a problem retrieving the blocked list.', 'blockedList': {}};
			}

			if (parsedResult['success'] == '1') {
				for (var i=0; i<parsedResult['record_type_counts'].length; i++) {
					jQuery('#ip-list-record-type option[value="'+parsedResult['record_type_counts'][i]['level']+'"]').text(parsedResult['record_type_counts'][i]['text']);
				}
					
				jQuery('.ip-list-content .ip-list-item').remove();
				for (var i=0; i<parsedResult['blockedList'].length; i++) {
					var ipListTemplate = jQuery('.ip-list-template').clone();
					ipListTemplate.removeClass('ip-list-template');
					ipListTemplate.addClass('ip-list-item blocked-item');
					if (i % 2 == 0) {
						ipListTemplate.addClass('even');
					} else {
						ipListTemplate.addClass('odd');
					}
					ipListTemplate.find('.blocked-id').val(parsedResult['blockedList'][i]['ip_id']);
					ipListTemplate.find('.blocked-ip-start a').attr('href', 'http://whois.domaintools.com/'+parsedResult['blockedList'][i]['ip_address_start']);
					ipListTemplate.find('.blocked-ip-start a').html(parsedResult['blockedList'][i]['ip_address_start']);
					ipListTemplate.find('.blocked-ip-end a').attr('href', 'http://whois.domaintools.com/'+parsedResult['blockedList'][i]['ip_address_end']);
					ipListTemplate.find('.blocked-ip-end a').html(parsedResult['blockedList'][i]['ip_address_end']);
					ipListTemplate.find('.offense-level .inner-ip-list-column').html(blockLevels[parsedResult['blockedList'][i]['offense_level']]);
					ipListTemplate.find('.blocked-time .inner-ip-list-column').html(parsedResult['blockedList'][i]['insert_time']);
					ipListTemplate.find('.blocked-count .inner-ip-list-column').html(parsedResult['blockedList'][i]['activity_count']);
					if (parsedResult['blockedList'][i]['notes'] == '') {
						ipListTemplate.find('.blocked-notes .inner-ip-list-column').html('&nbsp;');
					} else {
						ipListTemplate.find('.blocked-notes .inner-ip-list-column').html(parsedResult['blockedList'][i]['notes']);
					}
					jQuery('.ip-list-content').append(ipListTemplate);
				}
			}
			
			HT_toggle_loading('.HT-content-wrap');
			attach_handlers();
		});
	}
</script>