@block @block_multiblock
Feature: The block can have administrator set defaults
    In order to be customize the multiblock
    As an admin
    I need to be able to assign some site wide defaults
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher@example.com  |
      | student1 | Student   | User     | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: Test setting the multiblock title.
    Given the following config values are set as admin:
      | title          | Course Toolkit | block_multiblock |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Multiblock" block
    Then I should see "Course Toolkit"

  Scenario: Test setting the multiblock presnetation style.
    Given the following config values are set as admin:
      | presentation   | 0                 | block_multiblock |
      | subblock       | calendar_month    | block_multiblock |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Multiblock" block
    Then ".multiblock.multiblock-accordion" "css_element" should exist

  Scenario: Test setting the default blocks.
    Given the following config values are set as admin:
      | subblock   | calendar_month    | block_multiblock |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Multiblock" block
    Then "Calendar" "text" should exist in the "Multiblock" "block"
