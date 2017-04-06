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
 * Message class for auth-emailadmin plugin.
 *
 * @package    moodle multiauth
 * @copyright  2017 Felipe Carasso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth\emailadmin;
defined('MOODLE_INTERNAL') || die();

//require('../../config.php');
require_once($CFG->libdir.'/authlib.php');

class message {
    public static function send_confirmation_email_user($user) {
        global $CFG;
    
        $site = get_site();
        $supportuser = \core_user::get_support_user();
    
        $data = new \stdClass();
        $data->firstname = fullname($user);
        $data->sitename  = format_string($site->fullname);
        $data->admin     = generate_email_signoff();
    
        $subject = get_string('auth_emailadminconfirmationsubject', 'auth_emailadmin', format_string($site->fullname));
    
        $username = urlencode($user->username);
        $username = str_replace('.', '%2E', $username); // Prevent problems with trailing dots.
        $data->link  = $CFG->wwwroot;
        $data->username = $username;
        $message     = get_string('auth_emailadminuserconfirmation', 'auth_emailadmin', $data);
        $messagehtml = text_to_html(get_string('auth_emailadminuserconfirmation', 'auth_emailadmin', $data), false, false, true);
    
        $user->mailformat = 1;  // Always send HTML version as well.
    
        // Directly email rather than using the messaging system to ensure its not routed to a popup or jabber.
    
        return email_to_user($user, $supportuser, $subject, $message, $messagehtml);
    }
}
