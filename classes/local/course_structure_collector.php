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
 * Institutional course blueprints: classes local course structure collector.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\local;


/**
 * Collect portable, predictable structure without user information.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_structure_collector {
    /**
     * Capture course sections and module identities without activities' user records.
     */
    public function collect(int $courseid): array {
        global $DB;
        $course = get_course($courseid);
        $sections = $DB->get_records(
            'course_sections',
            ['course' => $courseid],
            'section ASC',
            'id,section,name,summary,summaryformat,availability'
        );
        $modinfo = get_fast_modinfo($course);
        $data = [
            'format' => $course->format, 'visible' => (int) $course->visible,
            'enablecompletion' => (int) $course->enablecompletion,
            'groupmode' => (int) $course->groupmode,
            'sections' => [], 'activities' => [],
        ];
        foreach ($sections as $section) {
            $data['sections'][] = [
                'number' => (int) $section->section,
                'name' => (string) ($section->name ?? ''),
                'summary' => (string) ($section->summary ?? ''),
                'summaryformat' => (int) $section->summaryformat,
                'availability' => (string) ($section->availability ?? ''),
            ];
        }
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->deletioninprogress) {
                continue;
            }
            $data['activities'][] = [
                'section' => (int) $cm->sectionnum, 'modname' => (string) $cm->modname,
                'name' => (string) $cm->name,
                'completion' => (int) $cm->completion,
                'availability' => (string) ($cm->availability ?? ''),
            ];
        }
        usort($data['activities'], static function (array $a, array $b): int {
            return strcmp(json_encode($a), json_encode($b));
        });
        return $data;
    }

    /**
     * Capture initial configuration, never pretending that unsupported activities can be constructed.
     */
    public function from_initial(array $settings): array {
        $count = max(1, min(52, (int) ($settings['sections'] ?? 5)));
        $sections = [];
        for ($number = 0; $number <= $count; $number++) {
            $sections[] = ['number' => $number, 'name' => '', 'summary' => '',
                'summaryformat' => FORMAT_HTML, 'availability' => ''];
        }
        return ['format' => (string) ($settings['format'] ?? 'topics'), 'visible' => 0,
            'enablecompletion' => (int) ($settings['enablecompletion'] ?? 1), 'groupmode' => 0,
            'sections' => $sections, 'activities' => []];
    }
}
