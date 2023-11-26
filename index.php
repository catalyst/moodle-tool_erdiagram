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
 * @copyright  Catalyst IT 2023
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
            $diagram = new tool_erdiagram\diagram();
            $output = $diagram->process_file($installxml, $options);
            echo <<<EOF
<script type='module'>
import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
mermaid.initialize({ startOnLoad: true });
</script>
<ul class="nav nav-tabs" id="myTab" role="tablist">
  <li class="nav-item" role="presentation">
    <a class="nav-link active" id="diagram-tab" data-toggle="tab" href="#diagram"
        role="tab" aria-controls="diagram" aria-selected="true">Diagram</a>
  </li>
  <li class="nav-item" role="presentation">
    <a class="nav-link" id="source-tab" data-toggle="tab" href="#source"
        role="tab" aria-controls="source" aria-selected="false">Source</a>
  </li>
</ul>
<div class="tab-content" id="myTabContent">
  <div class="tab-pane fade show active" id="diagram" role="tabpanel" aria-labelledby="diagram-tab">
<pre class='mermaid'>
$output
</pre>
  </div>
  <div class="tab-pane fade"             id="source" role="tabpanel" aria-labelledby="source-tab">
<pre>
$output
</pre>
  </div>
</div>
EOF;

        } else {
            $msg = 'File not found';
            \core\notification::add($msg, \core\notification::WARNING);
        }
    }
}

echo $OUTPUT->footer();

