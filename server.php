<?php
// NTP server, you may need root permissions to run it
// Waits for connections and sends time
require_once 'NTPLite.php';

// Listens on the local IP address
$address = '0.0.0.0';
$port = 123;

// Opens the socket using UDP datagrams
$socket = @stream_socket_server( "udp://$address:$port", $errno, $errstr, STREAM_SERVER_BIND );
if (!$socket) {
    echo "Socket error $errno: $errstr\n";
    return -1;
}

do {
    $data = stream_socket_recvfrom($socket, 1500, 0, $from);
    
    // If the connection comes from an unknown client, we don't treat it
    if ($from == '') {
        echo ">> Connection from unknown client, rejected\n";
    } else {
        echo ">> Connection: $from\n";
        
        // The client-sent query
        $NTP = new NTPLite();
        
        // NTPLite only supports client & server modes, other messages are rejected
        if (!$NTP->readMessage($data)) {
            $hex = '';
            for ($i = 0; $i < strlen($data); $i++) {
                $hex .= sprintf('%02x', ord($data[$i]));
            }
            echo "Bad request, aborted\n$hex\n";
        } else {
            $NTP->dump();
            echo "\n", $NTP;
            
            // Build the response, using arbitrary values for precision, delay & dispersion
            $NTP->leapIndicator = 0;
          //$NTP->versionNumber re-uses the sent value
            $NTP->mode = 4;
            $NTP->stratum = 6;
          //$NTP->pollInterval re-uses the sent value
            $NTP->precision = -20;
            $NTP->rootDelay = 0;
            $NTP->rootDispersion = 0.0120;
            $NTP->referenceIdentifier = ip2long('127.127.1.0'); // 'LOCL' is valid too
            
            // The current timestamp
            $now = time();
            $NTP->referenceTimestamp = NTPLite::convertTsUnixToSntp($now);
            $NTP->originateTimestamp = $NTP->transmitTimestamp; // re-uses the transmit TS
            $NTP->receiveTimestamp   = NTPLite::convertTsUnixToSntp($now);
            $NTP->transmitTimestamp  = NTPLite::convertTsUnixToSntp($now);
            
            // Sends the response
            stream_socket_sendto($socket, $NTP->writeMessage(), 0, $from);
        }
        
        unset($NTP);
        echo "<< Connection: $from\n\n";
    }
} while ($data !== false);
?>