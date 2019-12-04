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
 * Specialised restore task for the multiblock block
 *
 * TODO: Finish phpdocs
 */
class restore_multiblock_block_task extends restore_block_task {

    public function set_plan($plan) {
        parent::set_plan($plan);

        // So here we need to reorder the task list. Specifically, we need
        // to rearrange all instances of restore_block_task to put
        // instances of restore_multiblock_task_block first. And that means
        // we first have to get the task list, it's a protected property.
        $planclass = new ReflectionClass($plan);
        $taskproperty = $planclass->getProperty('tasks');
        $taskproperty->setAccessible(true);

        // So, this task (a multiblock) is the last task added. We need to splice it in.
        // First, break it off the list.
        $tasks = $taskproperty->getValue($plan);
        $thistask = array_pop($tasks);

        // Now we need to find which index contains the first instance of a block task.
        $index = null;
        foreach ($tasks as $id => $task) {
            if ($task instanceof restore_block_task) {
                $index = $id;
                break;
            }
        }

        if ($index !== null) {
            // Splice the item at the front of the queue. This should be fine in all backup cases
            // because in all backup types, the blocks are after the root/course/activity tasks.
            $tasks = array_merge(array_slice($tasks, 0, $index), [$thistask], array_slice($tasks, $index));
        } else {
            // Slightly bizarrely, there's no other blocks in the tasklist, just put this back.
            $tasks[] = $thistask;
        }
        
        // And put the queue back.
        $taskproperty->setValue($plan, $tasks);
    }

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
    }

    public function get_fileareas() {
    }

    public function get_configdata_encoded_attributes() {
    }

    static public function define_decode_contents() {
        return array();
    }

    static public function define_decode_rules() {
        return array();
    }
}
