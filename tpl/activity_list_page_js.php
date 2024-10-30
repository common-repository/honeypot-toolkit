<script type="text/javascript" language="javascript">
	HT_pagedSettings['currentSortCol'] = 'ip';
	HT_pagedSettings['pageLimit'] = 0;
	HT_pagedSettings['listType'] = 'activity';
	jQuery(function() {
		jQuery('.perform-bulk-action').click(function() {
			var activityIDs = new Array();
			jQuery('.activity-id:checked').each(function() {
				activityIDs.push(this.value);
			});
			var actionType = jQuery('.ht-bulk-action').val();
			
			if (activityIDs.length == 0) {
				HT_display_ui_dialog('Submission Error', 'You must select at least one row to perform the action on.');
			} else {
				if (actionType == 'blacklist') {
					var postVars = { 'activity_ids': activityIDs, HT_nonce: '<?php print $this->HT->nonce; ?>' };
					HT_submit_ajax('HT_blacklist_ip', postVars, '.HT-content-wrap', function() {fill_ip_list();});
				} else if (actionType == 'whitelist') {
					var postVars = { 'activity_ids': activityIDs, HT_nonce: '<?php print $this->HT->nonce; ?>' };
					HT_submit_ajax('HT_whitelist_ip', postVars, '.HT-content-wrap', function() {fill_ip_list();});
				} else if (actionType == 'remove') {
					var postVars = { 'activity_ids': activityIDs, HT_nonce: '<?php print $this->HT->nonce; ?>' };
					HT_submit_ajax('HT_remove_ip', postVars, '.HT-content-wrap', function() {fill_ip_list();});
				}
			}
		});

		jQuery('.ip-list-item.header a').click(function() {
			HT_pagedSettings['currentSortCol'] = 'ip';
			if (jQuery(this).hasClass('ip')) {
				HT_pagedSettings['currentSortCol'] = 'ip';
			} else if (jQuery(this).hasClass('time')) {
				HT_pagedSettings['currentSortCol'] = 'time';
			} else if (jQuery(this).hasClass('count')) {
				HT_pagedSettings['currentSortCol'] = 'count';
			} else if (jQuery(this).hasClass('type')) {
				HT_pagedSettings['currentSortCol'] = 'type';
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


	function fill_ip_list() {
		HT_toggle_loading('.HT-content-wrap');
		HT_set_button_state();
		jQuery('.ip-list-content .ip-list-item').remove();
		var postVars = {'search_q': jQuery('#search-ip-list-q').val(), 'record_type': HT_pagedSettings['recordType'], 'sort': HT_pagedSettings['currentSortCol'], 'direction': HT_pagedSettings['currentSortDir'], 'current_page': HT_pagedSettings['currentPage'], HT_nonce: '<?php print $this->HT->nonce; ?>'};
		jQuery.post(encodeURI(ajaxurl + '?action=HT_retrieve_activity_list'), postVars, function (result) {
			try {
				parsedResult = jQuery.parseJSON(result);
			} catch(err) {
				parsedResult = {'success': '0', 'message': 'There was a problem retrieving the activity list.', 'activityList': {}};
			}

			if (parsedResult['success'] == '1') {
				for (var i=0; i<parsedResult['record_type_counts'].length; i++) {
					jQuery('#ip-list-record-type option[value="'+parsedResult['record_type_counts'][i]['level']+'"]').text(parsedResult['record_type_counts'][i]['text']);
				}
				for (i=0; i<parsedResult['activityList'].length; i++) {
					var ipListTemplate = jQuery('.ip-list-template').clone();
					ipListTemplate.removeClass('ip-list-template');
					ipListTemplate.addClass('ip-list-item activity-item');
					if (i % 2 == 0) {
						ipListTemplate.addClass('even');
					} else {
						ipListTemplate.addClass('odd');
					}
					ipListTemplate.find('.activity-id').val(parsedResult['activityList'][i]['activity_id']);
					ipListTemplate.find('.activity-ip a').attr('href', 'http://whois.domaintools.com/'+parsedResult['activityList'][i]['ip_address']);
					ipListTemplate.find('.activity-ip a').html(parsedResult['activityList'][i]['ip_address']);
					ipListTemplate.find('.activity-time .inner-ip-list-column').html(parsedResult['activityList'][i]['last_activity']);
					ipListTemplate.find('.activity-count .inner-ip-list-column').html(parsedResult['activityList'][i]['activity_count']);
					ipListTemplate.find('.activity-type .inner-ip-list-column').html(parsedResult['activityList'][i]['activity_type']);

					if (parsedResult['activityList'][i]['notes'] == '') {
						ipListTemplate.find('.activity-notes .inner-ip-list-column').html('&nbsp;');
					} else {
						ipListTemplate.find('.activity-notes .inner-ip-list-column').html(parsedResult['activityList'][i]['notes']);
					}
					jQuery('.ip-list-content').append(ipListTemplate);
				}
			}
			
			HT_toggle_loading('.HT-content-wrap');
		});
	}
</script>