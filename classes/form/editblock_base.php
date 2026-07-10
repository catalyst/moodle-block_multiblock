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
 * Base class for common support when editing a multiblock subblock.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_multiblock\form;

use block_multiblock_proxy_edit_form;
use moodle_page;
use block_base;
/**
 * Base class for common support when editing a multiblock subblock.
 *
 * Note that this extends block_multiblock_proxy_edit_form - this does
 * not actually exist. This is an alias to whichever edit_form that the
 * subblock would instantiate, so that we can overlay our settings on
 * top and not deal with the full set of block settings which won't be
 * relevant.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editblock_base extends block_multiblock_proxy_edit_form {
    /** @var object The multiblock object being edited */
    public $multiblock;

    /**
     * The block instance we are editing.
     * @var block_base
     */
    private $_block;
    /**
     * The page we are editing this block in association with.
     * @var moodle_page
     */
    private $_page;

    /**
     * Page where we are adding or editing the block
     *
     * To access you can also use magic property $this->page
     *
     * @return moodle_page
     */
    protected function get_page(): moodle_page {
        if (!$this->_page && !empty($this->_customdata['page'])) {
            $this->_page = $this->_customdata['page'];
        }
        return $this->_page;
    }

    /**
     * Instance of the block that is being added or edited
     *
     * To access you can also use magic property $this->block
     *
     * If {{@see self::display_form_when_adding()}} returns true and the configuration
     * form is displayed when adding block, the $this->block->id will be null.
     *
     * @return block_base
     */
    protected function get_block(): block_base {
        $this->multiblock = $this->_customdata['multiblock'];
        if (!$this->_block && !empty($this->_customdata['block'])) {
            $this->_block = $this->_customdata['block'];
        }
        return $this->_block;
    }

    /**
     * Provides the save buttons for the edit-block form.
     */
    public function add_save_buttons() {
        // Now we add the save buttons.
        $mform =& $this->_form;

        $buttonarray = [];
        $buttonarray[] = &$mform->createElement(
            'submit',
            'saveandreturn',
            get_string('saveandreturntomanage', 'block_multiblock')
        );

        // If the page type indicates we're not on a wildcard page, we can probably* go back there.
        // Note: * for some definition of probably.
        if (isset($this->multiblock->instance->pagetypepattern)) {
            if (strpos($this->multiblock->instance->pagetypepattern, '*') === false) {
                $buttonarray[] = &$mform->createElement('submit', 'saveanddisplay', get_string('savechangesanddisplay'));
            }
        }

        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');
    }
}
