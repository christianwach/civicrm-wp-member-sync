CiviCRM Member Sync
===================

**Contributors:** [needle](https://profiles.wordpress.org/needle/), [cuny-academic-commons](https://profiles.wordpress.org/cuny-academic-commons/)<br/>
**Donate link:** https://www.paypal.me/interactivist<br/>
**Tags:** civicrm, member, membership, sync<br/>
**Requires at least:** 4.9<br/>
**Tested up to:** 6.2<br/>
**Stable tag:** 0.6.1<br/>
**License:** GPLv2 or later<br/>
**License URI:** https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Keep *WordPress* Users in sync with *CiviCRM* Memberships by granting either a Role or Capabilities to Users with that Membership.

## Description

*Please note:* this is the development repository for CiviCRM Member Sync. The plugin is also available in the [WordPress Plugin Directory](https://wordpress.org/plugins/civicrm-wp-member-sync/), which is the best place to get it from if you're not a developer.

The *CiviCRM Member Sync* plugin keeps a *WordPress* User in sync with a *CiviCRM* Membership by granting either a Role or Capabilities to a *WordPress* User who has that Membership.

This enables you to have, among other things, members-only content on your website that is only accessible to current members as defined by the Membership Types and status rules that you set up in the plugin's settings. CiviCRM Member Sync is compatible with both the [Members](https://wordpress.org/plugins/members/) and [Groups](https://wordpress.org/plugins/groups/) plugins for managing members-only content in WordPress.

### Notes

This plugin is being developed for a minimum of *WordPress 4.9* and *CiviCRM 5.19*.

It is strongly recommended that you also install [CiviCRM Admin Utilities](https://wordpress.org/plugins/civicrm-admin-utilities/) and have version 0.6.8 or greater activated. Make sure the checkbox labelled "Check this to fix the Contact 'soft delete' process" is checked so that Contacts that have been "soft deleted" have their corresponding WordPress User's status updated.

This plugin builds on the [GitHub repo](https://github.com/tadpolecc/civi_member_sync) written by [Tadpole Collective](https://tadpole.cc) and  originally developed by [Jag Kandasamy](https://github.com/jeevajoy). It has been given its own repo because it has diverged so significantly from its origins that it no longer makes sense to call it a fork or send changes upstream.

**Please note:** This plugin is still in active development. Use at your own risk.

## Installation

There are two ways to install from GitHub:

#### ZIP Download

If you have downloaded *CiviCRM Member Sync* as a ZIP file from the GitHub repository, do the following to install and activate the plugin:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/civicrm-wp-member-sync`
2. Make sure *CiviCRM* is activated
3. Activate the plugin
4. Configure the plugin as described above

#### git clone

If you have cloned the code from GitHub, it is assumed that you know what you're doing.

## Configuration

Before you get started, you will need to create all of your Membership Types and status rules for *CiviMember*. If you select 'roles' as your synchronization method, you will also need to create the *WordPress* Role(s) you would like to synchronize Memberships with. The default synchronisation method is 'capabilities', because *WordPress* has limited support for multiple Roles.

**Note:** This plugin can sync Membership on User login, User logout and on a scheduled basis. It can also sync a User's Role when the Membership is added, edited or deleted in *CiviCRM*.

## Working with Capabilities

* Visit the plugin's admin page at "CiviCRM" --> "Member Sync".
* Select "Capabilities" as the sync method
* Click on "Add Association Rule" to create a rule. You will need to create a rule for every CiviCRM Membership Type you would like to synchronize. For every Membership Type, you will need to determine the CiviMember states that define the member as "current" thereby granting them the appropriate WordPress Capabilities. It is most common to define "New", "Current" and "Grace" as current. Similarly, select which states represent the "expired" status thereby removing the WordPress Capabilities from the User. It is most common to define "Expired", "Pending", "Cancelled" and "Deceased" as the expired status.
* "Current Status" adds a "Membership Capability" to the WordPress User, while "Expired Status" removes the "Membership Capability" from the WordPress User. This Capability will be of the form `civimember_ID`, where `ID` is the numeric ID of the Membership Type. So, for Membership Type 2, the Capability will be `civimember_2`.
* **Note:** If you have the [Groups](https://wordpress.org/plugins/groups/) plugin active, then all `civimember_ID` Capabilities will be added to its custom Capabilities as well as to the list of Capabilities used to enforce read access on Posts. If you have Groups 2.8.0 or greater installed, then you will have the option to specify one or more "current" and "expired" Groups to which Users will be synced depending on whether their Membership is "current" or "expired".
* **Note:** If you have the [Members](https://wordpress.org/plugins/members/) plugin active, then the `restrict_content` Capability will also be added to to the WordPress User.
* **Note:** If you have [BuddyPress](https://wordpress.org/plugins/buddypress/) active, then you will have the option to specify one or more "current" and "expired" Groups to which Users will be synced depending on whether their Membership is "current" or "expired".
* An additional "Membership Status Capability" will also be added to the WordPress User that is tied to the status of their Membership. This Capability will be of the form `civimember_ID_NUM`, where `ID` is the numeric ID of the Membership Type and `NUM` is the numeric ID of the "Membership Status". So, for Membership Type 2 with Membership Status 4, the Capability will be `civimember_2_4`.

## Working with Roles

* Visit the plugin's admin page at "CiviCRM" --> "Member Sync".
* Select "Roles" as the sync method
* Click on "Add Association Rule" to create a rule. You will need to create a rule for every CiviCRM Membership Type you would like to synchronize. For every Membership Type, you will need to determine the CiviMember states that define the member as "current" thereby granting them the appropriate WordPress Role. It is most common to define "New", "Current" and "Grace" as current. Similarly, select which states represent the "expired" status thereby removing the WordPress Role from the User. It is most common to define "Expired", "Pending", "Cancelled" and "Deceased" as the expired status. With 'roles' as your synchronization method, also set the Role to be assigned if the Membership has expired in "Expiry Role". This is not needed when working with Capabilities.
* It may sometimes be necessary to manually synchronize Users. Click on the "Manual Synchronize" tab on the admin page to do so. You will want to use this when you initially configure this plugin to synchronize your existing Users.
1. **Note:** If you have the [Groups](https://wordpress.org/plugins/groups/) plugin activated and it is version 2.8.0 or greater, then you will have the option to specify one or more "current" and "expired" Groups to which Users will be synced depending on whether their Membership is "current" or "expired".
2. **Note:** If you have [BuddyPress](https://wordpress.org/plugins/buddypress/) active, then you will have the option to specify one or more "current" and "expired" Groups to which Users will be synced depending on whether their Membership is "current" or "expired".

## Manual Synchronize

It may sometimes be necessary to manually synchronize Users. Click on the "Manual Synchronize" tab on the admin page to do so. You will want to use this when you initially configure this plugin to synchronize your existing Users.

## Test Test Test

**Note:** Be sure to test this plugin thoroughly before using it in a production environment. At minimum, you should log in as a test User to ensure you have been granted the appropriate Role or Capabilities when that User is given Membership. Then take away the Membership for the User in their *CiviCRM* record, log back in as the test User, and make sure you no longer have that Role or those Capabilities.

## Known Issues

Code that used the `civi_wp_member_sync_after_insert_user` hook to send User Notifications on User Account creation should switch to the newer `civi_wp_member_sync_post_insert_user` hook to avoid the inadvertent loss of session data.
