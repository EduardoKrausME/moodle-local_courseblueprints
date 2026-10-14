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
 * Institutional course blueprints: classes local course comparison service.php.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Informational course-versus-pinned-version structural analysis.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_comparison_service {
    /** Compare portable arrays, including repeated activities as a multiset. */
    public function diff(array $baseline, array $actual): array {
        $differences = [];
        $left = [];
        $right = [];
        foreach ($baseline['sections'] ?? [] as $section) {
            $left[(int) $section['number']] = $section;
        }
        foreach ($actual['sections'] ?? [] as $section) {
            $right[(int) $section['number']] = $section;
        }
        foreach (array_diff(array_keys($left), array_keys($right)) as $number) {
            $differences[] = ['type' => 'section_removed', 'detail' => (string) $number];
        }
        foreach (array_diff(array_keys($right), array_keys($left)) as $number) {
            $differences[] = ['type' => 'section_added', 'detail' => (string) $number];
        }
        foreach (array_intersect(array_keys($left), array_keys($right)) as $number) {
            foreach (['name', 'summary', 'availability'] as $field) {
                if (($left[$number][$field] ?? '') !== ($right[$number][$field] ?? '')) {
                    $differences[] = ['type' => 'section_changed', 'detail' => $number . ':' . $field];
                }
            }
        }
        foreach (['format', 'enablecompletion', 'groupmode'] as $field) {
            if (($baseline[$field] ?? null) !== ($actual[$field] ?? null)) {
                $differences[] = ['type' => 'setting_changed', 'detail' => $field];
            }
        }
        $before = $this->inventory($baseline['activities'] ?? []);
        $after = $this->inventory($actual['activities'] ?? []);
        foreach ($before as $signature => $count) {
            for ($i = $after[$signature] ?? 0; $i < $count; $i++) {
                $differences[] = ['type' => 'activity_missing', 'detail' => $signature];
            }
        }
        foreach ($after as $signature => $count) {
            for ($i = $before[$signature] ?? 0; $i < $count; $i++) {
                $differences[] = ['type' => 'activity_extra', 'detail' => $signature];
            }
        }
        $oldsettings = $this->setting_inventory($baseline['activities'] ?? []);
        $newsettings = $this->setting_inventory($actual['activities'] ?? []);
        foreach (array_intersect(array_keys($oldsettings), array_keys($newsettings)) as $identity) {
            if (($before[$identity] ?? 0) === ($after[$identity] ?? 0) &&
                    $oldsettings[$identity] !== $newsettings[$identity]) {
                $differences[] = ['type' => 'activity_setting_changed', 'detail' => $identity];
            }
        }
        usort($differences, static function($a, $b): int {
            return strcmp($a['type'] . $a['detail'], $b['type'] . $b['detail']);
        });
        return $differences;
    }

    /** Collect normalized occurrences without unstable module IDs. */
    private function inventory(array $activities): array {
        $result = [];
        foreach ($activities as $activity) {
            $signature = (int) $activity['section'] . ':' . $activity['modname'] . ':' . $activity['name'];
            $result[$signature] = ($result[$signature] ?? 0) + 1;
        }
        return $result;
    }

    /** Compare completion and availability settings by repeated activity identity. */
    private function setting_inventory(array $activities): array {
        $result = [];
        foreach ($activities as $activity) {
            $identity = (int) $activity['section'] . ':' . $activity['modname'] . ':' . $activity['name'];
            $result[$identity][] = json_encode([
                (int) ($activity['completion'] ?? 0), (string) ($activity['availability'] ?? ''),
            ]);
        }
        foreach ($result as &$settings) {
            sort($settings, SORT_STRING);
        }
        return $result;
    }

    /** Analyze a course against its immutable post-provisioning baseline. */
    public function compare_course(int $courseid): array {
        global $DB;
        $map = $DB->get_record('local_cb_coursemap', ['courseid' => $courseid], '*', MUST_EXIST);
        $before = json_decode($map->baseline, true, 512, JSON_THROW_ON_ERROR);
        $actual = (new course_structure_collector())->collect($courseid);
        $version = (new blueprint_repository())->version((int) $map->versionid);
        $manifest = json_decode($version->manifest, true, 512, JSON_THROW_ON_ERROR);
        $difference = $this->diff($before, $actual);
        foreach ((new dependency_checker())->check($manifest) as $missing) {
            $difference[] = ['type' => 'dependency_unavailable', 'detail' => $missing];
        }
        return $difference;
    }
}
