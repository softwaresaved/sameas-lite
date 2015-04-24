<?php
/**
 * SameAs Lite
 *
 * This class provides a simple timer for timing HTTP requests
 * using cURL.
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
 * Simple timer for timing HTTP requests using cURL.
 */
class CurlTimer
{
    /** @var string|null $dataFile Data file to append output to */
    private $dataFile = null;

    /**
     * Set data file.
     * @param string|null $dataFile Data file to append output to.
     */
    public function setDataFile($dataFile = null)
    {
        $this->dataFile = $dataFile;
    }

    /**
     * Calculate time to issue HTTP GET request to and receive response
     * from a URL.
     * If a data file is set then its output is appended
     * to this file, otherwise it is printed.
     * @param string $url URL to issue requests to.
     * @return float Execution time in seconds.
     * @throws \Exception If an error arises, an HTTP response code
     * not equal to 200 is returned or if the response contains
     * <h2>Not Found</h2>".
     */
    public function httpGet($url)
    {
        $session = curl_init($url);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
        $start = microtime(true);
        $output = curl_exec($session);
        $end = microtime(true);
        $total = $end - $start;
        $errno = curl_errno($session);
        $error = curl_error($session);
        $http = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        $this->dumpOutput($output);
        if ($errno != 0)
        {
            throw new \Exception('cURL error number: ' .
                $errno . '. cURL error: ' . $error);
        }
        if ($http != 200)
        {
            throw new \Exception('Unexpected HTTP code. Expected: ' .
                200 . ' Received: ' . $http);
        }
        if (strpos($output, "<h2>Not Found</h2>") != false)
        {
            throw new \Exception("The return page contains 'Not Found'\n");
        }
	return $total;
    }

    /**
     * Append the output to the data file if defined. Otherwise
     * print them to standard output.
     * @param array $output HTTP output.
     */
    private function dumpOutput($output)
    {
        if ($this->dataFile != null)
        {
            file_put_contents($this->dataFile, print_r($output, TRUE) .
                PHP_EOL, FILE_APPEND);
        }
        else
        {
            print_r($output . PHP_EOL);
        }
    }
}
?>
