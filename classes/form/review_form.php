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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Institutional course blueprints: classes form review form.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\form;

defined('MOODLE_INTERNAL') || die();
require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Explicit evidence-backed privacy review form.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class review_form extends \moodleform {
    /**
     * Form API definition.
     */
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'versionid', $this->_customdata['versionid']);
        $mform->setType('versionid', PARAM_INT);
        $mform->addElement('advcheckbox', 'reviewed', get_string('reviewed', 'local_courseblueprints'));
        $mform->addRule('reviewed', null, 'required');
        $mform->addElement(
            'textarea',
            'reviewnotes',
            get_string('reviewnotes', 'local_courseblueprints'),
            ['rows' => 5, 'cols' => 75]
        );
        $mform->setType('reviewnotes', PARAM_TEXT);
        $mform->addRule('reviewnotes', null, 'required');
        $this->add_action_buttons(true, get_string('savereview', 'local_courseblueprints'));
    }
}
