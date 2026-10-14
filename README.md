# Institutional Course Blueprints (`local_courseblueprints`)

Moodle local plugin for creating immutable, versioned institutional course templates and provisioning courses through the native course, backup and restore APIs.

- Component: `local_courseblueprints`
- Location: `local/courseblueprints`
- Release: `1.0.0`
- Version: `2026101400`
- Minimum Moodle version: 4.5 LTS; Moodle 5.x is a compatibility target requiring CI/integration testing.
- License: GPL-3.0-or-later

## Intended workflow

1. A manager opens **Site administration → Courses → Institutional course blueprints**.
2. **Create blueprint** from an existing Moodle course or empty initial configuration.
3. For an existing course, an ad-hoc task creates a genuine Moodle backup with user data excluded and saves a private `.mbz` using the Moodle File API. Wait for the task to succeed.
4. Review the source content and artifacts manually for personal data, including content typed by teachers, file attachments, question text and descriptions. Record the review evidence. Heuristic checks are supplementary, not a privacy certification.
5. Publish the draft after dependencies are confirmed and archive hashes verified. Published versions cannot be edited; create new revisions instead.
6. Open **Provision courses**, choose a published revision, select a destination category and provide metadata and optional batch rows (`Full name|Short name`).
7. Review the dependency report and preview, then confirm. Ad-hoc tasks process each course independently and record status.
8. Monitor provisioning history and inspect informational structural divergence reports. Courses always retain a link to their original template revision.

## Site requirements

Moodle 4.5+ with working cron, Backup/Restore subsystem, Lock API backend, DB migration privileges and a local course category the operator may create courses within. Only managers (by default) receive the institutional blueprint capabilities. `moodle/backup:backupcourse` is required to create a template from a source course and `moodle/course:create` is required for provisioning in the destination category.

## Capability design

| Capability | Intended permission |
|---|---|
| `local/courseblueprints:view` | Catalogue, history, structure report, artifact export |
| `local/courseblueprints:create` | Create stable identities, revisions and import drafts |
| `local/courseblueprints:publish` | Authorize manual review, publish and archive |
| `local/courseblueprints:provision` | Queue courses for an approved revision |

Capability checks are server-side; Form API or sesskey validation guards all writes. Backup artifacts are never publicly exposed by `pluginfile`.

## Revision and audit model

`local_cb_blueprint` carries the stable business identity; `local_cb_version` carries revision, immutable structure and dependency manifests, backup SHA-256, author, review evidence, publication timestamp and content SHA-256. `local_cb_run` and `local_cb_item` record a unique request key, independent per-course statuses, errors and course IDs. `local_cb_coursemap` pins each newly provisioned course to exactly one revision with a structural baseline.

A published revision is append-only apart from the status transition to `archived`. Archiving does not delete artifacts or the revision record. There is deliberately **no auto-sync** from a new revision into existing courses.

## Backup, personal data and unsupported features

The plugin uses Moodle's `backup_controller` and `restore_controller`, configured without user participation data, rather than copying Moodle internal tables. Some Moodle activities, third-party plugin backups and authored file contents are not guaranteed to be personal-data-free just because the Moodle backup settings exclude users.

An explicit privacy review gate is mandatory before publishing. The built-in scanner detects visible email addresses and CPF patterns in section summaries only; it **does not** verify all attached files, question banks, activity settings or custom plugin data. Site administrators must inspect these manually. An unsupported dependency becomes a publication blocker. The plugin never claims to sanitize arbitrary contents reliably.

Course structure is collected and compared at a portable structural level (sections, titles, summaries, course-format identity, completion and group-mode flags, activity names/types/sections, availability markers). A Moodle backup may additionally contain question banks, grades setup, group configuration and competencies depending on native plugin support, but the v1.0.0 collector does **not** guarantee portable enumeration, reconfiguration or comparison of all those structures. Moodle plugins that do not implement appropriate backup/restore are not copied manually. Do not treat exported JSON alone as a complete source course template if the revision has an `.mbz` archive.

## Import / export

The **Export JSON** operation downloads the canonical structural manifest and hashes. Existing-source templates additionally require a separately downloaded `.mbz` archive at `/local/courseblueprints/export.php?id=VERSION_ID&artifact=1` (only privileged, authenticated users). On import, supply both files when `backuphash` exists. The importer verifies SHA-256 before storing a new, unpublished draft, and rejects manifest/schema mismatches and unavailable dependencies; operators must review and republish on the destination Moodle instance.

Do not share private model archives outside controlled backup channels. Exported archives may contain information written into course content, even if no Moodle user records are included.

## Failure and idempotency contract

The same 64-character request key resolves to the same batch, not duplicate jobs. Per-course tasks use the Moodle Lock API. A course is created with a reserved idnumber marker `cb-<item ID>`, and its ID and source version are persisted **before** restore. A failure before course creation can be retried. A failure after a course is created is marked `failed_review`: the plugin **never deletes it** and does **not repeat a potentially non-idempotent restore**. An orphan with a reserved marker is flagged for human reconciliation.

For a production recovery, an administrator must inspect the partial course, review the logs, decide whether to reuse or manually retire it, and submit a **new** provisioning request with a new target short name if appropriate. Deleting Moodle courses is intentionally not part of automated error recovery. Course visibility defaults to hidden until institutional review.

## Tests and CI

- `php -l` over all PHP files
- `python3 /path/to/moodle-plugin-validate/.chatgpt/run.py /absolute/path/to/local/courseblueprints`
- `python3 /path/to/moodle-plugin-validate/.chatgpt/run.py --check /absolute/path/to/local/courseblueprints`
- `moodle-plugin-ci codechecker`
- `moodle-plugin-ci phpunit --fail-on-warning`
- `moodle-plugin-ci behat`

The tests cover revision immutability, SHA integrity, dependency and privacy flags, batch idempotency, parameter validation and deterministic structural diffs. Integration tests need a real installed Moodle with backup/restore configured; GitHub Actions has been added but no CI outcome can be inferred until the workflows run.

See `docs/architecture.md`, `docs/installation.md`, `docs/usage.md` and `docs/security.md` for details.
