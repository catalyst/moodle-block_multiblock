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
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Specialised backup task for the multiblock block.
 * This is primarily about backing up the child blocks.
 */
class backup_multiblock_block_task extends backup_block_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        global $DB;

        // Find all the sub-blocks so we can add them to the backup plan.
        $subblocks = $DB->get_records('block_instances', ['parentcontextid' => $this->get_contextid()]);

        // Before we add anything to the plan, we need to sort out the progress meter.
        // The issue is, we're adding tasks to the progress queue but the size of the queue
        // for the progress meter was set up before we started the queue, so we have to fit it.
        // Unfortunately, it's a protected array inside the progress instance, so better
        // pop the lid and get ourselves access to it with Reflection.
        $progress = $this->get_progress();
        $progressclass = new ReflectionClass($progress);
        $progressproperty = $progressclass->getProperty('maxes');
        $progressproperty->setAccessible(true);
        $maxes = $progressproperty->getValue($progress);
        $maxes[count($maxes)-1] += count($subblocks);
        $progressproperty->setValue($progress, $maxes);

        foreach (array_keys($subblocks) as $blockid) {
            // Only Moodle2 format backups support blocks, not that the backup block task cares anyway.
            $task = backup_factory::get_backup_block_task(backup::FORMAT_MOODLE, $blockid);

            // Add it to the plan, then run the task for each sub-block.
            $this->plan->add_task($task);
            $task->build();
            $task->execute();
        }
    }

    public function get_fileareas() {
        return array(); // No associated fileareas
    }

    public function get_configdata_encoded_attributes() {
        return array(); // No special handling of configdata
    }

    static public function encode_content_links($content) {
        return $content; // No special encoding of links
    }
}

