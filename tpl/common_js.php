<?php
wp_enqueue_script('jquery-ui-tooltip');
?>
<script type="text/javascript" language="javascript">
	var HT_pagedSettings = {'currentPage': 0, 'currentSortDir': 'd', 'currentSortCol': '', 'pageLimit': 0, 'listType': '', 'recordType': ''};
	var formValidationRegex = new Array();
	<?php
		foreach($this->HT->validationRegex as $key=>$regex) {
			print "formValidationRegex['".$key."'] = ".$regex.";";
		}
	?>
	jQuery(function() {
		jQuery('#HT-ui-notices').dialog({
			dialogClass: 'HT-ui-dialog ui-notices',
			closeText: 'X',
			autoOpen: false,
			resizable: false,
			height: "auto",
			width: (jQuery(window).width() > 400)?'400':jQuery(window).width()-20,
			modal: true,
			position: {within: '.HT-content-wrap'},
			buttons: {
				"Ok": function() {
					jQuery(this).dialog("close");
					jQuery('.ui-widget-overlay.ui-front').css('zIndex', '9996');
				}
			},
			open: function(event, ui) {
				jQuery('.ui-widget-overlay.ui-front').css('position', 'fixed');
				jQuery('.ui-widget-overlay.ui-front').css('left', '0px');
				jQuery('.ui-widget-overlay.ui-front').css('right', '0px');
				jQuery('.ui-widget-overlay.ui-front').css('top', '0px');
				jQuery('.ui-widget-overlay.ui-front').css('bottom', '0px');
				jQuery('.ui-widget-overlay.ui-front').css('background', '#000');
				jQuery('.ui-widget-overlay.ui-front').css('opacity', '.5');
				jQuery('.ui-widget-overlay.ui-front').css('zIndex', '9998');
			}
		});

		jQuery('#HT-ui-edit-form').dialog({
			dialogClass: 'HT-ui-dialog ui-edit-form',
			closeText: 'X',
			autoOpen: false,
			resizable: false,
			height: "auto",
			width: (jQuery(window).width() > 400)?'400':jQuery(window).width()-20,
			modal: true,
			position: {within: '.HT-content-wrap'},
			buttons: {
				"Submit": function() {
				},
				"Cancel": function() {
					jQuery(this).dialog("close");
				}
			},
			open: function(event, ui) {
				jQuery('.ui-widget-overlay.ui-front').css('position', 'fixed');
				jQuery('.ui-widget-overlay.ui-front').css('left', '0px');
				jQuery('.ui-widget-overlay.ui-front').css('right', '0px');
				jQuery('.ui-widget-overlay.ui-front').css('top', '0px');
				jQuery('.ui-widget-overlay.ui-front').css('bottom', '0px');
				jQuery('.ui-widget-overlay.ui-front').css('background', '#000');
				jQuery('.ui-widget-overlay.ui-front').css('opacity', '.5');
				jQuery('.ui-widget-overlay.ui-front').css('zIndex', '9996');
			}
		});

		jQuery('.previous-page').click(function() {
			if (HT_pagedSettings['currentPage'] > 0) {
				HT_pagedSettings['currentPage']--;
				fill_ip_list();
			}
		});

		jQuery('.next-page').click(function() {
			HT_pagedSettings['currentPage']++;
			fill_ip_list();
		});

		jQuery('#ip-list-page-select').change(function() {
			HT_pagedSettings['currentPage'] = jQuery(this).val();
			fill_ip_list();
		});

		jQuery('#ip-list-record-type').change(function() {
			HT_pagedSettings['recordType'] = jQuery(this).val();
			HT_pagedSettings['currentPage'] = 0;
			fill_ip_list();
		});

		jQuery('#search-ip-list').click(function() {
			fill_ip_list();
		});

		jQuery('#reset-ip-search').click(function() {
			jQuery('#search-ip-list-q').val('');
			fill_ip_list();
		});

		jQuery('#search-ip-list-q').keydown(function(e) {
			if (e.keyCode == 13) {
				fill_ip_list();
				return false;
			}
		});

		jQuery('#select-all-ip-entries').click(function() {
			var checked=jQuery(this).is(':checked');
			jQuery('.ip-list-actions input[type="checkbox"]').each(function() {
				jQuery(this).prop('checked', checked);
			});
		});
		
		HT_attach_help_dialog();
	});

	function HT_fill_ip_list_page_select() {
		jQuery('#ip-list-page-select option').remove();
		jQuery('#ip-list-page-select').append('<option value="all">All</option>');
		for(var i=0; i<=HT_pagedSettings['pageLimit']; i++) {
			jQuery('#ip-list-page-select').append('<option value="'+i+'">'+(i+1)+'</option>');
		}
		jQuery('#ip-list-page-select').val(HT_pagedSettings['currentPage']);
	}
	
	function HT_set_button_state() {
		jQuery('#ip-list-page-select').val(HT_pagedSettings['currentPage']);
		var postVars = {'search_q': jQuery('#search-ip-list-q').val(), 'list_type': HT_pagedSettings['listType'], 'record_type': HT_pagedSettings['recordType'], HT_nonce: '<?php print $this->HT->nonce; ?>' };
		jQuery.post(encodeURI(ajaxurl + '?action=HT_retrieve_page_limit'), postVars, function (result) {
			try {
				var parsedResult = jQuery.parseJSON(result);
				if (parsedResult['success'] == '1' && HT_pagedSettings['pageLimit'] != parsedResult['pageLimit']) {
					HT_pagedSettings['pageLimit'] = parsedResult['pageLimit'];
					HT_fill_ip_list_page_select();
				}
			} catch(err) {
			}

			if (HT_pagedSettings['currentPage'] == 'all') {
				jQuery('.next-page, .previous-page').attr('disabled', 'disabled');
				jQuery('.next-page, .previous-page').addClass('disabled');
			} else {

				if (HT_pagedSettings['pageLimit'] > 0) {
					jQuery('#navigation-button-container').show();
				} else {
					jQuery('#navigation-button-container').hide();
				}
				
				if (HT_pagedSettings['currentPage'] >= HT_pagedSettings['pageLimit']) {
					jQuery('.next-page').attr('disabled', 'disabled');
					jQuery('.next-page').addClass('disabled');
				} else {
					jQuery('.next-page').removeAttr('disabled');
					jQuery('.next-page').removeClass('disabled');
				}
				if (HT_pagedSettings['currentPage'] <= 0) {
					jQuery('.previous-page').attr('disabled', 'disabled');
					jQuery('.previous-page').addClass('disabled');
				} else {
					jQuery('.previous-page').removeAttr('disabled');
					jQuery('.previous-page').removeClass('disabled');
				}
				if (HT_pagedSettings['currentPage'] > HT_pagedSettings['pageLimit'] && HT_pagedSettings['currentPage'] > 0) {
					while(HT_pagedSettings['currentPage'] > HT_pagedSettings['pageLimit']) {
						HT_pagedSettings['currentPage']--;
					}
					fill_ip_list();
				}
			}
		});
	}
	
	function HT_attach_help_dialog() {
		jQuery('.help-dialog').tooltip({
			content: function() {
				return HT_format_tooltip(jQuery(this).attr('title'));
			},
			show: {
				effect: "slideDown",
				delay: 50
			},
			hide: {
				effect: "slideUp",
				delay: 50
			}
		});
	}
	
	function HT_format_tooltip(tooltipTxt) {
		var formattedTxt = tooltipTxt.replace(/__ts__/g, '<div class="tooltip-title">');
		formattedTxt = formattedTxt.replace(/__rs__/g, '<div class="tooltip-row">');
		return formattedTxt.replace(/(__te__|__re__)/g, '</div>');
	}

	function HT_validate_form_inputs(inputContainer) {
		var pass = true;
		var submissionErrors = '';
		jQuery(inputContainer+' input, '+inputContainer+' textarea').each(function() {
			if (typeof(formValidationRegex[jQuery(this).prop('name')]) == 'undefined') {
				formValidationRegex[jQuery(this).prop('name')] = formValidationRegex['default'];
			}
			
			var labelID = jQuery(this).prop('name').replace(/\_/g, '-').replace(/(\[|\])/g, '').replace(/(\-[0-9]+|\-tmp\-[0-9]+)$/, '')+'-label';
			if (jQuery(this).prop('type') != 'radio' && jQuery(this).prop('type') != 'checkbox' && jQuery(this).prop('type') != 'button' && jQuery(this).prop('type') != 'file' && jQuery(this).prop('type') != 'submit' && jQuery(this).prop('type') != 'image' && jQuery(this).prop('type') != 'hidden' && !formValidationRegex[jQuery(this).prop('name')].test(jQuery(this).val())) {
				pass = false;
				var label=jQuery('#'+labelID);
				if (label.prop('id') == labelID) {
					submissionErrors += label.html() + ' is invalid.<br />';
					label.addClass('bad-input-label');
				} else if (jQuery(this).prop('title')) {
					submissionErrors += jQuery(this).prop('title') + ' is invalid.<br />';
					jQuery(this).addClass('bad-input');
				} else {
					if (!(/Please correct the fields marked in red/g).test(submissionErrors)) {
						submissionErrors += 'Please correct the fields marked in red.<br />';
					}
					jQuery(this).addClass('bad-input');
				}
			} else if (jQuery(this).prop('type') != 'radio' && jQuery(this).prop('type') != 'checkbox' && jQuery(this).prop('type') != 'file' && jQuery(this).prop('type') != 'button' && jQuery(this).prop('type') != 'submit' && jQuery(this).prop('type') != 'image' && jQuery(this).prop('type') != 'hidden') {
				var label=jQuery('#'+labelID);
				if (label.prop('id') == labelID) {
					label.removeClass('bad-input-label');
				} else {
					jQuery(this).removeClass('bad-input');
				}
			}
		});

		if (!pass) {
			HT_display_ui_dialog('Submission Error', submissionErrors);
		}
		return pass;
	}
	
	function HT_display_ui_edit_form(dialogTitle, inputValues, submitFunction) {
		jQuery('.HT-ui-dialog.ui-edit-form .ui-dialog-title').html(dialogTitle);
		for(var i in inputValues) {
			jQuery('#HT-ajax-edit-form-container .'+i).val(inputValues[i]);
		}
		jQuery('#HT-ui-edit-form').dialog('open');
		jQuery('.HT-ui-dialog.ui-edit-form .ui-dialog-buttonset .ui-button:first').off('click.uiSubmit');
		jQuery('.HT-ui-dialog.ui-edit-form .ui-dialog-buttonset .ui-button:first').on('click.uiSubmit', submitFunction);
	}
	
	function HT_display_ui_dialog(dialogTitle, dialogText) {
		jQuery('.HT-ui-dialog.ui-notices .ui-dialog-title').html(dialogTitle);
		jQuery('#HT-ajax-notices-container').html(dialogText);
		jQuery('#HT-ui-notices').dialog('open');
	}
	
	function HT_toggle_loading(container) {
		jQuery(container+' .fa-spinner').toggle();
		jQuery(container+' .HT-page-content').toggle();
	}
	
	function HT_submit_ajax(action, postVars, container, callback) {
		HT_toggle_loading(container);
		jQuery.post(encodeURI(ajaxurl + '?action='+action), postVars, function (result) {
			HT_toggle_loading(container);
			var parsedResult = {};
			try {
				parsedResult = jQuery.parseJSON(result);
			} catch(err) {
				parsedResult = {'success': '0', 'message': 'There was a problem with your submission.'};
			}

			if (parsedResult['success'] == '1' && typeof(callback) == 'function') {
				callback(parsedResult);
			}

			HT_display_ui_dialog('Submission Result', parsedResult['message']);
		});
	}

</script>