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
 * Renderable component for multiblocks.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_multiblock\output;

use renderable;
use renderer_base;
use templatable;
use block_multiblock;

/**
 * Renderable component for multiblocks.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main implements renderable, templatable {
    /** @var int The id of the multiblock itself. */
    private $multiblockid;

    /** @var array The instances of subblocks within this block to be rendered. */
    private $multiblock;

    /** @var string The template that we're going to pass to Mustache. */
    private $template;

    /**
     * Initialises the multiblock render helper.
     *
     * @param int $blockid The id of the multiblock itself.
     * @param array $multiblock The instances of subblocks within this block to be rendered.
     * @param string $template The template that we're going to pass to Mustache.
     */
    public function __construct(int $blockid, array $multiblock, string $template) {
        $this->multiblockid = $blockid;
        $this->multiblock = $multiblock;
        $this->template = $template;
    }

    /**
     * Get the template to be rendered for the given configured presentation of this block.
     *
     * @return string The template to be rendered.
     */
    public function get_template(): string {
        $presentations = block_multiblock::get_valid_presentations();
        $presentation = isset($presentations[$this->template]) ? $this->template : block_multiblock::get_default_presentation();
        return $presentations[$presentation]->get_template();
    }

    /**
     * Export this data so it can be used as the context for a Mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $context = [
            'multiblockid' => $this->multiblockid,
            'multiblock' => $this->multiblock,
            'template' => $this->template,
        ];

        $first = true;
        foreach ($context['multiblock'] as $id => $block) {
            $context['multiblock'][$id]['active'] = $first;
            $first = false;
        }

        return (object) $context;
    }
}
