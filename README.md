=== Contact Form 7 email verification ===
Contributors: magician11, bcworkz
Donate link: http://golightlyplus.com/code/
Tags: email, 
Requires at least: 3.6.1
Tested up to: 3.8
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Extends Contact Form 7 to allow for email addresses to be verified.

== Description ==

This "Contact Form 7 email verification" plugin extends the "Contact Form 7" plugin to automatically verify email addresses for all form submissions.

Once this plugin is activated, on a form submission..

1. The form submission does not get sent but instead saved temporarily.
2. The sender gets sent an email address with a link to click to confirm their email address.
3. Once the link is clicked, the user gets sent back to the website with the form on, and
4. the previously submitted form gets sent as per usual for CF7 functionality.

This functionality is particularly useful for legalese reasons, by verifying a person's identity (the ownership of their email address). This plugin was created as I wanted to verify that the form on my terms and conditions page was being filled out by the actual person.

For those interested, you can check out the code on GitHub [here](https://github.com/magician11/wpcf7-email-verification "WP CF7 email verification code on GitHub").

==TODOs==

There are things to still be done..

1. Attachments are not saved. i.e. This verification plugin currently does not support attachments for CF7 forms

== Installation ==

Just install and activate as per usual.

There are no settings in the current stable version to change.

== Frequently Asked Questions ==

= This is great, but it would be even better if it ... =

I would love to hear how you would like to improve it. Let me know [here](http://golightlyplus.com/contact-andrew/ "Contact Andrew").

= I've found a bug with it. Who can I tell? =

Awesome! As above, just [get in touch](http://golightlyplus.com/contact-andrew/ "Contact Andrew"). Or submit a message on the Support forum.

== Screenshots ==

Nothing to show here.

== Changelog ==

= 0.22 =
* Fixed bug on double calling the plugin
* Info message displayed now on clicking the verification link

= 0.11 =
* The first stable release.