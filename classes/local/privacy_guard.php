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
 * Institutional course blueprints: classes local privacy guard.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Conservative detection of PII embedded in course-authored HTML.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class privacy_guard {
    /** Find possible PII (heuristics; explicit expert review remains compulsory). */
    public function inspect(array $structure): array {
        $issues = [];
        foreach ($structure['sections'] ?? [] as $section) {
            $text = strip_tags(html_entity_decode((string) ($section['summary'] ?? ''), ENT_QUOTES, 'UTF-8'));
            if (preg_match('/[\w.+-]+@[\w.-]+\.[a-z]{2,}/iu', $text)) {
                $issues[] = 'email:section:' . $section['number'];
            }
            if (preg_match('/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/u', $text)) {
                $issues[] = 'cpf:section:' . $section['number'];
            }
        }
        return $issues;
    }
}
