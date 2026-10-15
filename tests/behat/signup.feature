@auth @auth_emailadmin
Feature: Self registration with admin confirmation using the emailadmin auth plugin
  In order to sign up on a site where admins confirm accounts
  As an anonymous user
  I need to be able to register with my email address

  Background:
    Given the following config values are set as admin:
      | registerauth   | emailadmin              |
      | auth           | manual,email,emailadmin |
      | passwordpolicy | 0                       |

  Scenario: Sign up with email as username, admin confirms, user can log in
    Given I am on site homepage
    And I follow "Log in"
    And I click on "Create new account" "link"
    Then I should see "Choose your sign-up details"
    And I set the following fields to these values:
      | Email address | robert.sack@example.com |
      | Email (again) | robert.sack@example.com |
      | Password      | ChangeMe!2026           |
      | First name    | Robert                  |
      | Last name     | Sack                    |
    And I press "Create my new account"
    Then I should see "Your account has been registered and is pending confirmation by the administrator."
    And I press "Continue"
    And I should see "You are not logged in"
    # The admin received the confirmation mail; emulate the admin clicking
    # the confirm link while logged in (confirm.php requires an admin
    # session, see ISSUE-26 guard).
    And I log in as "admin"
    And I confirm admin approval for "robert.sack"
    Then I should see "Your registration has been confirmed"
    And I log out
    # The confirmed user logs in with the derived username.
    And I follow "Log in"
    And I set the field "Username" to "robert.sack"
    And I set the field "Password" to "ChangeMe!2026"
    And I press "Log in"
    # Login succeeded if we can open the profile in edit mode (core signup
    # feature uses the same final check).
    And I open my profile in edit mode
    And the field "First name" matches value "Robert"
    And I log out
