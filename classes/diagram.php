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

namespace tool_erdiagram;

/**
 * ER Diagram generator
 *
 * @package     tool_erdiagram
 * @author      Marcus Green
 * @copyright   Catalyst IT 2023
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class diagram {


    /**
     * Extract data from the xml file and convert it to
     * mermaid er diagram markdown.
     * https://mermaid.js.org/config/Tutorials.html
     *
     * @param  string $installxml //path to dbmxl file i.e. mod/label/db/install.xml
     * @param  array $options //array containing output option flags
     * @return string $output //mermaid markdown @TODO change variable name
     */
    public function process_file (string $installxml, array $options) {
        $output = "erDiagram\n";

        $xmldbfile = new \xmldb_file($installxml);
        $xmldbfile->loadXMLStructure();

        $xmldbstructure = $xmldbfile->getStructure();
        $tables = $xmldbstructure->getTables();
        foreach ($tables as $table) {
            $tablename = $table->getName();
            $foreignkeys = $this->get_foreign_keys($table);
            foreach ($foreignkeys as $fkey) {
                $reftable = $fkey->getReftable();
                $fields = $fkey->getFields();
                $reffields = $fkey->getReffields();
                if (!empty($reffields) && count($reffields) > 0) {
                    $output .= "$reftable ||--o{ $tablename : \"$fields[0] -> {$reffields[0]}\"\n";
                }
            }
            $output .= $tablename;
            $output .= " {\n";
            foreach ($table->getFields() as $field) {
                if ($options['fieldnames']) {
                    $output .= '    ' . $this->get_field_type($field->getType());
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
    private function get_foreign_keys(\xmldb_table $table) {
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
     *
     * @param int $fieldtype Constant of field types
     * @return string type
     */
    private function get_field_type($fieldtype) {

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

}

