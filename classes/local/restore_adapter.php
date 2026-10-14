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
 * Institutional course blueprints: classes local restore adapter.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Native Moodle restore with controlled temporary-file cleanup.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_adapter {
    /** Restore the version archive into a newly created empty course. */
    public function restore(\stdClass $version, int $courseid, int $userid): void {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        $backup = new backup_adapter();
        $backup->verify_artifact($version);
        $restoreid = random_string(32);
        $directory = make_backup_temp_directory($restoreid);
        $controller = null;
        try {
            $path = $directory . '/blueprint.mbz';
            $backup->get_artifact((int) $version->id)->copy_content_to($path);
            $packer = get_file_packer('application/vnd.moodle.backup');
            if (!$packer->extract_to_pathname($path, $directory)) {
                throw new \moodle_exception('restorefailed', 'local_courseblueprints');
            }
            unlink($path);
            $controller = new \restore_controller($restoreid, $courseid, \backup::INTERACTIVE_NO,
                \backup::MODE_GENERAL, $userid, \backup::TARGET_CURRENT_ADDING);
            $plan = $controller->get_plan();
            foreach (['users', 'role_assignments', 'user_files', 'logs', 'grade_histories',
                    'enrolments'] as $name) {
                try {
                    $setting = $plan->get_setting($name);
                    $setting->set_value(0);
                } catch (\base_plan_exception $e) {
                    // Older Moodle/plugin restore plans may omit optional settings.
                }
            }
            if (!$controller->execute_precheck()) {
                throw new \moodle_exception('restorefailed', 'local_courseblueprints');
            }
            $controller->execute_plan();
        } finally {
            if ($controller) {
                $controller->destroy();
            }
            // Only plugin-owned temporary files; never the course itself.
            if (is_dir($directory)) {
                fulldelete($directory);
            }
        }
    }
}
