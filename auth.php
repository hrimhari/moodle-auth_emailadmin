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
 * Authentication Plugin: Email Authentication with admin confirmation
 *
 * @author Felipe Carasso
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package moodle multiauth
 *
 * 2012-12-03  File created based on 'email' package by Martin Dougiamas.
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');


/**
 * Email authentication plugin.
 */
class auth_plugin_emailadmin extends auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'emailadmin';
        $this->config = get_config('auth/emailadmin');
    }

    /* Backward compatible constructor. */
    public function auth_plugin_email() {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct();
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    public function user_login ($username, $password) {
        global $CFG, $DB;
        if ($user = $DB->get_record('user', array('username' => $username, 'mnethostid' => $CFG->mnet_localhost_id))) {
            return validate_internal_user_password($user, $password);
        }
        return false;
    }

    /**
     * Updates the user's password.
     *
     * called when the user password is updated.
     *
     * @param  object  $user        User table object  (with system magic quotes)
     * @param  string  $newpassword Plaintext password (with system magic quotes)
     * @return boolean result
     *
     */
    public function user_update_password($user, $newpassword) {
        $user = get_complete_user_data('id', $user->id);
        return update_internal_user_password($user, $newpassword);
    }

    public function can_signup() {
        return true;
    }

    /**
     * Sign up a new user ready for confirmation.
     * Password is passed in plaintext.
     *
     * @param object $user new user object
     * @param boolean $notify print notice with link and terminate
     */
    public function user_signup($user, $notify=true) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/lib.php');

        $user->password = hash_internal_user_password($user->password);

        $user->id = $DB->insert_record('user', $user);

        // Save any custom profile field information.
        profile_save_data($user);

        $user = $DB->get_record('user', array('id' => $user->id));

        $usercontext = context_user::instance($user->id);
        $event = \core\event\user_created::create(
            array(
                'objectid' => $user->id,
                'relateduserid' => $user->id,
                'context' => $usercontext
                )
            );
        $event->trigger();

        if (! $this->send_confirmation_email_support($user)) {
            print_error('auth_emailadminnoemail', 'auth_emailadmin');
        }

        if ($notify) {
            global $CFG, $PAGE, $OUTPUT;
            $emailconfirm = get_string('emailconfirm');
            $PAGE->navbar->add($emailconfirm);
            $PAGE->set_title($emailconfirm);
            $PAGE->set_heading($PAGE->course->fullname);
            echo $OUTPUT->header();
            notice(get_string('auth_emailadminconfirmsent', 'auth_emailadmin', $user->email), "$CFG->wwwroot/index.php");
        } else {
            return true;
        }
    }

    /**
     * Returns true if plugin allows confirming of new users.
     *
     * @return bool
     */
    public function can_confirm() {
        return true;
    }

    /**
     * Confirm the new user as registered.
     *
     * @param string $username
     * @param string $confirmsecret
     */
    public function user_confirm($username, $confirmsecret) {
        global $DB;
        $user = get_complete_user_data('username', $username);

        if (!empty($user)) {
            if ($user->confirmed) {
                return AUTH_CONFIRM_ALREADY;

            } else if ($user->auth != $this->authtype) {
                mtrace("Auth mismatch for user ". $user->username .": ". $user->auth ." != ". $this->authtype);
                return AUTH_CONFIRM_ERROR;

            } else if ($user->secret == $confirmsecret) {   // They have provided the secret key to get in.
                $DB->set_field("user", "confirmed", 1, array("id" => $user->id));
                if ($user->firstaccess == 0) {
                    $DB->set_field("user", "firstaccess", time(), array("id" => $user->id));
                }
                return AUTH_CONFIRM_OK;
            }
        } else {
            mtrace("User not found: ". $username);
            return AUTH_CONFIRM_ERROR;
        }
    }

    public function prevent_local_passwords() {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    public function is_internal() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password() {
        return true;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    public function change_password_url() {
        return null; // Use default internal method.
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    public function can_reset_password() {
        return true;
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param array $page An object containing all the data for this page.
     */
    public function config_form($config, $err, $user_fields) {
        include("config.html");
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     */
    public function process_config($config) {
        // Set to defaults if undefined.
        if (!isset($config->recaptcha)) {
            $config->recaptcha = false;
        }

        /*
         *  -1: First admin user found (default)
         *  -2: All admin users
         * >=0: Specific user id
         */
        if (!isset($config->notif_strategy) || !is_numeric($config->notif_strategy) || $config->notif_strategy < -2) {
            $config->notif_strategy = -1; // Default: first admin user found.
        }

        // Save settings.
        set_config('recaptcha', $config->recaptcha, 'auth/emailadmin');
        set_config('notif_strategy', $config->notif_strategy, 'auth/emailadmin');
        return true;
    }

    /**
     * Returns whether or not the captcha element is enabled, and the admin settings fulfil its requirements.
     * @return bool
     */
    public function is_captcha_enabled() {
        global $CFG;
        return isset($CFG->recaptchapublickey) &&
            isset($CFG->recaptchaprivatekey) &&
            get_config("auth/{$this->authtype}", 'recaptcha');
    }

    /**
     * Send email to admin with confirmation text and activation link for
     * new user.
     *
     * @param user $user A {@link $USER} object
     * @return bool Returns true if mail was sent OK to *any* admin and false if otherwise.
     */
    public function send_confirmation_email_support($user) {
        global $CFG;
        $config = $this->config;

        $site = get_site();
        $supportuser = core_user::get_support_user();

        $data = array();

        // Text compilation of all user fields except the password.
        $data["userdata"] = '';

        foreach (((array) $user) as $dataname => $datavalue) {
            if ( $dataname == "userdata" || $dataname == "password" ) {
                continue;
            }

            $data[$dataname]      = $datavalue;
            $data["userdata"]      .= $dataname . ': ' . $datavalue . PHP_EOL;
        }
        $data["sitename"]  = format_string($site->fullname);
        $data["admin"]     = generate_email_signoff();

        // Add custom fields.
        $data["customfields"] = $this->list_custom_fields($user);
        $data["userdata"] .= $data["customfields"];

        // (FECA) Crazy hack to reuse existing language logic (who knows, it could have been customized or something).
        global $USER, $COURSE, $SESSION;
        $lang_hack = new stdClass();
        $lang_hack->forcelang = $supportuser->lang;
        $lang_hack->lang = $supportuser->lang;
        $hack_backup = ['USER' => false, 'COURSE' => false, 'SESSION' => false];
        foreach ($hack_backup as $hack_backup_key => $hack_backup_value) {
            $hack_backup[$hack_backup_key] = $GLOBALS[$hack_backup_key];
            $GLOBALS[$hack_backup_key] = $lang_hack;
        }
        $use_lang = current_language();
        foreach ($hack_backup as $hack_backup_key => $hack_backup_value) {
            $GLOBALS[$hack_backup_key] = $hack_backup_value;
        }
        /* (FECA) End of crazy hack. Could just have repeated some ifs or just uses $CFG as suggested by ewallah, but no...
         * I had to do something crazy, hadn't I?
         */

        $subject = get_string_manager()->get_string('auth_emailadminconfirmationsubject',
                                                    'auth_emailadmin',
                                                    format_string($site->fullname),
                                                    $use_lang);

        $username = urlencode($user->username);
        $username = str_replace('.', '%2E', $username); // Prevent problems with trailing dots.
        $data["link"] = $CFG->wwwroot .'/auth/emailadmin/confirm.php?data='. $user->secret .'/'. $username;
        $message     = get_string('auth_emailadminconfirmation', 'auth_emailadmin', $data);
        $messagehtml = text_to_html($message, false, false, true);

        $user->mailformat = 1;  // Always send HTML version as well.

        // Directly email rather than using the messaging system to ensure its not routed to a popup or jabber.
        $admins = get_admins();
        $return = false;
        $admin_found = false;

        // Send message to fist admin (main) only. Remove "break" for all admins.
        error_log(print_r($config->notif_strategy, true));
        $config->notif_strategy = intval($config->notif_strategy);
        $send_list = array();
        foreach ($admins as $admin) {
            error_log(print_r( $config->notif_strategy . ":" . $admin->id, true ));
            if ($config->notif_strategy < 0 || $config->notif_strategy == $admin->id) {
                $admin_found = true;
            }
            if ($admin_found) {
                $send_list[] = $admin;
                if ($config->notif_strategy == -1 || $config->notif_strategy >= 0 ) {
                    break;
                }
            }
        }

        $errors = array();
        foreach ($send_list as $admin) {
            $result = email_to_user($admin, $supportuser, $subject, $message, $messagehtml);
            $return |= $result;
            if (! $result) {
                $errors[] = $admin->username;
            }
        }

        $error = '';
        if (!$admin_found) {
            $error = get_string("auth_emailadminnoadmin", "auth_emailadmin");
        }

        if (count($errors) > 0) {
            $error = get_string("auth_emailadminnotif_failed", "auth_emailadmin");
            foreach ($errors as $admin) {
                $error .= $admin . " ";
            }
        }

        if ($error != '') {
            error_log($error);
            $subject = get_string('auth_emailadminconfirmationsubject', 'auth_emailadmin', format_string($site->fullname));
            $message = $error . "\n" . get_string('auth_emailadminconfirmation', 'auth_emailadmin', $data);
            $messagehtml = text_to_html($message, false, false, true);
            foreach ($admins as $admin) {
                if (!in_array($admin->username, $errors)) {
                    $result = email_to_user($admin, $supportuser, $subject, $message, $messagehtml);
                }
            }
        }

        return $return;
    }

    /**
     * Return an array with custom user properties.
     *
     * @param user $user A {@link $USER} object
     */
    public function list_custom_fields($user) {
        global $CFG, $DB;

        $result = '';
        if ($fields = $DB->get_records('user_info_field')) {
            foreach ($fields as $field) {
                $fieldobj = new profile_field_base($field->id, $user->id);
                $result .= format_string($fieldobj->field->name.':') . ' ' . $fieldobj->display_data() . PHP_EOL;
            }
        }

        return $result;
    }
}
