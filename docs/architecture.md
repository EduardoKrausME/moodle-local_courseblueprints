# Architecture and data contracts

This is a Moodle `local_` plugin and not a stand-alone provisioning engine. Moodle's course, backup, restore, File API, Task API, Lock API, DML and XMLDB remain the authoritative infrastructure. The plugin's own tables contain business metadata, revision references, recorded safety approvals and operation state; they deliberately do not duplicate Moodle's course-module or question-bank schema.

## Lifecycle

`blueprint_repository` holds institutional names and ownership. `blueprint_version_manager` creates revisions under a keyed Moodle lock, canonicalizes nested JSON and computes content SHA-256. The published revision content is immutable. Separate `draft → published → archived` transitions are permitted; no other mutation route updates published content.

`course_structure_collector` enumerates portable sections and activities through Moodle APIs and carefully scoped read-only Moodle DB records. `dependency_checker` records Moodle activity and format components; unsupported components become blockers. `privacy_guard` only identifies suspicious patterns that are safe to detect deterministically. For an existing-source draft, `capture_blueprint_task` delegates to native Moodle Backup API and stores an `.mbz` in the private `artifact` File API area, item ID = version ID. A reviewed draft can be published only if required components are available and the backup hash matches the stored artifact.

`provisioning_service` and `provision_course_task` implement a two-level request/run/item model. The batch key is deduplicated under a lock, then each item uses its own item lock. For each item: dependency check, create hidden Moodle course, record course ID and pinned version, apply native Moodle restore or create empty sections, reapply allowed target metadata, compare, record status. `course_comparison_service` never writes to a course. `execution_history` provides audit views.

## Database shape

- `local_courseblueprints_bp`: stable code/name, owner, description.
- `local_courseblueprints_ver`: revision number, status, creator, manifests, content fingerprint, backup fingerprint, review evidence and publication timestamp.
- `local_courseblueprints_run`: batch ID, request key, original requester, timestamp.
- `local_courseblueprints_itm`: one target per row with category/name/date params, status, retry count, errors, validation report and durable course ID.
- `local_courseblueprints_map`: course ID, pinned version, item ID and original portable baseline.

A unique `(blueprintid, revision)` index prevents duplicate versions, and a unique `requestkey` plus `(runid, rownum)` prevents duplicate batched rows. A unique `courseid` mapping ensures each provisioned course points to exactly one source revision.

## Explicit boundaries

Exported JSON is a deterministic portable description, not a substitute for native Moodle Backup. The model cannot automatically prove backup/restore feature parity across all Moodle plugins or migrations between Moodle releases. Not all grade settings, competencies, question categories, activity completion rules, question references, and file contents are exposed through the portable comparison collector. They are not silently synthesized. Import must validate local dependencies and the backup hash, and manual review is mandatory before publication.

The plugin does not modify existing courses when new versions appear. Automatic reconciliation, deleting partial courses and importing enrolments/grades/attempts are intentionally excluded.
