@block @block_multiblock
Feature: Basic tests of multiple blocks
    In order to streamline course presentation
    As a teacher
    I need to be able to put multiple blocks in a single space

  Scenario: Adding a Course Toolkit with two sub-blocks
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
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Multiblock" block
    And I manage the contents of "Multiblock" block
    And I should see "This multiblock has no blocks inside it."
    And I set the field "Add a block" to "Logged in user"
    And I click on "input[value=Add]" "css_element"
    And I should see "Manage Multiblock contents"
    And I should see "Logged in user"
    And I set the field "Add a block" to "Recent activity"
    And I click on "input[value=Add]" "css_element"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Multiblock"
    And I should see "Logged in user"
    And I should see "Recent activity"
