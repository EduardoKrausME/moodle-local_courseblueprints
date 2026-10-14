# Security and limitation register

- Authentication: `require_login` for every page; system-context capabilities for catalogue, creation, publication and provisioning; Moodle course and category capabilities for source and target operations; sesskey for writes and Moodle Form API for structured inputs.
- Persistent privacy: every published backup resides in Moodle's managed File API with no public `pluginfile` delivery; exports require a privileged authenticated request. Metadata includes user authorship and operation references and is covered by the Privacy API provider.
- Creation: Moodle's `create_course` and `update_course`, not handcrafted SQL for Moodle core course insertion. Section editing uses core course APIs. Moodle Restore handles module resource payloads rather than plugin-defined SQL copies.
- Data exclusion: native backup/restore settings for user enrolments, activity user data, grades history, logs and role assignments are disabled when present; never assume that teacher-authored files and rich-text content are automatically sanitized.
- Publication: every revision requires a recorded human privacy/content review. Unresolved dependency flags block publication. An existing-source revision additionally requires a private archive and matching content hash.
- Idempotency: unique request keys, run-row uniqueness, task-level locks and durable pre-restore course markers. A partial course is never automatically removed or restored again, because transaction boundaries cannot undo Moodle Backup/Restore side effects.
- Limitations: heuristic PII scanning only examines section summaries. It does not inspect custom module database fields, document interiors, file metadata, question text or structured attempts. Human assessment before publication is mandatory. No claim is made that all Moodle versions implement the same backup/restore features or that export is a cross-version migration guarantee.
- Scope: no automatic conformity enforcement, automatic course deletion, user enrolment migration, grade/attempt history transfer or bulk permission propagation.

## Production gates

Before declaring a production release, validate on Moodle 4.5 and supported 5.x branches with installed third-party activity modules, run backup/restore integration, Behat, Moodle Code Checker, the repository-specific validator and PHPUnit with warnings treated as failures. A lint-only pass is not sufficient to declare the plugin release validated.
