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
 * Settings for the Moodle Multiblock.
 *
 * @package   block_multiblock
 * @copyright 2021 Muhammad Ali <ma2716@bath.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    global $DB, $PAGE, $CFG;

    $blocks = $DB->get_records('block', array('visible' => 1), 'name ASC');

    // Multiblock title (heading).
    $settings->add(new admin_setting_configtext('block_multiblock/title', get_string('multiblock_title', 'block_multiblock'),
    get_string('multiblock_title_desc', 'block_multiblock'), "",
    PARAM_TEXT));

    // Multiblock presentation style options array.
    $multiblockpresentationoptions = array();
    $presentations = block_multiblock::get_valid_presentations();
    foreach ($presentations as $presentationid => $presentation) {
        array_push($multiblockpresentationoptions, $presentationid);
    }
    // Multiblock presentation style.
    $settings->add(new admin_setting_configselect('block_multiblock/presentation',
    get_string('multiblock_presentation_style', 'block_multiblock'),
    get_string('multiblock_presentation_style_desc', 'block_multiblock'), 7, $multiblockpresentationoptions));

    // Multiblock - available sub-blocks.
    $blocklist = [];
    foreach ($blocks as $block) {
        if ($block->name == 'multiblock') {
            continue;
        }

        $blocklist[$block->name] = trim($block->name) ? trim($block->name) : '[block_' . $block->name . ']';
    }
    // Multiblock manage contents (add subblock).
    $settings->add(new admin_setting_configmultiselect('block_multiblock/subblock',
    get_string('multiblock_subblock', 'block_multiblock'),
    get_string('multiblock_subblock_desc', 'block_multiblock'), [1], $blocklist));

}
