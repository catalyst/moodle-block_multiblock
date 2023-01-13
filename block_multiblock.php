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
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net> 2021 James Pearce <jmp201@bath.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_multiblock\helper;
use block_multiblock\icon_helper;
use block_multiblock\adddefaultblock;

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
    /** @var object $output Temporary storage of the injected page renderer so we can pass it to child blocks at render time. */
    private $output;

    /**
     * Core function used to initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_multiblock');
    }

    /**
     * has_config - denotes whether your block wants to present a configuration interface to site admins or not
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Core function, specifies where the block can be used.
     *
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
        $defaulttitle = get_config('block_multiblock', 'title');
        if (isset($this->config->title)) {
            $this->title = format_string($this->config->title, true, ['context' => $this->context]);
        } else if ($defaulttitle) {
            $this->title = format_string($defaulttitle, true, ['context' => $this->context]);
        } else {
            $this->title = get_string('pluginname', 'block_multiblock');
        }
    }

    /**
     * Loads the child blocks of the current multiblock.
     *
     * @param int $contextid The multiblock's context instance id.
     * @return array An array of child blocks.
     */
    public function load_multiblocks($contextid) {
        global $DB;

        // Find all the things that relate to this block.
        $this->blocks = $DB->get_records('block_instances', ['parentcontextid' => $contextid], 'defaultweight, id');
        foreach ($this->blocks as $id => $block) {
            if (block_load_class($block->blockname)) {
                // Make the proxy class we'll need.
                $this->blocks[$id]->blockinstance = block_instance($block->blockname, $block);
                $this->blocks[$id]->blockname = $block->blockname;
                $this->blocks[$id]->visible = true;
                $this->blocks[$id]->blockpositionid = 0;
            }
        }

        return $this->blocks;
    }

    /**
     *  Used to add the default blocks to the multiblock.
     */
    public function add_default_blocks() {
        global $DB, $CFG;

        if (empty($this->instance)) {
            return $this->content;
        }

        $context = $DB->get_record('context', ['contextlevel' => CONTEXT_BLOCK, 'instanceid' => $this->instance->id]);

        $this->load_multiblocks($context->id);

        $multiblock = [];
        $isodd = true;
        $blockid = $this->instance->id;
        if (empty($this->blocks)) {

            $defaultblocksarray = explode(',', get_config('block_multiblock')->subblock);

            $addblock = new adddefaultblock();
            $addblock->init($blockid, $defaultblocksarray, $this->instance);

        }
    }

    /**
     * Used to generate the content for the block.
     *
     * @return string
     */
    public function get_content() {
        global $DB, $CFG;
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
        $blockid = $this->instance->id;

        if (empty($this->blocks)) {
            $this->add_default_blocks();
            $this->load_multiblocks($context->id);
        }

        foreach ($this->blocks as $id => $block) {
            if (empty($block->blockinstance)) {
                continue;
            }
            $content = $block->blockinstance->get_content_for_output($this->output);
            $multiblock[] = [
                'id' => $id,
                'class' => 'block_' . $block->blockinstance->name(),
                'type' => $block->blockinstance->name(),
                'is_odd' => $isodd,
                'title' => $block->blockinstance->get_title(),
                'content' => !empty($content->content) ? $content->content : '',
                'footer' => !empty($content->footer) ? $content->footer : '',
            ];
            $isodd = !$isodd;
        }

        $template = '';
        $presentations = static::get_valid_presentations();
        $multiblockpresentationoptions = [];
        foreach ($presentations as $presentationid => $presentation) {
            array_push($multiblockpresentationoptions, $presentationid);
        }
        $configuredpresentation = get_config('block_multiblock', 'presentation');
        if (!empty($this->config->presentation)) {
            $template = $this->config->presentation;
        } else if (isset($configuredpresentation)) {
            $template = $multiblockpresentationoptions[$configuredpresentation];
        } else if (isset($presentations['tabbed-list'])) {
            $template = 'tabbed-list';
        }

        $renderable = new \block_multiblock\output\main((int) $this->instance->id, $multiblock, $template);
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
     *
     * @param object $output The output renderer from the parent context (e.g. page renderer)
     * @return block_contents a representation of the block, for rendering.
     */
    public function get_content_for_output($output) {
        $this->output = $output;
        $bc = parent::get_content_for_output($output);

        if (empty($bc->controls)) {
            return $bc;
        }

        $str = get_string('managemultiblock', 'block_multiblock', $this->title);

        $newcontrols = [];
        foreach ($bc->controls as $control) {
            $newcontrols[] = $control;
            // Append our new item onto the controls if we're on the correct item.
            if (strpos($control->attributes['class'], 'editing_edit') !== false) {
                $newcontrols[] = new action_menu_link_secondary(
                    new moodle_url('/blocks/multiblock/manage.php', ['id' => $this->instance->id, 'sesskey' => sesskey()]),
                    icon_helper::preferences($str),
                    $str,
                    ['class' => 'editing_manage']
                );
            }
        }
        // Append a delete+split item on the end.
        $newcontrols[] = new action_menu_link_secondary(
            new moodle_url('/blocks/multiblock/manage.php', ['id' => $this->instance->id, 'sesskey' => sesskey(),
                    'action' => 'splitdelete']),
            icon_helper::delete($str),
            get_string('splitanddelete', 'block_multiblock', $this->title),
            ['class' => 'editing_manage']
        );
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

    /**
     * Copy all the children when copying to a new block instance.
     *
     * @param int $fromid The id number of the block instance to copy from
     * @return bool
     */
    public function instance_copy($fromid) {
        global $DB;

        $fromcontext = context_block::instance($fromid);

        $blockinstances = $DB->get_records('block_instances', ['parentcontextid' => $fromcontext->id], 'defaultweight, id');

        // Create all the new block instances.
        $newblockinstanceids = [];
        foreach ($blockinstances as $instance) {
            $originalid = $instance->id;
            unset($instance->id);
            $instance->parentcontextid = $this->context->id;
            $instance->timecreated = time();
            $instance->timemodified = $instance->timecreated;
            $instance->id = $DB->insert_record('block_instances', $instance);
            $newblockinstanceids[$originalid] = $instance->id;
            $blockcontext = context_block::instance($instance->id);  // Just creates the context record.
            $block = block_instance($instance->blockname, $instance);
            if (!$block->instance_copy($originalid)) {
                debugging("Unable to copy block-specific data for original block instance: $originalid
                    to new block instance: $instance->id", DEBUG_DEVELOPER);
            }
        }
        return true;
    }

    /**
     * Callback for when this block instance is being deleted, to clean up child blocks.
     *
     * @return bool
     */
    public function instance_delete() {
        global $DB;

        // Find all the things that relate to this block.
        foreach ($DB->get_records('block_instances', ['parentcontextid' => $this->context->id]) as $subblock) {
            blocks_delete_instance($subblock);
        }
        return true;
    }

    /**
     * Lists all the known presentation types that exist in the block.
     *
     * @return array An array of presentations for block rendering.
     */
    public static function get_valid_presentations(): array {
        static $presentations = null;

        if ($presentations === null) {

            foreach (core_component::get_component_classes_in_namespace('block_multiblock', 'layout') as $class => $ns) {
                if (strpos($class, $ns[0]) === 0) {
                    // We only care about non-abstract classes here.
                    $reflection = new ReflectionClass($class);
                    if ($reflection->isAbstract()) {
                        continue;
                    }
                    $classname = substr($class, strlen($ns[0]));

                    $instance = new $class;
                    $presentations[$instance->get_layout_id()] = $instance;
                }
            }
        }

        return $presentations;
    }

    /**
     * Returns the default presentation for the multiblock.
     *
     * @return string The default presentation's identifier.
     */
    public static function get_default_presentation(): string {
        $presentations = static::get_valid_presentations();
        $multiblockpresentationoptions = [];
        $configuredpresentation = get_config('block_multiblock', 'presentation');
        foreach ($presentations as $presentationid => $presentation) {
            array_push($multiblockpresentationoptions, $presentationid);
        }
        if ($configuredpresentation) {
            return $multiblockpresentationoptions[$configuredpresentation];
        }
        // Our expected default is not present, make sure we fall back to something.
        return array_keys($presentations)[0];
    }
}
