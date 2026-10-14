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
 * Institutional course blueprints: tests dependency privacy test.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints;


/**
 * Dependency, sanitization and portability checks.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_courseblueprints\local\dependency_checker
 * @covers \local_courseblueprints\local\privacy_guard
 * @covers \local_courseblueprints\local\import_export_validator
 */
final class dependency_privacy_test extends \advanced_testcase {
    /**
     * A missing module or unrecognized format is a publication blocker.
     */
    public function test_missing_dependencies_are_visible(): void {
        $checker = new \local_courseblueprints\local\dependency_checker();
        $issues = $checker->check(['schema' => 1, 'requires' => ['mod_impossiblecourseblueprintxyz'],
            'unsupported' => ['resource:personal_data']]);
        $this->assertContains('missing:mod_impossiblecourseblueprintxyz', $issues);
        $this->assertContains('unsupported:resource:personal_data', $issues);
    }

    /**
     * Identifiable information in section-authored text blocks publication.
     */
    public function test_pii_heuristics_identify_email_and_cpf(): void {
        $guard = new \local_courseblueprints\local\privacy_guard();
        $found = $guard->inspect(['sections' => [['number' => 1,
            'summary' => '<p>Ana ana@example.org / 111.222.333-44</p>']]]);
        $this->assertContains('email:section:1', $found);
        $this->assertContains('cpf:section:1', $found);
    }

    /**
     * An export with activity definitions but no archive is not silently treated as empty.
     */
    public function test_import_requires_archive_for_activities(): void {
        $structure = ['format' => 'topics', 'sections' => [],
            'activities' => [['section' => 1, 'modname' => 'forum', 'name' => 'Test']]];
        $manifest = ['schema' => 1, 'requires' => [], 'unsupported' => []];
        $document = ['schema' => 1, 'structure' => $structure, 'manifest' => $manifest,
            'hash' => hash('sha256', \local_courseblueprints\local\blueprint_version_manager::canonical_json([
                'structure' => $structure, 'manifest' => $manifest,
            ]))];
        $issues = (new \local_courseblueprints\local\import_export_validator())->validate($document);
        $this->assertContains('activities_without_backup', $issues);
    }

    /**
     * JSON with tampered content is rejected, even if manifest remains valid.
     */
    public function test_import_catches_manifest_tampering(): void {
        $validator = new \local_courseblueprints\local\import_export_validator();
        $issues = $validator->validate([
            'schema' => 1, 'structure' => ['sections' => []],
            'manifest' => ['schema' => 1, 'requires' => [], 'unsupported' => []],
            'hash' => str_repeat('0', 64),
        ]);
        $this->assertContains('contenthash_mismatch', $issues);
    }
}
