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
 * Form for editing a multiblock subblock, specific to Totara.
 *
 * @package   block_multiblock
 * @copyright 2020 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_multiblock\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for editing a multiblock subblock, specific to Totara.
 *
 * The Moodle version keeps it tidy by just overriding the definition
 * method but this isn't possible in Totara.
 *
 * @package   block_multiblock
 * @copyright 2020 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editblock_totara extends editblock_base {

    /**
     * Fix the form definition so we can actually have sub-block configuration.
     */
    public function definition_after_data() {
        $mform =& $this->_form;

        // Fix up the fields that need to be present but whose content should be hidden.
        $thingstohide = [
            'cs_enable_hiding',
            'cs_enable_docking',
            'cs_show_header',
            'cs_show_border',
            'bui_contexts',
            'bui_pagetypepattern',
            'bui_staticpagetypepattern',
            'bui_subpagepattern',
            'bui_defaultregion',
            'bui_defaultweight',
            'bui_visible',
            'bui_region',
            'bui_weight',
        ];

        foreach ($thingstohide as $element) {
            if (!empty($mform->_elementIndex[$element])) {
                $value = $mform->getElementValue($element);
                $mform->removeElement($element);
                $mform->addElement('hidden', $element, $value);
            }
        }

        // Just remove the things we don't care about.
        $thingstoremove = [
            'displayconfig',
            'bui_homecontext',
            'whereheader',
            'onthispage',
            'pagetypewarning',
            'restrictpagetypes',
            'submitbutton',
            'cancelbutton',
            'buttonar',
        ];
        foreach ($thingstoremove as $element) {
            if (!empty($mform->_elementIndex[$element])) {
                $mform->removeElement($element);
            }
        }

        $this->add_save_buttons();
    }
}
