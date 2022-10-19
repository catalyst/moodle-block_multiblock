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
use block_multiblock\icon_helper;
use block_multiblock\navigation;

require(__DIR__ . '/../../config.php');

require_once($CFG->libdir.'/tablelib.php');

$blockid = required_param('id', PARAM_INT);
$actionableinstance = optional_param('instance', 0, PARAM_INT);
$performaction = optional_param('action', '', PARAM_TEXT);

require_login();
list($block, $blockinstance, $blockmanager) = helper::bootstrap_page($blockid);

// Now we've done permissions checks, reset the URL to be the real one.
$pageurl = new moodle_url('/blocks/multiblock/manage.php', ['id' => $blockid]);
helper::set_page_real_url($pageurl);

$blockmanager->show_only_fake_blocks(true);

$blockctx = context_block::instance($blockid);
$multiblockblocks = $blockinstance->load_multiblocks($blockctx->id);

// Set up the add block routine.
$forcereload = false;
$addblock = new \block_multiblock\form\addblock($pageurl, ['id' => $blockid]);
if ($newblockdata = $addblock->get_data()) {
    if (!empty($newblockdata->addsubmit) && $newblockdata->addblock) {
        $position = 1;
        foreach ($multiblockblocks as $instance) {
            if ((int) $instance->defaultweight > $position) {
                $position = (int) $instance->defaultweight;
            }
        }

        // Add the block to the parent context, then move it in.
        $blockmanager->add_block($newblockdata->addblock, $blockmanager->get_default_region(), $position + 1,
            $block->showinsubcontexts);
        // Helpfully, $blockmanager won't give us back the id it just added, so we have to go find it.
        $conditions = [
            'blockname' => $newblockdata->addblock,
            'parentcontextid' => $PAGE->context->id,
        ];
        $lastinserted = $DB->get_records('block_instances', $conditions, 'id DESC', 'id', 0, 1);
        if ($lastinserted) {
            helper::move_block(current($lastinserted)->id, $blockid);
        }

        // Now we need to re-prep the table exist.
        $forcereload = true;
    } else if (!empty($newblockdata->movesubmit) && !empty($newblockdata->moveblock)) {
        // Merge it in and then reprep the table and form.
        helper::move_block($newblockdata->moveblock, $blockid);
        $forcereload = true;
    }
} else if ($performaction) {
    switch ($performaction) {
        case 'moveup':
            $positions = array_keys($multiblockblocks);
            if (in_array($actionableinstance, $positions) && $positions[0] != $actionableinstance) {
                $current = array_search($actionableinstance, $positions);
                $temp = $positions[$current - 1];
                $positions[$current - 1] = $positions[$current];
                $positions[$current] = $temp;
            }
            foreach ($positions as $position => $actionableinstance) {
                $new = (object) [
                    'id' => $actionableinstance,
                    'defaultweight' => $position + 1,
                ];
                $DB->update_record('block_instances', $new);
            }
            $forcereload = true;
            break;
        case 'movedown':
            $positions = array_keys($multiblockblocks);
            if (in_array($actionableinstance, $positions) && $positions[count($positions) - 1] != $actionableinstance) {
                $current = array_search($actionableinstance, $positions);
                $temp = $positions[$current + 1];
                $positions[$current + 1] = $positions[$current];
                $positions[$current] = $temp;
            }
            foreach ($positions as $position => $actionableinstance) {
                $new = (object) [
                    'id' => $actionableinstance,
                    'defaultweight' => $position + 1,
                ];
                $DB->update_record('block_instances', $new);
            }
            $forcereload = true;
            break;
        case 'split':
            helper::split_block($blockinstance->instance, $actionableinstance);
            $forcereload = true;
            break;
        case 'delete':
            blocks_delete_instance($multiblockblocks[$actionableinstance]);
            $forcereload = true;
            break;
        case 'splitdelete':
            $parenturl = navigation::get_page_url($blockid);
            foreach (array_keys($multiblockblocks) as $childid) {
                helper::split_block($blockinstance->instance, $childid);
            }
            blocks_delete_instance($blockinstance->instance);
            redirect($parenturl);
            break;
    }
}

// And begin our output.
echo $OUTPUT->header();

if ($forcereload) {
    $multiblockblocks = $blockinstance->load_multiblocks($blockctx->id);
    unset($_POST['addblock'], $_POST['moveblock']); // Reset the form element so it doesn't attempt to reuse values it had before.
    $addblock = new \block_multiblock\form\addblock($pageurl, ['id' => $blockid]);
}

if (empty($multiblockblocks)) {
    echo html_writer::tag('p', get_string('multiblockhasnosubblocks', 'block_multiblock'));
} else {
    $table = new flexible_table('block_multiblock_admin');

    $headers = [
        'title' => get_string('table:blocktitle', 'block_multiblock'),
        'type' => get_string('table:blocktype', 'block_multiblock'),
        'actions' => get_string('table:actions', 'block_multiblock'),
    ];
    if (!helper::is_totara()) {
        $headers['updated'] = get_string('table:lastupdated', 'block_multiblock');
    }
    $table->define_columns(array_keys($headers));
    $table->define_headers(array_values($headers));
    $table->define_baseurl(new moodle_url('/blocks/multiblock/manage.php', ['id' => $blockid]));
    $table->set_attribute('class', 'admintable blockstable generaltable');
    $table->set_attribute('id', 'multiblocktable');
    $table->sortable(false);
    $table->setup();

    $first = 0;
    $last = 0;
    foreach ($multiblockblocks as $instance) {
        if (!$first) {
            $first = $instance->id;
        }
        $last = $instance->id;
    }

    foreach ($multiblockblocks as $instance) {
        $actions = '';
        $baseactionurl = new moodle_url('/blocks/multiblock/manage.php', [
            'id' => $blockid,
            'instance' => $instance->id,
            'sesskey' => sesskey()
        ]);

        // Molve the sub-block up, if it's not the first one.
        if ($instance->id != $first) {
            $url = $baseactionurl;
            $url->params(['action' => 'moveup']);
            $actions .= $OUTPUT->action_icon($url, icon_helper::arrow_up(get_string('moveup')));
        } else {
            $actions .= icon_helper::space();
        }

        // Move sub-block down, if it's not the last one.
        if ($instance->id != $last) {
            $url = $baseactionurl;
            $url->params(['action' => 'movedown']);
            $actions .= $OUTPUT->action_icon($url, icon_helper::arrow_down(get_string('movedown')));
        } else {
            $actions .= icon_helper::space();
        }

        // Edit settings button.
        if (file_exists($CFG->dirroot . '/blocks/' . $instance->blockinstance->name() . '/edit_form.php')) {
            $url = new moodle_url('/blocks/multiblock/configinstance.php', [
                'id' => $blockid,
                'instance' => $instance->id,
                'sesskey' => sesskey(),
            ]);
            $actions .= $OUTPUT->action_icon($url, icon_helper::settings(get_string('settings')));
        } else {
            $actions .= icon_helper::space();
        }

        // Split out to parent context.
        $url = $baseactionurl;
        $url->params(['action' => 'split']);
        $actions .= $OUTPUT->action_icon($url, icon_helper::level_up(get_string('movetoparentpage', 'block_multiblock')));

        // Delete button.
        $url = $baseactionurl;
        $url->params(['action' => 'delete']);
        $actions .= $OUTPUT->action_icon($url, icon_helper::delete(get_string('delete')));

        $notitle = html_writer::tag('em', get_string('notitle', 'block_multiblock'), ['class' => 'text-muted']);

        $row = [
            !empty($instance->blockinstance->get_title()) ? $instance->blockinstance->get_title() : $notitle,
            get_string('pluginname', 'block_' . $instance->blockinstance->name()),
            $actions,
        ];
        if (!helper::is_totara()) {
            $row[] = userdate($instance->timemodified, get_string('strftimedatetime', 'core_langconfig'));
        }
        $table->add_data($row);
    }

    $table->print_html();
}

echo html_writer::empty_tag('hr');
$addblock->display();

echo $OUTPUT->footer();
