<script type="text/javascript" language="javascript">
	HT_pagedSettings['currentSortCol'] = 'ip-start';
	HT_pagedSettings['pageLimit'] = 0;
	HT_pagedSettings['listType'] = 'whitelist';
	jQuery(function() {
		jQuery('.perform-bulk-action').click(function() {
			var whitelistIDs = new Array();
			jQuery('.whitelist-id:checked').each(function() {
				whitelistIDs.push(this.value);
			});
			var actionType = jQuery('.ht-bulk-action').val();
			
			if (whitelistIDs.length == 0) {
				HT_display_ui_dialog('Submission Error', 'You must select at least one row to perform the action on.');
			} else {
				if (actionType == 'blacklist') {
					var postVars = { 'ip_list_ids': whitelistIDs, HT_nonce: '<?php print $this->HT->nonce; ?>' };
					HT_submit_ajax('HT_blacklist_ip', postVars, '.HT-content-wrap', function() {fill_ip_list();});
				} else if (actionType == 'remove') {
					var postVars = { 'ip_list_ids': whitelistIDs, HT_nonce: '<?php print $this->HT->nonce; ?>' };
					HT_submit_ajax('HT_remove_ip', postVars, '.HT-content-wrap', function() {fill_ip_list();});
				}
			}

		});
		
		jQuery('.add-ip-button').click(function() {
			if (HT_validate_form_inputs('.whitelist-item.new')) {
				var newIPStart = jQuery(this).closest('.ip-list-item').find('.new-ip-address-start').val();
				var newIPEnd = jQuery(this).closest('.ip-list-item').find('.new-ip-address-end').val();
				var newIPNotes = jQuery(this).closest('.ip-list-item').find('.new-ip-notes').val();
				var postVars = { 'new_ip_address_start': newIPStart, 'new_ip_address_end': newIPEnd, 'new_ip_notes': newIPNotes, HT_nonce: '<?php print $this->HT->nonce; ?>' };
				HT_submit_ajax('HT_whitelist_ip', postVars, '.HT-content-wrap', function() {fill_ip_list();});
			}
		});

		jQuery('.ip-list-item.header a').click(function() {
			HT_pagedSettings['currentSortCol'] = 'ip-start';
			if (jQuery(this).hasClass('ip-start')) {
				HT_pagedSettings['currentSortCol'] = 'ip-start';
			} else if (jQuery(this).hasClass('ip-end')) {
				HT_pagedSettings['currentSortCol'] = 'ip-start';
			} else if (jQuery(this).hasClass('activity')) {
				HT_pagedSettings['currentSortCol'] = 'activity';
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
		jQuery('.edit-whitelist-entry').click(function() {
			HT_toggle_loading('.HT-content-wrap');
			var whitelistID = jQuery(this).closest('.ip-list-item').find('.whitelist-id').val();
			var postVars = { 'whitelist_id': whitelistID, HT_nonce: '<?php print $this->HT->nonce; ?>' };
			jQuery.post(encodeURI(ajaxurl + '?action=HT_retrieve_entry_details'), postVars, function (result) {
				HT_toggle_loading('.HT-content-wrap');
				var parsedResult = {};
				try {
					parsedResult = jQuery.parseJSON(result);
				} catch(err) {
					parsedResult = {'success': '0', 'message': 'There was a problem retrieving the details for that item.'};
				}
				
				if (parsedResult['success'] == '1') {
					HT_display_ui_edit_form('Edit whitelist entry', parsedResult, function() {
						if (HT_validate_form_inputs('#HT-ajax-edit-form-container')) {
							var editWhitelistID = jQuery('#HT-ajax-edit-form-container .whitelist-id').val();
							var newIPStart = jQuery('#HT-ajax-edit-form-container .edit-ip-address-start').val();
							var newIPEnd = jQuery('#HT-ajax-edit-form-container .edit-ip-address-end').val();
							var newIPNotes = jQuery('#HT-ajax-edit-form-container .edit-ip-notes').val();
							var postVars = { 'edit_whitelist_id': editWhitelistID, 'edit_ip_address_start': newIPStart, 'edit_ip_address_end': newIPEnd, 'edit_ip_notes': newIPNotes, HT_nonce: '<?php print $this->HT->nonce; ?>' };
							jQuery('#HT-ui-edit-form').dialog("close");
							HT_submit_ajax('HT_edit_ip_entry', postVars, '.HT-content-wrap', function() {fill_ip_list();});
						}
					});
				} else {
					HT_display_ui_dialog('Error', parsedResult['message']);
				}
			});
		});
	}

	function fill_ip_list() {
		HT_toggle_loading('.HT-content-wrap');
		HT_set_button_state();
		jQuery('.ip-list-content .ip-list-item').remove();
		var postVars = {'search_q': jQuery('#search-ip-list-q').val(), 'sort': HT_pagedSettings['currentSortCol'], 'direction': HT_pagedSettings['currentSortDir'], 'current_page': HT_pagedSettings['currentPage'], HT_nonce: '<?php print $this->HT->nonce; ?>'};
		jQuery.post(encodeURI(ajaxurl + '?action=HT_retrieve_whitelist'), postVars, function (result) {
			try {
				var parsedResult = jQuery.parseJSON(result);
			} catch(err) {
				var parsedResult = {'success': '0', 'message': 'There was a problem retrieving the whitelist.', 'whitelist': {}};
			}

			for (i=0; i<parsedResult['whitelist'].length; i++) {
				var ipListTemplate = jQuery('.ip-list-template').clone();
				ipListTemplate.removeClass('ip-list-template');
				ipListTemplate.addClass('ip-list-item whitelist-item');
				if (i % 2 == 0) {
					ipListTemplate.addClass('even');
				} else {
					ipListTemplate.addClass('odd');
				}
				ipListTemplate.find('.whitelist-id').val(parsedResult['whitelist'][i]['ip_id']);
				ipListTemplate.find('.whitelist-ip-start a').attr('href', 'http://whois.domaintools.com/'+parsedResult['whitelist'][i]['ip_address_start']);
				ipListTemplate.find('.whitelist-ip-start a').html(parsedResult['whitelist'][i]['ip_address_start']);
				ipListTemplate.find('.whitelist-ip-end a').attr('href', 'http://whois.domaintools.com/'+parsedResult['whitelist'][i]['ip_address_end']);
				ipListTemplate.find('.whitelist-ip-end a').html(parsedResult['whitelist'][i]['ip_address_end']);
				ipListTemplate.find('.whitelist-count .inner-ip-list-column').html(parsedResult['whitelist'][i]['activity_count']);
				if (parsedResult['whitelist'][i]['notes'] == '') {
					ipListTemplate.find('.whitelist-notes .inner-ip-list-column').html('&nbsp;');
				} else {
					ipListTemplate.find('.whitelist-notes .inner-ip-list-column').html(parsedResult['whitelist'][i]['notes']);
				}
				jQuery('.ip-list-content').append(ipListTemplate);
			}
			
			HT_toggle_loading('.HT-content-wrap');
			attach_handlers();
		});
	}
</script>