moodle-auth_emailadmin
======================

Moodle plugin to provide email self-registration with admin confirmation.

The confirmation email is sent to the main admin account's email address.

When the admin clicks on the confirmation link, a "welcome" email is sent to the user.

Email body is customizable within the language file.

Based on the standard email-based self-registration module.

## Version 1.5.1 (2026)

* Version number raised above all known fork variants (20301101xx) so the
  maintained build installs as an upgrade over them.

## Version 1.5.0 (2026)

Maintained fork for Moodle 4.1 - 4.5:

* Fixed: admin confirmation emails were never sent on Moodle 4.x (the
  `auth\emailadmin\message` class was not autoloadable; now
  `classes/message.php` with the correct `auth_emailadmin` namespace).
* Fixed: signup form shipped by the plugin (email address doubles as the
  username, no separate username field). This replaces the old server side
  patch of the core signup form. The broken `[[createuserandpass]]` header
  is fixed: the string is now shipped by the plugin.
* Fixed: debug error_log call removed from the notification loop.
* Signup field configuration (optional/required profile fields) stays in
  Site administration > Users > User profile fields.

***Please read the INSTALL file carefully***
