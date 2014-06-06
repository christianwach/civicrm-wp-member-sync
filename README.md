CiviCRM WordPress Member Sync
=============================

The *CiviCRM WordPress Member Sync* plugin keeps a *WordPress* user in sync with a *CiviCRM* membership by granting either a role or capabilities to a *WordPress* user who has that membership.

This enables you to have, among other things, members-only content on your website that is only accessible to current members as defined by the membership types and status rules that you set up in *CiviCRM*.

#### Notes ####

This plugin has been developed using *WordPress 3.9*, *BuddyPress 1.9+* and *CiviCRM 4.4.5*. It requires the master branch of the [CiviCRM WordPress plugin](https://github.com/civicrm/civicrm-wordpress) plus the custom WordPress.php hook file from the [CiviCRM Hook Tester repo on GitHub](https://github.com/christianwach/civicrm-wp-hook-tester) so that it overrides the built-in *CiviCRM* file.

This plugin builds on the [GitHub repo](https://github.com/tadpolecc/civi_member_sync) written by [Tadpole Collective](https://tadpole.cc) and  originally developed by [Jag Kandasamy](http://www.orangecreative.net). It has been given its own repo because it has diverged so significantly from its origins that it no longer makes sense to call it a fork or send changes upstream.

**Please note:** This plugin is not fully tested in all environments and may not be production-ready for you. Use at your own risk.

#### Configuration ####

Before you get started, you wil need to create all of your membership types and status rules for *CiviMember*. If you select 'roles' as your synchronization method, you will also need to create the *WordPress* role(s) you would like to synchronize memberships with.

**Note:** This plugin can sync membership roles on user login, user logout and on a scheduled basis. It can also sync a user's role when the membership is added, edited or deleted in *CiviCRM*.

1. Visit the plugin's admin page at *Settings* --> *CiviCRM WordPress Member Sync*.
2. Click on *Add Association Rule* to create a rule. You will need to create a rule for every *CiviCRM* membership type you would like to synchronize. For every membership type, you will need to determine the *CiviMember* states that define the member as "current" thereby granting them the appropriate WordPress role or capabilities. It is most common to define *New*, *Current* & *Grace* as current. Similarly, select which states represent the "expired" status thereby removing the WordPress role from the user. It is most common to define *Expired*, *Pending*, *Cancelled* & *Deceased* as the expired status. If you select 'roles' as your synchronization method, also set the role to be assigned if the membership has expired in "Expiry Role". This is not needed when working with Capabilities.
3. It may sometimes be necessary to manually synchronize users. Click on the "Manually Synchronize" tab on the admin page to do so. You will want to use this when you initially configure this plugin to synchronize your existing users.

###### Working with Capabilities ######

*Current Status* adds a Membership Capability to the *WordPress* user, while *Expired Status* removes the Membership Capability from the *WordPress* user. This capability will be of the form "civimember_ID", where "ID" is the numeric ID of the Membership Type. So, for Membership Type 2, the capability will be "civimember_2". 

**Note:** If you have the "Members" plugin active, then the "restrict_content" capability will also be added.

An additional Membership Status Capability will also be added to the *WordPress* user that is tied to the status of their membership. This capability will be of the form "civimember_ID_NUM", where "ID" is the numeric ID of the Membership Type and "NUM" is the numeric ID of the Membership Status. So, for Membership Type 2 with Membership Status 4, the capability will be "civimember_2_4".

###### Test Test Test ######

**Note:** Be sure to test this plugin thoroughly before using it in a production environment. At minimum, you should log in as a test user to ensure you have been granted the appropriate role or capabilities when that user is given membership. Then take away the membership for the user in their CiviCRM record, log back in as the test user, and make sure you no longer have that role or those capabilities.

#### Installation ####

There are two ways to install from GitHub:

###### ZIP Download ######

If you have downloaded *CiviCRM WordPress Member Sync* as a ZIP file from the GitHub repository, do the following to install and activate the plugin and theme:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/civicrm-wp-member-sync`
2. Make sure *CiviCRM* is activated
3. Activate the plugin
4. Configure the plugin as described above

###### git clone ######

If you have cloned the code from GitHub, it is assumed that you know what you're doing.
