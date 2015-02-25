=== BP Local Avatars ===
Contributors: shanebp
Donate link: http://www.philopress.com/donate/
Tags: BuddyPress, members, avatars, gravatars
Author URI: http://philopress.com/contact/
Plugin URI: http://philopress.com/products/
Requires at least: WP 4.0, BP 2.0
Tested up to: WP 4.1, BP 2.2.1
Stable tag: 1.1
License: GPLv2 or later

A BuddyPress plugin that creates Gravatar avatars for any user without one, and stores them locally.

== Description ==

BP Local Avatars is a BuddyPress plugin.

Do you have members on your BuddyPress site who do not have an Avatar?
And you do not want to show the generic default avatar?
Or maybe you do not want each page view to include a lot of calls to gravatar.com to load avatars?

* This plugin will create a Gravatar Identicon avatar, thumb and full versions, for any user who does not already have an Avatar, and save it locally.
* Supports user creation, user registration, user login, and Bulk Generation.
* Uses the existing BuddyPress avatar directory structure.
* Conforms to the defined sizes for BuddyPress thumb and full avatars.
* Users can still upload an avatar via their profile.


Usage:

1. Provides an option in wp-admin under:
Settings -> Discussion > Default Avatar > BuddyPress Identicon (Generated and Stored Locally). 

2. Select and Save. Otherwise this plugin will not do anything.

3. After saving, you will see a link to 'Bulk Generate' avatars for all users who do not have a local avatar. If a user already has their own Gravatar, it will save it locally.


For more BuddyPress plugins, please visit http://www.philopress.com/


== Installation ==

1. Unzip and then upload the 'bp-local-avatars' folder to the '/wp-content/plugins/' directory

2. Activate the plugin through the 'Plugins' menu in WordPress

3. Go to Settings -> Discussion and scroll down to 'Default Avatar'. Select and Save the option called 'BuddyPress Identicon (Generated and Stored Locally)'.


== Frequently Asked Questions ==

= Does it support Monsterid and Wavatar? =
 Yes, but it defaults to Identicon.
 You can change the type of avatar created by adjusting the calls in 'public function create()'
 
= Are there any server requirements? =
 Just this: requires that 'allow_url_fopen' is set to true.  Which is the default setting on most servers. 


== Screenshots ==
1. Shows the new Default Avatar option and Bulk Generation link in Settings > Discussion > Avatars


== Changelog ==

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.0 =


