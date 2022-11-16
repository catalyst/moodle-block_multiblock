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
 * Icon handling abstraction.
 *
 * @package   block_multiblock
 * @copyright 2020 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_multiblock;

use core\output\flex_icon;
use pix_icon;

/**
 * Icon handling abstraction.
 *
 * Intended to abstract away Moodle 3.5+ vs Totara 12+.
 *
 * @package   block_multiblock
 * @copyright 2020 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class icon_helper {

    /**
     * Returns an icon.
     *
     * For cases where the icon identifier is the same in both Totara and Moodle,
     * this function takes care of the general setup.
     *
     * @param string $icon The icon identifier, e.g. "t/preferences".
     * @param string $str The alt text for this icon.
     * @return mixed An icon object for rendering. Type dependent whether using Moodle or Totara.
     */
    protected static function general_icon(string $icon, string $str) {
        if (class_exists('\\core\\output\\flex_icon')) {
            return flex_icon::get_icon($icon, 'moodle', ['class' => 'iconsmall', 'alt' => $str]);
        } else {
            return new pix_icon($icon, $str, 'moodle', ['class' => 'iconsmall']);
        }
    }

    /**
     * Returns a arrow up icon.
     *
     * @param string $str The alt text for this icon.
     * @return mixed An icon object for rendering. Type dependent whether using Moodle or Totara.
     */
    public static function arrow_up(string $str) {
        return static::general_icon('t/up', $str);
    }

    /**
     * Returns a arrow down icon.
     *
     * @param string $str The alt text for this icon.
     * @return mixed An icon object for rendering. Type dependent whether using Moodle or Totara.
     */
    public static function arrow_down(string $str) {
        return static::general_icon('t/down', $str);
    }

    /**
     * Returns a settings icon.
     *
     * @param string $str The alt text for this icon.
     * @return mixed An icon object for rendering. Type dependent whether using Moodle or Totara.
     */
    public static function settings(string $str) {
        return static::general_icon('i/settings', $str);
    }

    /**
     * Returns a preferences icon.
     *
     * @param string $str The alt text for this icon.
     * @return mixed An icon object for rendering. Type dependent whether using Moodle or Totara.
     */
    public static function preferences(string $str) {
        return static::general_icon('t/preferences', $str);
    }

    /**
     * Returns a trashcan icon.
     *
     * @param string $str The alt text for this icon.
     * @return mixed An icon object for rendering. Type dependent whether using Moodle or Totara.
     */
    public static function delete(string $str) {
        if (class_exists('\\core\\output\\flex_icon')) {
            return flex_icon::get_icon('i/delete', 'moodle', ['class' => 'iconsmall', 'alt' => $str]);
        } else {
            return new pix_icon('t/delete', $str, 'moodle', ['class' => 'iconsmall']);
        }
    }

    /**
     * Returns an icon to split a subblock up a level.
     *
     * @param string $str The alt text for this icon.
     * @return mixed An icon object for rendering. Type dependent whether using Moodle or Totara.
     */
    public static function level_up(string $str) {
        if (class_exists('\\core\\output\\flex_icon')) {
            return flex_icon::get_icon('level-up', 'moodle', ['class' => 'iconsmall', 'alt' => $str]);
        } else {
            return new pix_icon('i/import', $str, 'moodle', ['class' => 'iconsmall', 'alt' => $str]);
        }
    }

    /**
     * Returns a spacer icon to align action icon sets.
     *
     * @return string The rendered HTML for a spacer icon.
     */
    public static function space() : string {
        global $OUTPUT;

        if (class_exists('\\core\\output\\flex_icon')) {
            return $OUTPUT->flex_icon('spacer');
        } else {
            return $OUTPUT->pix_icon('i/empty', '');
        }
    }
}
