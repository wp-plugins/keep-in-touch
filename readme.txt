=== Plugin Name ===
Contributors: racanu
Tags: digest, contact, subscribe, newsletter
Requires at least: 4.1
Tested up to: 4.1
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows visitors to register for receiving a weekly digest of the newly added posts.

== Description ==

Offers a widget that enables visitors to subscribe for receiving by email a weekly digest
of newly added posts. This keeps the users closer to your site and doesn't force them to
"poll" for updates.

The widget only shows an input field for the email address and a 'Subscribe' button.
Then a short procedure is started to confirm the subscription:

* No validation is performed on the entered email address.

* After clicking on 'Subscribe', the user is presented a virtual page in which she is
asked to re-enter the email address and click again on a 'Subscribe' button.
This is a simple measure against robots placing subscription requests.

* Again, no validation is performed on the entered email address except it is matched with
the one previously entered. In case of a match, an email message is sent to the user
containing a link with a unique code, on which the user has to click to confirm her
request. The user is informed by this in a new virtual page.

* After the user clicks on the link in the email, the email address is considered
subscribed and it will receive weekly messages containing a digest of new posts from the
previous week, with direct links to the respective pages. In each message there is also
a link to allow the user to unsubscribe.

Unsubscribing follows a similar pattern in which the user will first receive an email to
confirm the request, with a link containing a unique code.

To keep it simple, no check is performed as to whether a user is already registered or
not. Any request can be performed in any phase of the process.

Weekly emails are sent using wp_mail(), on a configurable weekday at a configurable time.
Even when no posts have been added, the user will still receive and email to inform her of
that.

The emails will all contain the heading image of the theme at the top.

Until support is provided for selecting subscriptions, all subscribers will be subscribed
for the weekly digest and the newsletter.

**Warning!**

Only tested with the permalink format set as
"/index.php/%year%/%monthnum%/%day%/%postname%/" on a Windows server. Don't know if it
works with other formats, although the implementation is quite generic.

**Options page**

The plugin registers an option page that enables admins to:

* configure the weekday and time-of-day when the digests are sent
* (re)send the digest(s) to subscribers or to given email addresses
* send a newsletter to subscribers or to given email addresses

**Wishlist**

Some features to be added in some future version:

* Daily and maybe monthly digest.
* Timed removal of unconfirmed requests.
* Configurable messages and format(s) of digest(s)
* Support for selecting subscription options (digest, newsletter)

== Installation ==

1. Download the zip file, and use WordPress' plugin installation page in the dashboard
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place the shortcode(s) you need in your post

== Changelog ==

= 1.0.1 =
* Fixed escaped quotes in urls to media in newsletter

= 1.0.2 =
* Added styles to digest messages to show the list as a table

= 1.0.1 =
* Small readme change

= 1.0.0 =
* First release
