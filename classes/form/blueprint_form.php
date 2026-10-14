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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Institutional course blueprints: classes form blueprint form.php.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\form;

defined('MOODLE_INTERNAL') || die();
require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Institutional blueprint editor.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blueprint_form extends \moodleform {
    /** Form API definition. */
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('text', 'code', get_string('code', 'local_courseblueprints'));
        $mform->setType('code', PARAM_ALPHANUMEXT);
        $mform->addRule('code', null, 'required');
        $mform->addElement('text', 'name', get_string('name', 'local_courseblueprints'), ['size' => 65]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');
        $mform->addElement('textarea', 'description', get_string('description', 'local_courseblueprints'));
        $mform->setType('description', PARAM_TEXT);
        $mform->addElement('select', 'mode', get_string('sourcemode', 'local_courseblueprints'), [
            'blank' => get_string('sourceblank', 'local_courseblueprints'),
            'existing' => get_string('sourceexisting', 'local_courseblueprints'),
        ]);
        $mform->addElement('text', 'sourcecourseid', get_string('sourcecourseid', 'local_courseblueprints'));
        $mform->setType('sourcecourseid', PARAM_INT);
        $mform->hideIf('sourcecourseid', 'mode', 'eq', 'blank');
        $mform->addElement('select', 'format', get_string('courseformat', 'local_courseblueprints'), [
            'topics' => 'Topics', 'weeks' => 'Weeks', 'singleactivity' => 'Single activity',
        ]);
        $mform->hideIf('format', 'mode', 'eq', 'existing');
        $mform->addElement('text', 'sections', get_string('sections', 'local_courseblueprints'));
        $mform->setType('sections', PARAM_INT);
        $mform->setDefault('sections', 5);
        $mform->hideIf('sections', 'mode', 'eq', 'existing');
        $this->add_action_buttons(true, get_string('createblueprint', 'local_courseblueprints'));
    }

    /** Validate source course access and bounds. */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if ($data['mode'] === 'existing') {
            if (empty($data['sourcecourseid'])) {
                $errors['sourcecourseid'] = get_string('required');
            } else {
                $course = get_course((int) $data['sourcecourseid']);
                if (!has_capability('moodle/backup:backupcourse', \context_course::instance($course->id))) {
                    $errors['sourcecourseid'] = get_string('nopermissions', 'error', 'moodle/backup:backupcourse');
                }
            }
        } else if ($data['sections'] < 1 || $data['sections'] > 52) {
            $errors['sections'] = get_string('invalidsections', 'local_courseblueprints');
        }
        return $errors;
    }
}
