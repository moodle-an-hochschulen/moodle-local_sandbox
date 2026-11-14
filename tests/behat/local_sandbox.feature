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
    # The course end date in the course backup file is exactly 366 days (as 2000 is a leap year) after the course start date.
    Then the following fields match these values:
      | id_startdate_day   | 1       |
      | id_startdate_month | January |
      | id_startdate_year  | 2000    |
      | id_enddate_enabled | 1       |
      | id_enddate_day     | 1       |
      | id_enddate_month   | January |
      | id_enddate_year    | 2001    |
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to "Settings" in current page administration
    # The course end date is shifted by the same offset as the course start date.
    Then the following fields match these values:
      | id_startdate_day   | ##today##%d##     |
      | id_startdate_month | ##today##%B##     |
      | id_startdate_year  | ##today##%Y##     |
      | id_enddate_enabled | 1                 |
      | id_enddate_day     | ##+366 days##%d## |
      | id_enddate_month   | ##+366 days##%B## |
      | id_enddate_year    | ##+366 days##%Y## |

  Scenario: Counter check: Disable "Adjust course start date"
    Given the following config values are set as admin:
      | config                | value | plugin        |
      | adjustcoursestartdate | 0     | local_sandbox |
    When I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to "Settings" in current page administration
    Then the following fields match these values:
      | id_startdate_day   | 1       |
      | id_startdate_month | January |
      | id_startdate_year  | 2000    |
      | id_enddate_enabled | 1       |
      | id_enddate_day     | 1       |
      | id_enddate_month   | January |
      | id_enddate_year    | 2001    |
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to "Settings" in current page administration
    Then the following fields match these values:
      | id_startdate_day   | 1       |
      | id_startdate_month | January |
      | id_startdate_year  | 2000    |
      | id_enddate_enabled | 1       |
      | id_enddate_day     | 1       |
      | id_enddate_month   | January |
      | id_enddate_year    | 2001    |

  Scenario: Enable "Keep course ID"
    Given the following config values are set as admin:
      | config       | value | plugin        |
      | keepcourseid | 1     | local_sandbox |
    When I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I reload the page
    Then I should see "Sandbox Test Course"

  Scenario: Counter check: Disable "Keep course ID"
    # If the course gets a new ID, the old course page would show the error message
    # "Can't find data record in database table course" which Behat can't handle.
    # Instead, we check that the old course was deleted and put into the category recycle bin.
    Given the following config values are set as admin:
      | config            | value | plugin          |
      | keepcourseid      | 0     | local_sandbox   |
      | categorybinenable | 1     | tool_recyclebin |
      | autohide          | 0     | tool_recyclebin |
    When I log in as "admin"
    And I navigate to "Courses > Manage courses and categories" in site administration
    And I navigate to "Recycle bin" in current page administration
    Then I should see "There are no items in the recycle bin."
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I navigate to "Courses > Manage courses and categories" in site administration
    And I navigate to "Recycle bin" in current page administration
    Then I should see "Sandbox Test Course"
    And I should not see "There are no items in the recycle bin."
    And I am on "Sandbox Test Course" course homepage
    And I should see "Sandbox Test Course"

  Scenario Outline: Enable "Keep course name"
    Given the following config values are set as admin:
      | config         | value            | plugin        |
      | keepcoursename | <keepcoursename> | local_sandbox |
      # We keep the course ID just to be able to reload the course page easily after the restore.
      | keepcourseid   | 1                | local_sandbox |
    When I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | Course full name | Sandbox Edited Course |
    And I press "Save and display"
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I reload the page
    Then I should see "<coursename>"

    Examples:
      | keepcoursename | coursename            |
      | 0              | Sandbox Test Course   |
      | 1              | Sandbox Edited Course |

  Scenario Outline: Enable "Include users" setting
    Given the following "users" exist:
      | username | firstname | lastname |
      | user1    | User      | 1        |
    And the following "course enrolments" exist:
      | user  | course       | role    |
      | user1 | sandbox-test | student |
    And the course "sandbox-test" is backed up to the sandbox course backup file "sandbox-test.mbz"
    And the following config values are set as admin:
      | config                | value         | plugin        |
      | restore_general_users | <globalvalue> | restore       |
      | restore_general_users | <pluginvalue> | local_sandbox |
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to course participants
    Then I <shouldornot> see "User 1"

    Examples:
      | globalvalue | pluginvalue | shouldornot |
      | 0           | 0           | should not  |
      | 0           | 1           | should      |
      | 1           | 0           | should not  |
      | 1           | 1           | should      |

  Scenario Outline: Enable "Include enrolment methods" setting
    Given the following config values are set as admin:
      | config                     | value         | plugin        |
      | restore_general_enrolments | <globalvalue> | restore       |
      | restore_general_enrolments | <pluginvalue> | local_sandbox |
    When I log in as "admin"
    And I am on the "Sandbox Test Course" "enrolment methods" page
    And I click on "Delete" "link" in the "Self enrol (Teacher)" "table_row"
    And I click on "Continue" "button"
    And I should not see "Self enrol (Teacher)"
    And I run the scheduled task "local_sandbox\task\restore_courses"
    And I am on the "Sandbox Test Course" "enrolment methods" page
    Then I <shouldornot> see "Self enrol (Teacher)"

    Examples:
      | globalvalue | pluginvalue | shouldornot |
      | 0           | 0           | should not  |
      | 0           | 2           | should      |
      | 2           | 0           | should not  |
      | 2           | 2           | should      |

  Scenario Outline: Enable "Include role assignments" setting
    Given the following "users" exist:
      | username | firstname | lastname |
      | user1    | User      | 1        |
    And the following "course enrolments" exist:
      | user  | course       | role    |
      | user1 | sandbox-test | student |
    And the course "sandbox-test" is backed up to the sandbox course backup file "sandbox-test.mbz"
    And the following config values are set as admin:
      | config                           | value         | plugin        |
      | restore_general_users            | 1             | local_sandbox |
      | restore_general_role_assignments | <globalvalue> | restore       |
      | restore_general_role_assignments | <pluginvalue> | local_sandbox |
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to course participants
    Then I should see "<role>" in the "User 1" "table_row"

    Examples:
      | globalvalue | pluginvalue | role     |
      | 0           | 0           | No roles |
      | 0           | 1           | Student  |
      | 1           | 0           | No roles |
      | 1           | 1           | Student  |

  Scenario Outline: Enable "Include activities and resources" setting
    Given the following config values are set as admin:
      | config                     | value         | plugin        |
      | restore_general_activities | <globalvalue> | restore       |
      | restore_general_activities | <pluginvalue> | local_sandbox |
    When I log in as "admin"
    And I am on "Sandbox Test Course" course homepage with editing mode on
    And I should see "Welcome to your sandbox course."
    And I delete "Welcome to your sandbox course." activity
    And I run the scheduled task "local_sandbox\task\restore_courses"
    And I am on "Sandbox Test Course" course homepage
    Then I <shouldornot> see "Welcome to your sandbox course."

    Examples:
      | globalvalue | pluginvalue | shouldornot |
      | 0           | 0           | should not  |
      | 0           | 1           | should      |
      | 1           | 0           | should not  |
      | 1           | 1           | should      |

  Scenario Outline: Enable "Include blocks" setting
    Given the following "blocks" exist:
      | blockname    | contextlevel | reference    | pagetypepattern | defaultregion |
      | online_users | Course       | sandbox-test | course-view-*   | side-pre      |
    And the course "sandbox-test" is backed up to the sandbox course backup file "sandbox-test.mbz"
    And the following config values are set as admin:
      | config                 | value         | plugin        |
      | restore_general_blocks | <globalvalue> | restore       |
      | restore_general_blocks | <pluginvalue> | local_sandbox |
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    Then "Online users" "block" <shouldornot> exist

    Examples:
      | globalvalue | pluginvalue | shouldornot |
      | 0           | 0           | should not  |
      | 0           | 1           | should      |
      | 1           | 0           | should not  |
      | 1           | 1           | should      |

  Scenario Outline: Enable "Include filters" setting
    Given the "activitynames" filter is "on"
    And I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to "Filters" in current page administration
    And I set the field "menuactivitynames" to "Off"
    And I press "Save changes"
    And the course "sandbox-test" is backed up to the sandbox course backup file "sandbox-test.mbz"
    And the following config values are set as admin:
      | config                  | value         | plugin        |
      | restore_general_filters | <globalvalue> | restore       |
      | restore_general_filters | <pluginvalue> | local_sandbox |
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to "Filters" in current page administration
    Then the field "menuactivitynames" matches value "<filterstate>"

    Examples:
      | globalvalue | pluginvalue | filterstate  |
      | 0           | 0           | Default (On) |
      | 0           | 1           | Off          |
      | 1           | 0           | Default (On) |
      | 1           | 1           | Off          |

  Scenario Outline: Enable "Include comments" setting
    Given I log in as "admin"
    And the following "core_comment > Comments" exist:
      | contextlevel | reference    | component      | area          | content                        |
      | Course       | sandbox-test | block_comments | page_comments | This is a sandbox test comment |
    And the course "sandbox-test" is backed up to the sandbox course backup file "sandbox-test.mbz"
    And the following config values are set as admin:
      | config                   | value         | plugin        |
      # Comments can only be restored together with the users.
      | restore_general_users    | 1             | local_sandbox |
      | restore_general_comments | <globalvalue> | restore       |
      | restore_general_comments | <pluginvalue> | local_sandbox |
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I navigate to "Reports > Comments" in site administration
    Then I <shouldornot> see "This is a sandbox test comment"

    Examples:
      | globalvalue | pluginvalue | shouldornot |
      | 0           | 0           | should not  |
      | 0           | 1           | should      |
      | 1           | 0           | should not  |
      | 1           | 1           | should      |

  Scenario Outline: Enable "Include badges" setting
    Given the following "core_badges > Badges" exist:
      | name               | course       | description       | image                        | status | type |
      | Sandbox test badge | sandbox-test | Badge description | badges/tests/behat/badge.png | active | 2    |
    And the course "sandbox-test" is backed up to the sandbox course backup file "sandbox-test.mbz"
    And the following config values are set as admin:
      | config                     | value         | plugin        |
      # Badges can only be restored together with the users and the activities.
      | restore_general_users      | 1             | local_sandbox |
      | restore_general_activities | 1             | local_sandbox |
      | restore_general_badges     | <globalvalue> | restore       |
      | restore_general_badges     | <pluginvalue> | local_sandbox |
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to "Badges" in current page administration
    Then I <shouldornot> see "Sandbox test badge"

    Examples:
      | globalvalue | pluginvalue | shouldornot |
      | 0           | 0           | should not  |
      | 0           | 1           | should      |
      | 1           | 0           | should not  |
      | 1           | 1           | should      |

  Scenario Outline: Enable "Include calendar events" setting
    Given the following "events" exist:
      | name               | eventtype | course       | timestart    |
      | Sandbox test event | course    | sandbox-test | ##tomorrow## |
    And the course "sandbox-test" is backed up to the sandbox course backup file "sandbox-test.mbz"
    And the following config values are set as admin:
      | config                         | value         | plugin        |
      | restore_general_calendarevents | <globalvalue> | restore       |
      | restore_general_calendarevents | <pluginvalue> | local_sandbox |
      # The admin is not enrolled in the course, so the admin has to be allowed to see all events.
      | calendar_adminseesall          | 1             |               |
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I log in as "admin"
    And I am viewing calendar in "upcoming" view
    Then I <shouldornot> see "Sandbox test event"

    Examples:
      | globalvalue | pluginvalue | shouldornot |
      | 0           | 0           | should not  |
      | 0           | 1           | should      |
      | 1           | 0           | should not  |
      | 1           | 1           | should      |

  Scenario Outline: Enable "Include user completion information" setting
    Given the following "users" exist:
      | username | firstname | lastname |
      | user1    | User      | 1        |
    And the following "course enrolments" exist:
      | user  | course       | role    |
      | user1 | sandbox-test | student |
    And the following "activities" exist:
      | activity | course       | name              | completion |
      | page     | sandbox-test | Sandbox test page | 1          |
    And I am on the "Sandbox test page" "page activity" page logged in as "user1"
    And I toggle the manual completion state of "Sandbox test page"
    And I log out
    And the course "sandbox-test" is backed up to the sandbox course backup file "sandbox-test.mbz"
    And the following config values are set as admin:
      | config                           | value         | plugin        |
      # The user completion information can only be restored together with the users and the activities.
      # And the user is only shown on the activity completion report if the student role is restored as well.
      | restore_general_users            | 1             | local_sandbox |
      | restore_general_role_assignments | 1             | local_sandbox |
      | restore_general_activities       | 1             | local_sandbox |
      | restore_general_userscompletion  | <globalvalue> | restore       |
      | restore_general_userscompletion  | <pluginvalue> | local_sandbox |
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    Then "User 1" user <completionstate> "Sandbox test page" activity

    Examples:
      | globalvalue | pluginvalue | completionstate   |
      | 0           | 0           | has not completed |
      | 0           | 1           | has completed     |
      | 1           | 0           | has not completed |
      | 1           | 1           | has completed     |

  Scenario Outline: Enable "Include logs" setting
    Given the following "users" exist:
      | username | firstname | lastname |
      | user1    | User      | 1        |
    And the following "course enrolments" exist:
      | user  | course       | role    |
      | user1 | sandbox-test | student |
    And I am on the "Sandbox Test Course" course page logged in as "user1"
    And I log out
    And the course "sandbox-test" is backed up to the sandbox course backup file "sandbox-test.mbz"
    And the following config values are set as admin:
      | config                | value         | plugin        |
      # The logs can only be restored together with the users.
      | restore_general_users | 1             | local_sandbox |
      | restore_general_logs  | <globalvalue> | restore       |
      | restore_general_logs  | <pluginvalue> | local_sandbox |
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to "Reports > Logs" in current page administration
    And I set the field "menuuser" to "User 1"
    And I press "Get these logs"
    Then I <shouldornot> see "Course viewed"

    Examples:
      | globalvalue | pluginvalue | shouldornot |
      | 0           | 0           | should not  |
      | 0           | 1           | should      |
      | 1           | 0           | should not  |
      | 1           | 1           | should      |

  Scenario Outline: Enable "Include histories" setting
    Given the following "users" exist:
      | username | firstname | lastname |
      | user1    | User      | 1        |
    And the following "course enrolments" exist:
      | user  | course       | role    |
      | user1 | sandbox-test | student |
    And the following "grade items" exist:
      | itemname          | course       |
      | Sandbox test item | sandbox-test |
    And the following "grade grades" exist:
      | gradeitem         | user  | grade |
      | Sandbox test item | user1 | 42.00 |
    And the course "sandbox-test" is backed up to the sandbox course backup file "sandbox-test.mbz"
    And the following config values are set as admin:
      | config                     | value         | plugin        |
      # The grade histories can only be restored together with the users and the activities.
      | restore_general_users      | 1             | local_sandbox |
      | restore_general_activities | 1             | local_sandbox |
      | restore_general_histories  | <globalvalue> | restore       |
      | restore_general_histories  | <pluginvalue> | local_sandbox |
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I log in as "admin"
    And I am on "Sandbox Test Course" course homepage
    And I navigate to "View > Grade history" in the course gradebook
    And I press "Submit"
    Then the following <shouldornot> exist in the "gradereport_history" table:
      | Grade item        | Revised grade |
      | Sandbox test item | 42.00         |

    Examples:
      | globalvalue | pluginvalue | shouldornot |
      | 0           | 0           | should not  |
      | 0           | 1           | should      |
      | 1           | 0           | should not  |
      | 1           | 1           | should      |

  Scenario Outline: Enable "Include groups and groupings" setting
    Given the following "groups" exist:
      | name               | course       | idnumber |
      | Sandbox test group | sandbox-test | G1       |
    And the course "sandbox-test" is backed up to the sandbox course backup file "sandbox-test.mbz"
    And the following config values are set as admin:
      | config                 | value         | plugin        |
      | restore_general_groups | <globalvalue> | restore       |
      | restore_general_groups | <pluginvalue> | local_sandbox |
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I log in as "admin"
    And I am on the "Sandbox Test Course" "groups" page
    Then I <shouldornot> see "Sandbox test group"

    Examples:
      | globalvalue | pluginvalue | shouldornot |
      | 0           | 0           | should not  |
      | 0           | 1           | should      |
      | 1           | 0           | should not  |
      | 1           | 1           | should      |

  Scenario Outline: Enable "Include competencies" setting
    Given the following "core_competency > frameworks" exist:
      | shortname              | idnumber |
      | Sandbox test framework | SF1      |
    And the following "core_competency > competencies" exist:
      | shortname               | idnumber | competencyframework |
      | Sandbox test competency | SC1      | SF1                 |
    And the following "core_competency > course_competencies" exist:
      | course       | competency |
      | sandbox-test | SC1        |
    And the course "sandbox-test" is backed up to the sandbox course backup file "sandbox-test.mbz"
    And the following config values are set as admin:
      | config                       | value         | plugin        |
      | restore_general_competencies | <globalvalue> | restore       |
      | restore_general_competencies | <pluginvalue> | local_sandbox |
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I log in as "admin"
    And I am on the "Sandbox Test Course" "tool_lp > course competencies" page
    Then I <shouldornot> see "Sandbox test competency"

    Examples:
      | globalvalue | pluginvalue | shouldornot |
      | 0           | 0           | should not  |
      | 0           | 1           | should      |
      | 1           | 0           | should not  |
      | 1           | 1           | should      |

  # This setting is also tested with PHPUnit (see test_email_recipients() in tests/task/restore_courses_test.php).
  Scenario Outline: Enable "Email failures to" setting
    Given an email catcher server is configured
    And the following config values are set as admin:
      | config      | value | plugin        |
      | notifylevel | 1     | local_sandbox |
    And I log in as "admin"
    And I navigate to "Courses > Sandbox" in site administration
    And I set the field "Email failures to" to "<recipient>"
    And I press "Save changes"
    And I empty the email inbox
    When I run the scheduled task "local_sandbox\task\restore_courses"
    # The successful restore results in a notice for the restored course and a notice with the number of restored courses.
    Then user "admin" should have <emailcount> emails

    Examples:
      | recipient  | emailcount |
      | Nobody     | 0          |
      | Admin User | 2          |

  # This setting is also tested with PHPUnit (see test_email_threshold() in tests/task/restore_courses_test.php).
  Scenario Outline: Enable "Email Threshold" setting
    Given an email catcher server is configured
    # A course backup file without a matching course results in a warning.
    And the course "sandbox-test" is backed up to the sandbox course backup file "nonexisting.mbz"
    And I log in as "admin"
    And I navigate to "Courses > Sandbox" in site administration
    And I set the following fields to these values:
      | Email failures to | Admin User  |
      | Email Threshold   | <threshold> |
    And I press "Save changes"
    And I empty the email inbox
    When I run the scheduled task "local_sandbox\task\restore_courses"
    # The task sends two notices for the restored course and a warning for the skipped course backup file.
    # It does not send any notifications with the error level.
    Then user "admin" should have <emailcount> emails

    Examples:
      | threshold | emailcount |
      | Notice    | 3          |
      | Warning   | 1          |
      | Error     | 0          |

  # The delivery of the notifications through the message provider is also tested with PHPUnit
  # (see test_notification_message_provider() in tests/task/restore_courses_test.php).
  Scenario: Show the notifications in the notification popover
    Given the following config values are set as admin:
      | config      | value | plugin        |
      | notifylevel | 1     | local_sandbox |
    And I log in as "admin"
    And I navigate to "Courses > Sandbox" in site administration
    And I set the field "Email failures to" to "Admin User"
    And I press "Save changes"
    When I run the scheduled task "local_sandbox\task\restore_courses"
    And I reload the page
    And I open the notification popover
    Then I should see "NOTICE: Sandbox" in the "#nav-notification-popover-container" "css_element"
    # The popover lists the newest notification first, which is the notice with the number of restored courses
    # as this is sent by the task at the very end.
    And I click on "View full notification" "link" in the "#nav-notification-popover-container" "css_element"
    And I should see "NOTICE: Sandbox has restored 1 courses" in the "[data-region='notification-area']" "css_element"
