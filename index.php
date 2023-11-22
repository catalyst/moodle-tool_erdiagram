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
 * Create ER diagram Mermaid file
 *
 * @package    tool_erdiagram
 * @author     Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 *
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');


require_once($CFG->libdir . '/adminlib.php');
$PAGE->set_context(context_system::instance());

$PAGE->set_url('/admin/sqlgenerator.php');
$markup = optional_param('markup', '', PARAM_TEXT);

class tool_erdiagram_form extends moodleform {
    protected $mermaid;

    protected function definition() {
        global $CFG;
        $mform = $this->_form;
        $mform->addElement('text', 'pluginfolder', get_string('pluginfolder', 'tool_erdiagram'));
        $mform->setDefault('pluginfolder', 'mod/book');
        $mform->addHelpButton('pluginfolder', 'pluginfolder', 'tool_erdiagram');
        $mform->setType('pluginfolder', PARAM_TEXT);
        $mform->addElement('textarea', 'markup', 'Output', array('rows' => 10, 'cols' => 80));
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
$mform = new tool_erdiagram_form(new moodle_url('/local/erdiagram/'));
if ($data = $mform->get_data()) {
    $pluginfolder = $data->pluginfolder ?? '';
    if (isset($data->submitbutton)) {
        if (isset($data->pluginfolder) && $data->pluginfolder > "") {
            $installxml = $CFG->dirroot.'/'.$data->pluginfolder .'/db/install.xml';
            if (file_exists($installxml)) {
                $options['fieldnames'] = $data->fieldnames;
                $output = process_file($installxml, $options);
                $mform->set_data($output);

            } else {
                $msg = 'File not found';
                \core\notification::add($msg, \core\notification::WARNING);
            }
        }
    }
}
/**
 * Extract data from the xml file and convert it to
 * mermaid er diagram markdown.
 * https://mermaid.js.org/config/Tutorials.html
 *
 * @param  string $installxml //path to dbmxl file i.e. mod/label/db/install.xml
 * @param  array $options //array containing output option flags
 * @return string $output //mermaid markdown @TODO change variable name
 */
function  process_file (string $installxml, array $options) {
    $output = 'erDiagram'.PHP_EOL;

    $xmldbfile = new xmldb_file($installxml);
    $xmldbfile->loadXMLStructure();

    $xmldbstructure = $xmldbfile->getStructure();
    $tables = $xmldbstructure->getTables();
    foreach ($tables as $table) {
        $tablename = $table->getName();
        $foreignkeys = get_foreign_keys($table);
        foreach ($foreignkeys as $fkey) {
              $output .= $fkey->getReftable().'  ||--o{ '.$tablename.' : '. $fkey->getReffields()[0]. PHP_EOL;
        }
        $output .= ''.$tablename;
        $output .= ' {'.PHP_EOL;
        foreach ($table->getFields() as $field) {
            if ($options['fieldnames']) {
                $output .= '    '.get_field_type($field->getType());
                $output .= ' '.$field->getName();
                $comment = $field->getComment();
                if ($comment) {
                    $output .= '"'.$comment. '"';
                }
                $output .= PHP_EOL;
            }
        }
        $output .= '}'.PHP_EOL;
    }
    return $output;
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();

/**
 * Any key that is not a primary key is assumed to be
 * a PK/FK relationship.
 *
 * @param xmldb_table $table
 * @return array
 */
function get_foreign_keys(xmldb_table $table) {
    $keys = $table->getKeys();
    $foreignkeys = [];
    foreach ($keys as $key) {
        if ($key->getName() !== "primary") {
            $foreignkeys[] = $key;
        }
    }
    return $foreignkeys;
}

/**
 * Inspired by the function getTypeSQL found at
 * lib/ddl/sqlite_sql_generator.php
 * The "correct" datatypes may depend on what database
 * you are familiar with
 * @param int $xmldbtype Constant of field types
 * @return void
 */
function get_field_type($fieldtype) {

    switch ($fieldtype) {
        case XMLDB_TYPE_INTEGER:
            $typename = 'INTEGER';
            break;
        case XMLDB_TYPE_NUMBER:
            $typename = 'INTEGER';
            break;
        case XMLDB_TYPE_FLOAT:
            $typename = 'FLOAT';
            break;
        case XMLDB_TYPE_CHAR:
            $typename = 'VARCHAR';
            break;
        case XMLDB_TYPE_BINARY:
            $typename = 'BLOB';
            break;
        case XMLDB_TYPE_DATETIME:
            $typename = 'DATETIME';
        default:
        case XMLDB_TYPE_TEXT:
            $typename = 'TEXT';
            break;
    }
    return $typename;
}
