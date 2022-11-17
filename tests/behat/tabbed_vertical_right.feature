@block @block_multiblock @javascript
Feature: Vertical tabbed layout (right)
    In order to streamline course presentation
    As a teacher
    I need to be able to focus content with a vertical tabbed layout

  Scenario: Testing vertical tabbed layout (right)
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher@example.com  |
    When I log in as "teacher1"
    And I enable editing mode whilst on the dashboard
    # The usual 'And I add "Multiblock" block' step can fail in JS with lots of blocks present.
    And I add the "Multiblock" block
    And I configure the "Multiblock" block
    And I expand all fieldsets
    And I set the field "Multiblock presentation style" to "Vertical Tabs (Right)"
    And I set the field "Region" to "content"
    And I press "Save changes"
    And I manage the contents of "Multiblock" block
    And I expand all fieldsets
    And I add the HTML block field
    And I set the title of the HTML block to "First Item"
    And I set the field "Content" to "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."
    And I press "Save and return to manage"
    And I expand all fieldsets
    And I add the HTML block field
    And I set the title of the HTML block to "Second Item"
    And I set the field "Content" to "Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat."
    And I press "Save and display"
    Then I should see "First Item" in the ".block_multiblock" "css_element"
    And I should see "Second Item" in the ".block_multiblock" "css_element"
    And I should see "Lorem ipsum dolor sit" in the ".block_multiblock" "css_element"
    And I should not see "Ut enim ad minim veniam" in the ".block_multiblock" "css_element"
    And "Lorem ipsum" "text" should appear before "First Item" "link"
    And I click on "Second Item" "link"
    And I should not see "Lorem ipsum dolor sit" in the ".block_multiblock" "css_element"
    And I should see "Ut enim ad minim veniam" in the ".block_multiblock" "css_element"
