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
 * Language strings.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['archive'] = 'Archive';
$string['back'] = 'Back';
$string['backupfailed'] = 'Moodle backup did not return a usable stored file.';
$string['backupfile'] = 'Moodle .mbz archive (if required)';
$string['backupmissing'] = 'Blueprint archive is missing.';
$string['batch'] = 'Batch targets';
$string['batchhelp'] = 'Optional: one course per line as Full name|Short name (maximum 100). Leave blank for a single course.';
$string['catalogue'] = 'Institutional blueprints';
$string['category'] = 'Target category';
$string['chooseversion'] = 'Published blueprint version';
$string['code'] = 'Stable code';
$string['compare'] = 'Compare';
$string['comparisoninformative'] = 'This report is informational only; it never changes a course.';
$string['confirmprovision'] = 'Confirm provisioning';
$string['courseblueprints:create'] = 'Create blueprints';
$string['courseblueprints:provision'] = 'Provision courses from blueprints';
$string['courseblueprints:publish'] = 'Review, publish and archive blueprint versions';
$string['courseblueprints:view'] = 'View institutional blueprints and history';
$string['courseformat'] = 'Course format';
$string['createblueprint'] = 'Create blueprint';
$string['createddraft'] = 'New draft created; source-course backup is queued where applicable.';
$string['dependencies'] = 'Dependencies / unsupported components';
$string['description'] = 'Description';
$string['duplicatecode'] = 'This blueprint code is already in use.';
$string['emptycatalogue'] = 'No institutional blueprints have been created yet.';
$string['enddate'] = 'End date (optional)';
$string['error'] = 'Error';
$string['eventprovisioned'] = 'Course provisioned from blueprint';
$string['eventpublished'] = 'Blueprint revision published';
$string['export'] = 'Export JSON';
$string['fullname'] = 'Full course name';
$string['history'] = 'Provisioning history';
$string['idempotencyconflict'] = 'This request key belongs to another operation.';
$string['immutableversion'] = 'Published or archived versions cannot be edited.';
$string['import'] = 'Import';
$string['importinvalid'] = 'Invalid import: {$a}';
$string['integrityfailure'] = 'Content hash mismatch: blueprint artifact has changed.';
$string['invaliddates'] = 'End date cannot be earlier than start date.';
$string['invalidpreview'] = 'The preview has expired or does not belong to this session.';
$string['invalidsections'] = 'Select between 1 and 52 sections.';
$string['invalidversion'] = 'Invalid blueprint version for this action.';
$string['lockfailed'] = 'The operation is already in progress or could not be locked.';
$string['manifestfile'] = 'Blueprint manifest JSON';
$string['missingdependencies'] = 'Some required components are missing or unsupported.';
$string['name'] = 'Name';
$string['namesrequired'] = 'Enter full name and short name, or batch targets.';
$string['newrevision'] = 'New revision';
$string['next'] = 'Next';
$string['nodifferences'] = 'No differences detected in the supported structural scope.';
$string['nopublished'] = 'No published versions are available for provisioning.';
$string['pluginname'] = 'Institutional course blueprints';
$string['preview'] = 'Provisioning preview';
$string['privacy:metadata:authorid'] = 'The creator of the blueprint revision.';
$string['privacy:metadata:blueprint'] = 'Institutional blueprint with user ownership.';
$string['privacy:metadata:ownerid'] = 'The designated blueprint owner.';
$string['privacy:metadata:reviewerid'] = 'The reviewer who approved a blueprint revision.';
$string['privacy:metadata:reviewnotes'] = 'Manually recorded privacy review notes.';
$string['privacy:metadata:run'] = 'Provisioning batch requester.';
$string['privacy:metadata:userid'] = 'The user who requested the provisioning operation.';
$string['privacy:metadata:version'] = 'Version authorship and review notes.';
$string['provision'] = 'Provision courses';
$string['publish'] = 'Publish';
$string['queued'] = 'Course provisioning tasks have been queued.';
$string['restorefailed'] = 'Moodle native restore failed.';
$string['review'] = 'Review';
$string['reviewed'] = 'I have manually inspected all course material and confirmed that no personal data or unsupported content will be published.';
$string['reviewnotes'] = 'Privacy and content-review evidence';
$string['reviewrequired'] = 'A recorded manual privacy review is required before publication.';
$string['reviewwarning'] = 'Automated scanning is not a guarantee. Review every activity, attached file, question, resource and authored HTML before approving.';
$string['revision'] = 'Revision';
$string['revisionimportunsupported'] = 'An imported archived blueprint with an .mbz cannot be revised without recapturing its source; create a new import instead.';
$string['revisions'] = 'Revisions';
$string['savereview'] = 'Record review';
$string['sections'] = 'Number of sections';
$string['shortname'] = 'Short course name';
$string['shortnameexists'] = 'The short name is already used by a Moodle course.';
$string['sourceblank'] = 'Initial configuration';
$string['sourcecourseid'] = 'Source course ID';
$string['sourceexisting'] = 'Existing Moodle course';
$string['sourcemode'] = 'Blueprint source';
$string['sourcestale'] = 'Source course changed after the revision was captured. Create a new revision and review it.';
$string['startdate'] = 'Start date';
$string['status'] = 'Status';
$string['taskcapture'] = 'Capture blueprint backup';
$string['taskprovision'] = 'Provision course from blueprint';
$string['unpublishedversion'] = 'This version is not available for new provisioning.';
