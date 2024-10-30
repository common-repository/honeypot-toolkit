<div id="wrap" class="HT-content-wrap">
    <div class="ht-setting-icon fa fa-handshake-o" id="icon-ht-whitelist"> <br /> </div>
	
	<h2 class="ht-setting-title">Whitelisted IP Addresses</h2>
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
		<div class="ip-list-item whitelist-item header">
			<div class="whitelist-actions ip-list-column">
				<div class="inner-ip-list-column">
					Actions<br />
					<input type="checkbox" value="0" id="select-all-ip-entries">
					<select name="ht-bulk-action" class="ht-bulk-action">
					  <option value="blacklist">Blacklist</option>
					  <option value="remove">Remove</option>
					</select>
					<input type="button" name="perform-bulk-action" class="perform-bulk-action" value="Go">
				</div>
			</div>
			<div class="whitelist-ip-start ip-list-column">
				<div class="inner-ip-list-column">
					<a class="ip-start" href="#">IP Address Range Start</a>
				</div>
			</div>
			<div class="whitelist-ip-end ip-list-column">
				<div class="inner-ip-list-column">
					<a class="ip-end" href="#">IP Address Range End</a>
				</div>
			</div>
			<div class="whitelist-count ip-list-column">
				<div class="inner-ip-list-column">
					<a class="activity" href="#">Activity Count</a>
				</div>
			</div>
			<div class="whitelist-notes ip-list-column">
				<div class="inner-ip-list-column">
					<a class="notes" href="#">Notes</a>
				</div>
			</div>
			<div style="clear: both;"></div>
		</div>
			
			
		<div class="ip-list-content">
			
		</div>

		<div class="ip-list-item whitelist-item new">
			<div class="ip-list-actions ip-list-column">
				<div class="inner-ip-list-column">
					<input type="button" name="add-ip" class="add-ip-button" value="Add">
				</div>
			</div>
			<div class="whitelist-ip-start ip-list-column">
				<div class="inner-ip-list-column">
					<input type="text" name="new-ip-address-start" class="new-ip-address-start" value="" title="Start IP">
				</div>
			</div>
			<div class="whitelist-ip-end ip-list-column">
				<div class="inner-ip-list-column">
					<input type="text" name="new-ip-address-end" class="new-ip-address-end" value="" title="End IP">
				</div>
			</div>
			<div class="whitelist-count ip-list-column">
				<div class="inner-ip-list-column">
					&nbsp;
				</div>
			</div>
			<div class="whitelist-notes ip-list-column">
				<div class="inner-ip-list-column">
					<textarea name="new-ip-notes" class="new-ip-notes" title="Notes"></textarea>
				</div>
			</div>
			<div style="clear: both;"></div>
		</div>

		<div class="ip-list-template">
			<div class="ip-list-actions ip-list-column">
				<div class="inner-ip-list-column">
					<input type="checkbox" name="whitelist-id" class="whitelist-id" value="">
					<input type="button" name="edit-whitelist-entry" class="edit-whitelist-entry" value="Edit">
				</div>
			</div>
			<div class="whitelist-ip-start ip-list-column">
				<div class="inner-ip-list-column">
					<a href="" target="_blank"></a>
				</div>
			</div>
			<div class="whitelist-ip-end ip-list-column">
				<div class="inner-ip-list-column">
					<a href="" target="_blank"></a>
				</div>
			</div>
			<div class="whitelist-count ip-list-column">
				<div class="inner-ip-list-column">
				</div>
			</div>
			<div class="whitelist-notes ip-list-column">
				<div class="inner-ip-list-column">
				</div>
			</div>
			<div style="clear: both;"></div>
		</div>
	</div>
</div>


<div id="HT-ui-edit-form" title="Edit whitelist entry">
	<div id="HT-ajax-edit-form-container">
		<label for="edit-ip-address-start">Start IP</label>
		<input type="text" name="edit-ip-address-start" class="edit-ip-address-start" value="" title="Start IP"><br />
		<label for="edit-ip-address-end">End IP</label>
		<input type="text" name="edit-ip-address-end" class="edit-ip-address-end" value="" title="End IP"><br />
		<label for="edit-ip-address-notes">Notes</label>
		<textarea name="edit-ip-notes" class="edit-ip-notes" title="Notes"></textarea><br />
		<input type="hidden" name="whitelist-id" class="whitelist-id" value="">
	</div>
</div>