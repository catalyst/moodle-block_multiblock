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
 * @copyright 2022 University of Bath
 * @author    James Pearce <jmp201@bath.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_multiblock;
use block_multiblock\helper;

/**
 * Library for adding a block to a multiblock instance.
 *
 * @package   block_multiblock
 */
class adddefaultblock {
    /** @var array Storage of the block name -> block description of possibly addable sub-blocks. */
    public $blocklist = [];

    /**
     * Initialise the adding of a block to the multiblock.
     *
     * @param int $id is the id of the multiblock
     * @param array $arraytoadd is an array of blocks to add
     * @param object $multiblockinstance is the parent multiblock instance
     */
    public function init($id, $arraytoadd, $multiblockinstance) {

        $this->set_blocks_to_add($id, $arraytoadd, $multiblockinstance);

    }

    /**
     * This checks the blocks that can bee added to a multiblock,
     * selects the ones defined in the current default blocks setting and creates an instance of that new block
     * then moves it to the multiblock.
     *
     * @param int $blockid is the id of the multiblock
     * @param array $arraytoadd is an array of blocks to add
     * @param object $multiblockinstance is the parent multiblock instance
     */
    public function set_blocks_to_add($blockid, $arraytoadd, $multiblockinstance) {
        global $DB, $PAGE;

        // Load all possible blocks for the page.
        $blockmanager = $PAGE->blocks;
        $blockmanager->load_blocks();

        // Loop over the $arraytoadd to find the blocks to add, within the addable blocks.
        foreach ($arraytoadd as $toadd) {
            foreach ($blockmanager->get_addable_blocks() as $block) {
                if ($block->name == 'multiblock') {
                    continue;
                }
                // Found a block to add.
                if ($toadd == $block->name) {
                    // Add the block to the block list.
                    $this->blocklist[$block->name] = $block;

                    // Create a new instance of the block to add. This will put the new default blocks in the side-pre region.
                    $blockmanager->add_block($toadd, 'side-pre', 10, false);

                    $addedblocks = $DB->get_records('block_instances', ['parentcontextid' => $multiblockinstance->parentcontextid]);

                    foreach ($addedblocks as $addedblock) {
                        if ($addedblock->blockname == $toadd) {

                            // Move the newly created blocks into the multiblock.
                            helper::move_block($addedblock->id, $blockid);
                        }
                    }
                }
            }
        }
    }
}
