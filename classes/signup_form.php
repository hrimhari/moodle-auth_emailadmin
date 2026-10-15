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
 * Signup form for auth_emailadmin: email-as-username.
 *
 * Reproduces the server side patched form previously used on this site:
 * the username is derived from the email address, no separate username
 * field is shown. Field configuration (which profile fields are shown,
 * optional or required) stays in the Moodle core profile field settings.
 *
 * @package    auth_emailadmin
 * @copyright  2012 onwards Felipe Carasso (http://carassonet.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_emailadmin;

use core_text;
use core_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/login/signup_form.php');

/**
 * Signup form: username is derived from the email address.
 *
 * @package    auth_emailadmin
 * @copyright  2012 onwards Felipe Carasso (http://carassonet.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class signup_form extends \login_signup_form {

    /**
     * Build the signup form without a username element.
     *
     * Field order mirrors the patched production form: email, email2,
     * password, name fields, institution, country, profile fields, captcha,
     * site policy, buttons. All other behaviour (validation, captcha,
     * site policy, profile fields, rendering) is inherited unchanged.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'createuserandpass', get_string('createuserandpass', 'auth_emailadmin'), '');
        $mform->setExpanded('createuserandpass', true);

        // Email address doubles as the username on this site.
        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="25"');
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', get_string('missingemail'), 'required', null, 'client');
        $mform->addRule('email', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        $mform->setForceLtr('email');
        $mform->addHelpButton('email', 'usernamefromemail', 'auth_emailadmin');

        $mform->addElement('text', 'email2', get_string('emailagain'), 'maxlength="100" size="25"');
        $mform->setType('email2', PARAM_EMAIL);
        $mform->addRule('email2', get_string('missingemail'), 'required', null, 'client');
        $mform->addRule('email2', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        $mform->setForceLtr('email2');

        if (!empty($CFG->passwordpolicy)) {
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }
        // MAX_PASSWORD_CHARACTERS was introduced in Moodle 4.3; on 4.1/4.2 the
        // maxlength comes from the core signup form default (32).
        if (defined('MAX_PASSWORD_CHARACTERS')) {
            $maxlength = MAX_PASSWORD_CHARACTERS;
        } else {
            $maxlength = 32;
        }
        $mform->addElement('password', 'password', get_string('password'), [
            'maxlength' => $maxlength,
            'size' => 12,
            'autocomplete' => 'new-password'
        ]);
        $mform->setType('password', core_user::get_property_type('password'));
        $mform->addRule('password', get_string('missingpassword'), 'required', null, 'client');
        $mform->addRule('password', get_string('maximumchars', '', $maxlength),
            'maxlength', $maxlength, 'client');

        $namefields = useredit_get_required_name_fields();
        foreach ($namefields as $field) {
            $mform->addElement('text', $field, get_string($field), 'maxlength="100" size="30"');
            $mform->setType($field, core_user::get_property_type('firstname'));
            $stringid = 'missing' . $field;
            if (!get_string_manager()->string_exists($stringid, 'moodle')) {
                $stringid = 'required';
            }
            $mform->addRule($field, get_string($stringid), 'required', null, 'client');
        }

        // Institution instead of the core "city" field: Schrack Seconet
        // trainees enter their company, the standard user table column
        // "institution" (labelled "Institution") stores it natively.
        $mform->addElement('text', 'institution', get_string('institution'), 'maxlength="255" size="40"');
        $mform->setType('institution', PARAM_TEXT);

        $country = get_string_manager()->get_list_of_countries();
        $defaultcountry = [];
        $defaultcountry[''] = get_string('selectacountry');
        $country = array_merge($defaultcountry, $country);
        $mform->addElement('select', 'country', get_string('country'), $country);
        if (!empty($CFG->country)) {
            $mform->setDefault('country', $CFG->country);
        } else {
            $mform->setDefault('country', '');
        }

        profile_signup_fields($mform);

        if (signup_captcha_enabled()) {
            $mform->addElement('recaptcha', 'recaptcha_element', get_string('security_question', 'auth'));
            $mform->addHelpButton('recaptcha_element', 'recaptcha', 'auth');
            $mform->closeHeaderBefore('recaptcha_element');
        }

        // Hook for plugins to extend form definition.
        core_login_extend_signup_form($mform);

        // Add "Agree to sitepolicy" controls.
        $manager = new \core_privacy\local\sitepolicy\manager();
        $manager->signup_form($mform);

        // Buttons.
        $this->set_display_vertical();
        $this->add_action_buttons(true, get_string('createaccount'));
    }

    /**
     * Validate the form data.
     *
     * Derives the username from the email address, runs the standard core
     * username checks on the derived value, then continues with the standard
     * email/password/profile validation of the parent form.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     */
    public function validation($data, $files) {
        // Derive the username from the email address before any validation runs.
        $data['username'] = static::username_from_email($data['email']);
        $errors = parent::validation($data, $files);

        // The form has no username element: route username errors to the
        // email field so the user actually sees them (e.g. derived username
        // already taken by another account).
        if (!empty($errors['username'])) {
            $errors['email'] = $errors['username'];
            unset($errors['username']);
        }
        return $errors;
    }

    /**
     * Convert an email address into a valid Moodle username.
     *
     * Takes the local part of the address, lowercases it and cleans it with
     * the core username rules. Collisions are caught later by the core
     * username-exists checks (signup_validate_data).
     *
     * @param string $email the submitted email address
     * @return string the derived username
     */
    public static function username_from_email($email) {
        $username = $email;
        if (strpos($email, '@') !== false) {
            $username = substr($email, 0, strpos($email, '@'));
        }
        $username = core_text::strtolower(trim($username));
        return core_user::clean_field($username, 'username');
    }
}