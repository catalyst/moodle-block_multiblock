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
 * Steps definitions related with blocks.
 *
 * @package   block_multiblock
 * @copyright 2020 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Blocks management steps definitions.
 *
 * @package   block_multiblock
 * @copyright 2020 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_multiblock extends behat_base {
    /** @var object|null HTML/Text object based on moodle version. */
    private $htmlblock = null;

    /**
     * Return HTML/Text object based on moodle version
     *
     * @return object
     */
    private function htmlblock() {
        if (!empty($this->htmlblock)) {
            return $this->htmlblock;
        }

        global $CFG;
        $newblockname = $CFG->branch >= 400;

        $htmlblock = new stdClass;
        $htmlblock->name = $newblockname ? 'Text' : 'HTML';
        $htmlblock->defaulttitle = $newblockname ? '(new text block)' : '(new HTML block)';

        $this->htmlblock = $htmlblock;

        return $htmlblock;
    }

    /**
     * Clicks on "Manage ... contents" for specified block. Page must be in editing mode.
     *
     * Argument block_name may be either the name of the block or CSS class of the block.
     *
     * @Given /^I manage the contents of "(?P<block_name_string>(?:[^"]|\\")*)" block$/
     * @param string $blockname
     */
    public function i_manage_the_contents_of_block($blockname) {
        // Problem 1, the block name might be the name or the CSS.
        // Problem 2, the string conceivably could be 'Manage  contents' if the block name is empty.
        // As such we need to use what we do have of it.

        if (in_array($blockname, ["Text", "HTML"])) {
            $htmlblock = $this->htmlblock();
            $blockname = $htmlblock->name;
        }

        $this->execute("behat_blocks::i_open_the_blocks_action_menu", $this->escape($blockname));

        $this->execute('behat_general::i_click_on_in_the',
            array("Manage", "link", $this->escape($blockname), "block")
        );
    }

    /**
     * Adds an Text/HTML subblock in the manage contents page of a multiblock.
     *
     * @Given /^I add the HTML block field$/
     */
    public function i_add_the_html_block_field() {
        $block = $this->htmlblock();

        $this->execute("behat_forms::i_set_the_field_to", ["Add a block", $this->escape($block->name)]);
        $this->execute("behat_general::i_click_on_in_the", ["input[value=Add]", "css_element", "[role=main]", "css_element"]);
    }

    /**
     * Changes the title of a Text/HTML block in its configuration page.
     *
     * Argument oldtitle is the current name of the block.
     * Argument newtitle is the new name of the block.
     *
     * @Given /^I set the title of the HTML block to "(?P<new_string>(?:[^"]|\\")*)"$/
     * @Given /^I set the title of the HTML block to "(?P<new_string>(?:[^"]|\\")*)" from "(?P<old_string>(?:[^"]|\\")*)"$/
     *
     * @param string $newtitle
     * @param string|false $oldtitle
     */
    public function i_set_the_title_of_the_html_block_to($newtitle, $oldtitle = false) {
        $block = $this->htmlblock();
        $titlefieldlabel = $block->name . ' block title';
        $oldtitle = $oldtitle ?: $block->defaulttitle;

        $this->execute("behat_general::i_click_on_in_the", ["Settings", "link", $oldtitle, "table_row"]);
        $this->execute("behat_forms::i_set_the_field_to", [$this->escape($titlefieldlabel), $this->escape($newtitle)]);
    }

    /**
     * Enables editing mode for when you are on the dashboard.
     *
     * @Given /^I enable editing mode whilst on the dashboard$/
     */
    public function i_enable_editing_mode_whilst_on_the_dashboard() {
        global $CFG;
        if ($CFG->branch >= 400) {
            $this->execute("behat_navigation::i_turn_editing_mode_on");
        } else {
            $this->execute(
                'behat_general::i_click_on_in_the',
                [
                    'Customise this page',
                    'button',
                    "[id='page-header']",
                    'css_element'
                ]
            );
        }
    }
}
