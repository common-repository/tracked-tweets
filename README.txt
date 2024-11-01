=== Tracked Tweets ===
Contributors: JMcIntyre
Tags: twitter, google
Requires at least: 2.7
Tested up to: 2.8
Stable tag: 0.2.9
Donate Link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4474012

Posts are added to your twitter account. Tweet can be formatted. URL's are shortened using Tinyurl or bit.ly, and Google Analytics tracking is added.

== Description ==

* Define format of tweet, with control over where variables are inserted
* Track clicks to your blog using Google Analytics. The Source and Medium fields can be defined, Campaign name is set to the post title
* URL is shortened via tinyurl, tr.im, bit.ly, or the RevCanonical plugin
* If title is too long, it is shortened so the tweet can still be sent
* Basic hashtag support has been implemented
* Ability to define whether posts are tweeted on a per post basis (using a checkbox)

For support and queries, please leave a comment at <a href="http://www.jackmcintyre.net/projects/tracked-tweets/?utm_source=wordpress&utm_medium=plugin&utm_campaign=tracked-tweets">www.jackmcintyre.net/projects/tracked-tweets/</a>

== Screenshots ==

1. Main Options Page
2. New Post screen showing Tracked Tweets section

== Roadmap ==

* Ability to select hashtag from recently used
* Ability to manually tweet a post
* Ability for user to define custom URL shortener APIs

== Known Issues==

Testing has been limited. I expect there are bugs, particularly if you have long post titles or a complex Tweet layout

* Plugin has issues using 3rd party blogging tools (blogdesk)
* Issue with Scheduled posts not being tweeted

== Installation ==

1. Upload the tracked-tweets folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Update options in Settings -> Tracked Tweets

== Version History ==

= v 0.2.9 =

* Added support for bit.ly, tr.im and RevCanonical Plugin

= v 0.2.8 =

* Small code update to use wordpress functionality to set campaign title (rather than my own code)

= v 0.2.7 =

* Added settings link to plugin management page
* Added warning when settings are not set
* Choose between curl and file_get_contents depending on security
* Added Error message when server security will break plugin
* Fixed "Headers Already Sent" Issue

= v 0.2.6 =

* Fixed svn commit issue

= v 0.2.5 =

* Added title shortening to fit into 140 character limit
* Fixed bug - tweets now work if a post was a draft
* Added default setting for "tweet this post" checkbox
* Password does not have to be entered each time options are updated

= v 0.2.4 =

* Donate link opens in new window
* Added CSS, removed inline styles
* Cleaned code
* Fixed issue that was preventing some users from posting urls

= v 0.2.3 =

* Added donate link
* Added "Tweet this" checkbox

= v 0.2.1 =
* Updated some descriptive text within plugin options screen
