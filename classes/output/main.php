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
 * Class containing data for Multiblock.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_multiblock\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;


/**
 * Class containing data for Multiblock.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main implements renderable, templatable {
    private $multiblockid;
    private $multiblock;
    private $template;

    public function __construct($blockid, $multiblock, $template) {
        $this->multiblockid = $blockid;
        $this->multiblock = $multiblock;
        $this->template = $template;
    }

    public function get_template() {
        return 'block_multiblock/' . $this->template;
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
