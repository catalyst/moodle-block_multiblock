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

use block_multiblock\helper;
use admin_category;
use admin_settingpage;
use context_course;
use context_coursecat;
use context_system;
use context_user;
use moodle_url;
use navigation_node;

/**
 * Supporting infrastructure for the multiblock editing.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navigation {

    /**
     * While context_block provides getting a given page's URL,
     * it is not always 100% consistent or reliable. So, instead,
     * we do it ourselves.
     *
     * @param int $blockid The block's id from mdl_block_instances.
     * @return moodle_url The page URL relating to that block.
     */
    public static function get_page_url($blockid): moodle_url {
        global $DB, $CFG;

        $parentcontext = helper::find_nearest_nonblock_ancestor($blockid);
        $block = $DB->get_record('block_instances', ['id' => $blockid]);

        // If the block is in a user context, it could well be a dashboard.
        if ($parentcontext instanceof context_user) {
            if ($block->pagetypepattern == 'my-index') {
                return new moodle_url('/my/');
            }

            if (strpos($block->pagetypepattern, 'totara-dashboard') !== false) {

                if (preg_match('~^totara-dashboard-(\d+)$~', $block->pagetypepattern, $match)) {
                    return new moodle_url('/totara/dashboard/', ['id' => $match[1]]);
                }

                return new moodle_url('/totara/dashboard/');
            }
        }

        // If this is a system context, something really interesting could be happening.
        if ($parentcontext instanceof context_system) {
            if ($block->pagetypepattern == 'my-index') {
                return new moodle_url('/my/indexsys.php');
            }
            // Fix for Workplace custom pages
            if ($block->pagetypepattern == 'admin-tool-custompage') {
                return new moodle_url('/admin/tool/custompage/view.php', ['id' => $block->subpagepattern]);
            }
            if (strpos($block->pagetypepattern, 'totara-dashboard') !== false) {

                if (preg_match('~^totara-dashboard-(\d+)$~', $block->pagetypepattern, $match)) {
                    return new moodle_url('/totara/dashboard/layout.php', ['id' => $match[1]]);
                }

                return new moodle_url('/totara/dashboard/layout.php', ['id' => 1]);
            }
            // If local_dboard dashboard is being customised.
            if (strpos($block->pagetypepattern, 'dboard') !== false) {
                if (preg_match('~^dboard-(\d+)$~', $block->pagetypepattern, $match)) {
                    return new moodle_url('/local/dboard/index.php', ['id' => $match[1]]);
                }
            }
            return static::map_site_context_url($block->pagetypepattern, $parentcontext);
        }

        // If this is a course category, we might have the management page.
        if ($parentcontext instanceof context_coursecat) {
            if (substr($block->pagetypepattern, 0, 17) == 'course-management') {
                return new moodle_url('/course/management.php');
            }
        }

        // If this is a course, we might have to switch between course-view and course-info.
        if ($parentcontext instanceof context_course) {
            // If this is the site course (home page), we have to get a little fancier.
            if ($parentcontext->instanceid == SITEID) {
                if (!empty($CFG->defaulthomepage) && ($CFG->defaulthomepage == HOMEPAGE_MY)) {
                    return new moodle_url('/', ['redirect' => 0]);
                } else {
                    return new moodle_url('/');
                }
            }

            if (substr($block->pagetypepattern, 0, 11) == 'course-info') {
                return new moodle_url('/course/info.php', ['id' => $parentcontext->instanceid]);
            } else if ($block->pagetypepattern == 'course-edit') {
                return new moodle_url('/course/edit.php', ['id' => $parentcontext->instanceid]);
            }
        }

        return $parentcontext->get_url();
    }

    /**
     * Maps known page-type patterns to destinations within the site context.
     *
     * @param string $pagetypepattern The page type pattern the block lists
     * @param context $context The parent context
     * @return moodle_url The URL to route to
     */
    public static function map_site_context_url($pagetypepattern, $context): moodle_url {
        global $CFG;

        $map = [
            'admin-*' => '/admin/search.php',
            'my-index' => '/my/indexsys.php',
            'site-index' => '/',
        ];

        if (isset($map[$pagetypepattern])) {
            return new moodle_url($map[$pagetypepattern]);
        }

        // Page type admin-setting-x can either be a category or a settings page.
        if (preg_match('/^admin-setting-(.*)/i', $pagetypepattern, $match)) {
            require_once($CFG->libdir . '/adminlib.php');
            navigation_node::require_admin_tree();

            $adminroot = admin_get_root();
            $page = $adminroot->locate($match[1], true);

            if ($page instanceof admin_settingpage) {
                return new moodle_url('/admin/settings.php', ['section' => $match[1]]);
            } else if ($page instanceof admin_category) {
                return new moodle_url('/admin/category.php', ['category' => $match[1]]);
            } else if ($page instanceof admin_externalpage) {
                return new moodle_url($page->url);
            }
        }

        // Grade editing.
        if (strpos($pagetypepattern, 'admin-grade-') === 0) {
            // We can convert these from admin-grade-edit-scale-index to grade/edit/scale/index.php.
            $parts = explode('-', $pagetypepattern);
            array_shift($parts); // Remove the first part, 'admin'.
            return new moodle_url('/' . implode('/', $parts) . '.php');
        }

        // Otherwise it's based on the page URL:
        // PTP: admin-plugins -> admin/plugins.php.
        // PTP: admin-tool-customlang-index -> admin/tool/customlang/index.php.
        if (strpos($pagetypepattern, '*') === false) {
            $parts = explode('-', $pagetypepattern);
            return new moodle_url('/' . implode('/', $parts) . '.php');
        }

        // Just in case, we can always try the context's URL - that will get us *something*.
        return $context->get_url();
    }

    /**
     * Identifies if the specified URL is a dashboard.
     *
     * @param moodle_url $url The URL of the page in question.
     * @return bool True if the page is a dashboard.
     */
    public static function is_dashboard(moodle_url $url): bool {
        $local = $url->out_as_local_url(false);
        return strpos($local, '/my/') === 0 && strpos($local, '/my/indexsys') === false;
    }

    /**
     * Identifies if the specified URL is somewhere inside the admin panel.
     *
     * @param moodle_url $url The URL of the page in question.
     * @return bool True if the page is an admin page.
     */
    public static function is_admin_url(moodle_url $url): bool {
        global $CFG;

        $local = $url->out_as_local_url(false);
        return strpos($local, '/' . $CFG->admin . '/') === 0 || strpos($local, '/my/indexsys') === 0;
    }
}
