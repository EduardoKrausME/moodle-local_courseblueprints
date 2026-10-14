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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Institutional course blueprints: history.php.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = optional_param('id', 0, PARAM_INT);
require_login();
$context = context_system::instance();
require_capability('local/courseblueprints:view', $context);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/courseblueprints/history.php', $id ? ['id' => $id] : []));
$PAGE->set_title(get_string('history', 'local_courseblueprints'));
$PAGE->set_heading(get_string('history', 'local_courseblueprints'));
$history = new \local_courseblueprints\local\execution_history();
$rows = [];
if ($id) {
    $DB->get_record('local_cb_run', ['id' => $id], '*', MUST_EXIST);
    foreach ($history->items($id) as $item) {
        $rows[] = [
            'id' => $item->id, 'fullname' => s($item->fullname),
            'status' => s($item->status), 'error' => s($item->errormessage ?? ''),
            'hascourse' => !empty($item->courseid), 'courseid' => $item->courseid,
            'courseurl' => $item->courseid ?
                (new moodle_url('/course/view.php', ['id' => $item->courseid]))->out(false) : '',
            'compareurl' => $item->courseid ?
                (new moodle_url('/local/courseblueprints/compare.php', ['courseid' => $item->courseid]))->out(false) : '',
        ];
    }
} else {
    foreach ($history->recent() as $run) {
        $rows[] = [
            'id' => $run->id, 'fullname' => 'Batch #' . $run->id,
            'status' => 'V' . $run->versionid, 'error' => '',
            'hascourse' => false, 'courseid' => 0, 'compareurl' => '',
            'courseurl' => (new moodle_url('/local/courseblueprints/history.php', ['id' => $run->id]))->out(false),
            'isrun' => true,
        ];
    }
}
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_courseblueprints/history', [
    'rows' => $rows, 'fullname' => get_string('fullname', 'local_courseblueprints'),
    'status' => get_string('status', 'local_courseblueprints'),
    'error' => get_string('error', 'local_courseblueprints'),
    'compare' => get_string('compare', 'local_courseblueprints'),
    'view' => get_string('view'),
]);
echo $OUTPUT->footer();
