<div class="view-container" id="music-user" ng-show="!loading">
	<h1 translate>Settings</h1>

	<h2 translate>Paths</h2>
	<div>
		<div class="label-container">
			<label for="music-path" translate>Path to your music collection</label>:
		</div>
		<div class="icon-loading-small" ng-show="pathChangeOngoing" id="path-change-in-progress"></div>
		<input type="text" id="music-path" ng-class="{ 'invisible': pathChangeOngoing }"
			ng-model="settings.path" ng-click="selectPath()"/>
		<span style="color:red" ng-show="errorPath" translate>Failed to save the music collection path</span>
		<p><em translate>This setting specifies the folder which will be scanned for music.</em></p>
		<p><em translate>Note: When the path is changed, any previously scanned files outside the new path are removed from the collection and any playlists.</em></p>
	</div>
	<div>
		<div class="label-container">
			<label for="excluded-paths" translate>Paths to exclude from your music collection</label>:
		</div>
		<em>
			<p>
				<span translate>Specify folders within your music collection path which shall be excluded from the scanning.</span>
				<strong class="clickable" ng-click="showExcludeHint=true" ng-hide="showExcludeHint" translate>Show more…</strong>
			</p>
			<div ng-show="showExcludeHint">
				<p translate>You can use the wild cards '?', '*', and '**':</p>
				<ul class="info-list">
					<li translate><strong>?</strong> matches any one character within a path segment</li>
					<li translate><strong>*</strong> matches zero or more arbitrary characters within a path segment</li>
					<li translate><strong>**</strong> matches zero or more arbitrary characters including path segment separators '/'</li>
				</ul>
				<p translate>Paths with a leading '/' character are resolved relative to the user home directory and others relative to the music library base path.</p>
				<p translate>Changes to the excluded paths will only take effect upon rescan.</p>
			</div>
		</em>
		<table id="excluded-paths" class="grid">
			<tr class="excluded-path-row" ng-repeat="path in settings.excludedPaths track by $index">
				<td><input type="text" ng-model="settings.excludedPaths[$index]" ng-enter="$event.target.blur()" ng-blur="commitExcludedPaths()"/></td>
				<td class="key-action"><a class="icon icon-folder" ng-click="selectExcludedPath($index)" title="{{ 'Select folder' | translate }}"></a></td>
				<td class="key-action"><a class="icon icon-delete" ng-click="removeExcludedPath($index)" title="{{ 'Remove' | translate }}"></a></td>
			</tr>
			<tr class="add-row" ng-click="addExcludedPath()">
				<td><a class="icon" ng-class="savingExcludedPaths ? 'icon-loading-small' : 'icon-add'"></a></td>
				<td class="key-action"></td>
				<td class="key-action"></td>
			</tr>
		</table>
		<span style="color:red" ng-show="errorIgnoredPaths" translate>Failed to save the ignored paths</span>
	</div>

	<h2 translate>Reset</h2>
	<div>
		<div class="label-container">
			<label for="reset-collection" translate>Reset music collection</label>
		</div>
		<div class="icon-loading-small reset-in-progress" ng-show="collectionResetOngoing"></div>
		<input type="button" ng-class="{ 'invisible': collectionResetOngoing }"
			class="icon-delete reset-button" id="reset-collection" ng-click="resetCollection()"/>
		<p><em translate>This action resets all the scanned tracks and all the user-created playlists. After this, the collection can be scanned again from scratch.</em></p>
		<p><em translate>This may be desirable after changing the excluded paths, or if the database would somehow get corrupted. If the latter happens, please report a bug to the <a href="{{issueTrackerUrl}}" target="_blank">issue tracker</a>.</em></p>
	</div>
	<div>
		<div class="label-container">
			<label for="reset-radio" translate>Reset internet radio stations</label>
		</div>
		<div class="icon-loading-small reset-in-progress" ng-show="radioResetOngoing"></div>
		<input type="button" ng-class="{ 'invisible': radioResetOngoing }"
			class="icon-delete reset-button" id="reset-radio" ng-click="resetRadio()"/>
		<p><em translate>This action erases all the stations shown in the "Internet radio" view.</em></p>
	</div>
	<div>
		<div class="label-container">
			<label for="reset-podcasts" translate>Reset podcast channels</label>
		</div>
		<div class="icon-loading-small reset-in-progress" ng-show="podcastsResetOngoing"></div>
		<input type="button" ng-class="{ 'invisible': podcastsResetOngoing }"
			class="icon-delete reset-button" id="reset-podcasts" ng-click="resetPodcasts()"/>
		<p><em translate>This action erases all the channels shown in the "Podcasts" view.</em></p>
	</div>

	<h2 translate>User interface</h2>
	<div class="label-container">
		<label for="song-notifications-toggle" translate>Song change notifications</label>
	</div>
	<input type="checkbox" id="song-notifications-toggle" ng-model="songNotificationsEnabled"/>
	<p><em translate>Show desktop notification when the playing song changes. You also need to have the desktop notifications allowed in your browser for this site.</em></p>
	<p><em translate>Unlike the other settings, this switch is stored per browser and not per user account.</em></p>

	<h2 translate>Ampache and Subsonic</h2>
	<div translate>You can browse and play your music collection from external applications which support either Ampache or Subsonic API.</div>
	<div class="warning" translate>
		Note that Music may not be compatible with all Ampache/Subsonic clients. Check the verified <a href="{{ampacheClientsUrl}}" target="_blank">Ampache clients</a> and <a href="{{subsonicClientsUrl}}" target="_blank">Subsonic clients</a>.
	</div>
	<div>
		<code id="ampache-url" ng-bind="settings.ampacheUrl"></code>
		<a class="clipboardButton icon icon-clippy" ng-click="copyToClipboard('ampache-url')"></a><br />
		<em translate>Use this address to browse your music collection from an Ampache compatible player.</em> <em translate>If this URL doesn't work try to append '/server/xml.server.php'.</em>
	</div>
	<div>
		<code id="subsonic-url" ng-bind="settings.subsonicUrl"></code>
		<a class="clipboardButton icon icon-clippy" ng-click="copyToClipboard('subsonic-url')"></a><br />
		<em translate>Use this address to browse your music collection from a Subsonic compatible player.</em>
	</div>
	<div translate>
		Here you can generate passwords to use with the Ampache or Subsonic API. Separate passwords are used because they can't be stored in a really secure way due to the design of the APIs. You can generate as many passwords as you want and revoke them at anytime.
	</div>
	<table id="music-ampache-keys" class="grid" ng-show="settings.ampacheKeys.length">
		<tr class="head">
			<th translate>Description</th>
			<th class="key-action" translate>Revoke API password</th>
		</tr>
		<tr ng-repeat="key in settings.ampacheKeys">
			<td>{{key.description}}</td>
			<td class="key-action"><a class="icon" ng-class="key.loading ? 'icon-loading-small' : 'icon-delete'" ng-click="removeAPIKey(key)"></a></td>
		</tr>
	</table>
	<div id="music-ampache-form">
		<input type="text" id="music-ampache-description" ng-model="ampacheDescription"
			placeholder="{{ 'Description (e.g. App name)' | translate }}" ng-enter="addAPIKey()"/>
		<button translate ng-click="addAPIKey()">Generate API password</button>
		<span style="color:red" ng-show="errorAmpache" translate>Failed to generate new Ampache/Subsonic password</span>
		<div id="music-password-info" class="info" ng-show="ampachePassword">
			<span translate>Use the following credentials to connect to this Ampache/Subsonic instance.</span>
			<dl>
				<dt translate>Username:</dt>
				<dd>{{ settings.user }}</dd>
				<dt translate>Password:</dt>
				<dd><span id="pw-label">{{ ampachePassword }}</span><a class="clipboardButton icon icon-clippy" ng-click="copyToClipboard('pw-label')"></a></dd>
			</dl>
		</div>
	</div>

	<h2 translate>Admin</h2>
	<div class="clickable" ng-show="!showAdmin" ng-click="showAdmin=true" translate>Show...</div>
	<div ng-show="showAdmin">
		<p translate translate-params-filename="'<cloud root>/config/config.php'">
			There is no settings UI for the server-wide settings of the Music app but some settings are available by adding specific key-value pairs to the file <samp>{{filename}}</samp>. The available keys are described below.
		</p>
		<div>
			<p>music.lastfm_api_key</p>
			<p><em translate
					translate-params-lastfm-url="'https://www.last.fm/api/account/create'"
					translate-params-guide-url="'https://github.com/owncloud/music/wiki/Setting-up-Last.fm-connection'"
			>
				To see the artist biography and other information from Last.fm in the details view, you need to create an API account with Last.fm. For this, use the <a href="{{lastfmUrl}}" target="_blank">Last.fm form</a>. Only 'Contact email' and 'Application name' need to be filled in the form. You are then provided with an API key which should be used as a value for this key. For more details, see the <a href="{{guideUrl}}" target="_blank">tutorial</a>.
			</em></p>
		</div>
		<div>
			<p>music.cover_size</p>
			<p><em translate>
				Large album cover images are down-scaled to this size on the server before providing them for the web browser or the Subsonic/Ampache client. Smaller images are not up-scaled. The default size is 380 pixels. The value should be given as a single integer.
			</em></p>
		</div>
		<div>
			<p>music.allowed_radio_src</p>
			<p><em translate translate-params-url="'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/media-src'">
				Array of allowed non-HLS radio streaming hosts. Default is ['http://*:*', 'https://*:*'], allowing streaming from any remote URL. The given URLs will be added to the Content-Security-Policy header <a href="{{url}}" target="_blank">media-src</a>.
			</em></p>
		</div>
		<div>
			<p>music.allowed_radio_hls_src</p>
			<p><em translate
					translate-params-connect-src-url="'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/connect-src'"
					translate-params-media-src-url="'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/media-src'"
			>
				Array of allowed HLS radio streaming hosts. Default is [], blocking HLS streams from any remote URL. The given URLs will be added to the Content-Security-Policy header <a href="{{connectSrcUrl}}" target="_blank">connect-src</a>. Furthermore, if you specify any allowed sources, then also sources <samp>data:</samp> and <samp>blob:</samp> will be added to the CSP <a href="{{mediaSrcUrl}}" target="_blank">media-src</a>.
			</em></p>
		</div>
		<div>
			<p>music.podcast_auto_update_interval</p>
			<p>
				<em translate>The interval for automatic podcast update checks in hours. Decimal value can be used for sub-hour resolution. Negative value will disable automatic updating. The default value is 24 hours.</em><br/>
				<em translate>Note: the update rate is limited also by the execution rate of your cloud background task.</em>
			</p>
		</div>
	</div>

	<h2 translate>About</h2>
	<div>
		<p>
			<img class="logotype" src="<?php \OCA\Music\Utility\HtmlUtil::printSvgPath('music_logotype_horizontal') ?>" />
			<br/>
			<span translate>Music</span> <span>v{{ settings.appVersion }}</span>
			(<a href="https://github.com/owncloud/music/releases" target="_blank" translate>version history</a>)
		</p>
		<p translate>
			Please report any bugs and issues to the <a href="{{issueTrackerUrl}}" target="_blank">issue tracker</a>
		</p>
	</div>

</div>
