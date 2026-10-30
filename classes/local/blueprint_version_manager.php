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
 * Institutional course blueprints: classes local blueprint version manager.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\local;


/**
 * Version creation, publication and archive transitions.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blueprint_version_manager {
    /**
     * Deterministic canonical JSON for content hashes.
     */
    public static function canonical_json(array $value): string {
        self::sort_recursive($value);
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Recursively sort associative objects, retaining list ordering.
     */
    private static function sort_recursive(array &$value): void {
        if (!array_is_list($value)) {
            ksort($value, SORT_STRING);
        }
        foreach ($value as &$item) {
            if (is_array($item)) {
                self::sort_recursive($item);
            }
        }
    }

    /**
     * Create a new, never-in-place revision while holding a Moodle lock.
     */
    public function create_revision(
        int $blueprintid,
        int $authorid,
        array $structure,
        array $manifest,
        ?int $sourcecourseid = null
    ): int {
        global $DB;
        $repo = new blueprint_repository();
        $repo->get($blueprintid);
        $factory = \core\lock\lock_config::get_lock_factory('local_courseblueprints');
        $lock = $factory->get_lock('revision_' . $blueprintid, 30);
        if (!$lock) {
            throw new \moodle_exception('lockfailed', 'local_courseblueprints');
        }
        try {
            $revision = (int) $DB->get_field_sql(
                'SELECT MAX(revision) FROM {local_courseblueprints_ver} WHERE blueprintid = ?',
                [$blueprintid]
            );
            $payload = ['structure' => $structure, 'manifest' => $manifest];
            return $DB->insert_record('local_courseblueprints_ver', (object) [
                'blueprintid' => $blueprintid, 'revision' => $revision + 1,
                'status' => 'draft', 'authorid' => $authorid,
                'sourcecourseid' => $sourcecourseid, 'manifest' => self::canonical_json($manifest),
                'structure' => self::canonical_json($structure),
                'contenthash' => hash('sha256', self::canonical_json($payload)),
                'backuphash' => null, 'reviewed' => 0, 'reviewnotes' => null,
                'reviewerid' => null, 'reviewedat' => null,
                'timecreated' => time(), 'publishedat' => null,
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Mutate draft only. Includes independent review and evidence from content scanning.
     */
    public function set_review(int $versionid, bool $approved, string $notes): void {
        global $DB, $USER;
        $version = (new blueprint_repository())->version($versionid);
        if ($version->status !== 'draft') {
            throw new \moodle_exception('immutableversion', 'local_courseblueprints');
        }
        if (trim($notes) === '') {
            throw new \invalid_parameter_exception(get_string('invalidreviewrecord', 'local_courseblueprints'));
        }
        $DB->update_record('local_courseblueprints_ver', (object) [
            'id' => $versionid, 'reviewed' => (int) $approved, 'reviewnotes' => trim($notes),
            'reviewerid' => (int) $USER->id, 'reviewedat' => time(),
        ]);
    }

    /**
     * Publish only a reviewed revision with satisfied dependencies and intact artifacts.
     */
    public function publish(int $versionid): void {
        global $DB;
        $version = (new blueprint_repository())->version($versionid);
        if ($version->status !== 'draft') {
            throw new \moodle_exception('immutableversion', 'local_courseblueprints');
        }
        if (!$version->reviewed) {
            throw new \moodle_exception('reviewrequired', 'local_courseblueprints');
        }
        $manifest = json_decode($version->manifest, true, 512, JSON_THROW_ON_ERROR);
        $structure = json_decode($version->structure, true, 512, JSON_THROW_ON_ERROR);
        $expectedhash = hash('sha256', self::canonical_json([
            'structure' => $structure, 'manifest' => $manifest,
        ]));
        if (!hash_equals($expectedhash, $version->contenthash)) {
            throw new \moodle_exception('integrityfailure', 'local_courseblueprints');
        }
        if (empty($version->backuphash) && !empty($structure['activities'])) {
            throw new \moodle_exception('backupmissing', 'local_courseblueprints');
        }
        $results = (new dependency_checker())->check($manifest);
        if ($results) {
            throw new \moodle_exception('missingdependencies', 'local_courseblueprints');
        }
        if ($version->sourcecourseid || $version->backuphash) {
            (new backup_adapter())->verify_artifact($version);
        }
        $DB->update_record('local_courseblueprints_ver', (object) [
            'id' => $versionid, 'status' => 'published', 'publishedat' => time(),
        ]);
        \local_courseblueprints\event\blueprint_published::create([
            'context' => \context_system::instance(), 'objectid' => $versionid,
            'other' => ['blueprintid' => (int) $version->blueprintid],
        ])->trigger();
    }

    /**
     * Archival affects catalogue availability, never provisioning history.
     */
    public function archive(int $versionid): void {
        global $DB;
        $version = (new blueprint_repository())->version($versionid);
        if ($version->status !== 'published') {
            throw new \moodle_exception('immutableversion', 'local_courseblueprints');
        }
        $DB->update_record('local_courseblueprints_ver', (object) ['id' => $versionid, 'status' => 'archived']);
    }
}
