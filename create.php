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
 * Institutional course blueprints: create.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('local/courseblueprints:create', context_system::instance());
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/courseblueprints/create.php'));
$PAGE->set_title(get_string('createblueprint', 'local_courseblueprints'));
$PAGE->set_heading(get_string('createblueprint', 'local_courseblueprints'));

$form = new \local_courseblueprints\form\blueprint_form();
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/courseblueprints/index.php'));
}
if ($data = $form->get_data()) {
    require_sesskey();
    $sourceid = $data->mode === 'existing' ? (int) $data->sourcecourseid : null;
    $collector = new \local_courseblueprints\local\course_structure_collector();
    if ($sourceid) {
        require_capability('moodle/backup:backupcourse', context_course::instance($sourceid));
        $structure = $collector->collect($sourceid);
    } else {
        $structure = $collector->from_initial(['format' => $data->format, 'sections' => $data->sections]);
    }
    $manifest = (new \local_courseblueprints\local\dependency_checker())->manifest($structure);
    $warnings = (new \local_courseblueprints\local\privacy_guard())->inspect($structure);
    if ($warnings) {
        $manifest['unsupported'] = array_merge($manifest['unsupported'], $warnings);
    }
    // Always require explicit manual review, even when heuristic scanners found nothing.
    $repo = new \local_courseblueprints\local\blueprint_repository();
    $id = $repo->create($data->code, $data->name, $data->description, $USER->id);
    $versionid = (new \local_courseblueprints\local\blueprint_version_manager())->create_revision(
        $id,
        $USER->id,
        $structure,
        $manifest,
        $sourceid
    );
    if ($sourceid) {
        $task = new \local_courseblueprints\task\capture_blueprint_task();
        $task->set_custom_data(['versionid' => $versionid, 'userid' => $USER->id]);
        \core\task\manager::queue_adhoc_task($task, true);
    }
    redirect(
        new moodle_url('/local/courseblueprints/versions.php', ['id' => $id]),
        get_string('createddraft', 'local_courseblueprints')
    );
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
