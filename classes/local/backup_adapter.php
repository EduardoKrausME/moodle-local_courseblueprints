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
 * Institutional course blueprints: classes local backup adapter.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\local;


/**
 * Moodle-native backup adapter. No user information or grade history is included.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_adapter {
    /**
     * Capture a course to the private File API area of a draft revision.
     */
    public function capture(int $versionid, int $userid): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        $version = (new blueprint_repository())->version($versionid);
        if ($version->status !== 'draft' || !$version->sourcecourseid) {
            throw new \moodle_exception('immutableversion', 'local_courseblueprints');
        }
        $lock = \core\lock\lock_config::get_lock_factory('local_courseblueprints')->get_lock(
            'capture_' . $versionid,
            60
        );
        if (!$lock) {
            throw new \moodle_exception('lockfailed', 'local_courseblueprints');
        }
        $controller = null;
        try {
            $controller = new \backup_controller(
                \backup::TYPE_1COURSE,
                (int) $version->sourcecourseid,
                \backup::FORMAT_MOODLE,
                \backup::INTERACTIVE_NO,
                \backup::MODE_GENERAL,
                $userid
            );
            $plan = $controller->get_plan();
            foreach (
                ['users', 'anonymize', 'role_assignments', 'user_files', 'logs',
                    'grade_histories', 'badges', 'enrolments'] as $name
            ) {
                $setting = $this->optional_setting($plan, $name);
                if ($setting) {
                    $setting->set_value(0);
                }
            }
            // Keep module definitions, not submissions, completions or actual user interactions.
            foreach (['activities', 'blocks', 'filters', 'questionbank'] as $name) {
                $setting = $this->optional_setting($plan, $name);
                if ($setting) {
                    $setting->set_value(1);
                }
            }
            // A course edit between preview and backup would invalidate the revision.
            $before = json_decode($version->structure, true, 512, JSON_THROW_ON_ERROR);
            $current = (new course_structure_collector())->collect((int) $version->sourcecourseid);
            if (
                blueprint_version_manager::canonical_json($before) !==
                    blueprint_version_manager::canonical_json($current)
            ) {
                throw new \moodle_exception('sourcestale', 'local_courseblueprints');
            }
            $controller->execute_plan();
            $results = $controller->get_results();
            if (
                empty($results['backup_destination']) ||
                    !($results['backup_destination'] instanceof \stored_file)
            ) {
                throw new \moodle_exception('backupfailed', 'local_courseblueprints');
            }
            $source = $results['backup_destination'];
            $fs = get_file_storage();
            $context = \context_system::instance();
            $old = $fs->get_file(
                $context->id,
                'local_courseblueprints',
                'artifact',
                $versionid,
                '/',
                'blueprint.mbz'
            );
            if ($old) {
                $old->delete();
            }
            $fs->create_file_from_storedfile([
                'contextid' => $context->id, 'component' => 'local_courseblueprints',
                'filearea' => 'artifact', 'itemid' => $versionid, 'filepath' => '/',
                'filename' => 'blueprint.mbz', 'userid' => $userid,
            ], $source);
            // Remove our generated native backup file after the File API copy.
            $source->delete();
            $stored = $this->get_artifact($versionid);
            $DB->update_record('local_courseblueprints_ver', (object) [
                'id' => $versionid, 'backuphash' => $this->sha256($stored),
            ]);
        } finally {
            if ($controller) {
                $controller->destroy();
            }
            $lock->release();
        }
    }

    /**
     * Some Moodle releases omit settings; optional features must not break capture.
     */
    private function optional_setting(\backup_plan $plan, string $name) {
        try {
            return $plan->get_setting($name);
        } catch (\base_plan_exception $e) {
            return null;
        }
    }

    /**
     * Find only the version's private stored file.
     */
    public function get_artifact(int $versionid): \stored_file {
        $fs = get_file_storage();
        $stored = $fs->get_file(
            \context_system::instance()->id,
            'local_courseblueprints',
            'artifact',
            $versionid,
            '/',
            'blueprint.mbz'
        );
        if (!$stored) {
            throw new \moodle_exception('backupmissing', 'local_courseblueprints');
        }
        return $stored;
    }

    /**
     * Hash full file contents, not Moodle's SHA-1 content-addressed storage key.
     */
    public function sha256(\stored_file $stored): string {
        $stream = $stored->get_content_file_handle();
        try {
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);
            return hash_final($context);
        } finally {
            fclose($stream);
        }
    }

    /**
     * Fail closed if someone replaced the published archive.
     */
    public function verify_artifact(\stdClass $version): void {
        $file = $this->get_artifact((int) $version->id);
        if (
            empty($version->backuphash) ||
                !hash_equals($version->backuphash, $this->sha256($file))
        ) {
            throw new \moodle_exception('integrityfailure', 'local_courseblueprints');
        }
    }
}
