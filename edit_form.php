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
 * Form for editing multiblock parent block instances.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Form for editing multiblock parent block instances.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_multiblock_edit_form extends block_edit_form {

    /**
     * Adds the specific settings from this block to the general block editing form.
     *
     * @param moodleform $mform The block editing form object.
     */
    protected function specific_definition($mform) {
        global $CFG;
        // Let's have some configuration!
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // First, the block's title.
        $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_multiblock'));
        $mform->setType('config_title', PARAM_TEXT);
        $mform->setDefault('config_title', get_config('block_multiblock', 'title'));

        // Then which presentation format we want to use.
        $presentations = block_multiblock::get_valid_presentations();
        $options = [];
        foreach ($presentations as $presentationid => $presentation) {
            $suggested = $presentation->get_suggested_use();
            if (!in_array($suggested, ['main', 'sidebar'])) {
                continue;
            }

            $suggestedstring = get_string('layout:' . $suggested, 'block_multiblock');

            if (!isset($options[$suggestedstring])) {
                $options[$suggestedstring] = [];
            }
            $options[$suggestedstring][$presentationid] = $presentation->get_name();
        }
        $mform->addElement('selectgroups', 'config_presentation', get_string('presentation', 'block_multiblock'), $options);
        $mform->setDefault('config_presentation', block_multiblock::get_default_presentation());
    }
}
