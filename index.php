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
 * Institutional course blueprints: index.php.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/courseblueprints:view', $context);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/courseblueprints/index.php'));
$PAGE->set_title(get_string('pluginname', 'local_courseblueprints'));
$PAGE->set_heading(get_string('pluginname', 'local_courseblueprints'));

$repo = new \local_courseblueprints\local\blueprint_repository();
$cancreate = has_capability('local/courseblueprints:create', $context);
$canprovision = has_capability('local/courseblueprints:provision', $context);
$blueprints = [];
foreach ($repo->catalogue() as $blueprint) {
    $versions = $repo->versions((int) $blueprint->id);
    $blueprints[] = [
        'id' => $blueprint->id, 'code' => s($blueprint->code),
        'name' => s($blueprint->name), 'description' => s($blueprint->description),
        'count' => count($versions),
        'url' => (new moodle_url('/local/courseblueprints/versions.php', ['id' => $blueprint->id]))->out(false),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_courseblueprints/catalogue', [
    'title' => get_string('catalogue', 'local_courseblueprints'), 'blueprints' => $blueprints,
    'hasblueprints' => !empty($blueprints),
    'createurl' => (new moodle_url('/local/courseblueprints/create.php'))->out(false),
    'provisionurl' => (new moodle_url('/local/courseblueprints/provision.php'))->out(false),
    'historyurl' => (new moodle_url('/local/courseblueprints/history.php'))->out(false),
    'importurl' => (new moodle_url('/local/courseblueprints/import.php'))->out(false),
    'cancreate' => $cancreate, 'canprovision' => $canprovision,
    'createbutton' => get_string('createblueprint', 'local_courseblueprints'),
    'provisionbutton' => get_string('provision', 'local_courseblueprints'),
    'historybutton' => get_string('history', 'local_courseblueprints'),
    'importbutton' => get_string('import', 'local_courseblueprints'),
    'empty' => get_string('emptycatalogue', 'local_courseblueprints'),
    'revisions' => get_string('revisions', 'local_courseblueprints'),
]);
echo $OUTPUT->footer();
