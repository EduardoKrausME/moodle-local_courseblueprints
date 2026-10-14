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
 * Institutional course blueprints: export.php.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$artifact = optional_param('artifact', 0, PARAM_BOOL);
require_login();
require_capability('local/courseblueprints:view', context_system::instance());
$version = (new \local_courseblueprints\local\blueprint_repository())->version($id);
$blueprint = (new \local_courseblueprints\local\blueprint_repository())->get((int) $version->blueprintid);
$name = clean_filename($blueprint->code . '-v' . $version->revision);
if ($artifact) {
    $file = (new \local_courseblueprints\local\backup_adapter())->get_artifact($id);
    send_stored_file($file, 0, 0, true);
    exit;
}
$json = (new \local_courseblueprints\local\import_export_validator())->export($version);
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $name . '.json"');
header('X-Content-Type-Options: nosniff');
echo $json;
exit;
