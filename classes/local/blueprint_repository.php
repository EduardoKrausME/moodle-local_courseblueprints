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
 * Institutional course blueprints: classes local blueprint repository.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\local;


/**
 * Persistence for stable blueprint identities and revision records.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blueprint_repository {
    /**
     * Create an institutional blueprint.
     */
    public function create(string $code, string $name, string $description, int $ownerid): int {
        global $DB;
        $code = clean_param($code, PARAM_ALPHANUMEXT);
        if ($code === '' || trim($name) === '') {
            throw new \invalid_parameter_exception(get_string('invalidblueprintidentity', 'local_courseblueprints'));
        }
        if ($DB->record_exists('local_courseblueprints_bp', ['code' => $code])) {
            throw new \moodle_exception('duplicatecode', 'local_courseblueprints');
        }
        $now = time();
        return $DB->insert_record('local_courseblueprints_bp', (object) [
            'code' => $code, 'name' => trim($name), 'description' => $description,
            'ownerid' => $ownerid, 'timecreated' => $now, 'timemodified' => $now,
        ]);
    }

    /**
     * Return only pinned blueprint identity.
     */
    public function get(int $id): \stdClass {
        global $DB;
        return $DB->get_record('local_courseblueprints_bp', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Ordered catalogue.
     */
    public function catalogue(): array {
        global $DB;
        return array_values($DB->get_records('local_courseblueprints_bp', [], 'name ASC, id ASC'));
    }

    /**
     * Fetch versions in descending revision order.
     */
    public function versions(int $blueprintid): array {
        global $DB;
        return array_values($DB->get_records('local_courseblueprints_ver', ['blueprintid' => $blueprintid], 'revision DESC'));
    }

    /**
     * Fetch a specific revision.
     */
    public function version(int $id): \stdClass {
        global $DB;
        return $DB->get_record('local_courseblueprints_ver', ['id' => $id], '*', MUST_EXIST);
    }
}
