@block @block_multiblock @javascript
Feature: Accordion
    In order to streamline course presentation
    As a teacher
    I need to be able to focus content with an accordion layout

    Scenario: Testing accordion layout
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
    # The usual 'And I add "Multiblock" block' step can fail in JS with lots of blocks present.
    And I select "Add a block" from flat navigation drawer
    And I click on "Multiblock" "link"
    And I configure the "Course Toolkit" block
    And I set the field "Multiblock presentation style" to "Accordion"
    And I press "Save changes"
    And I manage the contents of "Course Toolkit" block
    And I expand all fieldsets
    And I set the field "Add a block" to "HTML"
    # Selenium gets confused between the Add button and the "Add a new sub-block" header.
    And I click on "input[value=Add]" "css_element"
    And I click on "Settings" "link" in the "(new HTML block)" "table_row"
    And I set the field "HTML block title" to "First Item"
    And I set the field "Content" to "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."
    And I press "Save and return to manage"
    And I expand all fieldsets
    And I set the field "Add a block" to "HTML"
    # Selenium gets confused between the Add button and the "Add a new sub-block" header.
    And I click on "input[value=Add]" "css_element"
    And I click on "Settings" "link" in the "(new HTML block)" "table_row"
    And I set the field "HTML block title" to "Second Item"
    And I set the field "Content" to "Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat."
    And I press "Save and return to manage"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "First Item" in the ".block_multiblock" "css_element"
    And I should see "Second Item" in the ".block_multiblock" "css_element"
    And I should see "Lorem ipsum dolor sit" in the ".block_multiblock" "css_element"
    And I should not see "Ut enim ad minim veniam" in the ".block_multiblock" "css_element"
    And I press "Second Item"
    And I should not see "Lorem ipsum dolor sit" in the ".block_multiblock" "css_element"
    And I should see "Ut enim ad minim veniam" in the ".block_multiblock" "css_element"