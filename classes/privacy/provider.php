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
 * Privacy Subsystem implementation for block_multiblock.
 *
 * @package   block_multiblock
 * @copyright 2020 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_multiblock\privacy;

use context;
use context_block;
use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider as userlist_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\userlist;

/**
 * Privacy Subsystem implementation for block_multiblock.
 *
 * @package   block_multiblock
 * @copyright 2020 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadata_provider, userlist_provider, plugin_provider {

    /**
     * Returns information about how block_multiblock stores its data.
     *
     * This plugin implements several interfaces:
     * - The \core_privacy\local\metadata\provider interface - Multiblock manages blocks that might store user data.
     * - The \core_privacy\local\request\core_userlist_provider interface - Multiblock queries dependent blocks for this data.
     * - The \core_privacy\local\request\plugin\provider interface - Multiblock interacts directly with core.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->link_subsystem('block', 'privacy:metadata:block');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the requested user.
     *
     * @param int $userid The user to lookup.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        // This won't be the full list of contexts, this is the list of contexts of the multiblock parents.
        // We will resolve the full list out when fetching or pruning actual data.
        // Note that we can only connect blocks to user data when they're in a user context.
        $contextlist = new contextlist;

        $sql = "SELECT c.id
                  FROM {block_instances} b
            INNER JOIN {context} c ON c.instanceid = b.id AND c.contextlevel = :contextblock
            INNER JOIN {context} bpc ON bpc.id = b.parentcontextid
                 WHERE b.blockname = :blockname
                   AND bpc.contextlevel = :contextuser
                   AND bpc.instanceid = :userid";

        $params = [
            'blockname' => 'multiblock',
            'contextblock' => CONTEXT_BLOCK,
            'contextuser' => CONTEXT_USER,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }


    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        // The users of a given multiblock context are the ones who own it; subblocks just inherit the parent context.
        // By extension this means they inherit the parent owner too.
        $context = $userlist->get_context();

        if (!is_a($context, context_block::class)) {
            return;
        }

        $params = [
            'blockname' => 'multiblock',
            'contextid' => $context->id,
            'contextuser' => CONTEXT_USER,
        ];

        $sql = "SELECT bpc.instanceid AS userid
                  FROM {context} c
                  JOIN {block_instances} bi ON bi.id = c.instanceid AND bi.blockname = :blockname
                  JOIN {context} bpc ON bpc.id = bi.parentcontextid AND bpc.contextlevel = :contextuser
                 WHERE c.id = :contextid";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * Unlike other parts of the privacy provider, this time we actually fetch the sub-block data.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT c.id AS contextid, bi.*
                  FROM {context} c
                  JOIN {block_instances} bi ON bi.id = c.instanceid AND c.contextlevel = :contextlevel
                 WHERE bi.blockname = :blockname
                   AND (c.id {$contextsql})";

        $params = [
            'blockname' => 'multiblock',
            'contextlevel' => CONTEXT_BLOCK,
        ];
        $params += $contextparams;

        $multiblocks = $DB->get_recordset_sql($sql, $params);
        $subblockcontexts = [];

        // We need to build contextlists for each of the subblocks, with their aggregate context lists.
        foreach ($multiblocks as $multiblock) {
            $subblock = "SELECT c.id AS contextid, bi.blockname
                           FROM {context} c
                           JOIN {block_instances} bi ON bi.id = c.instanceid AND c.contextlevel = :contextlevel
                          WHERE bi.parentcontextid = :parentcontext";
            $subblockparams = [
                'contextlevel' => CONTEXT_BLOCK,
                'parentcontext' => $multiblock->contextid,
            ];

            // Now step through all the subblocks of this particular block and query it.
            $subblocks = $DB->get_records_sql($subblock, $subblockparams);
            foreach ($subblocks as $subblock) {
                $subblockcontexts['block_' . $subblock->blockname][] = $subblock->contextid;
            }
        }

        foreach ($subblockcontexts as $component => $contexts) {
            $componentcontextlist = new approved_contextlist($user, $component, $contexts);
            $classname = $component . '\\privacy\\provider';
            if (class_exists($classname) && method_exists($classname, 'export_user_data')) {
                $classname::export_user_data($componentcontextlist);
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        if (!$context instanceof context_block) {
            return;
        }

        // The only way to delete data for the html block is to delete the block instance itself.
        if ($blockinstance = static::get_instance_from_context($context)) {
            blocks_delete_instance($blockinstance);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * This will delete the main multiblocks, which will also delete all child blocks.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof context_block) {
            return;
        }

        if ($blockinstance = static::get_instance_from_context($context)) {
            blocks_delete_instance($blockinstance);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * This will delete the main multiblocks, which will also delete all child blocks.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        foreach ($contextlist as $context) {
            if (!$context instanceof context_block) {
                continue;
            }

            if ($blockinstance = static::get_instance_from_context($context)) {
                blocks_delete_instance($blockinstance);
            }
        }
    }

    /**
     * Get the block instance record for the specified context.
     *
     * @param   context_block $context The context to fetch
     * @return  stdClass
     */
    protected static function get_instance_from_context(context_block $context) {
        global $DB;

        return $DB->get_record('block_instances', ['id' => $context->instanceid, 'blockname' => 'multiblock']);
    }
}
