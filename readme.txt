=== CiviCRM WordPress Member Sync ===
Contributors: needle, cuny-academic-commons
Donate link: https://www.paypal.me/interactivist
Tags: civicrm, member, membership, sync
Requires at least: 4.9
Tested up to: 5.7
Stable tag: 0.5.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Keep WordPress users in sync with CiviCRM memberships by granting either a role or capabilities to users with that membership.



== Description ==

CiviCRM WordPress Member Sync keeps a WordPress user in sync with a CiviCRM membership by granting either a role or capabilities to a WordPress user who has that membership.

This enables you to have, among other things, members-only content on your website that is only accessible to current members as defined by the membership types and status rules that you set up in this plugin's settings. CiviCRM WordPress Member Sync is compatible with both "[Members](https://wordpress.org/plugins/members/)" and "[Groups](https://wordpress.org/plugins/groups/)" for managing members-only content in WordPress. See the Installation section for details.

### Requirements

This plugin requires a minimum of *WordPress 4.4* and *CiviCRM 4.6*. It is compatible with the [Members](https://wordpress.org/plugins/members/) and [Groups](https://wordpress.org/plugins/groups/) plugins. Please refer to the Installation page for configuration instructions.

It is also strongly recommended that you also install [CiviCRM Admin Utilities](https://wordpress.org/plugins/civicrm-admin-utilities/) and have version 0.6.8 or greater activated. Make sure the checkbox labelled "Check this to fix the Contact 'soft delete' process" is checked so that Contacts that have been "soft deleted" have their corresponding WordPress User's status updated.

### Plugin Development

This plugin is in active development. For feature requests and bug reports (or if you're a plugin author and want to contribute) please visit the plugin's [GitHub repository](https://github.com/christianwach/civicrm-wp-member-sync).

### Shouts Out To...

This plugin builds on the [work](https://github.com/tadpolecc/civi_member_sync) done by [Tadpole Collective](https://tadpole.cc) and originally developed by [Jag Kandasamy](http://www.orangecreative.net). Kudos.



== Installation ==

1. Extract the plugin archive
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Make sure CiviCRM is activated and properly configured
1. Activate the plugin through the 'Plugins' menu in WordPress

The first thing to decide is whether you want CiviCRM WordPress Member Sync to synchronize CiviCRM Memberships to WordPress Roles or WordPress Capabilities. If, for example, you need your WordPress user roles to be independent of membership status, then choose Capabilities. The default synchronisation method is Capabilities, because WordPress has limited support for multiple roles.

<h4>New in version 0.4.2</h4>

If you have a large number of Membership Types, you can add the following code to your `wp-config.php` file:

`define( 'CIVI_WP_MEMBER_SYNC_MULTIPLE', true );`

This will allow you to select multiple Membership Types when adding an Association Rule. When saved, one Rule will be created for each of the selected Membership Types. This could save a lot of time in setting up your Association Rules. Thanks to [Foxpress Design](https://design.foxpress.io/) for funding this upgrade.

**Note:** Since version 0.4.4 it is not necessary to set this constant because this time-saving feature is now the default.

<h4>Working with Capabilities</h4>

1. Visit the plugin's admin page at "Settings" --> "CiviCRM WordPress Member Sync".
1. Select "Capabilities" as the sync method
1. Click on "Add Association Rule" to create a rule. You will need to create a rule for every CiviCRM membership type you would like to synchronize. For every membership type, you will need to determine the CiviMember states that define the member as "current" thereby granting them the appropriate WordPress capabilities. It is most common to define "New", "Current" and "Grace" as current. Similarly, select which states represent the "expired" status thereby removing the WordPress capabilities from the user. It is most common to define "Expired", "Pending", "Cancelled" and "Deceased" as the expired status.
1. "Current Status" adds a "Membership Capability" to the WordPress user, while "Expired Status" removes the "Membership Capability" from the WordPress user. This capability will be of the form "civimember_ID", where "ID" is the numeric ID of the Membership Type. So, for Membership Type 2, the capability will be "civimember_2".
1. **Note:** If you have the "[Groups](https://wordpress.org/plugins/groups/)" plugin active, then all "civimember_ID" capabilities will be added to its custom capabilities as well as to the list of capabilities used to enforce read access on posts. If you have Groups 2.8.0 or greater installed, then you will have the option to specify one or more "current" and "expired" groups to which users will be synced depending on whether their membership is "current" or "expired".
1. **Note:** If you have the "[Members](https://wordpress.org/plugins/members/)" plugin active, then the "restrict_content" capability will also be added.
1. **Note:** If you have [BuddyPress](https://wordpress.org/plugins/buddypress/) active, then you will have the option to specify one or more "current" and "expired" groups to which users will be synced depending on whether their membership is "current" or "expired".
1. An additional "Membership Status Capability" will also be added to the WordPress user that is tied to the status of their membership. This capability will be of the form "civimember_ID_NUM", where "ID" is the numeric ID of the Membership Type and "NUM" is the numeric ID of the "Membership Status". So, for Membership Type 2 with Membership Status 4, the capability will be "civimember_2_4".

<h4>Working with Roles</h4>

1. Visit the plugin's admin page at "Settings" --> "CiviCRM WordPress Member Sync".
1. Select "Roles" as the sync method
1. Click on "Add Association Rule" to create a rule. You will need to create a rule for every CiviCRM membership type you would like to synchronize. For every membership type, you will need to determine the CiviMember states that define the member as "current" thereby granting them the appropriate WordPress role. It is most common to define "New", "Current" and "Grace" as current. Similarly, select which states represent the "expired" status thereby removing the WordPress role from the user. It is most common to define "Expired", "Pending", "Cancelled" and "Deceased" as the expired status. With 'roles' as your synchronization method, also set the role to be assigned if the membership has expired in "Expiry Role". This is not needed when working with Capabilities.
1. It may sometimes be necessary to manually synchronize users. Click on the "Manually Synchronize" tab on the admin page to do so. You will want to use this when you initially configure this plugin to synchronize your existing users.
1. **Note:** If you have the "[Groups](https://wordpress.org/plugins/groups/)" plugin activated and it is version 2.8.0 or greater, then you will have the option to specify one or more "current" and "expired" groups to which users will be synced depending on whether their membership is "current" or "expired".
* **Note:** If you have [BuddyPress](https://wordpress.org/plugins/buddypress/) active, then you will have the option to specify one or more "current" and "expired" groups to which users will be synced depending on whether their membership is "current" or "expired".

<h4>Manual Synchronize</h4>

It may sometimes be necessary to manually synchronize users. Click on the "Manually Synchronize" tab on the admin page to do so. You will want to use this when you initially configure this plugin to synchronize your existing users.

<h4>Test Test Test</h4>

**Note:** Be sure to test this plugin thoroughly before using it in a production environment. At minimum, you should log in as a test user to ensure you have been granted the appropriate role or capabilities when that user is given membership. Then take away the membership for the user in their CiviCRM record, log back in as the test user, and make sure you no longer have that role or those capabilities.

<h4>Known Issues</h4>

Code that used the `civi_wp_member_sync_after_insert_user` hook to send User Notifications on User Account creation should switch to the newer `civi_wp_member_sync_post_insert_user` hook to avoid the inadvertent loss of session data.



== Changelog ==

= 0.5 =

* Introduce "Dry Run" functionality
* Compatibility with CiviCRM Admin Utilities "Manage Users" screen

= 0.4.7 =

* Support for syncing to BuddyPress Groups
* Fix sync for CiviCRM Memberships that do not require payment.

= 0.4.6 =

* Housekeeping release

= 0.4.5 =

* Fix validation on "Rule Add" screen

= 0.4.4 =

* Allow selection of multiple Membership Types when adding an Association Rule
* Introduce "civi_wp_member_sync_post_insert_user" action

= 0.4.3 =

* Introduce "civi_wp_member_sync_contact_retrieved" filter
* Fix references to CiviCRM WP Profile Sync methods

= 0.4.2 =

* Introduce "bulk create association rules" functionality
* Ask for confirmation before deleting an Association Rule

= 0.4.1 =

* Fix sync for Contacts in Trash - fix requires CiviCRM Admin Utilities 0.6.8+

= 0.4 =

* Support access control based on Groups plugin group membership

= 0.3.8 =

* Fix fatal error when renewing and changing Membership type

= 0.3.7 =

* Allow Manual Sync batch count to be filtered
* Ensure usernames are unique during WordPress user creation

= 0.3.6 =

* Allow sync to be restricted to CiviCRM Contacts of Contact Type "Individual"

= 0.3.5 =

* Fix creation of WordPress user on new Membership

= 0.3.4 =

* Make order of processed memberships explicit
* Support renewals dureing which the membership type changes

= 0.3.3 =

* Pass CiviCRM contact ID to "civi_wp_member_sync_auto_create_wp_user" filter
* Allow limitless API queries where needed

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



== Upgrade Notice ==

= 0.3.6 =

This version introduces a setting to allow sync to be restricted to CiviCRM Contacts of Contact Type "Individual". Once you have upgraded, please review plugin settings to make sure yours are correct.
