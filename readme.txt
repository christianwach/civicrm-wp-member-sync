=== CiviCRM WordPress Member Sync ===
Contributors: needle, cuny-academic-commons
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PZSKM8T5ZP3SC
Tags: civicrm, member, membership, sync
Requires at least: 3.9
Tested up to: 4.8
Stable tag: 0.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Keep WordPress users in sync with CiviCRM memberships by granting either a role or capabilities to users with that membership.



== Description ==

CiviCRM WordPress Member Sync keeps a WordPress user in sync with a CiviCRM membership by granting either a role or capabilities to a WordPress user who has that membership.

This enables you to have, among other things, members-only content on your website that is only accessible to current members as defined by the membership types and status rules that you set up in CiviCRM. This plugin is compatible with both "[Members](https://wordpress.org/plugins/members/)" and "[Groups](https://wordpress.org/plugins/groups/)" for managing members-only content in WordPress. See the Installation section for details.

### Requirements

This plugin requires a minimum of *WordPress 3.9* and *CiviCRM 4.6*. It is compatible with the [Members](https://wordpress.org/plugins/members/) and [Groups](https://wordpress.org/plugins/groups/) plugins. Please refer to the Installation page for configuration instructions as well as for how to use this plugin with versions of CiviCRM prior to 4.6.

### Plugin Development

This plugin is in active development. For feature requests and bug reports (or if you're a plugin author and want to contribute) please visit the plugin's [GitHub repository](https://github.com/christianwach/civicrm-wp-member-sync).

### Shouts Out To...

This plugin builds on the [work](https://github.com/tadpolecc/civi_member_sync) done by [Tadpole Collective](https://tadpole.cc) and originally developed by [Jag Kandasamy](http://www.orangecreative.net). Kudos.



== Installation ==

1. Extract the plugin archive
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Make sure CiviCRM is activated and properly configured
1. Activate the plugin through the 'Plugins' menu in WordPress

This plugin requires a minimum of *WordPress 3.9* and *CiviCRM 4.6*. For versions of CiviCRM prior to 4.6, this plugin requires the corresponding branch of the [CiviCRM WordPress plugin](https://github.com/civicrm/civicrm-wordpress) plus the custom WordPress.php hook file from the [CiviCRM Hook Tester repo on GitHub](https://github.com/christianwach/civicrm-wp-hook-tester) so that it overrides the built-in CiviCRM file. Please refer to the each repo for further instructions.

The first thing to decide is whether you want CiviCRM WordPress Member Sync to synchronize CiviCRM Memberships to WordPress Roles or WordPress Capabilities. If, for example, you need your WordPress user roles to be independent of membership status, then choose Capabilities. The default synchronisation method is Capabilities, because WordPress has limited support for multiple roles.

<h4>Working with Capabilities</h4>

1. Visit the plugin's admin page at "Settings" --> "CiviCRM WordPress Member Sync".
1. Select "Capabilities" as the sync method
1. Click on "Add Association Rule" to create a rule. You will need to create a rule for every CiviCRM membership type you would like to synchronize. For every membership type, you will need to determine the CiviMember states that define the member as "current" thereby granting them the appropriate WordPress capabilities. It is most common to define "New", "Current" and "Grace" as current. Similarly, select which states represent the "expired" status thereby removing the WordPress capabilities from the user. It is most common to define "Expired", "Pending", "Cancelled" and "Deceased" as the expired status.
1. "Current Status" adds a "Membership Capability" to the WordPress user, while "Expired Status" removes the "Membership Capability" from the WordPress user. This capability will be of the form "civimember_ID", where "ID" is the numeric ID of the Membership Type. So, for Membership Type 2, the capability will be "civimember_2".
1. **Note:** If you have the "[Groups](https://wordpress.org/plugins/groups/)" plugin active, then all "civimember_ID" capabilities will be added to its custom capabilities as well as to the list of capabilities used to enforce read access on posts.
1. **Note:** If you have the "[Members](https://wordpress.org/plugins/members/)" plugin active, then the "restrict_content" capability will also be added.
1. An additional "Membership Status Capability" will also be added to the WordPress user that is tied to the status of their membership. This capability will be of the form "civimember_ID_NUM", where "ID" is the numeric ID of the Membership Type and "NUM" is the numeric ID of the "Membership Status". So, for Membership Type 2 with Membership Status 4, the capability will be "civimember_2_4".

<h4>Working with Roles</h4>

1. Visit the plugin's admin page at "Settings" --> "CiviCRM WordPress Member Sync".
1. Select "Roles" as the sync method
1. Click on "Add Association Rule" to create a rule. You will need to create a rule for every CiviCRM membership type you would like to synchronize. For every membership type, you will need to determine the CiviMember states that define the member as "current" thereby granting them the appropriate WordPress role. It is most common to define "New", "Current" and "Grace" as current. Similarly, select which states represent the "expired" status thereby removing the WordPress role from the user. It is most common to define "Expired", "Pending", "Cancelled" and "Deceased" as the expired status. With 'roles' as your synchronization method, also set the role to be assigned if the membership has expired in "Expiry Role". This is not needed when working with Capabilities.
1. It may sometimes be necessary to manually synchronize users. Click on the "Manually Synchronize" tab on the admin page to do so. You will want to use this when you initially configure this plugin to synchronize your existing users.

<h4>Manual Synchronize</h4>

It may sometimes be necessary to manually synchronize users. Click on the "Manually Synchronize" tab on the admin page to do so. You will want to use this when you initially configure this plugin to synchronize your existing users.

<h4>Test Test Test</h4>

**Note:** Be sure to test this plugin thoroughly before using it in a production environment. At minimum, you should log in as a test user to ensure you have been granted the appropriate role or capabilities when that user is given membership. Then take away the membership for the user in their CiviCRM record, log back in as the test user, and make sure you no longer have that role or those capabilities.



== Changelog ==

= 0.3.2 =

* Add filter for username prior to creation of WordPress user

= 0.3.1 =

* Fix bug in PHP 7

= 0.3 =

* Support multiple Memberships per Contact
* AJAX-driven Manual Sync admin page
* Fix sync when Membership is renewed

= 0.2.7 =

* Disambiguate network-activated and site-activated installs
* Fix courtesy links to settings pages

= 0.2.6 =

* Fixes scheduled sync

= 0.2.5 =

* Updates compatibility with Civi plugin

= 0.2.4 =

* Adds actions and filters at critical points

= 0.2.3 =

* Adds compatibility with Groups plugin
* Better uninstallation cleanup

= 0.2.2 =

Fixes sync all reference Props EventConsulting.

= 0.2.1 =

Fixes current WordPress role selector. Props EventConsulting.

= 0.2 =

First public release

= 0.1 =

Initial release
