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
 * Confirm self registered user.
 * NOTE: based on original 'login/config.php' by Martin Dougiamas.
 *
 * @package    core
 * @subpackage auth
 * @copyright  2012 Felipe Carasso http://carassonet.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir.'/authlib.php');

function send_confirmation_email_user($user) {
    global $CFG;

    $site = get_site();
    $supportuser = core_user::get_support_user();

    $data = new stdClass();
    $data->firstname = fullname($user);
    $data->sitename  = format_string($site->fullname);
    $data->admin     = generate_email_signoff();

    $subject = get_string('auth_emailadminconfirmationsubject', 'auth_emailadmin', format_string($site->fullname));

    $username = urlencode($user->username);
    $username = str_replace('.', '%2E', $username); // prevent problems with trailing dots
    $data->link  = $CFG->wwwroot;
    $data->username = $username;
    $message     = get_string('auth_emailadminuserconfirmation', 'auth_emailadmin', $data);
    $messagehtml = text_to_html(get_string('auth_emailadminuserconfirmation', 'auth_emailadmin', $data), false, false, true);

    $user->mailformat = 1;  // Always send HTML version as well

    //directly email rather than using the messaging system to ensure its not routed to a popup or jabber

    return email_to_user($user, $supportuser, $subject, $message, $messagehtml);
}

$data = optional_param('data', '', PARAM_RAW);  // Formatted as:  secret/username

$p = optional_param('p', '', PARAM_ALPHANUM);   // Old parameter:  secret
$s = optional_param('s', '', PARAM_RAW);        // Old parameter:  username

$PAGE->set_url('/auth/emailadmin/confirm.php');
//$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_context(context_system::instance());

if (empty($CFG->registerauth)) {
    print_error('cannotusepage2');
}
$authplugin = get_auth_plugin($CFG->registerauth);

if (!$authplugin->can_confirm()) {
    print_error('cannotusepage2');
}

if (!empty($data) || (!empty($p) && !empty($s))) {

    if (!empty($data)) {
        $dataelements = explode('/', $data, 2); // Stop after 1st slash. Rest is username. MDL-7647
        $usersecret = $dataelements[0];
        $username   = $dataelements[1];
    } else {
        $usersecret = $p;
        $username   = $s;
    }

    $confirmed = $authplugin->user_confirm($username, $usersecret);

    if ($confirmed == AUTH_CONFIRM_ALREADY) {
        $user = get_complete_user_data('username', $username);
        $PAGE->navbar->add(get_string("alreadyconfirmed"));
        $PAGE->set_title(get_string("alreadyconfirmed"));
        $PAGE->set_heading($COURSE->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->box_start('generalbox centerpara boxwidthnormal boxaligncenter');
        echo "<h3>".get_string("thanks").", ". fullname($user) . "</h3>\n";
        echo "<p>".get_string("alreadyconfirmed")."</p>\n";
        echo $OUTPUT->single_button("$CFG->wwwroot/course/", get_string('courses'));
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;

    } else if ($confirmed == AUTH_CONFIRM_OK) {

        // The admin confirmed the account.

        if (!$user = get_complete_user_data('username', $username)) {
            print_error('cannotfinduser', '', '', s($username));
        }

        send_confirmation_email_user($user);
        $PAGE->navbar->add(get_string("confirmed"));
        $PAGE->set_title(get_string("confirmed"));
        $PAGE->set_heading($COURSE->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->box_start('generalbox centerpara boxwidthnormal boxaligncenter');
        echo "<h3>".get_string("thanks").", ". fullname($USER) . "</h3>\n";
        echo "<p>".get_string("confirmed")."</p>\n";
        echo $OUTPUT->single_button("$CFG->wwwroot/course/", get_string('courses'));
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;
    } else {
        mtrace("Confirm returned: ". $confirmed);
        print_error('invalidconfirmdata');
    }
} else {
    print_error("errorwhenconfirming");
}

redirect("$CFG->wwwroot/");
