<?php
echo "Testing TS3 connection...\n";

$host = '217.182.175.212';
$ports = [10011, 21218, 2022]; // Try different ports

foreach ($ports as $port) {
    echo "\nAttempting to connect to $host:$port\n";
    
    $socket = fsockopen($host, $port, $errno, $errstr, 5);
    
    if ($socket) {
        echo "Connection successful to port $port!\n";
        
        // Try to read the welcome message
        $response = fgets($socket, 1024);
        echo "Server response: " . $response . "\n";
        
        fclose($socket);
        break;
    } else {
        echo "Connection failed to port $port: $errstr ($errno)\n";
    }
}

echo "\nTest completed.\n";
?> 