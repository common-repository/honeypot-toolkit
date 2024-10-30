<div id="wrap" class="HT-content-wrap">
    <div class="ht-setting-icon fa fa-history" id="icon-ht-settings"> <br /> </div>
	
	<h2 class="ht-setting-title">Activity</h2>
    <div style="clear: both;"></div>

	<div class="fa fa-spinner fa-pulse fa-3x fa-fw"></div>
	
	<div class="HT-page-content">
		<div class="ip-list-navigation">
			<div id="navigation-button-container">
				<input type="button" class="previous-page disabled" value="&#9664;" disabled="disabled">
				<select id="ip-list-page-select"></select>
				<input type="button" class="next-page disabled" value="&#9654;" disabled="disabled">
			</div>
			<div id="search-input-wrapper">
				<input type="text" name="search_ip_list_q" id="search-ip-list-q">
				<input type="button" name="search_ip_list" id="search-ip-list" value="Search">
				<input type="button" name="reset_ip_search" id="reset-ip-search" value="Reset">
			</div>
		</div>
		<div class="ip-list-item activity-item header">
			<div class="ip-list-actions ip-list-column">
				<div class="inner-ip-list-column">
					Actions<br />
					<input type="checkbox" value="0" id="select-all-ip-entries">
					<select name="ht-bulk-action" class="ht-bulk-action">
					  <option value="blacklist">Blacklist</option>
					  <option value="whitelist">Whitelist</option>
					  <option value="remove">Remove</option>
					</select>
					<input type="button" name="perform-bulk-action" class="perform-bulk-action" value="Go">
				</div>
			</div>
			<div class="activity-ip ip-list-column">
				<div class="inner-ip-list-column">
					<a class="ip" href="#">IP Address</a>
				</div>
			</div>
			<div class="activity-time ip-list-column">
				<div class="inner-ip-list-column">
					<a class="time" href="#">Activity Time</a>
				</div>
			</div>
			<div class="activity-count ip-list-column">
				<div class="inner-ip-list-column">
					<a class="count" href="#">Activity Count</a>
				</div>
			</div>
			<div class="activity-type ip-list-column">
				<div class="inner-ip-list-column">
					<a class="type" href="#">Activity Type</a><br />
					<select id="ip-list-record-type">
			          <option value="">ALL</option>
			          <option value="login">Login</option>
			          <option value="404">404</option>
			        </select>
				</div>
			</div>
			<div class="activity-notes ip-list-column">
				<div class="inner-ip-list-column">
					<a class="notes" href="#">Notes</a>
				</div>
			</div>
			<div style="clear: both;"></div>
		</div>
		
		<div class="ip-list-content"></div>

		<div class="ip-list-template">
			<div class="ip-list-actions ip-list-column">
				<div class="inner-ip-list-column">
					<input type="checkbox" name="activity-id" class="activity-id" value="">
				</div>
			</div>
			<div class="activity-ip ip-list-column">
				<div class="inner-ip-list-column">
					<a href="" target="_blank"></a>
				</div>
			</div>
			<div class="activity-time ip-list-column">
				<div class="inner-ip-list-column">
				</div>
			</div>
			<div class="activity-count ip-list-column">
				<div class="inner-ip-list-column">
				</div>
			</div>
			<div class="activity-type ip-list-column">
				<div class="inner-ip-list-column">
				</div>
			</div>
			<div class="activity-notes ip-list-column">
				<div class="inner-ip-list-column">
				</div>
			</div>
			<div style="clear: both;"></div>
		</div>
	</div>
</div>