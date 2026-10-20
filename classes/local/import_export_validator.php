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
 * Institutional course blueprints: classes local import export validator.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\local;


/**
 * Conservative JSON schema and exported payload verifier.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_export_validator {
    /**
     * Validate structural envelope and portable metadata before trusting an import.
     */
    public function validate(array $document): array {
        $errors = [];
        if (
            !empty($document['backuphash']) &&
                (!is_string($document['backuphash']) ||
                !preg_match('/^[a-f0-9]{64}$/D', $document['backuphash']))
        ) {
            $errors[] = 'invalid_backup_hash';
        }
        if (($document['schema'] ?? null) !== 1) {
            $errors[] = 'unsupported_schema';
        }
        if (
            !is_array($document['structure'] ?? null) ||
                !is_array($document['manifest'] ?? null) ||
                !is_string($document['hash'] ?? null)
        ) {
            $errors[] = 'malformed_document';
        } else {
            $expected = hash('sha256', blueprint_version_manager::canonical_json([
                'structure' => $document['structure'], 'manifest' => $document['manifest'],
            ]));
            if (!hash_equals($expected, $document['hash'])) {
                $errors[] = 'contenthash_mismatch';
            }
            $errors = array_merge($errors, (new dependency_checker())->check($document['manifest']));
            if (empty($document['backuphash']) && !empty($document['structure']['activities'])) {
                $errors[] = 'activities_without_backup';
            }
        }
        return $errors;
    }

    /**
     * Export manifest-only JSON, never personal data or user records.
     */
    public function export(\stdClass $version): string {
        return blueprint_version_manager::canonical_json([
            'schema' => 1, 'revision' => (int) $version->revision,
            'structure' => json_decode($version->structure, true, 512, JSON_THROW_ON_ERROR),
            'manifest' => json_decode($version->manifest, true, 512, JSON_THROW_ON_ERROR),
            'hash' => $version->contenthash,
            'backuphash' => $version->backuphash,
        ]);
    }
}
