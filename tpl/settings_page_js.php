<script type="text/javascript" language="javascript">
	jQuery(function() {
		jQuery('#HT-tab-1').removeClass('ui-tabs-active ui-state-active');
		jQuery('#HT-tab-container').tabs();
		jQuery('#ht-save-settings').click(function() {
			if (HT_validate_form_inputs('#HT-settings-form')) {
				var postVars = {
					'ht-only-allow-whitelist': jQuery('#ht-only-allow-whitelist').val(),
					'ht-login-mon': jQuery('#ht-login-mon').is(':checked'),
					'ht-login-limit': jQuery('#ht-login-limit').val(),
					'ht-login-time-span': jQuery('#ht-login-time-span').val(),
					'ht-login-block-time': jQuery('#ht-login-block-time').val(),
					'ht-show-login-count': jQuery('#ht-show-login-count').val(),
					'ht-404-mon': jQuery('#ht-404-mon').is(':checked'),
					'ht-404-limit': jQuery('#ht-404-limit').val(),
					'ht-404-time-span': jQuery('#ht-404-time-span').val(),
					'ht-404-block-time': jQuery('#ht-404-block-time').val(),
					'ht-response-code': jQuery('#ht-response-code').val(),
					'ht-banned-usernames': jQuery('#ht-banned-usernames').val(),
					'ht-honeypot-path': jQuery('#ht-honeypot-path').val(),
					'ht-ph-api-key': jQuery('#ht-ph-api-key').val(),
					'ht-ph-bl-days': jQuery('#ht-ph-bl-days').val(),
					'ht-ph-bl-threat-score': jQuery('#ht-ph-bl-threat-score').val(),
					'ht-ph-check-ip-interval': jQuery('#ht-ph-check-ip-interval').val(),
					'ht-use-project-honeypot': jQuery('#ht-use-project-honeypot').is(':checked'),
					'ht-use-spamcop': jQuery('#ht-use-spamcop').is(':checked'),
					'ht-use-custom-honeypot': jQuery('#ht-use-custom-honeypot').is(':checked'),
					'ht-use-body-open-honeypot': jQuery('#ht-use-body-open-honeypot').is(':checked'),
					'ht-use-menu-honeypot': jQuery('#ht-use-menu-honeypot').is(':checked'),
					'ht-use-search-form-honeypot': jQuery('#ht-use-search-form-honeypot').is(':checked'),
					'ht-use-footer-honeypot': jQuery('#ht-use-footer-honeypot').is(':checked'),
					'ht-use-the-content-honeypot': jQuery('#ht-use-the-content-honeypot').is(':checked'),
					'ht-hide-usernames': jQuery('#ht-hide-usernames').is(':checked'),
					'ht-site-level-lists': jQuery('#ht-site-level-lists').is(':checked'),
					HT_nonce: '<?php print $this->HT->nonce; ?>'
				};

				HT_submit_ajax('HT_save_settings', postVars, '.HT-content-wrap', function(parsedResult) {
					if (parsedResult['hide-users'] == '1') {
						HT_process_usernames(0, 100, 'hide');
					} else if (parsedResult['reset-users'] == '1') {
						HT_process_usernames(0, 100, 'reset');
					}
				});
			}
		});
		HT_toggle_loading('.HT-content-wrap');
	});

	function HT_process_usernames(userOffset, rowLimit, userAction) {
		var postVars = {
			'user-offset': userOffset,
			'user-limit': rowLimit,
			'user-action': userAction,
			HT_nonce: '<?php print $this->HT->nonce; ?>'
		};
		
		if (userOffset == 0) {
			HT_toggle_loading('.HT-content-wrap');
		}
		
		jQuery.post(encodeURI(ajaxurl + '?action=HT_process_usernames'), postVars, function (result) {
			var parsedResult = {};
			try {
				parsedResult = jQuery.parseJSON(result);
			} catch(err) {
				parsedResult = {'success': '0', 'message': 'There was a problem with your submission.'};
			}

			if (parsedResult['success'] == '1') {
				if (parseInt(parsedResult['total'], 10) > parseInt(parsedResult['offset'], 10) + 100) {
					jQuery('#HT-progress-message').html('Still Working: ' + (parseInt(parsedResult['offset'], 10) + 100) + ' users have been processed.  There are still ' + (parseInt(parsedResult['total'], 10) - (parseInt(parsedResult['offset'], 10) + 100)) + ' left.');
					
					HT_process_usernames(parseInt(parsedResult['offset'], 10) + 100, 100, userAction);
				} else {
					jQuery('#HT-progress-message').html('');
					HT_toggle_loading('.HT-content-wrap');
					HT_display_ui_dialog('Submission Result', 'All users have been processed');
				}
			}
		});
	}
</script>