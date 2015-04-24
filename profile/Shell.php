<?php

/**
 * Command-line tool to invoke \SameAsLite\ShellTimer, save
 * the results to a file and print the execution time.
 *
 * Usage:
 * <pre>
 * $ php Shell.php COMMAND DATAFILE
 * </pre>
 * where:
 * - URL - shell command to run.
 * - DATAFILE - file to log query results into.
 *
 * Example:
 * <pre>
 * $ php profile/Shell.php "php profile/QuerySymbol.php 'mysql:host=127.0.0.1;port=3306;charset=utf8' table1 testuser testpass testdb http.51011a3008ce7eceba27c629f6d0020c 2>&1"
 * </pre>
 */

require_once 'vendor/autoload.php';
require_once 'profile/ShellTimer.php';

$cmd=$argv[1];
$dataFile = $argv[2];
$timer = new \SameAsLite\ShellTimer();
$timer->setDataFile($dataFile);
$total = $timer->execute($cmd);
printf("%.4f\n", $total);
?>
