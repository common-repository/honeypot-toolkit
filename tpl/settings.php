<div id="wrap" class="HT-content-wrap">
    <div class="ht-setting-icon fa fa-cogs" id="icon-ht-settings"> <br /> </div>
	
	<h2 class="ht-setting-title">Settings</h2>
    <div style="clear: both;"></div>

	<div id="HT-progress-message"></div>
	<div class="fa fa-spinner fa-pulse fa-3x fa-fw"></div>
	<div class="HT-page-content">
		<form method="post" action="" id="HT-settings-form">
			<div id="HT-tab-container">
				<ul id="HT-tab-menu">
					<li id="HT-tab-1" class="ui-tabs-active ui-state-active"><a href="#HT-tab-1-content">General Settings</a></li>
					<li id="HT-tab-2"><a href="#HT-tab-2-content">Project Honeypot</a></li>
					<li id="HT-tab-3"><a href="#HT-tab-3-content">Login</a></li>
					<li id="HT-tab-4"><a href="#HT-tab-4-content">404</a></li>
				</ul>
				<div style="clear:both;"></div>
				<div id="HT-tab-content">
					<div id="HT-tab-1-content" class="HT-tab-content" style="display: block;">
						<h3>General Settings</h3>
						<label for="ht-use-project-honeypot">Use Project Honeypot: </label><input type="checkbox" name="ht-use-project-honeypot" id="ht-use-project-honeypot" value="1" <?php print ($htSettings['use_project_honeypot'] == '1')? 'checked="checked"':''; ?>><span class="help-dialog fa fa-question-circle" title="__ts__Use Project Honeypot__te____rs__Use Project Honeypot to block users based on their threat score.__re____rs__You must enter your API Key on the Project Honeypot tab before this will work."></span><br />
						<label for="ht-use-spamcop">Use Spamcop: </label><input type="checkbox" name="ht-use-spamcop" id="ht-use-spamcop" value="1" <?php print ($htSettings['use_spamcop'] == '1')? 'checked="checked"':''; ?>><span class="help-dialog fa fa-question-circle" title="__ts__Use Spamcop__te____rs__Use Spamcop to block users based on their IP being listed in the block list.__re__"></span><br />
						<label for="ht-ph-check-ip-interval">Check Interval: </label>
						<select name="ht-ph-check-ip-interval" id="ht-ph-check-ip-interval">
							<?php for($i=1; $i<=60; $i++) { ?>
								<option value="<?php print $i; ?>" <?php print ($htSettings['ph_check_ip_interval'] == $i)? 'selected="selected"':''; ?>><?php print $i; ?></option>
							<?php } ?>
						<select>
						<span class="help-dialog fa fa-question-circle" title="__ts__Check Interval__te____rs__This is the interval in days that all IP addresses blocked by Project Honeypot and Spamcop will be checked to see if they are still on their block lists.__re____rs__This check will do a DNS query for every IP in your database that was blocked because of a response from Project Honeypot or Spamcop.  Be careful setting this to a low number if you have a large number of IP adresses on your block list."></span><br /><br />

						<?php
						if (is_multisite()) {
							?>
							<label for="ht-login-mon">Site Level Lists: </label><input type="checkbox" name="ht-site-level-lists" id="ht-site-level-lists" value="1" <?php print ($htSettings['site_level_lists'] == '1')? 'checked="checked"':''; ?>>
							<span class="help-dialog fa fa-question-circle" title="__ts__Site Level Lists__te____rs__Allow admins to manage the IP lists on each site and not just the network admin.__re__"></span><br />
							<?php
						}
						?>
						
						<label for="ht-only-allow-whitelist">Only Allow Whitelist: </label>
						<select name="ht-only-allow-whitelist" id="ht-only-allow-whitelist">
							<option value="1" <?php print ($htSettings['only_allow_whitelist'] == '1')? 'selected="selected"':''; ?>>Yes</option>
							<option value="0" <?php print ($htSettings['only_allow_whitelist'] == '0')? 'selected="selected"':''; ?>>No</option>
						</select>
						<span class="help-dialog fa fa-question-circle" title="__ts__Only Allow Whitelist__te____rs__With this enabled no one can access the site unless they are on the whitelist.__re__"></span><br />

						<label for="ht-login-mon">Monitor Login: </label><input type="checkbox" name="ht-login-mon" id="ht-login-mon" value="1" <?php print ($htSettings['login_mon'] == '1')? 'checked="checked"':''; ?>>
						<span class="help-dialog fa fa-question-circle" title="__ts__Monitor Login__te____rs__With this enabled login errors will be counted.__re____rs__If a visitor generates enough login errors to reach the limit they will be blocked for a period of time.__re____rs__The settings to control the limit, blocked time, and time span are located on the Login tab.__re____rs__Users on the whitelist are not monitored.__re____rs__This does not report the IP to Project Honeypot or Spamcop. You need to set up your honeypot script to report the IP to Project Honeypot for monitoring. Spamcop is a service where you can submit spam emails and they gather the IP addresses of offending servers to block from that.__re__"></span><br />

						<label for="ht-hide-usernames">Hide Usernames: </label><input type="checkbox" name="ht-hide-usernames" id="ht-hide-usernames" value="1" <?php print ($htSettings['hide_usernames'] == '1')? 'checked="checked"':''; ?>>
						<span class="help-dialog fa fa-question-circle" title="__ts__Hide Usernames__te____rs__When this is enabled the URL for all author archives will be changed to have an md5 hash instead of the username.__re____rs__The username presented on an article will also be changed to obfiscate it.__re____rs__This prevents scanning of usernames by appending ?author=## to the end of your sites URL.__re____rs__If a visitor attempts to log into your site with the md5 hash they will automatically be blocked if you have the Monitor Login option selected.__re__"></span><br />
						
						<label for="ht-404-limit">Monitor 404: </label><input type="checkbox" name="ht-404-mon" id="ht-404-mon" value="1" <?php print ($htSettings['404_mon'] == '1')? 'checked="checked"':''; ?>>
						<span class="help-dialog fa fa-question-circle" title="__ts__Monitor 404__te____rs__With this enabled 404 errors wil be counted.__re____rs__If a visitor generates enough 404 errors to reach the limit they will be blocked for a period of time.__re____rs__The settings to control the limit, blocked time, and time span are located on the 404 tab.__re____rs__Users on the whitelist are not monitored.__re____rs__This does not report the IP to Project Honeypot or Spamcop. You need to set up your honeypot script to report the IP to Project Honeypot for monitoring. Spamcop is a service where you can submit spam emails and they gather the IP addresses of offending servers to block from that.__re__"></span><br />

						<label for="ht-response-type">Response Code: </label>
						<select name="ht-response-code" id="ht-response-code">
							<option value="400" <?php print ($htSettings['response_code'] == 400)? 'selected="selected"':''; ?>>400: Bad Request</option>
							<option value="403" <?php print ($htSettings['response_code'] == 403)? 'selected="selected"':''; ?>>403: Forbidden</option>
							<option value="406" <?php print ($htSettings['response_code'] == 406)? 'selected="selected"':''; ?>>406: Not Acceptable</option>
							<option value="408" <?php print ($htSettings['response_code'] == 408)? 'selected="selected"':''; ?>>408: Request Timeout</option>
							<option value="410" <?php print ($htSettings['response_code'] == 410)? 'selected="selected"':''; ?>>410: Gone</option>
							<option value="429" <?php print ($htSettings['response_code'] == 429)? 'selected="selected"':''; ?>>429: Too many requests</option>
							<option value="502" <?php print ($htSettings['response_code'] == 502)? 'selected="selected"':''; ?>>502: Bad gateway</option>
							<option value="503" <?php print ($htSettings['response_code'] == 503)? 'selected="selected"':''; ?>>503: Service Unavailable</option>
							<option value="504" <?php print ($htSettings['response_code'] == 504)? 'selected="selected"':''; ?>>504: Gateway time-out</option>
							<option value="505" <?php print ($htSettings['response_code'] == 505)? 'selected="selected"':''; ?>>505: HTTP version not supported</option>
							<option value="508" <?php print ($htSettings['response_code'] == 508)? 'selected="selected"':''; ?>>508: Loop detected</option>
						</select>
						<span class="help-dialog fa fa-question-circle" title="__ts__Response Code__te____rs__The http response code that is returned when a user is blocked.__re__"></span>
					</div>
					<div id="HT-tab-2-content" class="HT-tab-content">
						<h3>Project Honeypot</h3>
						<label for="ht-ph-api-key">Project Honeypot API Key: </label><input type="text" name="ht-ph-api-key" id="ht-ph-api-key" value="<?php print $htSettings['ph_api_key']; ?>">
						<span class="help-dialog fa fa-question-circle" title="__ts__Project Honeypot API Key__te____rs__This is the HTTP:BL API key you can request from Project Honeypot.__re____rs__Go to https://www.projecthoneypot.org/httpbl_configure.php to request an API key.__re__"></span>&nbsp;<a href="https://www.projecthoneypot.org/httpbl_configure.php" target="_blank">Request&nbsp;Key</a><br />
						<label for="ht-ph-bl-max-days">Max Days: </label>
						<select name="ht-ph-bl-max-days" id="ht-ph-bl-max-days">
							<?php for($i=1; $i<=255; $i++) { ?>
								<option value="<?php print $i; ?>" <?php print ($htSettings['ph_bl_max_days'] == $i)? 'selected="selected"':''; ?>><?php print $i; ?></option>
							<?php } ?>
						<select>
						<span class="help-dialog fa fa-question-circle" title="__ts__Max Days__te____rs__Maximum number of days since the last time activity has been seen from this IP by Project Honeypot.__re__"></span><br />
						<label for="ht-ph-bl-threat-score">Threat Score: </label>
						<select name="ht-ph-bl-threat-score" id="ht-ph-bl-threat-score">
							<?php for($i=1; $i<=255; $i++) { ?>
								<option value="<?php print $i; ?>" <?php print ($htSettings['ph_bl_threat_score'] == $i)? 'selected="selected"':''; ?>><?php print $i; ?></option>
							<?php } ?>
						<select>
						<span class="help-dialog fa fa-question-circle" title="__ts__Threat Score__te____rs__This score is assigned internally by Project Honey Pot based on a number of factors including the number of honey pots the IP has been seen visiting and the damage done during those visits.__re__"></span><br />
						
						<?php
						if (!is_multisite()) {
							?>
							<label for="ht-honeypot-path">Honeypot Path: </label><input type="text" name="ht-honeypot-path" id="ht-honeypot-path" value="<?php print $htSettings['honeypot_path']; ?>"><br />
							
							<label for="ht-use-custom-honeypot">Use Custom Honeypot: </label><input type="checkbox" name="ht-use-custom-honeypot" id="ht-use-custom-honeypot" value="1" <?php print ($htSettings['use_custom_honeypot'] == '1')? 'checked="checked"':''; ?>><span class="help-dialog fa fa-question-circle" title="__ts__Use Custom Honeypot__te____rs__Checking this will add an action call to ht_custom_honeypot that you can use in your theme.__re____rs__Then you just need to add do_action('ht_custom_honeypot') wherever you would like the honeypot link to be printed.__re__"></span><br />

							<label for="ht-use-body-open-honeypot">Use Body Open Honeypot: </label><input type="checkbox" name="ht-use-body-open-honeypot" id="ht-use-body-open-honeypot" value="1" <?php print ($htSettings['use_body_open_honeypot'] === '0')? '':'checked="checked"'; ?>><span class="help-dialog fa fa-question-circle" title="__ts__Use Body Open Honeypot__te____rs__Checking this will add an action call to the wp_body_open action hook.__re____rs__Most themes call this after the body open tag.__re__"></span><br />

							<label for="ht-use-menu-honeypot">Use Menu Honeypot: </label><input type="checkbox" name="ht-use-menu-honeypot" id="ht-use-menu-honeypot" value="1" <?php print ($htSettings['use_menu_honeypot'] === '0')? '':'checked="checked"'; ?>><span class="help-dialog fa fa-question-circle" title="__ts__Use Menu Honeypot__te____rs__Checking this will add a filter call to the wp_nav_menu and wp_page_menu filter hooks.__re____rs__This will print your honeypot after your nav menus.__re__"></span><br />

							<label for="ht-use-search-form-honeypot">Use Search Form Honeypot: </label><input type="checkbox" name="ht-use-search-form-honeypot" id="ht-use-search-form-honeypot" value="1" <?php print ($htSettings['use_search_form_honeypot'] === '0')? '':'checked="checked"'; ?>><span class="help-dialog fa fa-question-circle" title="__ts__Use Search Form Honeypot__te____rs__Checking this will add a filter call to the get_search_form filter hook.__re____rs__This will print your honeypot after the search form either in your theme or in a widget that uses the get_search_form function.__re__"></span><br />
							
							<label for="ht-use-footer-honeypot">Use Footer Honeypot: </label><input type="checkbox" name="ht-use-footer-honeypot" id="ht-use-footer-honeypot" value="1" <?php print ($htSettings['use_footer_honeypot'] === '0')? '':'checked="checked"'; ?>><span class="help-dialog fa fa-question-circle" title="__ts__Use Footer Honeypot__te____rs__Checking this will add an action call to the wp_footer action hook.__re____rs__This will print your honeypot where the wp_footer function is called in your theme.__re__"></span><br />
							
							<label for="ht-use-the-content-honeypot">Use The Content Honeypot: </label><input type="checkbox" name="ht-use-the-content-honeypot" id="ht-use-the-content-honeypot" value="1" <?php print ($htSettings['use_the_content_honeypot'] === '0')? '':'checked="checked"'; ?>><span class="help-dialog fa fa-question-circle" title="__ts__Use The Content Honeypot__te____rs__Checking this will add a filter call to the the_content filter hook.__re____rs__This will print your honeypot after the post/page content in your theme.__re__"></span><br /><br />
							
							<div class="HT-network-msg fa fa-info-circle">If you uncheck all positions then your honeypot will never be added to your pages.</div>
							<?php
						}
						?>
					</div>
					<div id="HT-tab-3-content" class="HT-tab-content">
						<h3>Login</h3>
						<label for="ht-login-limit">Login Limit: </label><input type="text" name="ht-login-limit" id="ht-login-limit" value="<?php print $htSettings['login_limit']; ?>">
						<span class="help-dialog fa fa-question-circle" title="__ts__Login Limit__te____rs__The number of failed logins before a user is blocked.__re__"></span><br />
						<label for="ht-login-time-span">Time Span: </label><input type="text" name="ht-login-time-span" id="ht-login-time-span" value="<?php print $htSettings['login_time_span']; ?>">
						<span class="help-dialog fa fa-question-circle" title="__ts__Time Span__te____rs__The number of seconds that failed logins will be tracked.__re____rs__If a user hits the limit of failed logins within this number of seconds they will be blocked.__re__"></span><br />
						<label for="ht-login-block-time">Block Time: </label><input type="text" name="ht-login-block-time" id="ht-login-block-time" value="<?php print $htSettings['login_block_time']; ?>">
						<span class="help-dialog fa fa-question-circle" title="__ts__Block Time__te____rs__The number of seconds that a user will be blocked if they reach the login limit.__re__"></span><br />
						<label for="ht-show-login-count">Show Failed Count: </label>
						<select name="ht-show-login-count" id="ht-show-login-count">
							<option value="1" <?php print ($htSettings['show_login_count'] == '1')? 'selected="selected"':''; ?>>Yes</option>
							<option value="0" <?php print ($htSettings['show_login_count'] == '0')? 'selected="selected"':''; ?>>No</option>
						</select>
						<span class="help-dialog fa fa-question-circle" title="__ts__Show Failed Count__te____rs__Setting this will show a visitor the number of times they have failed to log in and how many tries they have before being blocked.__re__"></span><br />
						<label for="ht-banned-uernames">Banned Usernames: </label><textarea rows="5" cols="30" name="ht-banned-usernames" id="ht-banned-usernames"><?php print $htSettings['banned_usernames']; ?></textarea>
						<span class="help-dialog fa fa-question-circle" title="__ts__Banned Usernames__te____rs__This is a list of usernames that will automatically get a visitor blocked.__re____rs__Adding the admin user to this list is recommended if you have set a different user for your admin account as many bots try that user first.__re____rs__Users on the whitelist are not blocked if they use one of these users.__re____rs__Each user must be on a different line.__re____rs__Example:<br />User1<br />User2<br />User3__re__"></span>
					</div>
					<div id="HT-tab-4-content" class="HT-tab-content">
						<h3>404</h3>
						<label for="ht-404-limit">404 Limit: </label><input type="text" name="ht-404-limit" id="ht-404-limit" value="<?php print $htSettings['404_limit']; ?>">
						<span class="help-dialog fa fa-question-circle" title="__ts__404 Limit__te____rs__The number of 404 errors before a user is blocked.__re__"></span><br />
						<label for="ht-404-time-span">Time Span: </label><input type="text" name="ht-404-time-span" id="ht-404-time-span" value="<?php print $htSettings['404_time_span']; ?>">
						<span class="help-dialog fa fa-question-circle" title="__ts__Time Span__te____rs__The number of seconds that 404 errors will be tracked.__re____rs__If a user hits the limit of 404 errors within this number of seconds they will be blocked.__re__"></span><br />
						<label for="ht-404-block-time">Block Time: </label><input type="text" name="ht-404-block-time" id="ht-404-block-time" value="<?php print $htSettings['404_block_time']; ?>">
						<span class="help-dialog fa fa-question-circle" title="__ts__Block Time__te____rs__The number of seconds that a user will be blocked if they reach the 404 error limit.__re__"></span>
					</div>
					<div class="HT-form-control-container"><input type="button" name="ht-save-settings" id="ht-save-settings" value="Save"></div>
				</div>
			</div>
		</form>
	</div>
</div>