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

        $output = <<<EOF
digraph g {

    fontname="Helvetica,Arial,sans-serif"
    nodesep=1
    node [
        shape=record,
        fontsize=9,
        fontname="Helvetica",
    ];
    edge [
        fontname="Helvetica,Arial,sans-serif",
    ]
    graph [
        rankdir=LR,
        overlap=false,
        splines=true,
    ];

    comment="Now all of the component tables"

EOF;

        $xmldbfile = new \xmldb_file($installxml);
        $xmldbfile->loadXMLStructure();

        $xmldbstructure = $xmldbfile->getStructure();
        $tables = $xmldbstructure->getTables();

        $externaltables = [];
        $componenttables = [];
        foreach ($tables as $table) {
            $componenttables[] = $table->getName();
        }
        $componenttablenodes = '';

        foreach ($tables as $table) {
            $tablename = $table->getName();
            $componenttablenodes .= "        \"$tablename\";\n";

            // Chunk for each table.
            $fields = '';
            foreach ($table->getFields() as $field) {
                if ($options['fieldnames']) {
                    $fieldtype = $this->get_field_type($field->getType());
                    $fieldname = $field->getName();
                    $fields .=
                        sprintf("            <tr><td %-30s align=\"left\">%-10s</td><td %-31s align=\"left\">%-26s</td></tr>\n",
                        "port=\"in$fieldname\"",
                        $fieldtype,
                        "port=\"out$fieldname\"",
                        $fieldname);

                }
            }
             $output .= <<<EOF

    $tablename [
        shape=none,
        margin=0,
        style=filled,
        color="#333333",
        fillcolor=white,
        label=<
        <table border="0" cellborder="1" cellspacing="0" cellpadding="2">
            <tr><td bgcolor="lightblue" colspan="2">$tablename</td></tr>
$fields
        </table>>
    ];

EOF;
            // Show references between tables.
            $foreignkeys = $this->get_foreign_keys($table);
            foreach ($foreignkeys as $fkey) {
                $reftable = $fkey->getReftable();
                $fields = $fkey->getFields();
                $reffields = $fkey->getReffields();
                if (!empty($reffields) && count($reffields) > 0) {

                    // Only show the link if the referenced table is also in the diagram.
                    if (in_array($reftable, $componenttables) ) {
                        if ($options['fieldnames']) {
                            if ($tablename == $reftable) {
                                $output .= "    $tablename:in{$fields[0]}:w -> $reftable:in{$reffields[0]}:w [minlen=1];\n";
                            } else {
                                $output .= "    $tablename:out{$fields[0]} -> $reftable:in{$reffields[0]};\n";
                            }
                        } else {
                            $output .= "    $tablename -> $reftable;\n";
                        }
                    } else {
                        $externaltables[$reftable] = 1;
                        if ($options['fieldnames']) {
                            $output .= "    $tablename:out{$fields[0]} -> $reftable;\n";
                        } else {
                            $output .= "    $tablename -> $reftable;\n";
                        }
                    }
                }
            }
        }

        $output .= <<<EOF

    subgraph cluster_component {
        label="Component tables";
        style=filled;
		color="#eeeeee";
$componenttablenodes
    }

EOF;

        // Show external tables we discovered while linking.
        if ($externaltables) {

            $output .= <<<EOF

    comment="Now all of the external tables"

EOF;

            foreach ($externaltables as $table => $val) {
                $output .= <<<EOF
    $table [
        shape=none,
        margin=0,
        style=filled,
        color="#333333",
        fillcolor=white,

        label=<
        <table border="0" cellborder="1" cellspacing="0" cellpadding="3">
            <tr><td port="$table" bgcolor="orange" colspan="2">$table</td></tr>
        </table>>
    ]

EOF;
            }
            $exttables = '';
            foreach ($externaltables as $table => $val) {
                $exttables .= "        \"$table\";\n";
            }
            $output .= <<<EOF
    subgraph cluster_core_tables {
        label="Core tables";
        style=filled;
        color="#ffdd00";
$exttables
    }

EOF;
        }

        $output .= "}\n";
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
                $typename = 'int';
                break;
            case XMLDB_TYPE_NUMBER:
                $typename = 'number';
                break;
            case XMLDB_TYPE_FLOAT:
                $typename = 'float';
                break;
            case XMLDB_TYPE_CHAR:
                $typename = 'varchar';
                break;
            case XMLDB_TYPE_BINARY:
                $typename = 'blob';
                break;
            case XMLDB_TYPE_DATETIME:
                $typename = 'datetime';
            default:
            case XMLDB_TYPE_TEXT:
                $typename = 'text';
                break;
        }
        return $typename;
    }

}

