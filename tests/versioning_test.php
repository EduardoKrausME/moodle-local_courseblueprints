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
 * Institutional course blueprints: tests versioning test.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints;

defined('MOODLE_INTERNAL') || die();

/**
 * Version and content immutability tests.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class versioning_test extends \advanced_testcase {
    /** Published versions cannot be mutated. */
    public function test_revision_numbers_are_monotonic_and_published_is_immutable(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $repo = new \local_courseblueprints\local\blueprint_repository();
        $id = $repo->create('blueprint-A', 'Original', '', $user->id);
        $manager = new \local_courseblueprints\local\blueprint_version_manager();
        $structure = (new \local_courseblueprints\local\course_structure_collector())->from_initial(['sections' => 2]);
        $manifest = (new \local_courseblueprints\local\dependency_checker())->manifest($structure);
        $v1 = $manager->create_revision($id, $user->id, $structure, $manifest);
        $v2 = $manager->create_revision($id, $user->id, $structure, $manifest);
        $this->assertEquals(1, $repo->version($v1)->revision);
        $this->assertEquals(2, $repo->version($v2)->revision);
        $manager->set_review($v1, true, 'Verified that this initial configuration contains no PII.');
        $manager->publish($v1);
        $this->assertEquals('published', $repo->version($v1)->status);
        $this->expectException(\moodle_exception::class);
        $manager->set_review($v1, true, 'Second alteration must not be possible');
    }

    /** Archived versions remain readable by IDs already pinned to courses. */
    public function test_archiving_does_not_delete_revision(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $repo = new \local_courseblueprints\local\blueprint_repository();
        $id = $repo->create('blueprint-B', 'Original', '', $user->id);
        $manager = new \local_courseblueprints\local\blueprint_version_manager();
        $structure = (new \local_courseblueprints\local\course_structure_collector())->from_initial(['sections' => 1]);
        $manifest = (new \local_courseblueprints\local\dependency_checker())->manifest($structure);
        $v = $manager->create_revision($id, $user->id, $structure, $manifest);
        $manager->set_review($v, true, 'Checked authored source and every setting.');
        $manager->publish($v);
        $manager->archive($v);
        $this->assertEquals('archived', $repo->version($v)->status);
    }

    /** Fingerprints should not depend on associative insertion order. */
    public function test_hash_is_order_invariant_for_associative_objects(): void {
        $manager = new \local_courseblueprints\local\blueprint_version_manager();
        $this->assertSame($manager::canonical_json(['a' => 1, 'b' => ['z' => 2, 'c' => 3]]),
            $manager::canonical_json(['b' => ['c' => 3, 'z' => 2], 'a' => 1]));
    }
}
