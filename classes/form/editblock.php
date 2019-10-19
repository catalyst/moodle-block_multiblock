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
 * Manage multiblock instances.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_multiblock\form;
use ReflectionMethod;
use moodleform;
use block_multiblock_proxy_edit_form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for editing a multiblock subblock.
 */
class editblock extends block_multiblock_proxy_edit_form {
    public $block;
    public $blockparent;
    public $page;

    public function __construct($actionurl, $block, $page) {
        global $CFG;
        $this->block = $block->blockinstance;
        $this->block->instance->visible = true;
        $this->block->instance->region = $this->block->instance->defaultregion;
        $this->block->instance->weight = $this->block->instance->defaultweight;
        $this->page = $page;

        if (!empty($this->block->configdata)) {
            $this->block->config = @unserialize(base64_decode($this->block->configdata));
        }

        parent::__construct($actionurl, $this->block, $this->page);
    }

    public function definition() {
        global $CFG;

        $mform =& $this->_form;

        $this->specific_definition($mform);

        $this->add_action_buttons();
    }
}
