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

class component extends moodleform {
    protected $mermaid;

    protected function definition() {
        global $CFG;
        $mform = $this->_form;
        $mform->addElement('text', 'pluginfolder', get_string('pluginfolder', 'tool_erdiagram'));

        $mform->setDefault('pluginfolder', 'mod/book');
        $mform->addHelpButton('pluginfolder', 'pluginfolder', 'tool_erdiagram');
        $mform->setType('pluginfolder', PARAM_TEXT);

        $mform->addElement('textarea', 'markup', 'Output', ['rows' => 10, 'cols' => 80]);
        $mform->setType('markup', PARAM_TEXT);

        $mform->addElement('advcheckbox', 'fieldnames', 'Field Names');
        $mform->setType('fieldnames', PARAM_BOOL);

        $mform->addElement('static', 'mermark', 'Rendered diagram');
        $mform->addElement('submit', 'submitbutton', get_string('submit'));
    }
    /**
     * Update fields in the form after it has been constructed.
     *
     * @param string $data
     * @return void
     */
    public function set_data($data) {
        $this->_form->getElement('markup')->setValue($data);
        $mermark = "
            <script type='module'>
                import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
                mermaid.initialize({ startOnLoad: true });
        </script>
        <pre class='mermaid'>
            $data
            </pre>";
        $this->_form->getElement('mermark')->setValue($mermark);
    }
    public function data_preprocessing($merdata) {
        $this->mermaid = $merdata;
    }


}
