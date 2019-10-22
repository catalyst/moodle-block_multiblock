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

require(__DIR__ . '/../../config.php');

require_once($CFG->libdir.'/tablelib.php');

$blockid = required_param('id', PARAM_INT);
$actionableinstance = optional_param('instance', 0, PARAM_INT);
$performaction = optional_param('action', '', PARAM_TEXT);

$blockctx = context_block::instance($blockid);
$block = $DB->get_record('block_instances', ['id' => $blockid], '*', MUST_EXIST);
if (block_load_class($block->blockname)) {
    $class = 'block_' . $block->blockname;
    $blockinstance = new $class;
    $blockinstance->_load_instance($block, $PAGE);
}

$PAGE->set_context($blockctx);
$PAGE->set_url($blockctx->get_url());
$PAGE->set_pagelayout('admin');

// The my-dashboard page adds an additional 'phantom' block region to cope with the dashboard content.
if ($block->pagetypepattern == 'my-index') {
    $PAGE->blocks->add_region('content');
} else {
    // But if we're on anything other than my dashboard, we want to initialise the navbar fully.
    $PAGE->navigation->initialise();
}

$PAGE->navbar->add(get_string('managemultiblock', 'block_multiblock', $blockinstance->get_title()));

require_login();

$blockmanager = $PAGE->blocks;

if (!$blockinstance->user_can_edit() && !$this->page->user_can_edit_blocks()) {
    throw new moodle_exception('nopermissions', '', $this->page->url->out(), get_string('editblock'));
}

// Now we've done permissions checks, reset the URL to be the real one.
$pageurl = new moodle_url('/blocks/multiblock/manage.php', ['id' => $blockid]);
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('managemultiblock', 'block_multiblock', $blockinstance->title));
$PAGE->set_heading(get_string('managemultiblock', 'block_multiblock', $blockinstance->title));

$blockmanager->show_only_fake_blocks(true);

$multiblockblocks = $blockinstance->load_multiblocks($PAGE->context->id);

// Let's show off the blocks we have.
echo $OUTPUT->header();

// Set up the add block routine.
$forcereload = false;
$addblock = new \block_multiblock\form\addblock($pageurl, ['id' => $blockid]);
if ($newblockdata = $addblock->get_data()) {
    if ($newblockdata->addblock) {
        $position = 1;
        foreach ($multiblockblocks as $instance) {
            if ((int) $instance->defaultweight > $position) {
                $position = (int) $instance->defaultweight;
            }
        }

        $blockmanager->add_block($newblockdata->addblock, $block->defaultregion, $block->defaultweight, $block->showinsubcontexts);

        // Now we need to re-prep the table exist.
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
        case 'delete':
            blocks_delete_instance($multiblockblocks[$actionableinstance]);
            $forcereload = true;
            break;
    }
}

if ($forcereload) {
    $multiblockblocks = $blockinstance->load_multiblocks($PAGE->context->id);
    unset($_POST['addblock']); // Reset the form element so it doesn't attempt to reuse the value it had before.
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
        'updated' => get_string('table:lastupdated', 'block_multiblock'),
    ];
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

        if ($instance->id != $first) {
            $url = $baseactionurl;
            $url->params(['action' => 'moveup']);
            $actions .= $OUTPUT->action_icon($url, new pix_icon('t/up', get_string('moveup')));
        } else {
            $actions .= $OUTPUT->pix_icon('i/empty', '');
        }
        if ($instance->id != $last) {
            $url = $baseactionurl;
            $url->params(['action' => 'movedown']);
            $actions .= $OUTPUT->action_icon($url, new pix_icon('t/down', get_string('movedown')));
        } else {
            $actions .= $OUTPUT->pix_icon('i/empty', '');
        }
        if (file_exists($CFG->dirroot . '/blocks/' . $instance->blockinstance->name() . '/edit_form.php')) {
            $url = new moodle_url('/blocks/multiblock/configinstance.php', [
                'id' => $blockid,
                'instance' => $instance->id,
                'sesskey' => sesskey(),
            ]);
            $actions .= $OUTPUT->action_icon($url, new pix_icon('i/settings', get_string('settings')));
        } else {
            $actions .= $OUTPUT->pix_icon('i/empty', '');
        }

        $url = $baseactionurl;
        $url->params(['action' => 'delete']);
        $actions .= $OUTPUT->action_icon($url, new pix_icon('i/delete', get_string('delete')));

        $notitle = html_writer::tag('em', get_string('notitle', 'block_multiblock'), ['class' => 'text-muted']);

        $row = [
            !empty($instance->blockinstance->get_title()) ? $instance->blockinstance->get_title() : $notitle,
            get_string('pluginname', 'block_' . $instance->blockinstance->name()),
            $actions,
            userdate($instance->timemodified, get_string('strftimedatetime', 'core_langconfig'))
        ];
        $table->add_data($row);
    }

    $table->print_html();
}

echo html_writer::empty_tag('hr');
$addblock->display();

echo $OUTPUT->footer();