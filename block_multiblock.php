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
 * Class that does all the magic.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Block multiblock class definition.
 *
 * This block can be added to a variety of places to display multiple blocks in one space.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_multiblock extends block_base {

    /**
     * Core function used to initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_multiblock');
    }

    /**
     * Core function, specifies where the block can be used.
     * @return array
     */
    public function applicable_formats() {
        return [
            'all' => true,
        ];
    }

    /**
     * Sets the block's title for a specific instance based on its configuration.
     */
    public function specialization() {
        if (isset($this->config->title)) {
            $this->title = format_string($this->config->title, true, ['context' => $this->context]);
        } else {
            $this->title = get_string('pluginname', 'block_multiblock');
        }
    }

    public function load_multiblocks($contextid) {
        global $DB, $PAGE;

        // Find all the things that relate to this block.
        $this->blocks = $DB->get_records('block_instances', ['parentcontextid' => $contextid], 'defaultweight, id');

        foreach ($this->blocks as $id => $block) {
            if (block_load_class($block->blockname)) {
                // Make the proxy class we'll need.
                $this->blocks[$id]->blockinstance = block_instance($block->blockname, $block);
                $this->blocks[$id]->blockname = $block->blockname;
            }
        }

        return $this->blocks;
    }

    public function instance_delete() {
        global $DB, $PAGE;

        // Find all the things that relate to this block.
        foreach ($DB->get_records('block_instances', ['parentcontextid' => $this->context->id]) as $subblock) {
            blocks_delete_instance($subblock);
        }
        return true;
    }

    /**
     * Used to generate the content for the block.
     * @return string
     */
    public function get_content() {
        global $DB;
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        $context = $DB->get_record('context', ['contextlevel' => CONTEXT_BLOCK, 'instanceid' => $this->instance->id]);

        $this->load_multiblocks($context->id);

        $multiblock = [];
        $isodd = true;
        foreach ($this->blocks as $id => $block) {
            if (empty($block->blockinstance)) {
                continue;
            }
            $content = $block->blockinstance->get_content();
            $multiblock[] = [
                'id' => $id,
                'class' => 'block_' . $block->blockinstance->name(),
                'type' => $block->blockinstance->name(),
                'is_odd' => $isodd,
                'title' => $block->blockinstance->get_title(),
                'content' => !empty($content->text) ? $content->text : '',
                'footer' => !empty($content->footer) ? $content->footer: '',
            ];
            $isodd = !$isodd;
        }

        $template = !empty($this->config->presentation) ? $this->config->presentation : 'tabbed-list';
        $renderable = new \block_multiblock\output\main($this->instance->id, $multiblock, $template);
        $renderer = $this->page->get_renderer('block_multiblock');

        $this->content = (object) [
            'text' => $renderer->render($renderable),
            'footer' => ''
        ];

        return $this->content;
    }

    /**
     * Return a block_contents object representing the full contents of this block.
     *
     * This internally calls ->get_content(), and then adds the editing controls etc.
     * @return block_contents a representation of the block, for rendering.
     */
    public function get_content_for_output($output) {
        $bc = parent::get_content_for_output($output);

        if (empty($bc->controls)) {
            return $bc;
        }

        $newcontrols = [];
        foreach ($bc->controls as $control) {
            $newcontrols[] = $control;
            if (strpos($control->attributes['class'], 'editing_edit') !== false) {
                $str = get_string('managemultiblock', 'block_multiblock', $this->title);
                $newcontrols[] = new action_menu_link_secondary(
                    new moodle_url('/blocks/multiblock/manage.php', ['id' => $this->instance->id, 'sesskey'=> sesskey()]),
                    new pix_icon('t/preferences', $str, 'moodle', ['class' => 'iconsmall', 'title' => '']),
                    $str,
                    ['class' => 'editing_manage']
                );
            }
        }
        $bc->controls = $newcontrols;

        return $bc;
    }

    /**
     * Allows the block to be added multiple times to a single page
     * @return boolean
     */
    public function instance_allow_multiple() {
        return true;
    }
}
