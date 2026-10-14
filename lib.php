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
 * File serving handler.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Never expose blueprint artifacts via an unauthenticated URL.
 *
 * @param stdClass $course Course.
 * @param stdClass $cm Course module.
 * @param context $context Request context.
 * @param string $filearea File area.
 * @param array $args File path.
 * @param bool $forcedownload Force a download.
 * @param array $options Download options.
 * @return bool
 */
function local_courseblueprints_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
): bool {
    require_login();
    require_capability('local/courseblueprints:view', context_system::instance());
    return false;
}
