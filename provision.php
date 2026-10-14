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
 * Institutional course blueprints: provision.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/courseblueprints:provision', $context);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/courseblueprints/provision.php'));
$PAGE->set_title(get_string('provision', 'local_courseblueprints'));
$PAGE->set_heading(get_string('provision', 'local_courseblueprints'));
$repo = new \local_courseblueprints\local\blueprint_repository();
$service = new \local_courseblueprints\local\provisioning_service();
$step = optional_param('step', 1, PARAM_INT);

if ($step === 3 && optional_param('action', '', PARAM_ALPHA) === 'confirm') {
    require_sesskey();
    $nonce = required_param('nonce', PARAM_ALPHANUMEXT);
    $draft = $SESSION->local_cb_wizard ?? null;
    if (!$draft || !hash_equals($draft['nonce'], $nonce) || $draft['userid'] != $USER->id) {
        throw new moodle_exception('invalidpreview', 'local_courseblueprints');
    }
    $runid = $service->enqueue(
        $draft['versionid'],
        $draft['targets'],
        $draft['requestkey'],
        $USER->id
    );
    unset($SESSION->local_cb_wizard);
    redirect(
        new moodle_url('/local/courseblueprints/history.php', ['id' => $runid]),
        get_string('queued', 'local_courseblueprints')
    );
}

if ($step === 3) {
    $draft = $SESSION->local_cb_wizard ?? null;
    if (!$draft || $draft['userid'] != $USER->id) {
        redirect(new moodle_url('/local/courseblueprints/provision.php'));
    }
    $version = $repo->version($draft['versionid']);
    $issues = (new \local_courseblueprints\local\dependency_checker())->check(
        json_decode($version->manifest, true, 512, JSON_THROW_ON_ERROR)
    );
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_courseblueprints/preview', [
        'rows' => array_map(static function (array $target): array {
            return ['fullname' => s($target['fullname']), 'shortname' => s($target['shortname']),
                'categoryid' => $target['categoryid'],
                'startdate' => userdate($target['startdate']),
                'enddate' => $target['enddate'] ? userdate($target['enddate']) : '-'];
        }, $draft['targets']),
        'nonce' => $draft['nonce'], 'sesskey' => sesskey(),
        'actionurl' => (new moodle_url('/local/courseblueprints/provision.php', ['step' => 3]))->out(false),
        'hasissues' => !empty($issues), 'issues' => implode(', ', $issues),
        'confirm' => get_string('confirmprovision', 'local_courseblueprints'),
        'title' => get_string('preview', 'local_courseblueprints'),
        'category' => get_string('category', 'local_courseblueprints'),
        'fullname' => get_string('fullname', 'local_courseblueprints'),
        'shortname' => get_string('shortname', 'local_courseblueprints'),
    ]);
    echo $OUTPUT->footer();
    exit;
}

if ($step === 2) {
    $versionid = required_param('versionid', PARAM_INT);
    $version = $repo->version($versionid);
    if ($version->status !== 'published') {
        throw new moodle_exception('unpublishedversion', 'local_courseblueprints');
    }
    $form = new \local_courseblueprints\form\provision_settings_form(null, ['versionid' => $versionid]);
    if ($form->is_cancelled()) {
        redirect(new moodle_url('/local/courseblueprints/provision.php'));
    }
    if ($data = $form->get_data()) {
        require_sesskey();
        if ((int) $data->versionid !== $versionid) {
            throw new moodle_exception('invalidversion', 'local_courseblueprints');
        }
        $targets = [];
        $lines = preg_split('/\R/u', trim($data->batch ?? ''));
        if (!empty(trim($data->batch ?? ''))) {
            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $columns = explode('|', $line);
                if (count($columns) !== 2) {
                    throw new invalid_parameter_exception('Every batch row must have FULLNAME|SHORTNAME');
                }
                $targets[] = $service->normalize([
                    'categoryid' => $data->categoryid, 'fullname' => $columns[0],
                    'shortname' => $columns[1], 'startdate' => $data->startdate,
                    'enddate' => $data->enddate,
                ]);
            }
        } else {
            $targets[] = $service->normalize([
                'categoryid' => $data->categoryid, 'fullname' => $data->fullname,
                'shortname' => $data->shortname, 'startdate' => $data->startdate,
                'enddate' => $data->enddate,
            ]);
        }
        if (!$targets || count($targets) > 100) {
            throw new invalid_parameter_exception('Batch must contain between 1 and 100 courses');
        }
        $shortnames = array_column($targets, 'shortname');
        if (count(array_unique($shortnames)) !== count($shortnames)) {
            throw new invalid_parameter_exception('Duplicate short names in batch');
        }
        foreach ($targets as $target) {
            require_capability('moodle/course:create', context_coursecat::instance($target['categoryid']));
        }
        $SESSION->local_cb_wizard = [
            'userid' => $USER->id, 'versionid' => $versionid, 'targets' => $targets,
            'nonce' => bin2hex(random_bytes(16)), 'requestkey' => hash('sha256', random_bytes(32)),
        ];
        redirect(new moodle_url('/local/courseblueprints/provision.php', ['step' => 3]));
    }
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

$options = [];
foreach ($repo->catalogue() as $blueprint) {
    foreach ($repo->versions((int) $blueprint->id) as $version) {
        if ($version->status === 'published') {
            $options[$version->id] = $blueprint->name . ' — V' . $version->revision;
        }
    }
}
if (!$options) {
    redirect(
        new moodle_url('/local/courseblueprints/index.php'),
        get_string('nopublished', 'local_courseblueprints')
    );
}
$form = new \local_courseblueprints\form\provision_select_form(null, ['versions' => $options]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/courseblueprints/index.php'));
}
if ($data = $form->get_data()) {
    require_sesskey();
    if (!isset($options[$data->versionid])) {
        throw new moodle_exception('invalidversion', 'local_courseblueprints');
    }
    redirect(new moodle_url(
        '/local/courseblueprints/provision.php',
        ['step' => 2, 'versionid' => $data->versionid]
    ));
}
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
