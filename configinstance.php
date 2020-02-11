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
 * Manage multiblock instances.
 *
 * @package   block_multiblock
 * @copyright 2019 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_multiblock\helper;
use block_multiblock\navigation;

require(__DIR__ . '/../../config.php');

require_once($CFG->libdir.'/tablelib.php');

$blockid = required_param('id', PARAM_INT);
$actionableinstance = required_param('instance', PARAM_INT);

list($block, $blockinstance) = helper::bootstrap_page($blockid);
require_login();

$blockmanager = $PAGE->blocks;

if (!$blockinstance->user_can_edit() && !$this->page->user_can_edit_blocks()) {
    throw new moodle_exception('nopermissions', '', $this->page->url->out(), get_string('editblock'));
}

$pageurl = new moodle_url('/blocks/multiblock/configinstance.php', ['id' => $blockid, 'instance' => $actionableinstance]);
$PAGE->set_url($pageurl);

$blockmanager->show_only_fake_blocks(true);

$multiblockblocks = $blockinstance->load_multiblocks($PAGE->context->id);
if (!isset($multiblockblocks[$actionableinstance])) {
    redirect(new moodle_url('/blocks/multiblock/manage.php', ['id' => $blockid, 'sesskey' => sesskey()]));
}

$PAGE->navbar->add($multiblockblocks[$actionableinstance]->blockinstance->get_title());

$formfile = $CFG->dirroot . '/blocks/' . $multiblockblocks[$actionableinstance]->blockinstance->name() . '/edit_form.php';
$classname = '';
if (is_readable($formfile)) {
    require_once($CFG->dirroot . '/blocks/edit_form.php');
    require_once($formfile);
    $classname = 'block_' . $multiblockblocks[$actionableinstance]->blockinstance->name() . '_edit_form';
}

if (!$classname || !class_exists($classname)) {
    throw new \Exception('Could not load block configuration for ' . $classname);
}

class_alias($classname, 'block_multiblock_proxy_edit_form');
$editform = new \block_multiblock\form\editblock($pageurl, $multiblockblocks[$actionableinstance], $PAGE, $blockinstance);

if ($editform->is_cancelled()) {
    redirect(new moodle_url('/blocks/multiblock/manage.php', ['id' => $blockid, 'sesskey' => sesskey()]));
} else if ($data = $editform->get_data()) {
    $config = new stdClass;
    foreach ($data as $configfield => $value) {
        if (strpos($configfield, 'config_') !== 0) {
            continue;
        }
        $field = substr($configfield, 7);
        $config->$field = $value;
    }
    $multiblockblocks[$actionableinstance]->blockinstance->instance_config_save($config);

    // If we pressed save and display, go to the page where the block lives.
    if (!empty($data->saveanddisplay)) {
        redirect(navigation::get_page_url($blockid));
    }

    // Otherwise return to the management page.
    redirect(new moodle_url('/blocks/multiblock/manage.php', ['id' => $blockid, 'sesskey' => sesskey()]));
}

echo $OUTPUT->header();

$editform->set_data($multiblockblocks[$actionableinstance]->blockinstance->instance);
$editform->display();

echo $OUTPUT->footer();
