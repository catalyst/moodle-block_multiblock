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
 * Multiblock uninstallation.
 *
 * @package   block_multiblock
 * @copyright 2020 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_multiblock\helper;

/**
 * Clean up multiblocks when uninstalling.
 *
 * Finds every multiblock instance and decomposes any blocks in them back to parent context.
 */
function xmldb_block_multiblock_uninstall() : bool {
    global $DB;

    // Set up the queries we're going to be using.
    $sql = "SELECT c.id AS contextid, bi.*
              FROM {context} c
              JOIN {block_instances} bi ON bi.id = c.instanceid AND c.contextlevel = :contextlevel
             WHERE bi.blockname = :blockname";

    $params = [
        'blockname' => 'multiblock',
        'contextlevel' => CONTEXT_BLOCK,
    ];

    $childsql = "SELECT c.id AS contextid, bi.id AS blockid
                   FROM {context} c
                   JOIN {block_instances} bi ON bi.id = c.instanceid AND c.contextlevel = :contextlevel
                  WHERE bi.parentcontextid = :parentcontext";

    $multiblocks = $DB->get_records_sql($sql, $params);
    foreach ($multiblocks as $multiblock) {
        // Now we have the blocks, find each block's children.
        $childparams = [
            'contextlevel' => CONTEXT_BLOCK,
            'parentcontext' => $multiblock->contextid,
        ];
        $children = $DB->get_records_sql($childsql, $childparams);

        // And split each child out of the parent.
        foreach ($children as $childblock) {
            helper::split_block($multiblock, $childblock->blockid);
        }
    }

    return true;
}
