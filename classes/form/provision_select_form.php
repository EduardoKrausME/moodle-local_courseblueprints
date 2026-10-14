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
 * Institutional course blueprints: classes form provision select form.php.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_courseblueprints\form;

defined('MOODLE_INTERNAL') || die();
require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Selection step for the provisioning wizard.
 *
 * @package local_courseblueprints
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provision_select_form extends \moodleform {
    /**
     * Form API definition.
     */
    protected function definition(): void {
        $options = $this->_customdata['versions'];
        $mform = $this->_form;
        $mform->addElement('select', 'versionid', get_string('chooseversion', 'local_courseblueprints'), $options);
        $mform->addRule('versionid', null, 'required');
        $this->add_action_buttons(true, get_string('next', 'local_courseblueprints'));
    }
}
