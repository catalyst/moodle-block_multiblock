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
 * Behaviour for vertical-tabbed-list-columns-2-66-33 layout.
 *
 * @package   block_multiblock
 * @copyright 2020 Peter Burnett <peterburnett@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_multiblock\layout;

use block_multiblock\helper;

/**
 * Behaviour for vertical-tabbed-list-right layout.
 *
 * @package   block_multiblock
 * @copyright 2020 Peter Burnett <peterburnett@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tabbed_list_columns_2_66_33 extends abstract_layout {

    /**
     * Returns the recommended uses for this block.
     *
     * @return string 'sidebar' or 'main' to suggest which is recommended for this block.
     */
    public function get_suggested_use() : string {
        return 'main';
    }

    /**
     * Returns the internal ID that this layout would use and be identified by.
     *
     * Defaults to the class name.
     *
     * @return string The layout ID.
     */
    public function get_layout_id() : string {
        return 'tabbed-list-columns-2-66-33';
    }

    /**
     * Returns the Mustache template required to render this block.
     *
     * @return string The Mustache template.
     */
    public function get_template() : string {
        if (!helper::is_totara()) {
            return 'block_multiblock/tabbed-list-columns-2-66-33-bootstrap4';
        } else {
            return 'block_multiblock/tabbed-list-columns-2-66-33-bootstrap3';
        }
    }
}
