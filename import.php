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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Institutional course blueprints: import.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('local/courseblueprints:create', context_system::instance());
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/courseblueprints/import.php'));
$PAGE->set_title(get_string('import', 'local_courseblueprints'));
$PAGE->set_heading(get_string('import', 'local_courseblueprints'));
$form = new \local_courseblueprints\form\import_form();
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/courseblueprints/index.php'));
}
if ($data = $form->get_data()) {
    require_sesskey();
    $json = $form->get_file_content('manifestfile');
    $document = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($document)) {
        throw new invalid_parameter_exception('Invalid JSON object');
    }
    $validator = new \local_courseblueprints\local\import_export_validator();
    $errors = $validator->validate($document);
    if ($errors) {
        throw new moodle_exception('importinvalid', 'local_courseblueprints', '', implode(', ', $errors));
    }
    $backupfile = $form->get_new_filename('backupfile');
    if (!empty($document['backuphash']) && !$backupfile) {
        throw new moodle_exception('backupmissing', 'local_courseblueprints');
    }
    if (empty($document['backuphash']) && $backupfile) {
        throw new moodle_exception('importinvalid', 'local_courseblueprints', '', 'Unexpected backup');
    }
    $tmpfile = null;
    try {
        if ($backupfile) {
            $tmpfile = make_request_directory() . '/blueprint.mbz';
            if (!$form->save_file('backupfile', $tmpfile, true)) {
                throw new moodle_exception('backupmissing', 'local_courseblueprints');
            }
            if (!hash_equals($document['backuphash'], hash_file('sha256', $tmpfile))) {
                throw new moodle_exception('integrityfailure', 'local_courseblueprints');
            }
        }
        $repo = new \local_courseblueprints\local\blueprint_repository();
        $blueprintid = $repo->create($data->code, $data->name, 'Imported blueprint', $USER->id);
        $versionid = (new \local_courseblueprints\local\blueprint_version_manager())->create_revision(
            $blueprintid, $USER->id, $document['structure'], $document['manifest']);
        if ($tmpfile) {
            get_file_storage()->create_file_from_pathname([
                'contextid' => context_system::instance()->id,
                'component' => 'local_courseblueprints', 'filearea' => 'artifact',
                'itemid' => $versionid, 'filepath' => '/', 'filename' => 'blueprint.mbz',
                'userid' => $USER->id,
            ], $tmpfile);
            $DB->set_field('local_courseblueprints_ver', 'backuphash', $document['backuphash'], ['id' => $versionid]);
        }
        redirect(new moodle_url('/local/courseblueprints/versions.php', ['id' => $blueprintid]),
            get_string('createddraft', 'local_courseblueprints'));
    } finally {
        if ($tmpfile && is_file($tmpfile)) {
            unlink($tmpfile);
        }
    }
}
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
