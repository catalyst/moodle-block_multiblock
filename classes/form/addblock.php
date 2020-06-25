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
use block_multiblock\helper;
use core_plugin_manager;
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
    /** @var array Storage of the block name -> block description of possibly addable sub-blocks. */
    public $blocklist = [];

    /**
     * Handles the general setup of the form for adding sub-blocks to a multiblock.
     */
    public function definition() {
        $mform =& $this->_form;

        if (!empty($this->_customdata['id'])) {
            $this->set_block_list();
        }
        if (empty($this->blocklist)) {
            return;
        }

        $mform->addElement('header', 'addnewblock', get_string('addnewblock', 'block_multiblock'));

        $group = [];
        $group[] = &$mform->createElement('select', 'addblock', get_string('addblock'), $this->blocklist);
        $group[] = &$mform->createElement('submit', 'addsubmit', get_string('add'));
        $mform->addGroup($group, 'addblockgroup', '', [' '], false);

        $siblings = $this->get_sibling_blocks($this->_customdata['id']);
        if (!empty($siblings)) {
            $siblings = ['' => get_string('selectblock', 'block_multiblock')] + $siblings;
            $mform->addElement('header', 'moveexistingblock', get_string('moveexistingblock', 'block_multiblock'));
            $mform->setExpanded('moveexistingblock', false);

            $siblinggroup = [];
            $siblinggroup[] = &$mform->createElement('select', 'moveblock',
                                                     get_string('moveexistingblock', 'block_multiblock'), $siblings);
            $siblinggroup[] = &$mform->createElement('submit', 'movesubmit', get_string('move'));
            $mform->addGroup($siblinggroup, 'siblinggroup', '', [' '], false);
        }
    }

    /**
     * Given the instance id of a multiblock, identify the possible addable blocks.
     */
    public function set_block_list() {
        global $DB, $PAGE;

        $this->blocklist = [
            '' => get_string('selectblock', 'block_multiblock'),
        ];

        helper::set_page_fake_url();
        $PAGE->blocks->load_blocks();
        foreach ($PAGE->blocks->get_addable_blocks() as $block) {
            if ($block->name == 'multiblock') {
                continue;
            }

            $this->blocklist[$block->name] = trim($block->title) ? trim($block->title) : '[block_' . $block->name . ']';
        }
        helper::set_page_real_url();
    }

    /**
     * Finds other blocks in the same place to be merged in.
     *
     * @param int $instanceid The instance of a multiblock to find other blocks in the same context.
     */
    public function get_sibling_blocks($instanceid) {
        global $DB;

        $available = [];
        $invalidstatuses = [
            core_plugin_manager::PLUGIN_STATUS_MISSING,
            core_plugin_manager::PLUGIN_STATUS_DOWNGRADE,
            core_plugin_manager::PLUGIN_STATUS_DELETE,
        ];
        foreach (core_plugin_manager::instance()->get_plugins_of_type('block') as $block) {
            if (!in_array($block->get_status(), $invalidstatuses)) {
                $available[] = $block->name;
            }
        }

        // First we have to find the block's parent context, then the blocks in that context.
        $record = $DB->get_record('block_instances', ['id' => $instanceid]);
        $siblings = $DB->get_records('block_instances', ['parentcontextid' => $record->parentcontextid]);
        // And remove the current block, we can't add ourselves to ourself now...
        unset ($siblings[$instanceid]);

        $blocks = [];
        foreach ($siblings as $instanceid => $sibling) {
            // Can't add a multiblock to itself in any universe.
            if ($sibling->blockname == 'multiblock') {
                continue;
            }

            // Make sure that the plugin actually exists.
            if (!in_array($sibling->blockname, $available)) {
                continue;
            }

            // Does it have a title?
            if (!empty($sibling->configdata)) {
                $config = unserialize(base64_decode($sibling->configdata));
                if (!empty($config->title)) {
                    $blocks[$instanceid] = $config->title . ' (' . get_string('pluginname', 'block_' . $sibling->blockname) . ')';
                    continue;
                }
            }

            // Add it to the list.
            $blocks[$instanceid] = get_string('pluginname', 'block_' . $sibling->blockname);
        }

        return $blocks;
    }
}
