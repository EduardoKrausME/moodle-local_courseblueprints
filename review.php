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
 * Institutional course blueprints: review.php.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
require_login();
require_capability('local/courseblueprints:publish', context_system::instance());
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/courseblueprints/review.php', ['id' => $id]));
$PAGE->set_title(get_string('review', 'local_courseblueprints'));
$PAGE->set_heading(get_string('review', 'local_courseblueprints'));
$repo = new \local_courseblueprints\local\blueprint_repository();
$version = $repo->version($id);
if ($version->status !== 'draft') {
    throw new moodle_exception('immutableversion', 'local_courseblueprints');
}
$form = new \local_courseblueprints\form\review_form(null, ['versionid' => $id]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/courseblueprints/versions.php', ['id' => $version->blueprintid]));
}
if ($data = $form->get_data()) {
    require_sesskey();
    if ((int) $data->versionid !== $id) {
        throw new moodle_exception('invalidversion', 'local_courseblueprints');
    }
    (new \local_courseblueprints\local\blueprint_version_manager())->set_review($id,
        (bool) $data->reviewed, $data->reviewnotes);
    redirect(new moodle_url('/local/courseblueprints/versions.php', ['id' => $version->blueprintid]));
}
$structure = json_decode($version->structure, true, 512, JSON_THROW_ON_ERROR);
$warnings = (new \local_courseblueprints\local\privacy_guard())->inspect($structure);
$warnings = array_merge($warnings, (new \local_courseblueprints\local\dependency_checker())->check(
    json_decode($version->manifest, true, 512, JSON_THROW_ON_ERROR)));
echo $OUTPUT->header();
echo $OUTPUT->notification(get_string('reviewwarning', 'local_courseblueprints'), 'warning');
echo $OUTPUT->render_from_template('local_courseblueprints/warnings', [
    'warnings' => array_map(static function(string $warning): array {
        return ['text' => s($warning)];
    }, $warnings),
]);
$form->display();
echo $OUTPUT->footer();
