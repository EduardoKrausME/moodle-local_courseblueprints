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
 * Institutional course blueprints: tests comparison test.php.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints;

defined('MOODLE_INTERNAL') || die();

/**
 * Informative, deterministic structural divergence checks.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comparison_test extends \advanced_testcase {
    /** Detect changes without attempting course modifications. */
    public function test_structural_differences_reported(): void {
        $base = ['format' => 'topics', 'sections' => [
            ['number' => 0, 'name' => 'General'], ['number' => 1, 'name' => 'Unit A'],
        ], 'activities' => [
            ['section' => 1, 'modname' => 'forum', 'name' => 'Discussion'],
            ['section' => 1, 'modname' => 'forum', 'name' => 'Discussion'],
        ]];
        $actual = ['format' => 'weeks', 'sections' => [
            ['number' => 0, 'name' => 'General'], ['number' => 2, 'name' => 'Unit B'],
        ], 'activities' => [
            ['section' => 1, 'modname' => 'forum', 'name' => 'Discussion'],
            ['section' => 2, 'modname' => 'assign', 'name' => 'Submission'],
        ]];
        $diff = (new \local_courseblueprints\local\course_comparison_service())->diff($base, $actual);
        $types = array_column($diff, 'type');
        $this->assertContains('section_removed', $types);
        $this->assertContains('section_added', $types);
        $this->assertContains('activity_missing', $types);
        $this->assertContains('activity_extra', $types);
        $this->assertContains('setting_changed', $types);
    }
}
