<?php

/**
 * @author Felipe Carasso
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package moodle multiauth
 *
 * Authentication Plugin: Email Authentication with admin confirmation
 *
 * Standard authentication function.
 *
 * 2012-12-03  File created based on 'email' package by Martin Dougiamas.
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
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
    function auth_plugin_emailadmin() {
        $this->authtype = 'emailadmin';
        $this->config = get_config('auth/emailadmin');
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    function user_login ($username, $password) {
        global $CFG, $DB;
        if ($user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id))) {
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
    function user_update_password($user, $newpassword) {
        $user = get_complete_user_data('id', $user->id);
        return update_internal_user_password($user, $newpassword);
    }

    function can_signup() {
        return true;
    }

    /**
     * Sign up a new user ready for confirmation.
     * Password is passed in plaintext.
     *
     * @param object $user new user object
     * @param boolean $notify print notice with link and terminate
     */
    function user_signup($user, $notify=true) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/lib.php');

        $user->password = hash_internal_user_password($user->password);

        $user->id = $DB->insert_record('user', $user);

        /// Save any custom profile field information
        profile_save_data($user);

        $user = $DB->get_record('user', array('id'=>$user->id));
        //events_trigger('user_created', $user);
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
            print_error('auth_emailadminnoemail','auth_emailadmin');
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
    function can_confirm() {
        return true;
    }

    /**
     * Confirm the new user as registered.
     *
     * @param string $username
     * @param string $confirmsecret
     */
    function user_confirm($username, $confirmsecret) {
        global $DB;
        $user = get_complete_user_data('username', $username);

        if (!empty($user)) {
            if ($user->confirmed) {
                return AUTH_CONFIRM_ALREADY;

            } else if ($user->auth != $this->authtype) {
                mtrace("Auth mismatch for user ". $user->username .": ". $user->auth ." != ". $this->authtype);
                return AUTH_CONFIRM_ERROR;

            } else if ($user->secret == $confirmsecret) {   // They have provided the secret key to get in
                $DB->set_field("user", "confirmed", 1, array("id"=>$user->id));
                if ($user->firstaccess == 0) {
                    $DB->set_field("user", "firstaccess", time(), array("id"=>$user->id));
                }
                return AUTH_CONFIRM_OK;
            }
        } else {
            mtrace("User not found: ". $username);
            return AUTH_CONFIRM_ERROR;
        }
    }

    function prevent_local_passwords() {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    function is_internal() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    function can_change_password() {
        return true;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    function change_password_url() {
        return null; // use default internal method
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    function can_reset_password() {
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
    function config_form($config, $err, $user_fields) {
        include "config.html";
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     */
    function process_config($config) {
        // set to defaults if undefined
        if (!isset($config->recaptcha)) {
            $config->recaptcha = false;
        }

        // save settings
        set_config('recaptcha', $config->recaptcha, 'auth/emailadmin');
        return true;
    }

    /**
     * Returns whether or not the captcha element is enabled, and the admin settings fulfil its requirements.
     * @return bool
     */
    function is_captcha_enabled() {
        global $CFG;
        return isset($CFG->recaptchapublickey) && isset($CFG->recaptchaprivatekey) && get_config("auth/{$this->authtype}", 'recaptcha');
    }

    /**
     * Send email to admin with confirmation text and activation link for
     * new user.
     *
     * @param user $user A {@link $USER} object
     * @return bool Returns true if mail was sent OK to *any* admin and false if otherwise.
     */
    function send_confirmation_email_support($user) {
        global $CFG;
    
        $site = get_site();
        $supportuser = core_user::get_support_user();
    

        $data = new stdClass();
        $data->firstname = fullname($user);
        $data->sitename  = format_string($site->fullname);
        $data->admin     = generate_email_signoff();

        $data->userdata = '';
        foreach(((array) $user) as $dataname => $datavalue) {
            $data->userdata	 .= $dataname . ': ' . $datavalue . PHP_EOL;
        }

        // Add custom fields
        $data->userdata .= $this->list_custom_fields($user);
    
        $subject = get_string('auth_emailadminconfirmationsubject', 'auth_emailadmin', format_string($site->fullname));
    
        $username = urlencode($user->username);
        $username = str_replace('.', '%2E', $username); // prevent problems with trailing dots
        $data->link  = $CFG->wwwroot .'/auth/emailadmin/confirm.php?data='. $user->secret .'/'. $username;
        $message     = get_string('auth_emailadminconfirmation', 'auth_emailadmin', $data);
        $messagehtml = text_to_html(get_string('auth_emailadminconfirmation', 'auth_emailadmin', $data), false, false, true);
    
        $user->mailformat = 1;  // Always send HTML version as well
    
        //directly email rather than using the messaging system to ensure its not routed to a popup or jabber
        $admins = get_admins();
        $return = false;

        // Send message to fist admin (main) only. Remove "break" for all admins
        foreach ($admins as $admin) {
            $return |= email_to_user($admin, $supportuser, $subject, $message, $messagehtml);
            break;
        }

        return $return;
    }

    /**
     * Return an array with custom user properties.
     *
     * @param user $user A {@link $USER} object
     */
    function list_custom_fields($user) {
        global $CFG, $DB;

        $result = '';
        if ($fields = $DB->get_records('user_info_field')) {
            foreach($fields as $field) {
                $fieldobj = new profile_field_base($field->id, $user->id);
                $result .= format_string($fieldobj->field->name.':') . ' ' . $fieldobj->display_data() . PHP_EOL;
            }
        }

        return $result;
    }
}
