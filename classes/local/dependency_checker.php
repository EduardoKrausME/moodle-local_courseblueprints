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
 * Institutional course blueprints: classes local dependency checker.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\local;


/**
 * Dependencies must be explicit and cannot be skipped during provisioning.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dependency_checker {
    /**
     * Generate a minimal inventory for installed formats and activity modules.
     */
    public function manifest(array $structure): array {
        $required = ['format_' . $structure['format']];
        foreach ($structure['activities'] ?? [] as $activity) {
            $required[] = 'mod_' . $activity['modname'];
        }
        $unsupported = [];
        foreach (array_merge($structure['sections'] ?? [], $structure['activities'] ?? []) as $entry) {
            $conditions = trim((string) ($entry['availability'] ?? ''));
            if ($conditions === '') {
                continue;
            }
            try {
                $rule = json_decode($conditions, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($rule)) {
                    $unsupported[] = 'availability_invalid';
                    continue;
                }
                $this->availability_dependencies($rule, $required, $unsupported);
            } catch (\JsonException $e) {
                $unsupported[] = 'availability_invalid';
            }
        }
        $required = array_values(array_unique($required));
        sort($required, SORT_STRING);
        $versions = [];
        foreach ($required as $component) {
            $version = get_config($component, 'version');
            if ($version !== false) {
                $versions[$component] = (int) $version;
            }
        }
        return ['schema' => 1, 'requires' => $required, 'minversions' => $versions,
            'unsupported' => array_values(array_unique($unsupported)),
            'source' => ['moodle' => (string) get_config('moodle', 'release')]];
    }

    /**
     * Walk Moodle restriction trees and record the condition plugins required.
     */
    private function availability_dependencies(array $node, array &$required, array &$unsupported): void {
        if (isset($node['type'])) {
            $type = (string) $node['type'];
            if (preg_match('/^[a-z][a-z0-9_]*$/D', $type)) {
                $required[] = 'availability_' . $type;
            } else {
                $unsupported[] = 'availability_type_invalid';
            }
        }
        foreach ($node['c'] ?? [] as $child) {
            if (is_array($child)) {
                $this->availability_dependencies($child, $required, $unsupported);
            } else {
                $unsupported[] = 'availability_child_invalid';
            }
        }
    }

    /**
     * Return a machine-readable, sorted list of blockers.
     */
    public function check(array $manifest): array {
        $issues = [];
        if (
            ($manifest['schema'] ?? null) !== 1 || !isset($manifest['requires']) ||
                !is_array($manifest['requires'])
        ) {
            return ['invalid_manifest'];
        }
        foreach ($manifest['unsupported'] ?? [] as $unsupported) {
            $issues[] = 'unsupported:' . clean_param((string) $unsupported, PARAM_TEXT);
        }
        foreach ($manifest['requires'] as $component) {
            if (!is_string($component) || !preg_match('/^(mod|format|availability)_[a-z][a-z0-9_]*$/D', $component)) {
                $issues[] = 'invalid_component';
                continue;
            }
            $directory = \core_component::get_component_directory($component);
            if (!$directory || !is_dir($directory)) {
                $issues[] = 'missing:' . $component;
                continue;
            }
            $minimum = (int) ($manifest['minversions'][$component] ?? 0);
            $installed = (int) get_config($component, 'version');
            if ($minimum && $installed < $minimum) {
                $issues[] = 'older_plugin:' . $component;
            }
        }
        $issues = array_values(array_unique($issues));
        sort($issues, SORT_STRING);
        return $issues;
    }
}
