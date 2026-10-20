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
 * Institutional course blueprints: classes form provision settings form.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\form;

defined('MOODLE_INTERNAL') || die();
require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Target course parameters and batch entry.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provision_settings_form extends \moodleform {
    /**
     * Form API definition.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'versionid', $this->_customdata['versionid']);
        $mform->setType('versionid', PARAM_INT);
        $mform->addElement(
            'select',
            'categoryid',
            get_string('category', 'local_courseblueprints'),
            \core_course_category::make_categories_list('moodle/course:create')
        );
        $mform->addRule('categoryid', null, 'required');
        $mform->addElement('text', 'fullname', get_string('fullname', 'local_courseblueprints'), ['size' => 60]);
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addElement('text', 'shortname', get_string('shortname', 'local_courseblueprints'));
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addElement('date_time_selector', 'startdate', get_string('startdate', 'local_courseblueprints'));
        $mform->addElement(
            'date_time_selector',
            'enddate',
            get_string('enddate', 'local_courseblueprints'),
            ['optional' => true]
        );
        $mform->addElement(
            'textarea',
            'batch',
            get_string('batch', 'local_courseblueprints'),
            ['rows' => 5, 'cols' => 75]
        );
        $mform->setType('batch', PARAM_RAW_TRIMMED);
        $mform->addElement('static', 'batchhelp', '', get_string('batchhelp', 'local_courseblueprints'));
        $this->add_action_buttons(true, get_string('preview', 'local_courseblueprints'));
    }

    /**
     * Validate both single and batch configurations.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (
            trim($data['batch'] ?? '') === '' && (trim($data['fullname'] ?? '') === '' ||
                trim($data['shortname'] ?? '') === '')
        ) {
            $errors['fullname'] = get_string('namesrequired', 'local_courseblueprints');
        }
        if (!empty($data['enddate']) && $data['enddate'] < $data['startdate']) {
            $errors['enddate'] = get_string('invaliddates', 'local_courseblueprints');
        }
        return $errors;
    }
}
