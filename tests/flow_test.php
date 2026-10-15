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
 * Signup/confirm/login flow tests for auth_emailadmin.
 *
 * @package   auth_emailadmin
 * @copyright 2026 onwards stadtschamane
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_emailadmin;

use advanced_testcase;

/**
 * Full flow test: signup via user_signup, admin confirm, login.
 *
 * @package   auth_emailadmin
 * @copyright 2026 onwards stadtschamane
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class flow_test extends advanced_testcase {

    /**
     * Create a signup like login/signup.php would (incl. signup_setup_new_user).
     *
     * @return \stdClass the created user record
     */
    protected function signup_test_user(): \stdClass {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/auth/emailadmin/auth.php');
        require_once($CFG->dirroot . '/user/editlib.php');

        $auth = get_auth_plugin('emailadmin');

        $user = new \stdClass();
        $user->email = 'robert.sack@example.com';
        $user->password = 'ChangeMe!2026';
        $user->firstname = 'Robert';
        $user->lastname = 'Sack';
        $user->city = 'Vienna';
        $user->institution = 'Schrack Seconet';
        $user->country = 'AT';
        $user->lang = 'en';
        $user->username = ''; // Derived by the plugin from the email address.
        $user = signup_setup_new_user($user);

        $this->assertTrue($auth->user_signup($user, false));

        $newuser = $DB->get_record('user', ['username' => 'robert.sack']);
        $this->assertNotEmpty($newuser, 'User was created with the derived username');
        $this->assertEquals('robert.sack@example.com', $newuser->email);
        $this->assertEquals('Schrack Seconet', $newuser->institution, 'Signup stores the institution field');
        $this->assertEquals(0, $newuser->confirmed, 'New user starts unconfirmed');
        $this->assertEquals('emailadmin', $newuser->auth);

        return $newuser;
    }

    /**
     * Signup stores the user with the email-derived username and correct auth.
     *
     * @covers \auth_plugin_emailadmin::user_signup
     */
    public function test_signup_derives_username(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        set_config('registerauth', 'emailadmin');
        $this->signup_test_user();
    }

    /**
     * An unconfirmed user must not be able to log in (redirect to login page).
     *
     * @covers \auth_plugin_emailadmin::user_login
     */
    public function test_unconfirmed_login_rejected(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        set_config('registerauth', 'emailadmin');
        $this->signup_test_user();

        $auth = get_auth_plugin('emailadmin');
        // user_login() redirects to the login page with an "awaiting approval"
        // notice; in CLI context redirect() throws.
        $this->expectException(\moodle_exception::class);
        $auth->user_login('robert.sack', 'ChangeMe!2026');
    }

    /**
     * After admin confirmation the user can log in with the derived username.
     *
     * @covers \auth_plugin_emailadmin::user_confirm
     * @covers \auth_plugin_emailadmin::user_login
     */
    public function test_confirm_then_login(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        set_config('registerauth', 'emailadmin');
        $newuser = $this->signup_test_user();

        $auth = get_auth_plugin('emailadmin');
        $this->assertEquals(AUTH_CONFIRM_OK, $auth->user_confirm('robert.sack', $newuser->secret));
        $newuser = $DB->get_record('user', ['username' => 'robert.sack']);
        $this->assertEquals(1, $newuser->confirmed, 'User confirmed by admin');

        // Confirmed user logs in with the derived username.
        $this->assertTrue($auth->user_login('robert.sack', 'ChangeMe!2026'));
        $this->assertFalse($auth->user_login('robert.sack', 'WrongPassword!'));
    }
}