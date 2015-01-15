=== CiviCRM WordPress Member Sync ===
Contributors: needle, cuny-academic-commons
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PZSKM8T5ZP3SC
Tags: civicrm, member, membership, sync
Requires at least: 3.9
Tested up to: 4.1
Stable tag: 0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Keep WordPress users in sync with CiviCRM memberships by granting either a role or capabilities to users with that membership.


== Description ==

CiviCRM WordPress Member Sync keeps a WordPress user in sync with a CiviCRM membership by granting either a role or capabilities to a WordPress user who has that membership. This enables you to have, among other things, members-only content on your website that is only accessible to current members as defined by the membership types and status rules that you set up in CiviCRM.

This plugin is in active development. For feature requests and bug reports, please visit the GitHub repo at https://github.com/christianwach/civicrm-wp-member-sync


== Installation ==

1. Extract the plugin archive
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Make sure CiviCRM is activated
1. Activate the plugin through the 'Plugins' menu in WordPress

This plugin requires a minimum of WordPress 3.9, BuddyPress 1.8 and CiviCRM 4.6-alpha. For versions of CiviCRM prior to 4.6-alpha, this plugin requires the corresponding branch of the [CiviCRM WordPress plugin](https://github.com/civicrm/civicrm-wordpress) plus the custom WordPress.php hook file from the [CiviCRM Hook Tester repo on GitHub](https://github.com/christianwach/civicrm-wp-hook-tester) so that it overrides the built-in CiviCRM file. Please refer to the each repo for further instructions.

<h4>Working with Roles</h4>

1. Visit the plugin's admin page at "Settings" --> "CiviCRM WordPress Member Sync".
2. Click on "Add Association Rule" to create a rule. You will need to create a rule for every CiviCRM membership type you would like to synchronize. For every membership type, you will need to determine the CiviMember states that define the member as "current" thereby granting them the appropriate WordPress role or capabilities. It is most common to define "New", "Current" and "Grace" as current. Similarly, select which states represent the "expired" status thereby removing the WordPress role from the user. It is most common to define "Expired", "Pending", "Cancelled" and "Deceased" as the expired status. If you select 'roles' as your synchronization method, also set the role to be assigned if the membership has expired in "Expiry Role". This is not needed when working with Capabilities.
3. It may sometimes be necessary to manually synchronize users. Click on the "Manually Synchronize" tab on the admin page to do so. You will want to use this when you initially configure this plugin to synchronize your existing users.

<h4>Working with Capabilities</h4>

"Current Status" adds a "Membership Capability" to the WordPress user, while "Expired Status" removes the "Membership Capability" from the WordPress user. This capability will be of the form "civimember_ID", where "ID" is the numeric ID of the Membership Type. So, for Membership Type 2, the capability will be "civimember_2".

**Note:** If you have the "Members" plugin active, then the "restrict_content" capability will also be added.

An additional "Membership Status Capability" will also be added to the WordPress user that is tied to the status of their membership. This capability will be of the form "civimember_ID_NUM", where "ID" is the numeric ID of the Membership Type and "NUM" is the numeric ID of the "Membership Status". So, for Membership Type 2 with Membership Status 4, the capability will be "civimember_2_4".

<h4>Test Test Test</h4>

**Note:** Be sure to test this plugin thoroughly before using it in a production environment. At minimum, you should log in as a test user to ensure you have been granted the appropriate role or capabilities when that user is given membership. Then take away the membership for the user in their CiviCRM record, log back in as the test user, and make sure you no longer have that role or those capabilities.



== Changelog ==

= 0.1 =

Initial release
