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
 * Institutional course blueprints: classes local provisioning service.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Resumable, lock-protected provisioning. Existing courses are never deleted.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provisioning_service {
    /** Parameter whitelist; no arbitrary course settings can be injected. */
    public function normalize(array $input): array {
        $data = [
            'categoryid' => (int) ($input['categoryid'] ?? 0),
            'fullname' => trim((string) ($input['fullname'] ?? '')),
            'shortname' => trim((string) ($input['shortname'] ?? '')),
            'startdate' => max(0, (int) ($input['startdate'] ?? 0)),
            'enddate' => max(0, (int) ($input['enddate'] ?? 0)),
        ];
        if (!$data['categoryid'] || !$data['fullname'] || !$data['shortname'] ||
                \core\text::strlen($data['fullname']) > 254 || \core\text::strlen($data['shortname']) > 254 ||
                ($data['enddate'] && $data['startdate'] > $data['enddate'])) {
            throw new \invalid_parameter_exception('Invalid provisioning parameters');
        }
        return $data;
    }

    /** Build one batch with exactly one row per target; requestkey protects retries. */
    public function enqueue(int $versionid, array $targets, string $requestkey, int $userid): int {
        global $DB;
        $version = (new blueprint_repository())->version($versionid);
        if ($version->status !== 'published') {
            throw new \moodle_exception('unpublishedversion', 'local_courseblueprints');
        }
        if (!preg_match('/^[a-f0-9]{64}$/D', $requestkey) || !$targets || count($targets) > 100) {
            throw new \invalid_parameter_exception('Invalid request key or target count');
        }
        if ((new dependency_checker())->check(json_decode($version->manifest, true, 512, JSON_THROW_ON_ERROR))) {
            throw new \moodle_exception('missingdependencies', 'local_courseblueprints');
        }
        $lock = \core\lock\lock_config::get_lock_factory('local_courseblueprints')->get_lock('batch_' . $requestkey, 30);
        if (!$lock) {
            throw new \moodle_exception('lockfailed', 'local_courseblueprints');
        }
        try {
            $data = array_map([$this, 'normalize'], $targets);
            $requesthash = hash('sha256', blueprint_version_manager::canonical_json([
                'versionid' => $versionid, 'targets' => $data,
            ]));
            $existing = $DB->get_record('local_courseblueprints_run', ['requestkey' => $requestkey]);
            if ($existing) {
                if ((int) $existing->versionid !== $versionid || (int) $existing->userid !== $userid ||
                        !hash_equals($existing->requesthash, $requesthash)) {
                    throw new \moodle_exception('idempotencyconflict', 'local_courseblueprints');
                }
                return (int) $existing->id;
            }
            foreach ($data as $target) {
                $context = \context_coursecat::instance($target['categoryid']);
                if (!has_capability('local/courseblueprints:provision', \context_system::instance(), $userid) ||
                        !has_capability('moodle/course:create', $context, $userid)) {
                    throw new \required_capability_exception($context, 'moodle/course:create', 'nopermissions', '');
                }
                if ($DB->record_exists('course', ['shortname' => $target['shortname']])) {
                    throw new \moodle_exception('shortnameexists', 'local_courseblueprints');
                }
            }
            $transaction = $DB->start_delegated_transaction();
            $runid = $DB->insert_record('local_courseblueprints_run', (object) [
                'versionid' => $versionid, 'requestkey' => $requestkey,
                'requesthash' => $requesthash, 'userid' => $userid, 'timecreated' => time(),
            ]);
            foreach ($data as $i => $target) {
                $itemid = $DB->insert_record('local_courseblueprints_itm', (object) array_merge($target, [
                    'runid' => $runid, 'rownum' => $i, 'versionid' => $versionid,
                    'params' => '{}', 'status' => 'queued', 'attempts' => 0,
                    'timecreated' => time(), 'timemodified' => time(),
                ]));
                $task = new \local_courseblueprints\task\provision_course_task();
                $task->set_custom_data(['itemid' => $itemid]);
                \core\task\manager::queue_adhoc_task($task, true);
            }
            $transaction->allow_commit();
            return $runid;
        } finally {
            $lock->release();
        }
    }

    /** Process a single request; a prior partially created course becomes manual review. */
    public function execute(int $itemid): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        $lock = \core\lock\lock_config::get_lock_factory('local_courseblueprints')->get_lock('item_' . $itemid, 30);
        if (!$lock) {
            throw new \moodle_exception('lockfailed', 'local_courseblueprints');
        }
        try {
            $item = $DB->get_record('local_courseblueprints_itm', ['id' => $itemid], '*', MUST_EXIST);
            if (in_array($item->status, ['complete', 'needs_review', 'failed_review'], true)) {
                return;
            }
            $version = (new blueprint_repository())->version((int) $item->versionid);
            if (!in_array($version->status, ['published', 'archived'], true)) {
                throw new \moodle_exception('unpublishedversion', 'local_courseblueprints');
            }
            $run = $DB->get_record('local_courseblueprints_run', ['id' => $item->runid], '*', MUST_EXIST);
            $categorycontext = \context_coursecat::instance((int) $item->categoryid);
            if (!has_capability('local/courseblueprints:provision', \context_system::instance(), $run->userid) ||
                    !has_capability('moodle/course:create', $categorycontext, $run->userid)) {
                throw new \required_capability_exception($categorycontext,
                    'moodle/course:create', 'nopermissions', '');
            }
            if ($item->courseid) {
                // A restore may have failed midway. Never run it again on this course.
                $this->record_status($item, 'failed_review', 'partial_course',
                    'Existing provisioned course requires manual review; automatic restore disabled');
                return;
            }
            // Sentinel makes a post-create crash detectable without creating a second course.
            $sentinel = 'cb-' . $itemid;
            if ($DB->record_exists('course', ['idnumber' => $sentinel])) {
                $this->record_status($item, 'failed_review', 'orphan_detected',
                    'A course with this provisioning sentinel already exists; reconcile manually');
                return;
            }
            $item->attempts++;
            $this->record_status($item, 'processing');
            $DB->set_field('local_courseblueprints_itm', 'attempts', $item->attempts, ['id' => $itemid]);
            $manifest = json_decode($version->manifest, true, 512, JSON_THROW_ON_ERROR);
            if ((new dependency_checker())->check($manifest)) {
                throw new \moodle_exception('missingdependencies', 'local_courseblueprints');
            }
            if ($version->backuphash) {
                (new backup_adapter())->verify_artifact($version);
            }
            $structure = json_decode($version->structure, true, 512, JSON_THROW_ON_ERROR);
            $course = create_course((object) [
                'category' => $item->categoryid,
                'fullname' => $item->fullname, 'shortname' => $item->shortname,
                'idnumber' => $sentinel, 'format' => $structure['format'],
                'visible' => 0, 'startdate' => (int) $item->startdate,
                'enddate' => (int) $item->enddate,
                'enablecompletion' => (int) ($structure['enablecompletion'] ?? 1),
                'groupmode' => (int) ($structure['groupmode'] ?? 0),
            ]);
            // Durable markers before any nontransactional Restore API work.
            $DB->set_field('local_courseblueprints_itm', 'courseid', $course->id, ['id' => $itemid]);
            $DB->insert_record('local_courseblueprints_map', (object) [
                'courseid' => $course->id, 'versionid' => $version->id, 'itemid' => $itemid,
                'baseline' => $version->structure, 'timecreated' => time(),
            ]);
            if ($version->backuphash) {
                (new restore_adapter())->restore($version, (int) $course->id, (int) $run->userid);
            } else {
                $this->create_sections($course, $structure);
            }
            // Restore may alter metadata; parameterized settings remain authoritative.
            update_course((object) [
                'id' => $course->id, 'fullname' => $item->fullname,
                'shortname' => $item->shortname, 'category' => $item->categoryid,
                'startdate' => $item->startdate, 'enddate' => $item->enddate,
                'visible' => 0, 'idnumber' => $sentinel,
            ]);
            $report = (new course_comparison_service())->compare_course((int) $course->id);
            $DB->update_record('local_courseblueprints_itm', (object) [
                'id' => $itemid, 'status' => empty($report) ? 'complete' : 'needs_review',
                'validation' => json_encode($report, JSON_THROW_ON_ERROR), 'timemodified' => time(),
            ]);
            \local_courseblueprints\event\course_provisioned::create([
                'context' => \context_course::instance($course->id),
                'objectid' => $course->id, 'relateduserid' => $run->userid,
                'other' => ['versionid' => (int) $version->id],
            ])->trigger();
        } catch (\Throwable $e) {
            if (isset($item)) {
                $current = $DB->get_record('local_courseblueprints_itm', ['id' => $itemid]);
                $status = ($current && $current->courseid) ? 'failed_review' : 'queued';
                $this->record_status($item, $status, 'processing_failed', $e->getMessage());
            }
            throw $e;
        } finally {
            $lock->release();
        }
    }

    /** Create empty sections via the Moodle course API. */
    private function create_sections(\stdClass $course, array $structure): void {
        global $DB;
        $numbers = array_column($structure['sections'] ?? [], 'number');
        $numbers = array_filter(array_map('intval', $numbers), static function(int $number): bool {
            return $number > 0 && $number <= 52;
        });
        if ($numbers) {
            course_create_sections_if_missing($course, $numbers);
        }
        foreach ($structure['sections'] ?? [] as $section) {
            $existing = $DB->get_record('course_sections', [
                'course' => $course->id, 'section' => (int) $section['number'],
            ]);
            if ($existing && !empty($section['name'])) {
                course_update_section($course, $existing, ['name' => $section['name']]);
            }
        }
    }

    /** Persist an individual outcome, without deleting course data on failure. */
    private function record_status(\stdClass $item, string $status, ?string $code = null,
            ?string $message = null): void {
        global $DB;
        $DB->update_record('local_courseblueprints_itm', (object) [
            'id' => $item->id, 'status' => $status, 'errorcode' => $code,
            'errormessage' => $message, 'timemodified' => time(),
        ]);
    }
}
