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
 * ER diagram graphviz converter
 *
 * @package    tool_erdiagram
 * @author     Kevin Pham <kevinpham@catalyst-au.net>
 * @copyright  Catalyst IT, 2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dot {

    /**
     * Generate image according to DOT script. This function will spawn a process
     * with "dot" command and pipe the "dot_script" to it and pipe out the
     * generated image content.
     *
     * @param  string $dotscript the script for DOT to generate the image.
     * @param  string $type supported image types: jpg, gif, png, svg, ps.
     * @return binary|string content of the generated image on success, empty string on failure.
     *
     * @author     cjiang
     * @author     Kevin Pham <kevinpham@catalyst-au.net>
     */
    public static function generate(string $dotscript, ?string $type = 'svg') {
        global $CFG, $OUTPUT;

        $descriptorspec = [
            // The stdin is a pipe that the child will read from.
            0 => ['pipe', 'r'],
            // The stdout is a pipe that the child will write to.
            1 => ['pipe', 'w'],
            // The stderr is a pipe that the child will write to.
            2 => ['pipe', 'w'],
        ];

        $cmd = (!empty($CFG->pathtodot) ? $CFG->pathtodot : 'dot') . ' -T' . $type;
        $process = proc_open(
            $cmd,
            $descriptorspec,
            $pipes,
            sys_get_temp_dir(),
            ['PATH' => getenv('PATH')]
        );

        if (is_resource($process)) {
            [$stdin, $stdout, $stderr] = $pipes;
            fwrite($stdin, $dotscript);
            fclose($stdin);

            $output = stream_get_contents($stdout);

            $err = stream_get_contents($stderr);
            if (!empty($err)) {
                $error = "failed to execute cmd: \"$cmd\". stderr: `$err`<br><pre>";
                $lines = explode("\n", $dotscript);
                for ($c = 0; $c < count($lines); $c++) {
                    $error .= sprintf('%3d', $c + 1) . ' ' . s($lines[$c]) . "\n";
                }
                $error .= "</pre>";
                return $error;
            }

            fclose($stderr);
            fclose($stdout);
            proc_close($process);
            return $output;
        }

        throw new \Exception("failed to execute cmd \"$cmd\"");
    }

}
