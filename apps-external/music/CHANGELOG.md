## 1.4.1 - 2021-10-31
### Added
- `occ` commands `playlist-export` and `playlist-import`
  [#832](https://github.com/owncloud/music/issues/832)

### Changed
- Ampache: A few more actions now support pagination with offset and limit: `artist_albums`, `artist_songs`, `album_songs`, `search_songs`
- Subsonic: Added support to `getArtistInfo` to identify the artist using a track ID, an album ID, or a folder ID
  [#906](https://github.com/owncloud/music/issues/906)
- Subsonic: Added support to `getAlbumInfo` to identify the album using a track or folder ID

### Fixed
- A performance problem affecting Subsonic method `getArtist`, Ampache action `artist_albums`, and a few other functions
- Duplicate folders showing up in the tree layout of the Folders view with some tree structures
  [#905](https://github.com/owncloud/music/issues/905)

## 1.4.0 - 2021-10-10
### Added
- Hierarchical tree layout for the Folders view
  [#742](https://github.com/owncloud/music/issues/742)
- Cover art to the playlist details pane
- Subsonic features: 
  * Support playlist cover art
  * Added methods `getAlbumInfo`, `getAlbumInfo2`, `createInternetRadioStation`, `updateInternetRadioStation`, `deleteInternetRadioStation`, `scrobble`
  * Support types `frequent` and `recent` in methods `getAlbumList` and `getAlbumList2`
- Ampache features:
  * Support playlist cover art
  * Added action `record_play`
  * Support filters `frequent`, `recent`, and `forgotten` in the action `stats` for tracks, albums, and artists
- Comprehensive translations for the main app for Chinese (China)
  [#899](https://github.com/owncloud/music/pull/899) @RuofengX

### Changed
- Use smaller heading size in the Folders and Genres views
- Show the loading indicator on the web UI while check for new audio files is in progress
- Format dates and times in the details pane according the locale from the user settings
- All alphabetical sorting on the web UI now respects the rules of the locale from the user settings
- Minor optimizations on the scanning speed 
- Use HTML5 localStorage instead of cookies to store web UI settings like volume and selected view layouts
- Direct the Subsonic and Ampache base URLs to the Music app front page
  * With this, the "Open in browser" buttons found on some clients open the Music app instead of the cloud default view
- Subsonic: When browsing by folders, the main level is now the contents of the library root (previously, a level above was shown with just the one folder)
- Subsonic: When browsing by folders, don't show the folders excluded from the library
- Subsonic: Optimized loading the tracks of long playlists
- Subsonic: API version updated to 1.16.1
- Updated getID3 library to version 1.9.21-202109171300 (contains no relevant changes but this is a release version as opposed to the previously used development versions)
- Updated webpack from v4 to v5 (5.58.1)

### Fixed
- Show the German translations added in v1.3.2 also when the selected language variant is "informal: du" or "Austria"
  [#890](https://github.com/owncloud/music/pull/890)
- Deprecated use of ReflectionType on Subsonic and Ampache APIs which broke some API features on PHP8
  [#896](https://github.com/owncloud/music/issues/896)
- Navigation pane auto-collapse on mobile layout not working on recent versions of Nextcloud
- Tracks and podcasts with missing metadata causing page load failure on [Ultrasonic](https://f-droid.org/en/packages/org.moire.ultrasonic/)
- Small layout issues in the details pane
- "No search results" briefly showing up while the web UI was being loaded

## 1.3.3 - 2021-09-06
### Fixed
- Update from v1.3.1 not working properly on Nextcloud
  [#892](https://github.com/owncloud/music/issues/892)

## 1.3.2 - 2021-09-05
### Added
- Comprehensive German translation for the main app
  [#890](https://github.com/owncloud/music/pull/890) @simonspa

### Changed
- The second level parent folder name of a track is used as fallback for the artist name, in case the name cannot be extracted from the file tags

### Fixed
- Not being able to subscribe podcasts from some providers
  [#888](https://github.com/owncloud/music/pull/888) @icewind1991
- Subsonic: Argument `musicFolderId` on `getIndexes` not being optional, breaking compatibility with Soundwaves Player
  [#885](https://github.com/owncloud/music/issues/885)
- Non-latin characters showing as question marks (?) on track/album/artist names of WAV files having both RIFF and ID3v2 tags (fixed by updating getID3 to v1.9.20-202109010614)
  [#882](https://github.com/owncloud/music/issues/882)
- Application update on Nextcloud not working over Music app versions older than v1.0.0 (introduced in v1.2.1)
  [#889](https://github.com/owncloud/music/issues/889)
  [#883](https://github.com/owncloud/music/issues/883)
- Ampache: Action `stream` not supporting the type `podcast` or `podcast_episode`
  [#891](https://github.com/owncloud/music/issues/891)

## 1.3.1 - 2021-08-28
A mistake made when creating the release package 1.3.0 broke the application pretty badly. This version is a new attempt with the same content.

### Added
- Scrolling to the album by clicking the album name or image on the album details pane
- Scrolling to the artist by clicking the artist name or image on the artist details pane
- Support for podcasts
  [#875](https://github.com/owncloud/music/pull/875)
  * Dedicated view on the web UI
  * Check for new episodes manually or automatically on the background by schedule
  * Details pane for podcast channels and episodes
  * Searching/filtering in the podcasts view by channel and episode titles
  * Subsonic API including methods `getPodcasts`, `getNewestPodcasts`, `refreshPodcasts`, `createPodcastChannel`, `deletePodcastChannel`
  * Ampache API including methods `podcasts`, `podcast`, `podcast_create`, `podcast_delete`, `podcast_episodes`, `podcast_episode`, `update_podcast`
  * `occ` commands `music:podcast-add`, `music:podcast-reset`, `music:podcast-update`
- Subsonic method `getTopSongs`

### Changed
- Show the play icon overlay on album cover also in the Albums compact layout while in search mode
- Show icon also for the playlists in the navigation pane
- Excluded folder picker UI is launched with the music library path set as the base path (requires NC16+)
  [#876](https://github.com/owncloud/music/issues/876)
- Limit all Ampache results to maximum of 5000 entries to follow the API specification
- Subsonic/Ampache: On fuzzy search, match each whitespace-separated substring separately unless quotation marks used
  * Among other things, this fixes the search on [Substreamer](https://play.google.com/store/apps/details?id=com.ghenry22.substream2) which implicitly adds the quotation
- Subsonic API version updated to 1.13.0
- Ampache API version updated to 4.4.0 (aka 440000)
- Updated getID3 library to the version 1.9.20-202107131440

### Fixed
- Performance problem on Subsonic actions `getAlbumList` and `getAlbumList2` with huge libraries
  [#873](https://github.com/owncloud/music/issues/873)
- Last.fm details view not showing the tag correctly if the track/album/artist has only one tag
- Ampache client [AmpacheAlbumPlayer](https://play.google.com/store/apps/details?id=com.stemaker.ampachealbumplayer) being incompatible
- Continuing playback from the same offset when moving from Files to Music (broken since 1.0.0)
- Misleading error message shown when viewing details for an album not found from Last.fm

## 1.3.0 - 2021-08-28
(broken version)

## 1.2.1 - 2021-06-27
### Added
- Support for Nextcloud 22

### Changed
- Stream audio files without first allocating the whole file to RAM, to avoid extensive RAM use with large files
  [#864](https://github.com/owncloud/music/issues/864)
- Updated the getID3 library to version 1.9.20-202106221748 to fix scan errors with PHP8
  [#856](https://github.com/owncloud/music/issues/856)
  [#867](https://github.com/owncloud/music/issues/867)
- Deliveries for ownCloud and Nextcloud are now technically incompatible and not just signed differently
  [#865](https://github.com/owncloud/music/issues/865)

### Fixed
- Albums compact layout not collapsing albums if view switched while the search box had some text

## 1.2.0 - 2021-05-13
### Added
- Desktop notification shown when the playing song changes (with a setting to opt out)
  [#828](https://github.com/owncloud/music/issues/828)
- Alternative compact layout for the Albums view
  [#840](https://github.com/owncloud/music/issues/840)
- Support for Windows-style relative paths when parsing playlist files
  [#845](https://github.com/owncloud/music/issues/845)

### Changed
- Clicking the song info area on player bar now activates the playing view and scrolls to the current track (instead of just scrolling to the current track if available in the current view)
- Ampache/Subsonic: Trim whitespace from the begin and end of search query string
- Play icon overlay on top of album cover modified to be clearly visible both on dark and light backgrounds

### Fixed
- Details icon not being shown after a truncated album title in the Albums view
- Errors being spammed to the log on NC18+ with PHP older than 7.4 when config.php has `'debug' => true`
  [#849](https://github.com/owncloud/music/issues/849)
- Subsonic method `getPlaylist` breaking if the list has any invalid tracks
  [#853](https://github.com/owncloud/music/issues/853)
- Ampache methods returning empty result sets to Amarok which passes (invalid) argument `limit=0`
  [#854](https://github.com/owncloud/music/issues/854)
- Non-ASCII characters breaking scanning if PHP has been configured to use internal encoding other than UTF-8
  [#846](https://github.com/owncloud/music/issues/846)
- Scanning with `occ` breaking if option `--debug` given
- Scanning not working if the `allow_url_fopen` is disabled in php.ini
  [#763](https://github.com/owncloud/music/issues/763)

## 1.1.0 - 2021-03-24
### Added
- Action to sort a playlist by title, album, or artist
  [#689](https://github.com/owncloud/music/issues/689)
- Keyboard shortcut shift+space to stop the playback
- Details pane for radio stations
- Support for editing the name and the stream URL of the radio stations
- Support for creating new radio stations by manually entering the data
- Tooltip showing the full version of any truncated title in the details pane

### Changed
- jQuery library updated from 3.5.1 to 3.6.0
- lodash library updated from 4.17.20 to 4.17.21
- getID3 library updated from 1.9.20-202102260858 to 1.9.20-202103112222 (commit a309234) to fix error on parsing WAV files
  [#837](https://github.com/owncloud/music/issues/837)

### Fixed
- Potential database corruption if updating from Music version < 0.13.0 (introduced in v1.0.3)
- Playlist "updated" timestamp not updating on the UI when tracks removed or manually reordered or name or comment modified
- View unnecessarily scrolling when opening track details in the playlist view
- The color of the icon in the "no search results" box in the NC dark mode
- Playlist comment modification not synced to server if the text box was clicked again after the modification but before defocusing the field
- Details pane "Follow playback" not working correctly when playing Internet radio
- Some external deployment scripts ignoring the empty but vital Music app directories
  [#838](https://github.com/owncloud/music/issues/838)

## 1.0.3 - 2021-03-01
### Added
- Support for Nextcloud 21
  [#830](https://github.com/owncloud/music/issues/830)
- Support for PHP 8.0
- Comprehensive Finnish translation for the main application

### Changed
- Library getID3 to development version 1.9.20-202102260858, fixing e.g. [scan stalling](https://help.nextcloud.com/t/glacial-import-too-many-songs/107732) on some corrupted files

## 1.0.2 - 2021-02-18
### Fixed
- Scan stopping if a track with unknown album encourtered within the root folder (bug introduced in v1.0.0)
- Subsonic: [Jamstash](https://github.com/tsquillario/Jamstash) not working with its default configuration
  [#787](https://github.com/owncloud/music/issues/787)
- Subsonic: Method `createPlaylist` not supporting the editing of existing playlists, breaking the playlist reordering on [Jamstash](https://github.com/tsquillario/Jamstash)

### Changed
- Use the [Keep a CHANGELOG format](https://keepachangelog.com) for the changelog to show the latest changes in the Apps management of Nextcloud.
  [#824](https://github.com/owncloud/music/issues/824) @siccovansas

## 1.0.1 - 2021-02-13
- fix playlist and radio exporting not working on Nextcloud versions after 13 (bug introduced in v1.0.0) (#822)
- fix a minor layout issue in the Internet radio "Getting started" text
- fix wrong tooltip sometimes being shown in the music player embedded to Files
- this version is not released in the ownCloud marketplace because it brings significant benefit only for the Nextcloud users

## 1.0.0 - 2021-02-05
- move to semantic version numbering
  * major version is incremented when the new version drops support for some ownCloud, Nextcloud, or PHP version
  * minor version is incremented when the new version has new features but no compatibility break occurs
  * patch version is incremented when the new version has only bug fixes and/or trivially small tweaks
- drop support for PHP 7.0
- support importing and playing external streams (#784)
  * new view 'Internet radio' added to the application web UI
  * radio stations may be imported from playlist files (PLS, M3U, or M3U8)
  * radio stations may be exported to playlist file (M3U8)
  * external streams stored in the playlist files may be played within the Files app
  * also HLS type streams are supported, but their source hosts must be separately white-listed for security reasons
- add 'repeat one' as a third state for the 'Repeat' button (#808)
- workaround to show the album cover images on the mediaSession (the OS integration) also on Firefox
- hide album art from the controls pane on extra narrow window (360px or less)
- show list numbering in the playlist sidebar tab within Files also on NC20
- allow jumping to the beginning of the playing track with the 'skip previous' button also when playing with the fallback Aurora.js solution
- Subsonic:
  * add API method `getInternetRadioStations`
  * add property `created` to all album and song responses (#817)
  * add property `changed` to all playlist responses
  * return the actual user avatar with `getAvatar` (the logic to return the app logo as the avatar had got broken on v0.17.0)
  * include the encoding attribute in all the XML responses
- Ampache:
  * support `add` and `update` filters in the actions `get_indexes`, `artists`, `albums`, `songs`, and `playlists`
  * return proper `update` and `add` values in the `handshake` response (similar to the unmerged PR #514)
  * include the encoding attribute in all the XML responses
- fix play time showing as "0:60" for half a second before changing to "1:00" (#814)
- fix the support for IE10 and IE11 (broken since v0.17.0)
- fix seeking with the media control "fast forward" and "rewind" buttons
- fix parsing M3U files with empty lines (some garbage was being parsed from them)
- fix a performance issue on album art loading (issue was introduced in v0.15.0)
- fix layout and behavioral issues with the navigation pane and especially the actions popup
- update jQuery library to v3.5.1
- several other minor fixes and tweaks
- lot of internal refactoring

## 0.17.3 - 2020-11-07
- fix 'Delete' and some other actions not working correctly on Files when Music v0.17.2 installed (#798) (this was a webpack build issue, no source code change was needed)
- fix `occ` commands of the Music app not working if Nextcloud Mail is installed (#799)

## 0.17.2 - 2020-11-04
- UI tweaks:
  * add icons to the navigation pane (#794)
  * use more appropriate icons for the "skip next" and "skip previous" buttons
  * highlight the settings view 'Remove' and 'Select folder' buttons on hover
  * mobile layout: do not auto-close the navigation pane when playlist "Rename" selected
  * removed the play icon from the browser window title (previously shown while playing)
- prevent log file exploding in size on some systems if the DB content is in inconsistent state (#793)
- fix the github "Source code (zip/tar.gz)" files to properly include also the .js/.css sources (this has been broken since v0.12.0)
- attach a hash to the webpack .js and .css bundle names, ensuring they will get reloaded after the update (without user needing to make forced reload on the browser)
- update AngularJS to version 1.8.2

## 0.17.1 - 2020-10-20
- fix web UI not loading on ownCloud when installed from the release package (#792)
- the issue above does not affect Nextcloud, and there's no reason to publish this version in the Nextcloud app store

## 0.17.0 - 2020-10-18
- support for Nextcloud 20 (#790)
- provide settings option to exclude paths from the music library (#762)
  * adding excluded paths takes effect only upon rescan, but the command line tool `occ music:scan --remove-obsolete` can be used for this
- improved performance for the option `--remove-obsolete` of the `occ music:scan` command
- update getID3 library to the development version 1.9.20-202010031049 to fix an issue in parsing WAV files with a `bext` chunk (#788)
- improvements to the artist details pane when Last.fm connection is set up:
  * clicking a similar artist opens it within the details pane if the artist is present in the music library
  * more similar artists can be fetched with the link "Show all..."
- enable navigation from the album details pane to the album artist details
- Subsonic API:
  * API version updated to 1.11.0
  * added methods `getArtistInfo` and `getArtistInfo2`
  * added methods `getSimilarSongs` and `getSimilarSongs2`
  * added methods `getBookmarks`, `createBookmark`, and `deleteBookmark` (loosely based on the work by @gavine99 on #752)
  * when listing albums of an artist, include also albums where the artist is only featured and not the album artist
  * use UTC time and the "zulu format" on all timestamps (similar to "real" Subsonic server)
- Ampache API:
  * API version updated to 400001 (aka v4.0.0) (#727)
  * added action `get_indexes`
  * added action `stats` (only filters `newest`, `flagged`, and `random` supported for now)
  * added action `flag`
  * added action `goodbye`
  * added action `get_art`
  * added action `playlist_generate` (only mode `random` is supported for now)
  * added action `playlist_create`
  * added action `playlist_edit`
  * added action `playlist_delete`
  * added action `playlist_add_song`
  * added action `playlist_remove_song`
  * the `total_count` element was once again added to the XML responses because Amproid client was found to depend on it
  * for song responses, the 'title' element has been duplicated with another key 'name' because this is how the API is specified
  * the session lifetime is now extended with any valid authorized API call and not just with the `ping` call
  * the response to `ping` now contains also the lowest compatible API version, session expiry time, and the app name and version
  * the response to `handshake` now has key `api` instead of `version`
  * use error code 404 in case the requested entity is not found (matches the "real" Ampache server and recently updated specification of the API v4.x)
- fix a bug in the cache invalidation which could lead to already deleted tracks showing up on the web UI
- fix the front-end breaking on minification (similar to the old bug #434)
- bundle the front-end code and vendor libraries using webpack
  * includes jQuery 3.4.1 which is used instead of the older version shipped with the cloud
  * includes lodash.sh 4.17.20 which is now used instead of underscore.js shipped with the cloud
  * includes AngularJS 1.8.1 (up from version 1.8.0)
  * + number of smaller vendor libraries, some of which were updated, too
- small UI tweaks
- internal refactoring

## 0.16.0 - 2020-08-02
- support for playlist files (#777, #652, #645).
  * exporting playlist to M3U8 file
  * importing playlist from M3U8, M3U, or PLS file
  * playing M3U8, M3U, and PLS files directly in Files
  * the file paths within the playlist file must be relative
- playlist details pane
- album details pane
- Last.fm data on tracks details pane when available
- navigation from track details to album and artist details
- when navigating from the Files player to Music, continue from the same time offset
- add link to the github change log under the About section in the Settings view
- fix rendering of lyrics and other track details which use "CR only" new lines (#780)
- internal refactoring and minor fixes

## 0.15.1 - 2020-07-12
- fix unable to scan music after database reset (regression in v0.15.0) (#774)
- Ampache:
  * fix `handshake` and `ping` not working on JSON API (#768)
  * fix handling of `offset` argument in the action `playlists` (#768)
  * fix action `tags` to return the genres in alphabetical order (regression in v0.15.0)
  * provide limited support for actions `download` and `stream` (#768)
- Subsonic:
  * fix an error being logged if `getRandomSongs` called on an empty library (#775)
  * fix method `getGenres` to return the genres in alphabetical order (regression in v0.15.0)
- add optional argument `--folder=<target>` to `occ music:scan` to scan only the specified folder (#771)

## 0.15.0 - 2020-07-10
- added artist details pane
  * the pane shows artist biography from Last.fm if the API connection has been configured
  * the pane shows also the artist image if available in the user's library
- scan artist images from files named like "Artist Name.jpg" (or any other supported image file format)
  * the file name is case-sensitive
  * the image is provided via the route `api/artist/{id}/cover`
  * the image is available via the Ampache and Subsonic APIs
- support showing time-synced lyrics from file metadata (#657)
- tabbed layout for the track details pane (#657)
- sort tracks by the file names in the 'Folders' view (#770)
- open all external links to a new tab
- style all external links the same way as elsewhere in ownCloud and Nextcloud (underlined and postfixed with an arrow)
- proper handling for the keyboard play/pause button on Firefox (unlike Chrome and Edge, FF does not yet have a full media key support)
- fix app not working using URL with no trailing slash '/' (#765)
- fix Nextcloud notifications window empty layout being broken within the Music app on NC16+ (#772)
- fix the "empty album slots" layout problem reintroduced by Nextcloud 18 (#773)
- Ampache API improvements:
  * initial JSON API support (#768)
    + the API is not yet frozen by Ampache and the spec still has some inconsistencies
  * remove the <total_count> element from all results
    + this has been deprecated by Ampache, and none of the known clients seem to utilize it
    + removing the element already now simplified the implementation of the JSON API support
  * fix action `tag` returning the requested genre without track/album/artist counts
  * support `filter` and `exact` arguments also in the `tags` action (allows searching genre by name)
  * add <art> element to the artist results
- getID3 library updated to version 1.9.20
- AngularJS library updated to version 1.8.0
- drop support for ownCloud versions 8.2 and 9.x
- drop support for Nextcloud versions 9 - 12
- drop support for PHP 5.6
- drop support for Internet Explorer 9

## 0.14.1 - 2020-05-17
- improvements for the Subsonic API:
  * support for the types `alphabeticalByArtist` and `newest` in `getAlbumList` and `getAlbumList2`
  * add `duration` property to `album` type response entries
  * add `duration`, `created`, and `comment` properties to `playlist` type response entries
  * fix a bug in getting tracks/albums for the "unknown genre"
  * fix several issues in the track numbering on playlists
  * fix response of `getLicense` to conform the Subsonic API specification (#759)
  * set API version to 1.10.2 (#758)
- improvements for the Ampache API:
  * add support for the action `tag` (this was forgotten from v0.14.0 where other tag/genre related actions were added)
  * add element `total_count` to all responses
  * add `year` property to all `song` responses
  * proper error handling when requesting unavailable song/album/artist/etc.
  * fix several issues in the track numbering on playlists
- fix unhandled exception if the web UI is used after the user has been logged out (#682)
- fix 6th track being duplicated in the search mode on albums with exactly 6 tracks
- fix navigation from embedded Files player to the Music app (broken since v0.13.0)
- fix navigation from the search results in the Files app to the Music app
- update AngularJS library to the version 1.7.9
- update js-cookie library to the version 2.2.1

## 0.14.0 - 2020-04-21
- add 'Genres' view to the web UI
- integrate the web UI with the (Chrome) media control API (provides e.g. support for HW keys play/pause/etc and integration to the OS lock screen)
- improvements for Subsonic API:
  * support genres: add methods `getGenres` and `getSongsByGenre`, and add parameter `genre` to methods `getRandomSongs` and `getAlbumList(2)`
  * support defining year ranges on methods `getAlbumList(2)` and `getRandomSongs`
  * add `year` and `genre` properties to all album responses, add `genre` property to all song responses
  * support starring tracks, albums, and artists (#750) (stars are visible only via Subsonic)
  * proper paging when fetching random album list (one album returned only once)
  * optimize getting large folder contents
  * fix/tweak the logic used in the method `getLyrics`
  * API version set to 1.10.1
- improvements for Ampache API:
  * support `limit` and `offset` parameters on more actions; this fixes the "Random Artists/Albums/Playlists" feature on the Ampache plugin in the Kodi media player
  * support genres using the `tags` feature of the API: added actions `tags`, `tag_artists`, `tag_albums`, and `tag_songs` and include `tag` properties on song/album/artist responses
- drop the SoundManager2 library in favor of using HTML5 API directly
- update getID3 to development version 1.9.19-202004201144 (should help on #123)
- allow rescanning previously scanned tracks with `occ music:scan` by using the option `--rescan`
- fix unable to open details pane if getID3 can't extract any metadata tags
- fix several small bugs causing logged error notes (but no undesired behavior) at least on Nextcloud 18 + PHP 7.4
- fix a typo in the 'Settings' view (#751 by @amalvarenga)

## 0.13.2 - 2020-03-25
- fix SW update problems caused by the disk-number-migration script introduced in v0.13.1 (#748)

## 0.13.1 - 2020-03-22
- do not treat each disc of a multi-disc album as a separate album (#680)
- fix year tag not being extracted from M4A files (#744 by @ChrisJAllan)
- fix disc number tag not being extracted from M4A files (#746)
- update getID3 to development version 1.9.19-202003150936
  * should fix cover art not showing up on M4A files (#743)
  * fixes the issue which forced us previously to remove one commit from v1.9.19 
- log the error if opening a file for metadata extraction fails (#123)
- do not stop the whole scanning process if opening a file throws an exception
- tweak the ordering of the fields in the track details pane
- fix embedded player not showing metadata for the unscanned files (regression introduced in v0.13.0)

## 0.13.0 - 2020-02-16
- searching/filtering by title/album/artist/year/folder/path (#662, #367)
  * the query may freely combine details from tracks and their parent entities (album/artist/folder)
  * searching by album name and year works only in the Albums view
  * searching by folder name/path works only in the Folders view
  * quotation may be used to treat multiple words as a single entity instead of as separate substrings
  * searching by title/album/artist works also within the Files app, but this is much more limited than within the Music app
- improved performance/scalability for huge music collections also in the "All tracks" view
- improved quality of cover images in the Albums view and in the Ampache/Subsonic APIs (#734)
  * default size for the images is now 380px, but this can be altered with `config.php` using the key `music.cover_size`
- Subsonic: respect the `size` attribute given to method `getCoverArt`
- support for PHP 7.4 (#738)
- support for Nextcloud 19
- updated getID3 to version 1.9.19 (minus one commit which caused us problems)
- fixed scan state being shown incorrectly after resetting the music collection
- fixed a background color problem on NC18 with dark theme (#739)
- fixed a null-reference problem on the `postWrite` file hook occurring on NC16+

## 0.12.1 - 2019-12-31
- fix broken scrolling in Albums and Folders views on Nextcloud 14+ (#733)

## 0.12.0 - 2019-12-29
- improved performance for huge music collections (#728)
- collapse the "Scanning..." popup to the bottom of the screen when there are already tracks shown (#728)
- do not download the album cover image before scrolling to the album in question (#719, #653)
- Ampache: Sanitize the XML results so that no illegal characters are included (#723)
- Subsonic: Fix names containing ampersand (&) missing from the results (regression from v0.11.1)
- Subsonic: Fix format (f=...) argument being ignored if user credentials are incorrect or missing (#730)
- fix a potential dead-lock on Folders view deactivation
- fix part of the screen width being unused on NC14+ with extra wide screen when the details pane is open
- the repository no longer contains the bundled js and css files needed for execution
- the delivery packages no longer contain the source js and css files, only the bundles for execution

## 0.11.1 - 2019-11-21
- improved support for Subsonic API:
  * support most parts of the API v1.8 (added methods getUser, getArtists, getArtist, getSong, search3, getAvatar, getLyrics, updatePlaylist, deletePlaylist, along with a few other implemented as stubs)
  * prevent navigating to folders outside the user music path (#725)
  * fix notice "Only variables should be passed by reference" being logged
  * fix malformed contents breaking the XML responses
- fix warning about placeholder.js being logged on NC17+ (#721)
- fix warning about array_walk callback syntax being logged on NC17+ (#726)
- internal refactoring

## 0.11.0 - 2019-10-12
- support for Subsonic API (#718)
- truncate variable length text fields before storing them to the database (#632, #716)
- add copy-to-clipboard buttons to the Settings view next to Ampache/Subsonic addresses and passwords
- fix the dark theme detection on Nextcloud 18

## 0.10.1 - 2019-09-08
- fix warnings being logged on PHP7.2+ when fetching albums via Ampache API (#714)
- provide string "Unknown artist/album" in Ampache API for nameless entities
- allow album name to span multiple rows in the "tablet" layout
- lazy-load getID3 library to avoid interfering with AudioPlayer occ scan (#715)
- update the getID3 library to version 1.9.17
- declare support for Nextcloud 18

## 0.10.0 - 2019-07-23
- simple Folders view (#651)
- fix unable to load collection.json if there's any albums with invalid album_artist_id; remove these corrupt albums on background cleanup task (#710)
- fix "jump to previous" not always working while track was loading (caused as side effect of #691)
- declare support for Nextcloud 17

## 0.9.5 - 2019-04-27
- comprehensive support for the Nextcloud dark theme
- locale-dependent ordering for artists/albums/tracks with accented alphabets (#695)
- add link '#' to the alphabet navigation for names starting with numerals (#697 by @greku)
- add link '…' to the alphabet navigation for names starting with characters sorted after Z
- improvements for alphabet navigation on short screens (like landscape mobile phone) (#699 by @greku)
- fix unable to show details if the file tags contain any invalid utf-8 characters (#694 by @greku)
- double action for the "previous" button: first click jumps to the beginning of the current track and second click to the previous track (#691)

## 0.9.4 - 2019-03-18
- support for PHP 7.3 (#687)
- minor fixes on some localizations (e.g. #679)
- fix playlist files being incorrectly listed as audio tracks (#674 by @greku)
- fix play controls being nigh invisible with the Nextcloud dark theme (#688 by @Faldon)

## 0.9.3 - 2018-12-09
- replace deprecated call `getScrollbarWidth()` with `OC.Util.getScrollbarWidth()` (#667)
- update the getID3 library to version 1.9.16
- fix a bug on music library path changing when the previous library root is no longer present
- slightly tweak the width threshold between the "desktop" and "tablet" layouts
- declare support for Nextcloud 16
- declare support for ownCloud 10 (i.e. all 10.x versions instead of the former 10.1; oC is moving to semantic versioning)

## 0.9.2 - 2018-09-16
- fix settings link not showing up on Firefox in mobile layout with NC14+
- fix the controls bar position when collapsible navigation pane is open in mobile layout
- fix positioning of the "Update scanning results" button on mobile layout
- fix UI sometimes getting into inconsistent state when library path changed (invalid SQL exception on back-end)
- fix performance issue on file deletion when the user has huge amount of image files (#664)
- performance/scalability: do not search cover art for *all* users after scanning library of one user
- show load indicator on settings view while library path change ongoing
- do not scan files with MIME type 'application/ogg'

## 0.9.1 - 2018-09-09
- fix the application layout being totally messed up on Nextcloud 14-beta and later (#660)
- fix huge library not loading with some distributions of SQLite (#239)
- show also the username used on the Ampache API when generating a password (with LDAP, this may differ from the login name) (#60)
- increase the maximum allowed clock deviation on Ampache handshake from 100 to 600 seconds (#60)
- tweak the Settings view to work better on extra narrow screens
- declare compatibility with Nextcloud 15
- replace some deprecated core API calls with the more modern alternatives
- remove a couple of forgotten development-time log prints

## 0.9.0 - 2018-08-09
- enable playing more than one track from Files: provide next/previous buttons and jump automatically to next file after reaching the end of file (#641)
- show alphabet navigation also in the "All tracks" view
- provide "All tracks" as a playlist on the Ampache API
- when playback is started by clicking a track, it now continues also after current album and artist (affect also the scope of shuffle and repeat) (#350)
- when the play scope is limited to one album/artist, show a slight highlight around the scope
- preserve the play queue history when clicking on a track to play it, unless the play scope changes
- more visible highlight on the target playlist when dragging a track to a playlist
- make the client-side caching of cover images work already on the first time the images are loaded (instead of the second)
- do not spam HTTP requests if next/previous track button is clicked repeatedly and rapidly
- add a few keyboard shortcuts: play/pause [space], previous track [left arrow], next track [right arrow]
- focus the text field automatically when clicking to create/rename a playlist
- prevent creating/renaming playlists with empty name
- small updates on translations (although they are still very much incomplete)
- fix library being erased when library path "changed" from default (empty) value to '/'
- fix track name shown only as "Loading..." when playing a public share on ownCloud 8.2
- fix play/pause icon in front of track name moving with a delay when track changed
- fix highlighting of the current view vanishing when dragging a track to a playlist
- fix a layout bug in the details pane with narrow screen on ownCloud (not reproducible on Nextcloud)
- fix a small UI jitter on playlist create/rename forms
- fix embedded music player bar being a bit too narrow when playing public shares
- support optional `artist` filter in the Shiva API endpoint `/api/albums` (#46)
- remove the previously deprecated endpoint `/api/file/{fileId}/webdav`
- lots of internal refactoring

## 0.8.0 - 2018-07-21
- track details pane (#656)
- show a confirmation dialog before deleting a playlist
- changed icons of "Repeat" and "Rename playlist" buttons
- cache the collection.json to file instead of DB, preventing problems with large collections on certain DB configurations (#588)
- cache collection.json also on the client-side (as discussed on #588)
- fix a layout issue in albums view which could cause empty "album slots" to show between albums (introduced in v0.7.0)
- fix unable to play tracks on some untypical proxy configurations (#650)
- fix a layout issue on album titles in the mobile view
- fix the controls bar being broken on NC13 with all versions of IE and other older browsers
- fix play icon not being shown on track which was collapsed when the playback started (regression from v0.5.6)
- fix a typo in the "reset library" confirmation dialog (#655)
- fix controls bar track info not being clickable on mobile landscape layout
- follow-up fix for the Firefox scrollbar issue (#631)

## 0.7.0 - 2018-06-17
- improved performance of the "All tracks" view, especially with large collections (#647)
- show the track and artist names extracted from metadata also when playing a public share (#616)
- remove tracks from the index on collection path change only when strictly necessary (#627)
- allow resetting the music index from the settings view (#302)
- fix the "Show all XX tracks"/"Show less" links on all versions of IE (regression from v0.5.6)
- add "About" section (along with the new logotype by @nunojesus) to the Settings view
- small UI fixes and tweaks
- use "nodebug" version of soundmanager2 lib also in Files and Share views
- prevent log warning spamming with PHP 7.2 (#642)
- support for ownCloud 10.1
- updated the getid3 library to version 1.9.15
- internal refactoring: enforced ownCloud coding standard 1.0.1 (#643) etc.

## 0.6.1 - 2018-05-13
- fix not being able to play file types other than mp3 and flac (regression from 0.6.0)

## 0.6.0 - 2018-05-10
- settings view moved from the "personal settings" to the Music app itself (#625 by @greku)
- improve performance of updateFolderCover on large installations (#637 by @jmdeboer-surfsara)
- disable Music app's embedded player for individual shared files on NC13+ (#630)
- remove all data of the user when user deleted
- fix navigation pane closing on mobile layout when creating new playlist (#626 by @greku)
- fix occasional unhandled exceptions on NC13+ (e.g. #636)
- fix shared file view being broken on Chrome for Android if both Music and Audio Player are enabled (#629)
- fix horizontal scroll bar appearing on NC13 with Firefox (#631)
- fix crash on first login of a new user when the default files contain any audio files (#638)

## 0.5.6 - 2018-02-17
- front-end optimizations (#614, #615 by @Biont)
- optimize number of DB queries when building collection.json (may help on #601)
- check validity of the passed user names in the occ command line tool (#602)
- support for `--group` option in all occ commands (#613 by @greku)
- remote playlist support on the Ampache API (#611)
- support for actions `artist` and `album` on the Ampache API (used at least by Power Ampache)
- support for Nextcloud 14
- fix controls pane layout on Nextcloud 13 and 14 (#617)

## 0.5.5 - 2017-12-10
- fix Content Security Policy error being printed to browser console when starting playback on Chrome (#498)
- fix playing files which have '%' character followed by two digits in their name by using URL encoding (#299)
- enable running the background cleanup task on request with the `occ music:cleanup` command
- fix range requests on the endpoint `/api/file/{fileId}`
- fix files music player not being used on Chromium installations with no audio codecs (regression introduced in v0.5.4)
- remove any "broken" track entries in the background cleanup task (#588)
- workaround for not being able to play mp3 file on the "shared file" page if the file has no embedded cover (#596)
- enable client-side caching of the album cover art
- add option `--remove-obsolete` to the command `occ music:scan` to remove any inaccessible previously scanned files (#567)

## 0.5.4 - 2017-11-10
- workaround for an authentication issue on Ampache Plugin for Rhythmbox (#590)
- improved the cover image handling in the files music player (#582)
- launch the files music player only for those audio files supported in the current browser (#591)
- enable the localization of the app (many UI strings are still unlocalized, though) (#592)
- fix previously created playlists disappearing each time the app is loaded (regression introduced in v0.5.3)
- when creating collection.json, skip tracks with DB problems insted of failing the whole process (related to #588)

## 0.5.3 - 2017-10-15
- workaround for buffer progress bug on Firefox (#587)
- downscale cover images on the server to save bandwidth (#589)
- fix track being removed from all users when unshared from a group (#581)
- fix broken navigation bar layout on recent development versions of Nextcloud 13
- slightly improve the performance of the /collection and /scanstate endpoints with large music libraries (related to #588)
- fix /collection endpoint occasionally failing with code 500 when called during scanning
- do not show the loading indicator indefinitely if getting the /collection endpoint fails for any reason

## 0.5.2 - 2017-09-26
- possibility to start playing a playlist without opening the respective view
- clicking the playing track again now pauses the playback
- fix current view not being highlighted correctly on the sidebar menu on Nextcloud
- fix overly long track names sometimes not being shown correctly in the player bar
- properly react to moving/renaming audio or cover image file (#417)
- show Music app icon in the personal settings on Nextcloud
- other small UI tweaks

## 0.5.1 - 2017-09-08
- fixed Music app breaking the authentication page of password protected public shares (regression in 0.5.0)

## 0.5.0 - 2017-09-04
- added a small stand-alone music player which plays audio files in the Files and Share views

## 0.4.4 - 2017-08-17
- fixed scanning breaking if file metadata contains invalid UTF-8 characters (#576)
- updated aurora.js and its plugins to latest versions; this fixes some (but not all) playback problems on Chromium
- update database automatically when audio file uploaded to a publicly shared folder
- Ampache and Shiva APIs: return album tracks sorted by track number
- Shiva API: track now has field 'ordinal' instead of 'number' to follow the API specification (#453)
- the release package in github is now unsigned (v0.4.3 was signed with ownCloud certificate)

## 0.4.3 - 2017-08-01
- fixed app not loading with too large cover images (regression in 0.4.2)
- fixed metadata parsing issue: extracting album artist, track #, disc #, or year could fail on some files (regression in 0.4.2)

## 0.4.2 - 2017-07-31
- fixed updating from versions <= 0.3.13 (#571)
- fixed loading of embedded cover images via Ampache API
- fixed compatibility with recent Nextcloud 13 development versions
- use bigint when storing file_ids to database (#569)
- improved performance of cover image loading (#570)

## 0.4.1 - 2017-07-24
- dropped support for ownCloud 8.1

## 0.4.0 - 2017-07-17
- support for playlists (#555)
- improved performance and scalability (#564)
- prevent database corruption on simultaneous updates (#322, #480)
- fixed handling of local shares and improved the performance of the related hooks (#566)
- made the album and artist grouping case insensitive (#316)
- support for new file formats (when browser support available): WAV, M4A, M4B
- seeking supported also for FLAC files (when browser support available)
- fixed parsing of album artist tag from FLAC files
- fixed some charset issues by updating getid3 library to version 1.9.14 (#410)
- allowed one album to cover several release years (#279, #307)
- support ISO-formatted date tags (#430)
- improved the heuristics of deducing track number and name from file name when not given in tags
- refresh the UI automatically when the scanning process is done
- show the currently playing track in the window title
- lot of internal refactoring and small UI fixes and tweaks

## 0.3.13 - 2016-12-20
- refactored scanner.php
- improved album art extraction performance
- improved metadata extraction (use custom patched getID3, having track and album artist as fallback for each other)
- improved behaviour of scroll links
- fixed bug in AlbumMapper.findAlbumCover
- fixed album deletion
- fixed layout (new music availability, scanning, overlapping scrollbar, autoscrolling to album, album-art resizing on window resize, mobile style fixes, viewBox to app icon)
- fixed inclusion of getID3 when required #551 - thanks @apotek
- fixed usage of deprecated APIs
- fixed playback order when playing album
- fixed disc number extraction
- fixed UI glitches #522
- improved metadata display (view track artist if different from album artist)
- add support for HTTP Range requests allowing Ampache API clients to seek files #528
- improved playback by preffering SoundManager2 and falling back to Aurora.js if the former is not available
- fixed seeking during playback
- fixed file delete hook
- fixed volume control and improved its layout
- dropped support for ownCloud <= 8.0

## 0.3.12 - 2016-07-26
- provide bitrate and mime information in the Ampache API's song endpoint
- expose Ampache token generation API
- fixed Konrad Mosoń's profile url
- add new design for empty content
- add support for disc numbers
- add support for albumartist tag
- improved album art extraction
- fixed blank page on asset.pipeline enable
- frontend optimizations

## 0.3.11 - 2016-04-11
- fix syntax in info.xml
- fix issues when mail app is activated

## 0.3.10 - 2016-03-09
- general fixes for ownCloud 9.0.0
- fix missing request token for WebDAV requests - #474
- fix bug for not translated strings - #473

## 0.3.9 - 2016-03-07
- increase robustness against removed files - thanks @jerome-pouiller - #452
- update underscore
- drop stable6 supports
- fix blank page in ownCloud 9.0
- bring in backbone
- fix some layout issues
- better SQL for the cleanup code

## 0.3.8 - 2015-10-27
- support for ogg (#416 by pellaeon)
- fix issue with not existing prepareQuery (#411 by roha4000)
- fix failures after upload to public link shares (#436, #387)
- fix for Angular variable names (#425 by DavidPrevot)

## 0.3.7 - 2015-07-16
- fix issue with SQL statement in background job for MySQL (#372)
- run integration tests on travis

## 0.3.6 - 2015-07-09
- works now with ownCloud 7, 8, 8.1 and master
- fix twice opened file chooser in personal settings (#344)
- move to core shipped AppFramework (ownCloud 7.0.0+) (#390)
- proper cleanup SQL statement (#347 by butonic)
- automated tests for the Ampache API (#380)
- automated tests against stable7+ versions of core and all DBs on travis (391)

## 0.3.5 - 2015-02-16
- reset-database command
- set length of a track in the database and expose via Ampache
- fix album count in Ampache API
- expose Album cover via (inofficial) Ampache API
- ownCloud 8 compatibility
- user interaction needed to start background scan and reload the music view

## 0.3.4 - 2014-09-04
Thanks to Volkan Geezer (@wakeup) and Yu-De (@pellaeon)

- switch to aurora.js for JavaScript decoding of music files (ability to
  support more codecs) - currently just mp3 and flac - thanks to @pellaeon

- make batch rescan incremental
- make userFolder optional - get rid of wrong type of parameter error logs
- add check for natural numbers above 0 for track number
- add --debug switch to music:scan command to list memory usage of each step
- fix for not working apps/music/#/file/ID routes
- fix broken expand track list for albums
- use WebDAV for file access as it provides a better stability and functionality

Internal
- drop unsupported calls to ownCloud private APIs
- use dependency injection for scan command

Known issues
- mp3 seeking isn't working

## 0.3.3 - 2014-08-12
Thanks to Dan Mac (@danmac-uk)

- Fix undefined index COUNT(*)
  * add a name to the COUNT(*) statement
  * should work with MySQL, PostgreSQL, MSSQL, Oracle, SQLite
- Fix Ampache URL confusion
  * remove of '/server/xml.server.php'
  * add note

## 0.3.2 - 2014-08-12
- RESTful playlist API (thanks @wakeup - Volkan Gezer)
- Updated libraries (AngularJS 1.2.21, angular-gettext 1.0.0, drop jQuery)
- refactor cleanup method (reduce injected dependencies)
  * move clean up task to separate helper class
- drop stable5 fixes as they are unused
- verified support for ownCloud 6
- prepare use of sidebar and mobile responsive sidebar
- migrate to ownCloud 7 core CSS
- add ID to ampache session - fixes issues with the DB mapper (#213)
- make user folder injectable into rescan method

## 0.3.0 - 2014-08-06
General
- disable share hook, because it delayed the sharing action a lot
- add index for cover_file_id in albums
- playstate is now represented in the URL
- change scan count from 50 to 20 - should fix #172, fix #212
- remove album cover search on remove of album cover (should speedup deletions)

ownCloud 7 related
- adjusted design to ownCloud 7 (loading spinner, no shadow on icon)
- fixes to get it work with ownCloud 7 (especially public shares and Ampache API)
- fixes several typos and minor issues

Internal
- migration from separate AppFramework to core provided AppFramework
- JavaScript 3rdparty library management is now handled by bower
- getID3 is update to v1.9.8, which fixes a memory leak - see #212
- change handling of routes in a proper way as preparation for playlist functionality - GSoC project by @wakeup
- improved documentation of PHP classes
- licence header cleanup (shrinked)
- respect the user ID on update (scanner)

Known issues
- listen to shared files doesn't work - this is a issue of the ownCloud core and will hopefully be fixed in 7.0.2 and 6.0.5 - owncloud/core#10173

## 0.2 - 2014-04-30
- handle shared files properly (also fixes mounted storage)
- albums with same name but different artists or years are now different albums by @leizh
- cover and track download moved to music app from files app
- stop scan loop if processed count is greater than total count
- close the session to enable parallel requests to be processed
- add notification for skipped tracks
- the music in the database is now restricted to the user specified path

- update Sounmanager to V2.97a.20131201

- fix mobile styles by @jbtbnl and @wakeup
- fix left alignment issues of artist name and tracks on mobile
- remove unused code

Known bugs:
- seeking in Chromium doesn't work

## 0.1.9.1-rc - 2014-03-26
- navigation bar on left is now thinned out for small screen sizes #185

- fix empty music app #184
- fix broken play for public shared music files #186
- fix rendering issues in IE10+ #188
- fix broken album request in Ampache API #189

## 0.1.9-rc - 2014-03-25
- allow public share music playback #124
  * start/stop implementation, filelist is playlist, no repeat
- mobile styles for phone & tablet
- search provider for artist, album & track
- command to rescan the music files from ownCloud console.php
  * Thanks to @leizh
  * music:scan
  * all users or a specific user
- improved performance on loading of artists (a lot less SQL statements)
- seek in progressbar
- redirect from music file in files app to music app (autoplay) on click
- album art priority (cover, albumart, front, folder, others)
- step by step scanning (50 on each step)
  * display of scanning progress
- Chrome now uses HTML5 audio instead of flash fallback

- Ampache API (unstable)
  * security
    + user can generate passwords to use with the Ampache API
    + ability to revoke those passwords
  * new DB tables:
    + ampache_sessions - session tokens
    + ampache_users - generated passwords
  * Ampache API (ADD and UPDATE parameters are unsupported yet):
    + handshake
    + ping
    + artists
    + artist_albums
    + album_songs
    + albums
    + artist_songs (also supports offset & limit)
    + songs
    + song
    + search_song
  * delivery of music file with ampache token
  * middleware to authenticate user with ampache token

- fix cover detection - double to single quotes - fixes #134
- fix integrity constraint violation for shared files - fix #127
- shorten index names for oracle (max 30 chars)
- fix SQL statements
- fix error while fileUpdated hook handling - fix #154
- Unknown artists, albums & titles now localizable
  * allow and use NULL instead of fixed artist or album name
  * add localized string to represent these albums and artists
  * migration: convert existing 'owncloud unnknown ...' placeholders to NULL

Internal
- new URL generation inside the Javascript
- DB Mapper & Entities:
  * Album added attributes: trackCount, artist (both not filled by default)
  * Album added methods: getNameString (returns an translated string for unknown artists)
  * AlbumMapper added methods: count, countByArtist, findAllByName
  * Artist added attributes: trackCount, albumCount (both not filled by default)
  * Artist added methods: getNameString (returns an translated string for unknown artists)
  * ArtistMapper added methods: count, findAllByName
  * Track added attributes: fileSize
  * TrackMapper: count methods now return actual count and not an array with 'COUNT(*)'
  * TrackMapper added methods: count, findAllByName, findAllByNameRecursive
  * add limit ScanStatus SQL
- Tests:
  * add L10nStub to properly mock the L10n class of ownCloud core
  * push test coverage to 100%
- Build:
  * add Makefile command to do PHP unit tests and create the test coverage
  * exclude external PHP files from test coverage
- Core API:
  * add call to register components to personal settings page
  * fix typos
- merged l10n extraction to upstream - removed patchfiles
- minimalized travis-ci footprint
- CSRF token used for restangular queries
- AngularJS 1.2.14
- Underscore 1.6.0

## 0.1.7-beta - 2013-12-21
Merry Christmas release
- increase polling interval for whileplaying - fixes #131
- fix play icon bug in IE - SVGs are replaced by PNGs in IE - fixes #126
- FileAction for music files
  * add api call to resolve track by fileid
  * AngularJS route 'file/:id'
  * PlayerController.playFile(id)
  * load fileactions script on every page to register FileAction
- added input validation for year - fixes some crashes of the scanner
- fix OC5 issues with MDB2 and Oracle DB
- fix database restrictions for oracle (#120, #119)
- fix l10n-compile for non-latin languages - remove jslint warning
- fix [[ to {{ transition in translations
- removes second scrollbar
- fix angular scope issues and css issues

Internal
- fix CSS style - remove comma
- RestAngular 1.2.1
- fix some global variables
- fix l10n issues
- whitespace fix in SQL statement
- fix some leftovers of the OCA\AppFramework -> OCA\Music\AppFramework change
- $.placeholder() was renamed to $.imageplaceholder() in master
- Play indicator beside the track in the album view
- move MainController to top, so every children can use it's variables
- make alphabet navigation more dynamic
- add l10n for PHP

Known bugs:
- doesn't play mp3/ogg in IE8

## 0.1.6-alpha - 2013-10-05
- L10n support
- OGG metadata extraction - just works for local files - not for external ones refs #73
- proper deletion of database cache
- metadata extraction fix - disable 2GB filesize check in getID3
- use Flash fallback in Chrome - drawback: just MP3 playback - there is a notification if this is the case
- fix album art/placeholder race condition
- no more appframework dependency
- Flash unblock element
- alphabet navigation resizes with window height
- hide alphabet navigation if there is no music
- proper IE8 PNGs
- fulltree for artists only return tracks of the artist - #99
- scanner uses the shortest artist name if multiple artists are detected
- scrollbar fix - was overlaped by player bar #102

Known bugs:
- in IE 9 and 10 the play icons haven't the correct width/height (fixed in v0.1.7-beta)

## 0.1.5-alpha - 2013-09-24
- use images in album folders as album art
  * first uploaded image to a folder is used as album art
  * addition and deletion of covers is detected
- alphabet navigation bar to the left
  * highlight available letters (of the artists)
- use flash 8 for fallback player
- fix ogg playback
- play the clicked song of an album and not the first song of the album - fixes #83
- limit metadatascan to audio files
- Adds clean up background job
  * find covers for albums without cover
  * remove tracks without files, albums without tracks and artists without albums and tracks
- AngularJS 1.0.8
- Various fixes and improvements - especially PostgreSQL
  * various fixes, also for PostgreSQL
  * cast number to int
  * use correct sql statement for checking for albums
  * unit test for case when album is null
  * move casting to appframework entity
  * remove blank lines

Known bugs:
- does not scroll perfect
- non-dynamic creating of the navigation bar

## 0.1.4-alpha - 2013-09-05
- show track number in track list
- fix icon glitches in Firefox
- show playing status icon (fixes #82)
- previous button (fixes #72)
- shuffle/repeat button (fixes #77)
- correct sorting order for playlist
- show loading state
- sort albums by year
- visualize loading state
- make scanner more robust and fix PHP errors
- disable execution time for rescan
- realign playerbar content and adding whitespace (ref #80)

Known bugs:
- clicking a song the first song of the album is played instead of the actual clicked song

## 0.1.3-alpha - 2013-09-04
- cliched icons (fixes #70)
- database is cleaned after update to this version
- first fixes for undetected metadata (extracts track number and title from filename)
- fix album without year issue (albums were duplicated)
- sort tracks by tracknumber and show them if available

## 0.1.2-alpha - 2013-09-02
stable5 fixes
- loading of getid3
- CSS

## 0.1.1-alpha - 2013-08-29
Fixes, clean-ups and logging from JS to the backend
- log API (for javascript logging to backend)
- fix empty artists (backend)
- album view fixed
- log errors in frontend to backend
- fix playback for artist
- remove minify directive
- reset played songs and current song for playlist

## 0.1-alpha - 2013-08-29
First release of the new music app
- useable with OC5+
- shiva API
- metadata extraction for artist, album and track
- single page frontend
- multimedia playback in all browsers trough HTML5 and flash fallback
- testing of the backend code

Known bugs:
- shuffle, repeat and previous button are out of functionality
- non-high-resolution icons in IE8
- no Ampache support
- slow for large music collections
- tracks without artist or album are not listed in the frontend (but already in the database)
