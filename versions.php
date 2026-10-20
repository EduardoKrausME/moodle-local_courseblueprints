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
 * Institutional course blueprints: versions.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
require_login();
$context = context_system::instance();
require_capability('local/courseblueprints:view', $context);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/courseblueprints/versions.php', ['id' => $id]));
$repo = new \local_courseblueprints\local\blueprint_repository();
$blueprint = $repo->get($id);
$PAGE->set_title(format_string($blueprint->name));
$PAGE->set_heading(format_string($blueprint->name));

$action = optional_param('action', '', PARAM_ALPHA);
$versionid = optional_param('versionid', 0, PARAM_INT);
$manager = new \local_courseblueprints\local\blueprint_version_manager();
$canpublish = has_capability('local/courseblueprints:publish', $context);
$cancreate = has_capability('local/courseblueprints:create', $context);
if ($action && $versionid) {
    require_sesskey();
    $version = $repo->version($versionid);
    if ((int) $version->blueprintid !== $id) {
        throw new moodle_exception('invalidversion', 'local_courseblueprints');
    }
    if ($action === 'publish' || $action === 'archive') {
        require_capability('local/courseblueprints:publish', $context);
        if ($action === 'publish') {
            $manager->publish($versionid);
        } else {
            $manager->archive($versionid);
        }
        redirect($PAGE->url, get_string('changessaved'));
    }
    if ($action === 'revision') {
        require_capability('local/courseblueprints:create', $context);
        $source = $version->sourcecourseid ? (int) $version->sourcecourseid : null;
        if ($source) {
            require_capability('moodle/backup:backupcourse', context_course::instance($source));
            $structure = (new \local_courseblueprints\local\course_structure_collector())->collect($source);
            $manifest = (new \local_courseblueprints\local\dependency_checker())->manifest($structure);
        } else {
            if ($version->backuphash) {
                throw new moodle_exception('revisionimportunsupported', 'local_courseblueprints');
            }
            $structure = json_decode($version->structure, true, 512, JSON_THROW_ON_ERROR);
            $manifest = json_decode($version->manifest, true, 512, JSON_THROW_ON_ERROR);
        }
        $newid = $manager->create_revision($id, $USER->id, $structure, $manifest, $source);
        if ($source) {
            $task = new \local_courseblueprints\task\capture_blueprint_task();
            $task->set_custom_data(['versionid' => $newid, 'userid' => $USER->id]);
            \core\task\manager::queue_adhoc_task($task, true);
        }
        redirect($PAGE->url, get_string('createddraft', 'local_courseblueprints'));
    }
}
$versionrows = [];
foreach ($repo->versions($id) as $version) {
    $manifest = json_decode($version->manifest, true, 512, JSON_THROW_ON_ERROR);
    $issues = (new \local_courseblueprints\local\dependency_checker())->check($manifest);
    $versionrows[] = [
        'revision' => $version->revision, 'status' => s($version->status),
        'id' => $version->id, 'reviewed' => (bool) $version->reviewed,
        'hasissues' => !empty($issues), 'issues' => implode(', ', $issues),
        'hasbackup' => !empty($version->backuphash),
        'ispublished' => $version->status === 'published',
        'isdraft' => $version->status === 'draft',
        'canpublish' => $canpublish, 'cancreate' => $cancreate,
        'reviewurl' => (new moodle_url('/local/courseblueprints/review.php', ['id' => $version->id]))->out(false),
        'exporturl' => (new moodle_url('/local/courseblueprints/export.php', ['id' => $version->id]))->out(false),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_courseblueprints/versions', [
    'versions' => $versionrows, 'sesskey' => sesskey(), 'blueprintid' => $id,
    'actionurl' => $PAGE->url->out(false), 'canpublish' => $canpublish,
    'publish' => get_string('publish', 'local_courseblueprints'),
    'archive' => get_string('archive', 'local_courseblueprints'),
    'revisionbutton' => get_string('newrevision', 'local_courseblueprints'),
    'review' => get_string('review', 'local_courseblueprints'),
    'export' => get_string('export', 'local_courseblueprints'),
    'revision' => get_string('revision', 'local_courseblueprints'),
    'status' => get_string('status', 'local_courseblueprints'),
    'dependencies' => get_string('dependencies', 'local_courseblueprints'),
    'returnurl' => (new moodle_url('/local/courseblueprints/index.php'))->out(false),
    'back' => get_string('back'),
]);
echo $OUTPUT->footer();
