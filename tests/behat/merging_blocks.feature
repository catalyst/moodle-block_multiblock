@block @block_multiblock
Feature: Accordion layout
    In order to improve existing content
    As a teacher
    I need to be able to merge existing blocks into a newly made multiblock

  Scenario: Creating a block and then moving it into a multiblock
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
    And I add the "Logged in user" block
    And I add the "Multiblock" block
    And I should see "Teacher One"
    And I should not see "Teacher One" in the ".block_multiblock" "css_element"
    And I manage the contents of "Multiblock" block
    And I expand all fieldsets
    And I set the field "Move existing block" to "Logged in user"
    And I click on "input[value=Move]" "css_element"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Multiblock"
    And I should see "Student User" in the ".block_multiblock" "css_element"
