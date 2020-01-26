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

        $this->execute("behat_blocks::i_open_the_blocks_action_menu", $this->escape($blockname));

        $this->execute('behat_general::i_click_on_in_the',
            array("Manage", "link", $this->escape($blockname), "block")
        );
    }
}
