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
 * Form for adding a block to a multiblock instance.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_multiblock\form;
use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for adding a block to a multiblock instance.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class addblock extends moodleform {
    public $blocklist = [];

    public function definition() {
        $mform =& $this->_form;

        if (!empty($this->_customdata['id'])) {
            $this->set_block_list($this->_customdata['id']);
        }
        if (empty($this->blocklist)) {
            return;
        }

        $group = [];
        $group[] = &$mform->createElement('select', 'addblock', get_string('addblock'), $this->blocklist);
        $group[] = &$mform->createElement('submit', 'submitbutton', get_string('add'));
        $mform->addGroup($group, 'addblock', '', [' '], false);
    }

    public function set_block_list($instanceid) {
        global $DB, $PAGE;

        $this->blocklist = [
            '' => get_string('selectblock', 'block_multiblock'),
        ];

        foreach ($PAGE->blocks->get_addable_blocks() as $block) {
            if ($block->name == 'multiblock') {
                continue;
            }

            $this->blocklist[$block->name] = trim($block->title) ? trim($block->title) : '[block_' . $block->name . ']';
        }
    }
}
