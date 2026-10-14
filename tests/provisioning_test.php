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
 * Institutional course blueprints: tests provisioning test.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints;


/**
 * Parameterization and idempotent queuing tests.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_courseblueprints\local\provisioning_service
 */
final class provisioning_test extends \advanced_testcase {
    /**
     * Repeated batch request must return the existing run ID without duplicating rows.
     */
    public function test_idempotent_batch_request(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $user = get_admin();
        $category = $this->getDataGenerator()->create_category();
        $repo = new \local_courseblueprints\local\blueprint_repository();
        $blueprintid = $repo->create('idempotencytest', 'Idempotency', '', $user->id);
        $manager = new \local_courseblueprints\local\blueprint_version_manager();
        $structure = (new \local_courseblueprints\local\course_structure_collector())->from_initial(['sections' => 2]);
        $manifest = (new \local_courseblueprints\local\dependency_checker())->manifest($structure);
        $versionid = $manager->create_revision($blueprintid, $user->id, $structure, $manifest);
        $manager->set_review($versionid, true, 'Checked initial blank configuration');
        $manager->publish($versionid);
        $service = new \local_courseblueprints\local\provisioning_service();
        $key = hash('sha256', 'same-request');
        $targets = [['categoryid' => $category->id, 'fullname' => 'Test blueprint course',
            'shortname' => 'TEST-BLUEPRINT-001', 'startdate' => 0, 'enddate' => 0]];
        $first = $service->enqueue($versionid, $targets, $key, $user->id);
        $second = $service->enqueue($versionid, $targets, $key, $user->id);
        $this->assertEquals($first, $second);
        $this->assertEquals(1, $DB->count_records('local_courseblueprints_itm', ['runid' => $first]));
    }

    /**
     * Previously created orphan sentinel is not claimed or created again.
     */
    public function test_orphan_after_crash_requires_manual_review(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $admin = get_admin();
        $category = $this->getDataGenerator()->create_category();
        $repo = new \local_courseblueprints\local\blueprint_repository();
        $blueprintid = $repo->create('crashrecover', 'Recovery', '', $admin->id);
        $manager = new \local_courseblueprints\local\blueprint_version_manager();
        $structure = (new \local_courseblueprints\local\course_structure_collector())->from_initial(['sections' => 1]);
        $manifest = (new \local_courseblueprints\local\dependency_checker())->manifest($structure);
        $version = $manager->create_revision($blueprintid, $admin->id, $structure, $manifest);
        $manager->set_review($version, true, 'Manually inspected empty initial configuration');
        $manager->publish($version);
        $service = new \local_courseblueprints\local\provisioning_service();
        $run = $service->enqueue($version, [[
            'categoryid' => $category->id, 'fullname' => 'Target', 'shortname' => 'CBRECOVERY',
        ]], hash('sha256', 'crash'), $admin->id);
        $item = $DB->get_record('local_courseblueprints_itm', ['runid' => $run], '*', MUST_EXIST);
        $existing = $this->getDataGenerator()->create_course([
            'idnumber' => 'cb-' . $item->id, 'shortname' => 'ALREADY-EXISTS',
        ]);
        $service->execute((int) $item->id);
        $after = $DB->get_record('local_courseblueprints_itm', ['id' => $item->id], '*', MUST_EXIST);
        $this->assertSame('failed_review', $after->status);
        $this->assertEquals(0, $after->courseid);
        $this->assertTrue($DB->record_exists('course', ['id' => $existing->id]));
    }

    /**
     * Validate dates and course names before scheduling tasks.
     */
    public function test_invalid_parameterization_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        (new \local_courseblueprints\local\provisioning_service())->normalize([
            'categoryid' => 1, 'fullname' => 'Course', 'shortname' => 'C-1',
            'startdate' => 200, 'enddate' => 100,
        ]);
    }
}
