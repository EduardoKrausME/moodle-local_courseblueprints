# Installation and verification

1. Install a compatible Moodle 4.5 LTS or 5.x instance with a configured cron, database and lock factory.
2. Place this plugin at `$CFG->dirroot/local/courseblueprints` and verify `version.php` exposes component `local_courseblueprints`.
3. Visit **Site administration → Notifications** or run `php admin/cli/upgrade.php --non-interactive` to apply XMLDB tables and capabilities.
4. Confirm administrators have the system-context `local/courseblueprints:view`, `:create`, `:publish` and `:provision` capabilities. Grant provision users `moodle/course:create` in destination categories and template creators `moodle/backup:backupcourse` on source courses.
5. Check cron using `php admin/cli/cron.php`. A template captured from an existing course only obtains an archive when its ad-hoc backup task runs.
6. Execute the required validator and Moodle CI commands from the README; test Backup/Restore against the exact Moodle branches and third-party module versions deployed at the institution.
7. Before production activation, configure permission reviews, backup storage quotas, retention rules and a separate staging site.

## Upgrade

Always back up the Moodle database and dataroot before plugin upgrades. Only the plugin's own XMLDB tables are created. The external Moodle backup subsystem decides which source components are portable; installing the plugin cannot fix a third-party module without native backup/restore support.

## Troubleshooting

If an archive remains missing, check the task queue and Moodle Backup logs; publishing intentionally fails until the archive exists. If a course status is `failed_review`, inspect the partial course and keep it hidden. Never rely on transaction rollback to remove Moodle Restore API effects.
