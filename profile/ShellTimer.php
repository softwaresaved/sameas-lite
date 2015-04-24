<?php
/**
 * SameAs Lite
 *
 * This class provides a simple timer for timing shell commands.
 *
 * @package   SameAsLite
 * @author    The Software Sustainability Institute <info@software.ac.uk>
 * @copyright 2015 The University of Edinburgh
 * @license   Apache 2.0
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace SameAsLite;

/**
 * Simple timer for timing shell commands.
 */
class ShellTimer
{
    /**
     * Set data file.
     * @param string|null $dataFile Data file to append shell output
     * to.
     */
    public function setDataFile($dataFile = null)
    {
        $this->dataFile = $dataFile;
    }

    /**
     * Calculate time to run a shell command.
     * If a data file is set then its output is appended
     * to this file.
     * @param string $command Shell command.
     * @return float Execution time in seconds.
     * @throws \Exception If a non-zero return code is returned
     * by the shell command or any other error arises.
     */
    public function execute($command)
    {
        $start = microtime(true);
        exec($command, $outputs, $returnCode);
        $end = microtime(true);
        $total = $end - $start;
        $this->dumpOutputs($outputs);
        if ($returnCode != 0)
        {
            throw new \Exception('Non-zero return code: ' . $returnCode);
        }
	return $total;
    }

    /**
     * Append the output array to the data file if defined.
     * @param array $outputs Output array.
     */
    private function dumpOutputs($outputs)
    {
        if ($this->dataFile != null)
        {
            foreach ($outputs as $output)
            {
                file_put_contents($this->dataFile, print_r($output, TRUE) .
                    PHP_EOL, FILE_APPEND);
            }
        }
    }
}
?>
