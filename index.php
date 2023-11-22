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
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('toolerdiagram');

require_once($CFG->libdir . '/adminlib.php');
$PAGE->set_context(context_system::instance());

$PAGE->set_url('/admin/sqlgenerator.php');
$markup = optional_param('markup', '', PARAM_TEXT);

echo $OUTPUT->header();

$mform = new tool_erdiagram\form\component(new moodle_url('/admin/tool/erdiagram/'));
$mform->display();

if ($data = $mform->get_data()) {
    $pluginfolder = $data->pluginfolder ?? '';
    if (isset($data->submitbutton) && $pluginfolder > '') {
        $installxml = "$CFG->dirroot/$pluginfolder/db/install.xml";
        if (file_exists($installxml)) {
            $options['fieldnames'] = $data->fieldnames;
            $output = process_file($installxml, $options);
            echo <<<EOF
<script type='module'>
import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
mermaid.initialize({ startOnLoad: true });
</script>
<pre class='mermaid'>
$output
</pre>
EOF;

        } else {
            $msg = 'File not found';
            \core\notification::add($msg, \core\notification::WARNING);
        }
    }
}

echo $OUTPUT->footer();

/**
 * Extract data from the xml file and convert it to
 * mermaid er diagram markdown.
 * https://mermaid.js.org/config/Tutorials.html
 *
 * @param  string $installxml //path to dbmxl file i.e. mod/label/db/install.xml
 * @param  array $options //array containing output option flags
 * @return string $output //mermaid markdown @TODO change variable name
 */
function process_file (string $installxml, array $options) {
    $output = "erDiagram\n";

    $xmldbfile = new xmldb_file($installxml);
    $xmldbfile->loadXMLStructure();

    $xmldbstructure = $xmldbfile->getStructure();
    $tables = $xmldbstructure->getTables();
    foreach ($tables as $table) {
        $tablename = $table->getName();
        $foreignkeys = get_foreign_keys($table);
        foreach ($foreignkeys as $fkey) {
            $reftable = $fkey->getReftable();
            $fields = $fkey->getFields();
            $reffields = $fkey->getReffields();
            if (!empty($reffields) && sizeof($reffields) > 0) {
                $output .= "$reftable ||--o{ $tablename : \"$fields[0] -> {$reffields[0]}\"\n";
            }
        }
        $output .= $tablename;
        $output .= " {\n";
        foreach ($table->getFields() as $field) {
            if ($options['fieldnames']) {
                $output .= '    ' . get_field_type($field->getType());
                $output .= ' ' . $field->getName();
                $comment = $field->getComment();
                if ($comment) {
                    $output .= '"' . $comment . '"';
                }
                $output .= "\n";
            }
        }
        $output .= "}\n";
    }
    return $output;
}

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
