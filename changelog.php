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
 * Changelog.
 *
 * @package   block_multiblock
 * @copyright 2020 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

?>

Multiblock Changelog.
(File protected as a .php file to avoid leaking details of instance in use.)

1.2.3 - 2020022403
 * Added Behat tests for dropdown layout. (#4)
 * Travis builds now use Chrome not Firefox.

1.2.2 - 2020022402
 * Added Behat tests for accordion/tabbed/vertical layouts. (#4)

1.2.1 - 2020022401
 * Fixed dropdown markup for Totara 12. (#44)
 * Fixed vertical tabbed views for Totara 12. (#48)

1.2.0 - 2020022400
 * Added privacy support
 * Fixed permissions handling relating to block contexts. (Part of #22)


1.1.2 - 2020022300
 * Add hints in configure-block for recommended layouts. (#34)


1.1.1 - 2020021301
 * Support for Moodle 3.5


1.1.0 - 2020021300
 * Support for Totara 12+ (#18)


1.0.3 - 2020021102
 * Added vertical tabs - right aligned. (#33)


1.0.2 - 2020021101
 * Added a changelog.
 * Added badges to the readme.
 * Refactored some internals to allow future development. (#29)


1.0.1 - 2020021100
 * Added save-and-display option to the editing a subblock instance. (#25)


1.0.0 - 2019092600
 * Initial code, not initially released under this version except on GitHub.
