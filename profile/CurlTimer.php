<?php
/**
 * SameAs Lite
 *
 * This class provides a simple timer for timing HTTP GET requests
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
 * Simple timer for timing HTTP GET requests using cURL.
 */
class CurlGetTimer
{
    /** @var string $url Prefix of URL to issue GET request to */
    private $url = null;
    /** @var string|null $dataFile Data file to append GET results to */
    private $dataFile = null;

    /**
     * Constructor. Saves URL.
     *
     * @param string $url Prefix of URL to issue GET request to. This
     * is assumed not to have a trailing "/".
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * Set data file.
     * @param string|null $dataFile Data file to append GET
     * results to.
     */
    public function setDataFile($dataFile = null)
    {
        $this->dataFile = $dataFile;
    }

    /**
     * Calculate time to issue HTTP GET request and receive response
     * to a URL composed from a given resource appended to the URL
     * held by this class.
     * If a data file is set then the response is appended
     * to this file.
     * @param string $resource  Resource to append to URL.
     * @return float Execution time in seconds.
     * @throws \Exception If an error arises, an HTTP response code
     * not equal to 200 is returned or if the response contains
     * <h2>Not Found</h2>".
     */
    public function httpGet($resource)
    {
        $getUrl = $this->url . '/' . $resource;
        $session = curl_init($getUrl);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
        $start = microtime(true);
        $result = curl_exec($session);
        $end = microtime(true);
        $total = $end - $start;
        $errno = curl_errno($session);
        $error = curl_error($session);
        $http = curl_getinfo($session, CURLINFO_HTTP_CODE);
        curl_close($session);
        $this->dumpResult($result);
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
        if (strpos($result, "<h2>Not Found</h2>") != false)
        {
            throw new \Exception("The return page contains 'Not Found'\n");
        }
	return $total;
    }

    /**
     * Append the results to the data file if defined.
     * @param array $result HTTP GET result.
     */
    private function dumpResult($result)
    {
        if ($this->dataFile != null)
        {
            file_put_contents($this->dataFile, print_r($result, TRUE) .
                PHP_EOL, FILE_APPEND);
        }
    }
}
?>
