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
 * Institutional course blueprints: classes privacy provider.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\privacy;

use core_privacy\local\metadata\collection as metadata_collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as request_provider;


/**
 * Personal-data metadata and system-context export/deletion operations.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements core_userlist_provider, metadata_provider, request_provider {
    /**
     * Describe retained user identity fields.
     */
    public static function get_metadata(metadata_collection $collection): metadata_collection {
        $collection->add_database_table('local_courseblueprints_bp', [
            'ownerid' => 'privacy:metadata:ownerid',
        ], 'privacy:metadata:blueprint');
        $collection->add_database_table('local_courseblueprints_ver', [
            'authorid' => 'privacy:metadata:authorid', 'reviewerid' => 'privacy:metadata:reviewerid',
            'reviewnotes' => 'privacy:metadata:reviewnotes',
        ], 'privacy:metadata:version');
        $collection->add_database_table('local_courseblueprints_run', [
            'userid' => 'privacy:metadata:userid',
        ], 'privacy:metadata:run');
        return $collection;
    }

    /**
     * Data is attached only to site-level administrator operations.
     */
    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {
        global $DB;
        $contexts = new \core_privacy\local\request\contextlist();
        if (
            $DB->record_exists('local_courseblueprints_bp', ['ownerid' => $userid]) ||
                $DB->record_exists('local_courseblueprints_ver', ['authorid' => $userid]) ||
                $DB->record_exists('local_courseblueprints_ver', ['reviewerid' => $userid]) ||
                $DB->record_exists('local_courseblueprints_run', ['userid' => $userid])
        ) {
            $contexts->add_context(\context_system::instance()->id);
        }
        return $contexts;
    }

    /**
     * Export only the requesting user's authorship and operations.
     */
    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_SYSTEM) {
                continue;
            }
            $data = (object) [
                'blueprints' => array_values($DB->get_records('local_courseblueprints_bp', ['ownerid' => $userid])),
                'versions' => array_values($DB->get_records('local_courseblueprints_ver', ['authorid' => $userid])),
                'runs' => array_values($DB->get_records('local_courseblueprints_run', ['userid' => $userid])),
            ];
            \core_privacy\local\request\writer::with_context($context)->export_data([
                get_string('pluginname', 'local_courseblueprints')], $data);
        }
    }

    /**
     * Site-wide privacy erasure may remove institutional audit ownership.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        $replacement = (int) get_admin()->id;
        $DB->execute('UPDATE {local_courseblueprints_bp} SET ownerid = ?', [$replacement]);
        $DB->execute('UPDATE {local_courseblueprints_ver} SET authorid = ?, reviewerid = NULL, reviewnotes = NULL', [$replacement]);
        $DB->execute('UPDATE {local_courseblueprints_run} SET userid = ?', [$replacement]);
    }

    /**
     * Remove author identity while retaining courses, versions and idempotency records.
     */
    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_SYSTEM) {
                continue;
            }
            $replacement = (int) get_admin()->id;
            if ($replacement === (int) $userid) {
                continue;
            }
            $DB->set_field('local_courseblueprints_bp', 'ownerid', $replacement, ['ownerid' => $userid]);
            $DB->set_field('local_courseblueprints_ver', 'reviewnotes', null, ['authorid' => $userid]);
            $DB->set_field('local_courseblueprints_ver', 'authorid', $replacement, ['authorid' => $userid]);
            $DB->set_field('local_courseblueprints_ver', 'reviewerid', null, ['reviewerid' => $userid]);
            $DB->set_field('local_courseblueprints_run', 'userid', $replacement, ['userid' => $userid]);
        }
    }

    /**
     * Add users referenced in institutional history to the privacy request.
     */
    public static function get_users_in_context(\core_privacy\local\request\userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        foreach (
            ['local_courseblueprints_bp' => 'ownerid', 'local_courseblueprints_ver' => 'authorid',
                'local_courseblueprints_run' => 'userid'] as $table => $field
        ) {
            $sql = "SELECT DISTINCT {$field} FROM {{$table}}";
            $userlist->add_users($DB->get_fieldset_sql($sql));
        }
        $userlist->add_users($DB->get_fieldset_sql(
            'SELECT DISTINCT reviewerid FROM {local_courseblueprints_ver} WHERE reviewerid IS NOT NULL'
        ));
    }

    /**
     * Erase an approved selection of user identities.
     */
    public static function delete_data_for_users(\core_privacy\local\request\approved_userlist $userlist): void {
        global $DB;
        if ($userlist->get_context()->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        $replacement = (int) get_admin()->id;
        foreach ($userlist->get_userids() as $userid) {
            if ((int) $userid === $replacement) {
                continue;
            }
            $DB->set_field('local_courseblueprints_bp', 'ownerid', $replacement, ['ownerid' => $userid]);
            $DB->set_field('local_courseblueprints_ver', 'reviewnotes', null, ['authorid' => $userid]);
            $DB->set_field('local_courseblueprints_ver', 'authorid', $replacement, ['authorid' => $userid]);
            $DB->set_field('local_courseblueprints_ver', 'reviewerid', null, ['reviewerid' => $userid]);
            $DB->set_field('local_courseblueprints_run', 'userid', $replacement, ['userid' => $userid]);
        }
    }
}
