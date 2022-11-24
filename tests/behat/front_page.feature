@block @block_multiblock @javascript
Feature: Front page test
    In order to customise the front page
    As a teacher
    I need to be able to put multiple blocks in a single space

  Scenario: Adding a Multiblock on the front page
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | User     | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    When I log in as "admin"
    And I am on site homepage
    And I turn editing mode on
    And I add the "Multiblock" block
    And I manage the contents of "Multiblock" block
    And I should see "This multiblock has no blocks inside it."
    And I expand all fieldsets
    And I set the field "Add a block" to "Logged in user"
    And I click on "input[value=Add]" "css_element"
    And I should see "Manage Multiblock contents"
    And I should see "Logged in user"
    And I expand all fieldsets
    And I set the field "Add a block" to "Recent activity"
    And I click on "input[value=Add]" "css_element"
    And I log out
    And I log in as "student1"
    And I am on site homepage
    Then I should see "Multiblock"
    And I should see "Logged in user"
    And I should see "Recent activity"
