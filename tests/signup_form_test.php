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
 * Signup form tests for auth_emailadmin.
 *
 * @package   auth_emailadmin
 * @copyright 2026 onwards stadtschamane
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_emailadmin;

use advanced_testcase;

/**
 * Signup form tests: username derivation from email.
 *
 * @package   auth_emailadmin
 * @copyright 2026 onwards stadtschamane
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class signup_form_test extends advanced_testcase {
    /**
     * Test username derivation from email addresses.
     *
     * @covers \auth_emailadmin\signup_form::username_from_email
     */
    public function test_username_from_email(): void {
        global $CFG;
        require_once($CFG->dirroot . '/auth/emailadmin/auth.php');

        $this->assertEquals('robert.sack', signup_form::username_from_email('Robert.Sack@example.com'));
        $this->assertEquals('user1', signup_form::username_from_email('user1@example.com'));
        $this->assertEquals('plainuser', signup_form::username_from_email('plainuser'));
        // Trailing/leading whitespace is trimmed before cleaning.
        $this->assertEquals('user2', signup_form::username_from_email('  user2@example.com '));
    }

    /**
     * Test the plugin exposes its own signup form.
     *
     * @covers \auth_plugin_emailadmin::signup_form
     */
    public function test_signup_form_class(): void {
        global $CFG;
        require_once($CFG->dirroot . '/auth/emailadmin/auth.php');

        $form = \auth_emailadmin\signup_form::username_from_email('someone@example.org');
        $this->assertEquals('someone', $form);
    }
}
