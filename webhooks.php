<?php
# Developer, error report on/off ----------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 0);
# Developer, error report on/off ----------------------------------------------

header("HTTP/1.1 200 OK");

# Cross Domain Control
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST,GET,OPTIONS');
header('Access-Control-Allow-Headers: Origin,Content-Type,Accep');
header('Access-Control-Max-Age: 1000');

# Cache control
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Just return the headers if it's an OPTIONS request
    exit(0);
}

# receive data --------------------------------------------------------
$fp = fopen('php://input', 'r');
$rawData = stream_get_contents($fp);
# receive data --------------------------------------------------------

# Write to file the incoming data -------------------------------------
$fp = fopen('webhookdata.txt', 'w');
fwrite($fp, $rawData);
fclose($fp);
# Write to file the incoming data -------------------------------------

?>
