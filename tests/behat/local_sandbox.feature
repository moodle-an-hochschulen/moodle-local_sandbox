@local @local_sandbox @_file_upload @javascript
Feature: Configuring the sandbox plugin
  In order to have the sandbox courses being restored
  As admin
  I need to be able to configure the sandbox plugin

  Background:
    Given the following "courses" exist:
      | fullname            | shortname    |
      | Sandbox Test Course | sandbox-test |
    And the following config values are set as admin:
      | enableasyncbackup | 0 |
    And I log in as "admin"
    And I am on the "Sandbox Test Course" "restore" page
    And I click on "Manage course backups" "button"
    And I upload "local/sandbox/tests/fixtures/sandbox-test.mbz" file to "Files" filemanager
    And I click on "Save changes" "button"
    And I am on the "Sandbox Test Course" "restore" page
    And I merge "sandbox-test.mbz" backup into the current course after deleting it's contents using this options:
      | Schema | Overwrite course configuration | Yes |
    And I navigate to "Courses > Sandbox" in site administration
    And I upload "local/sandbox/tests/fixtures/sandbox-test.mbz" file to "Course backups" filemanager
    And I click on "Save changes" "button"
    And I log out

  Scenario: Check basic restore functionality.
    When I log in as "admin"
    And I am on "Sandbox Test Course" course homepage with editing mode on
    And I add a assign activity to course "Sandbox Test Course" section "1" and I fill the form with:
      | Assignment name | This is an assignment |
    And I add the "Calendar" block
    Then I should see "This is an assignment"
    And I should see "Calendar" in the "#block-region-side-pre .block_calendar_month" "css_element"
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I am on "Sandbox Test Course" course homepage
    Then I should not see "This is an assignment"
    And "#block-region-side-pre .block_calendar_month" "css_element" should not exist

  Scenario: Check if enrolled users are removed after the restore.
    Given the following "users" exist:
      | username | firstname | lastname |
      | user1    | User      | 1        |
    And the following "course enrolments" exist:
      | user  | course       | role    |
      | user1 | sandbox-test | student |
    When I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to course participants
    Then I should see "User 1" in the "participants" "table"
    And I run the scheduled task "local_sandbox\task\restore_courses"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to course participants
    Then I should see "Nothing to display"

  Scenario: Enable "Adjust course start date"
    Given the following config values are set as admin:
      | config                | value | plugin        |
      | adjustcoursestartdate | 1     | local_sandbox |
    When I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to "Settings" in current page administration
    Then I should see "1" in the "#id_startdate_day" "css_element"
    And I should see "January" in the "#id_startdate_month" "css_element"
    And I should see "2000" in the "#id_startdate_year" "css_element"
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to "Settings" in current page administration
    Then I should see "##today##%d##" in the "#id_startdate_day" "css_element"
    And I should see "##today##%B##" in the "#id_startdate_month" "css_element"
    And I should see "##today##%Y##" in the "#id_startdate_year" "css_element"

  Scenario: Counter check: Disable "Adjust course start date"
    Given the following config values are set as admin:
      | config                | value | plugin        |
      | adjustcoursestartdate | 0     | local_sandbox |
    When I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to "Settings" in current page administration
    Then I should see "1" in the "#id_startdate_day" "css_element"
    And I should see "January" in the "#id_startdate_month" "css_element"
    And I should see "2000" in the "#id_startdate_year" "css_element"
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to "Settings" in current page administration
    Then I should see "1" in the "#id_startdate_day" "css_element"
    And I should see "January" in the "#id_startdate_month" "css_element"
    And I should see "2000" in the "#id_startdate_year" "css_element"

  Scenario: Enable "Keep course ID"
    Given the following config values are set as admin:
      | config       | value | plugin        |
      | keepcourseid | 1     | local_sandbox |
    When I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I reload the page
    Then I should see "Sandbox Test Course"

  # If the course gets a new ID we would check that there's the error message
  # "Can't find data record in database table course". However behat fails the
  # step because of the Moodle exception.
  # So this counter check is not testable.
  # Scenario: Counter check: Disable "Keep course ID"

  # We do not test this setting
  # Scenario: Enable "Include users" setting

  Scenario: Enable "Include enrolment methods" setting
    Given the following config values are set as admin:
      | config                     | value | plugin        |
      | restore_general_enrolments | 2     | local_sandbox |
    And the following "users" exist:
      | username | firstname | lastname |
      | user1    | User      | 1        |
    When I log in as "user1"
    And I am on "Sandbox Test Course" course homepage
    And I click on "Enrol me" "button"
    Then I should see "Welcome to your sandbox course."
    And I log out
    When I log in as "admin"
    And I am on the "Sandbox Test Course" "enrolment methods" page
    And I click on "Delete" "link" in the "Self enrol (Teacher)" "table_row"
    And I click on "Continue" "button"
    Then I should not see "Self enrol (Teacher)"
    And I run the scheduled task "local_sandbox\task\restore_courses"
    And I am on the "Sandbox Test Course" "enrolment methods" page
    Then I should see "Self enrol (Teacher)"

  # We do not test this setting
  # Scenario: Enable "Include role assignments" setting

  Scenario: Enable "Include activities and resources" setting
    Given the following config values are set as admin:
      | config                           | value | plugin        |
      | restore_general_activities       | 1     | local_sandbox |
    When I log in as "admin"
    And I am on "Sandbox Test Course" course homepage with editing mode on
    Then I should see "Welcome to your sandbox course."
    And I delete "Welcome to your sandbox course." activity
    And I run the scheduled task "local_sandbox\task\restore_courses"
    And I am on "Sandbox Test Course" course homepage
    Then I should see "Welcome to your sandbox course."

  # We do not test this setting
  # Scenario: Enable "Include blocks" setting

  # We do not test this setting
  # Scenario: Enable "Include filters" setting

  # We do not test this setting
  # Scenario: Enable "Include comments" setting

  # We do not test this setting
  # Scenario: Enable "Include badges" setting

  # We do not test this setting
  # Scenario: Enable "Include calendar events" setting

  # We do not test this setting
  # Scenario: Enable "Include user completion information" setting

  # We do not test this setting
  # Scenario: Enable "Include logs" setting

  # We do not test this setting
  # Scenario: Enable "Include histories" setting

  # We do not test this setting
  # Scenario: Enable "Include groups and groupings" setting

  # We do not test this setting
  # Scenario: Enable "Include competencies" setting

  # We do not test this setting
  # Scenario: Enable "Email failures to" setting

  # We do not test this setting
  # Scenario: Enable "Email Threshold" setting
