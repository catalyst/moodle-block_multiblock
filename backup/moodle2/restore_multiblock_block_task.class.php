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
 * Restoration steps for the multiblock plugin.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Specialised restore task for the multiblock block
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_multiblock_block_task extends restore_block_task {

    /**
     * Injects the restore plan into this task.
     *
     * While receiving the dependency of the restore_plan, rearrange
     * the queue to make sure multiblock is processed first.
     *
     * @param restore_plan $plan The restore plan for this restore job.
     */
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

    /**
     * Mandatory function for defining processing settings on restore.
     */
    protected function define_my_settings() {
    }

    /**
     * Mandatory function for defining processing steps on restore.
     *
     * Moodle actually inadvertantly handles all the steps automatically.
     */
    protected function define_my_steps() {
    }

    /**
     * Mandatory function for handling file areas on restoration.
     *
     * @return array A list of file areas handled by this plugin.
     */
    public function get_fileareas() {
        return [];
    }

    /**
     * Return a list of attributes that requires decoding during restore.
     *
     * @return array A list of attributes ot be fixed.
     */
    public function get_configdata_encoded_attributes() {
        return [];
    }

    /**
     * Return a list of contents for the plugin that will need to be decoded during restore.
     *
     * @return array A list of content items to be decoded.
     */
    public static function define_decode_contents() {
        return array();
    }

    /**
     * Return a list of find/replace rules to decode content during restore.
     *
     * @return array A list of find/replace rules.
     */
    public static function define_decode_rules() {
        return array();
    }
}
