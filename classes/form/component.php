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


namespace tool_erdiagram\form;

use moodleform;

/**
 * Plugin strings are defined here.
 *
 * @package     tool_erdiagram
 * @category    string
 * @author      Marcus Green
 * @copyright   Catalyst IT 2023
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class component extends moodleform {

    /**
     * Form definition
     */
    protected function definition() {
        global $CFG;
        $mform = $this->_form;
        $mform->addElement('text', 'pluginfolder', get_string('pluginfolder', 'tool_erdiagram'));

        $mform->setDefault('pluginfolder', 'mod/book');
        $mform->addHelpButton('pluginfolder', 'pluginfolder', 'tool_erdiagram');
        $mform->setType('pluginfolder', PARAM_TEXT);

        $mform->addElement('advcheckbox', 'fieldnames', 'Field Names');
        $mform->setType('fieldnames', PARAM_BOOL);

        $mform->addElement('submit', 'submitbutton', get_string('submit'));
    }

}
