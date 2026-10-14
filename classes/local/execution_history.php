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
 * Institutional course blueprints: classes local execution history.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\local;


/**
 * Append-oriented provisioning history facade.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class execution_history {
    /**
     * Fetch run items with independent outcomes.
     */
    public function items(int $runid): array {
        global $DB;
        return array_values($DB->get_records('local_courseblueprints_itm', ['runid' => $runid], 'rownum ASC'));
    }

    /**
     * Recent runs for audit.
     */
    public function recent(int $limit = 30): array {
        global $DB;
        return array_values($DB->get_records('local_courseblueprints_run', [], 'timecreated DESC', '*', 0, $limit));
    }
}
