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

use block_multiblock\navigation;
use block_multiblock\form\editblock;
use block_multiblock\form\editblock_totara;
use context;
use context_block;
use context_helper;
use moodle_exception;
use moodle_url;
use navigation_node;

/**
 * Supporting infrastructure for the multiblock editing.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /** @var moodle_url $pageurl The URL that the block's context belongs to. */
    protected static $pageurl = null;

    /** @var moodle_url $realpageurl The URL that we're really on for this block management. */
    protected static $realpageurl = null;

    /**
     * Sets the block parent URL (i.e. fake context) for permissions checks.
     *
     * @param moodle_url $url The URL to set this to (will be reused if not supplied)
     */
    public static function set_page_fake_url(moodle_url $url = null) {
        global $PAGE;

        if (!is_null($url)) {
            static::$pageurl = $url;
        }

        $PAGE->set_url(static::$pageurl);
    }

    /**
     * Sets the real page URL (e.g. management).
     *
     * @param moodle_url $url The URL to set this to (will be reused if not supplied)
     */
    public static function set_page_real_url(moodle_url $url = null) {
        global $PAGE;

        if (!is_null($url)) {
            static::$realpageurl = $url;
        }

        $PAGE->set_url(static::$realpageurl);
    }

    /**
     * Provide some functionality for bootstrapping the page for a given block.
     *
     * Namely: load the block instance, some $PAGE setup, navigation setup.
     *
     * @param int $blockid The block ID being operated on.
     * @return array Return the block record and its instance class.
     */
    public static function bootstrap_page($blockid) : array {
        global $DB, $PAGE;

        $block = $DB->get_record('block_instances', ['id' => $blockid], '*', MUST_EXIST);
        if (block_load_class($block->blockname)) {
            $class = 'block_' . $block->blockname;
            $blockinstance = new $class;
            $blockinstance->_load_instance($block, $PAGE);
        }

        $parentctx = context::instance_by_id($block->parentcontextid);

        $PAGE->set_context($parentctx);

        $actualpageurl = navigation::get_page_url($blockid);
        static::set_page_fake_url($actualpageurl);
        $PAGE->set_pagelayout('admin');

        $blockmanager = $PAGE->blocks;

        // The my-dashboard page adds an additional 'phantom' block region to cope with the dashboard content.
        if (navigation::is_dashboard($actualpageurl)) {
            $PAGE->blocks->add_region('content');
            // For some reason, adding extra navbar items to dashboard requires doing it twice.
            $PAGE->navbar->add(get_string('managemultiblock', 'block_multiblock', $blockinstance->get_title()),
                new moodle_url('/blocks/multiblock/manage.php', ['id' => $blockid, 'sesskey' => sesskey()]));
            $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
        }

        // Blocks on admin pages require loading the admin tree to be able to render the breadcrumbs.
        if (navigation::is_admin_url($actualpageurl)) {
            navigation_node::require_admin_tree();
        }

        // If the course is on a block page we need to explicitly load the course to get the correct breadcrumbs.
        if ($parentctx->contextlevel == CONTEXT_COURSE) {
            $courseid = $parentctx->instanceid;
            context_helper::preload_course($courseid);
            $course = $DB->get_record('course', ['id' => $courseid]);
            $PAGE->set_course($course);
        }

        // If it's an activity, we have to do a bit more work, loading the course and the context module.
        if ($parentctx->contextlevel == CONTEXT_MODULE) {
            $cm = $DB->get_record('course_modules', ['id' => $parentctx->instanceid]);
            $PAGE->set_cm($cm);
        }

        // And hand over to the Moodle architecture to do its thing.
        $PAGE->navigation->initialise();
        $PAGE->navbar->add(get_string('manageblocklocation', 'block_multiblock'), $actualpageurl);
        $PAGE->navbar->add(get_string('managemultiblock', 'block_multiblock', $blockinstance->get_title()),
            new moodle_url('/blocks/multiblock/manage.php', ['id' => $blockid, 'sesskey' => sesskey()]));

        require_sesskey();

        $PAGE->set_title(get_string('managemultiblocktitle', 'block_multiblock', $blockinstance->title));
        $PAGE->set_heading(get_string('managemultiblocktitle', 'block_multiblock', $blockinstance->title));

        if (!$blockinstance->user_can_edit() || !$PAGE->user_can_edit_blocks()) {
            throw new moodle_exception('nopermissions', '', $PAGE->url->out(), get_string('editblock', 'block_multiblock'));
        }

        return [$block, $blockinstance, $blockmanager];
    }

    /**
     * Finds the parent non-block context for a given block.
     * For example, a dashboard can hook off the user context
     * for which the dashboard is created, which contains the
     * multiblock context, which under it will contain the
     * child blocks. This, given the child block id will
     * traverse the parents in order until it hits the closest
     * ancestor that is not a block context.
     *
     * @param int $blockid
     * @return object A context object reflecting the nearest ancestor
     */
    public static function find_nearest_nonblock_ancestor($blockid) {
        global $DB;

        $context = $DB->get_record('context', ['instanceid' => $blockid, 'contextlevel' => CONTEXT_BLOCK]);
        // Convert the path from /1/2/3 to [1, 2, 3], remove the leading empty item and this item.
        $path = explode('/', $context->path);
        $path = array_diff($path, ['', $context->id]);
        foreach (array_reverse($path) as $contextid) {
            $parentcontext = $DB->get_record('context', ['id' => $contextid]);
            if ($parentcontext->contextlevel != CONTEXT_BLOCK) {
                // We found the one we care about.
                return context::instance_by_id($parentcontext->id);
            }
        }

        throw new coding_exception('Could not find parent non-block ancestor for block id ' . $blockid);
    }

    /**
     * Splits a subblock out of the multiblock and returns it to the
     * parent context that the parent multiblock lives in.
     *
     * @param object $parent The parent instance object, from block_instances table.
     * @param int $childid The id of the subblock to remove, from block_instances table.
     */
    public static function split_block($parent, $childid) {
        global $DB;

        // Get the block details and the target context to move it to.
        $subblock = $DB->get_record('block_instances', ['id' => $childid]);
        $parentcontext = static::find_nearest_nonblock_ancestor($childid);

        // Copy some parameters from the parent since that's what we're using now.
        $params = [
            'showinsubcontexts', 'requiredbytheme', 'pagetypepattern', 'subpagepattern',
            'defaultregion', 'defaultweight',
        ];
        foreach ($params as $param) {
            $subblock->$param = $parent->$param;
        }

        // Then set up the parts that aren't inherited from the old parent, and commit.
        $subblock->parentcontextid = $parentcontext->id;
        $subblock->timemodified = time();
        $DB->update_record('block_instances', $subblock);

        // Now fix the position to mirror the parent, if it has one.
        $parentposition = $DB->get_record('block_positions', [
            'contextid' => $parentcontext->id,
            'blockinstanceid' => $parent->id,
        ], '*', IGNORE_MISSING);
        if ($parentposition) {
            // The parent has a specific position, we need to add that.
            $newchild = $parentposition;
            $newchild->blockinstanceid = $childid;
            $DB->insert_record('block_positions', $newchild);
        }

        // Finally commit the updated context path to this block.
        $childcontext = context_block::instance($childid);
        $childcontext->update_moved($parentcontext);
    }

    /**
     * Moves a block from its current (non-multiblock) context to under a given
     * new multiblock parent.
     *
     * @param int $newchild The id of the block instance to move
     * @param int $newparent The id of the multiblock instance to move to
     */
    public static function move_block($newchild, $newparent) {
        global $DB;

        $parent = $DB->get_record('block_instances', ['id' => $newparent]);
        $subblock = $DB->get_record('block_instances', ['id' => $newchild]);
        $parentcontext = context_block::instance($newparent);

        // Copy some parameters from the parent since that's what we're using now.
        $params = [
            'showinsubcontexts', 'requiredbytheme', 'pagetypepattern', 'subpagepattern',
            'defaultregion',
        ];
        foreach ($params as $param) {
            $subblock->$param = $parent->$param;
        }

        // And fix the position, add it to the end of all the blocks attached to this sub-block.
        $max = $DB->get_record_sql("
            SELECT MAX(defaultweight) AS maxweight
              FROM {block_instances}
             WHERE parentcontextid = ?
          GROUP BY parentcontextid", [$parentcontext->id]);
        if (empty($max)) {
            $subblock->defaultweight = 1;
        } else {
            $subblock->defaultweight = $max->maxweight + 1;
        }

        // Then set up the parts that aren't inherited from the old parent, and commit.
        $subblock->parentcontextid = $parentcontext->id;
        $subblock->timemodified = time();
        $DB->update_record('block_instances', $subblock);

        // And remove any references it has to its own position, regardless of where it was, since
        // those don't exist any more.
        $DB->delete_records('block_positions', ['blockinstanceid' => $newchild]);

        // Finally commit the updated context path to this block.
        $childcontext = context_block::instance($newchild);
        $childcontext->update_moved($parentcontext);
    }

    /**
     * Different functionality might need to be used if implementing
     * Multiblock on a Totara installation as opposed to a Moodle.
     *
     * @return bool True if the current installation is Totara.
     */
    public static function is_totara(): bool {
        return class_exists('\\totara_core\\helper');
    }

    /**
     * Gets the correct editing form based for configuring an instance.
     *
     * @param string|moodle_url $actionurl The form action to submit to
     * @param block_base $block The block class being edited
     * @param object $page The contextually appropriate $PAGE type object of the block being edited
     * @param object $multiblock The multiblock object being edited (mostly for its configuration
     */
    public static function get_edit_form($actionurl, $block, $page, $multiblock = null) {
        if (static::is_totara()) {
            return new editblock_totara($actionurl, $block, $page, $multiblock);
        } else {
            return new editblock($actionurl, $block, $page, $multiblock);
        }
    }
}
