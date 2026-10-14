<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Institutional course blueprints: compare.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
require_login();
require_capability('local/courseblueprints:view', context_system::instance());
require_capability('moodle/course:view', context_course::instance($courseid));
$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_url(new moodle_url('/local/courseblueprints/compare.php', ['courseid' => $courseid]));
$PAGE->set_title(get_string('compare', 'local_courseblueprints'));
$PAGE->set_heading(get_string('compare', 'local_courseblueprints'));
$report = (new \local_courseblueprints\local\course_comparison_service())->compare_course($courseid);
$differences = [];
foreach ($report as $item) {
    $differences[] = ['type' => s($item['type']), 'detail' => s($item['detail'])];
}
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_courseblueprints/comparison', [
    'differences' => $differences, 'nodifferences' => empty($differences),
    'empty' => get_string('nodifferences', 'local_courseblueprints'),
    'info' => get_string('comparisoninformative', 'local_courseblueprints'),
]);
echo $OUTPUT->footer();
