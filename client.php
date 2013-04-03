<?php
// NTP client
// Sends a message and reads the response
require_once 'NTPLite.php';

// The server address
$address = '127.0.0.1';
$port = 123;

// Opens the socket to the SNTP server, uses UDP datagrams
$socket = @stream_socket_client( "udp://$address:$port", $errno, $errstr );
if (!$socket) {
    echo "Socket error $errno: $errstr\n";
    return -1;
}

$NTP = new NTPLite( false );

// Fills in the message to send
$NTP->leapIndicator = 0;
$NTP->versionNumber = 3;
$NTP->mode = 3;
$NTP->stratum = 0;
$NTP->pollInterval = 0;
$NTP->precision = 0;
$NTP->rootDelay = 0;
$NTP->rootDispersion = 0;
$NTP->referenceIdentifier = 0;

// Timestamps
$NTP->referenceTimestamp = 0;
$NTP->originateTimestamp = 0;
$NTP->receiveTimestamp   = 0;
$NTP->transmitTimestamp  = 0;

// Authentication
$NTP->keyIdentifier = 0;
$NTP->messageDigest = 0;

// Displays the query message
echo "Query:\n", $NTP, "\n";

// Sends the query message
$query = $NTP->writeMessage();
fwrite($socket, $query);

// Tries to read the server response
$response = fread($socket, 1500);
if ($NTP->readMessage($response)) {
    echo "Response:\n", $NTP;
    // Displays the server time
    $now = NTPLite::convertTsSntpToUnix($NTP->transmitTimestamp);
    // SNTP uses UTC timestamps
    echo "\nThe server time (UTC) is: " . gmdate('l j F Y, H:i:s e', $now);
    echo "\nSet your local clock to:  " .   date('l j F Y, H:i:s e', $now) . "\n";
} else {
    echo "Failed to read server response\n";
}

fclose($socket);
unset($NTP);
?>