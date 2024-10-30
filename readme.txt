=== IvyCat Announcements ===
Contributors: dgilfoy, ivycat, sewmyheadon
Donate link: http://www.ivycat.com/contribute/
Tags: announcements, bulletins, role based
Requires at least: 3.1
Tested up to: 3.4.1
Stable tag: trunk
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

==Short Description ==

Custom Post type Announcements with user levels and date checking.

==Description==
A plugin that displays annoucements on pages and posts. They can be scheduled so that they are only displayed between specified dates/times. Utilizes custom post types. Requires WordPress 3.1 for use of multiple taxonomy querying. 

== Notes ==

Plugin is depending upon theme styling.  Version 1.0 of this plugin does not contain native styles.  If you are curious as to the reasoning behind this, check out:  

http://nimbu.in/p/wordcampseattle/

This is a minimal plugin, function over form.  If you would like to extend it, or would like us to extend it in later versions, feel free to contact us at admins@ivycat.com.  We do custom plugins as well as upgrades and features for existing plugins.

== Installation ==

1. Upload the entire ivycat-announcements directory to your plugins folder 
2. Click Install Plugin in your WordPress plugin page
3. ??? Profit ???

== Usage ==

You can use the functions to display your announcements using shortcode or directly in your theme files.

Shortcode 

[ica_announcement]:  displays all announcements for users in pertinant groups
[ica_announcement id='1']: displays announcement with post id of 1 ** you can entere ANY number here ;) **
[ica_announcement group="group-slug"]: Displays announcement in the group specified by the slug.

Direct Theme PHP.

You can access the announcement functions by using the $ica_announce object as such:

$ica_announce->display_announcements();
$ica_announce->get_announcement($id);
$ica_announce->get_announcements_by_group($group);

== Screenshots ==

Nothing yet.

== Frequently Asked Questions ==

= The Admin menu says "Bulletins" and not Announcements. =

Announcements didn't fit so well.  Rather than change this, we used a word which means the same thing. Sorry for the confusion!

== Changelog ==

= 1.03 =
* Updated plugin header file, readme.txt, removed read.me

= 1.02 =
* Updated Text Files

== Upgrade Notice ==

= 1.03 =
* Updated plugin header file, readme.txt, removed read.me - not a critical update.

== Road Map ==

1. Add page / post specific announcements.
2. Add Custom User group support.
3. Hi Opal!
