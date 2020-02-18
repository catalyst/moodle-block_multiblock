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
 * Form for editing a multiblock subblock.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_multiblock\form;
use block_multiblock;
use block_multiblock\form\editblock_constructor;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for editing a multiblock subblock in Moodle.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editblock extends editblock_base {

    /**
     * Sets up the form definition - this will intentionally override the normal block
     * block configuration so we only get the parts specific to the subblock.
     */
    public function definition() {
        $mform =& $this->_form;

        $this->specific_definition($mform);

        if (!empty($this->multiblock) && $mform->elementExists('config_title')) {
            $presentations = block_multiblock::get_valid_presentations();
            $defaultconfig = (object) ['presentation' => block_multiblock::get_default_presentation()];
            $config = !empty($this->multiblock->config) ? $this->multiblock->config : $defaultconfig;
            if ($presentations[$config->presentation]->requires_title()) {
                $requiredmsg = get_string('requirestitle', 'block_multiblock', $this->multiblock->get_title());
                $mform->addRule('config_title', $requiredmsg, 'required', null, 'client');
            }
        }

        $this->add_save_buttons();
    }
}
