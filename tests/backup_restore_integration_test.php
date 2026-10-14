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
 * Institutional course blueprints: tests backup restore integration test.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints;


/**
 * End-to-end Moodle backup and private artifact checksum integration test.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_courseblueprints\local\backup_adapter
 * @covers \local_courseblueprints\local\restore_adapter
 */
final class backup_restore_integration_test extends \advanced_testcase {
    /**
     * A real course backup must produce a private, verifiable .mbz.
     */
    public function test_real_course_backup_has_verifiable_artifact(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        $generator->create_instance(['course' => $course->id, 'name' => 'Forum for backup']);
        $repo = new \local_courseblueprints\local\blueprint_repository();
        $id = $repo->create('integrationcourse', 'Integration', '', get_admin()->id);
        $structure = (new \local_courseblueprints\local\course_structure_collector())->collect($course->id);
        $manifest = (new \local_courseblueprints\local\dependency_checker())->manifest($structure);
        $versionid = (new \local_courseblueprints\local\blueprint_version_manager())->create_revision(
            $id,
            get_admin()->id,
            $structure,
            $manifest,
            $course->id
        );
        $adapter = new \local_courseblueprints\local\backup_adapter();
        $adapter->capture($versionid, get_admin()->id);
        $version = $repo->version($versionid);
        $adapter->verify_artifact($version);
        $this->assertEquals(64, strlen($version->backuphash));
        // Restore uses Moodle's real restore_controller into a newly created course.
        $destination = $this->getDataGenerator()->create_course(['format' => $course->format]);
        (new \local_courseblueprints\local\restore_adapter())->restore(
            $version,
            $destination->id,
            get_admin()->id
        );
        $after = (new \local_courseblueprints\local\course_structure_collector())->collect($destination->id);
        $this->assertContains('forum', array_column($after['activities'], 'modname'));
    }
}
