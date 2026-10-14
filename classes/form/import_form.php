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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Institutional course blueprints: classes form import form.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\form;

defined('MOODLE_INTERNAL') || die();
require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Import validated blueprints as unapproved drafts.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form extends \moodleform {
    /** Form API definition. */
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('text', 'code', get_string('code', 'local_courseblueprints'));
        $mform->setType('code', PARAM_ALPHANUMEXT);
        $mform->addRule('code', null, 'required');
        $mform->addElement('text', 'name', get_string('name', 'local_courseblueprints'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');
        $mform->addElement('filepicker', 'manifestfile', get_string('manifestfile', 'local_courseblueprints'),
            null, ['accepted_types' => ['.json'], 'maxbytes' => 1048576]);
        $mform->addRule('manifestfile', null, 'required');
        $mform->addElement('filepicker', 'backupfile', get_string('backupfile', 'local_courseblueprints'),
            null, ['accepted_types' => ['.mbz']]);
        $this->add_action_buttons(true, get_string('import', 'local_courseblueprints'));
    }
}
