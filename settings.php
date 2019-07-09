<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Admin settings and defaults. Heavily based on auth/email/settings.php.
 *
 * @package auth_emailadmin
 * @copyright  2019 Felipe Carasso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Introductory explanation.
    $settings->add(new admin_setting_heading('auth_emailadmin/pluginname', '',
        new lang_string('auth_emailadmindescription', 'auth_emailadmin')));

    $options = array(
        new lang_string('no'),
        new lang_string('yes'),
    );

    $settings->add(new admin_setting_configselect('auth_emailadmin/recaptcha',
        new lang_string('auth_emailadminrecaptcha_key', 'auth_emailadmin'),
        new lang_string('auth_emailadminrecaptcha', 'auth_emailadmin'), 0, $options));
    $options = array('-1' => get_string("auth_emailadminnotif_strategy_first", "auth_emailadmin"), 
        '-2' => get_string("auth_emailadminnotif_strategy_all", "auth_emailadmin")
        );
    $admins = get_admins();
    foreach ($admins as $admin) {
        $options[$admin->id] = $admin->username;
    }

    $settings->add(new admin_setting_configselect('auth_emailadmin/notif_strategy',
        new lang_string('auth_emailadminnotif_strategy_key', 'auth_emailadmin'),
        new lang_string('auth_emailadminnotif_strategy', 'auth_emailadmin'), -1, $options));

    // Display locking / mapping of profile fields.
    $authplugin = get_auth_plugin('emailadmin');
    display_auth_lock_options($settings, $authplugin->authtype, $authplugin->userfields,
            get_string('auth_fieldlocks_help', 'auth'), false, false);
}
