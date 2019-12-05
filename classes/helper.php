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
 * Supporting infrastructure for the multiblock editing.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_multiblock;

use context_block;

defined('MOODLE_INTERNAL') || die();

/**
 * Supporting infrastructure for the multiblock editing.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Provide some functionality for bootstrapping the page for a given block.
     *
     * Namely: load the block instance, some $PAGE setup, navigation setup.
     *
     * @param int $blockid The block ID being operated on.
     * @return array Return the block record and its instance class.
     */
    public static function bootstrap_page($blockid) {
        global $DB, $PAGE;

        $blockctx = context_block::instance($blockid);
        $block = $DB->get_record('block_instances', ['id' => $blockid], '*', MUST_EXIST);
        if (block_load_class($block->blockname)) {
            $class = 'block_' . $block->blockname;
            $blockinstance = new $class;
            $blockinstance->_load_instance($block, $PAGE);
        }

        $PAGE->set_context($blockctx);
        $PAGE->set_url($blockctx->get_url());
        $PAGE->set_pagelayout('admin');

        return [$block, $blockinstance];
    }
}
