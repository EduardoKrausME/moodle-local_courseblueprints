# Operator guide

## Create and publish

1. Create a stable model with a unique code and institutional owner. The first revision starts in `draft`.
2. Use an existing course (enter its numeric Moodle course ID) or create an initial structure with a selected format and section count. For existing courses, wait for the backup ad-hoc task to complete.
3. Review all sections, activities, embedded HTML, attached files and question text for real-world personal data and rights to reuse. The automated email/CPF heuristics are only a first pass, not certification.
4. Record the review evidence on the draft and publish. If an issue cannot be resolved, edit the **source** and create another revision; do not force an unsupported version into production.
5. Archive an old published revision to remove it from selection while retaining previously created courses and audit history.

## Provision

1. Select a published revision.
2. Choose a destination category; provide the full and short names or enter one `Full name|Short name` pair per line for a batch of up to 100 courses. Dates and supported metadata apply to each requested course.
3. Review the preview and dependencies, then confirm using the browser session action.
4. Monitor **History**. Each item is independent; `complete` means supported structural checks matched, `needs_review` means the course was created but structural differences need inspection, `failed_review` means a partly created course needs a human decision. A job that fails before creating a course may be retried by the task system.
5. The new course remains hidden; inspect Moodle sections, activities, question-bank links and role settings before making it available to learners.

## Compare

The Compare action produces deterministic lists of added and removed sections, missing and extra activities, changed supported settings, and unavailable components. It is informative only; it does not normalize the live course or attempt to synchronize with a new model revision.

## Import and export

Export the JSON structure from the revision page; when the version has an archive, download the authenticated `.mbz` via `export.php?id=VERSION_ID&artifact=1`. Import as a new draft using an unused code and supply both files. Review and publish separately after checking the destination's installed Moodle plugins and data policies.
