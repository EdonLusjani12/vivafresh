<?php
$host = '91.187.123.126';
$port = 2323;
$timeout = 5;

$connection = @fsockopen($host, $port, $errno, $errstr, $timeout);

if ($connection) {
    echo "✅ Success: Able to connect to $host on port $port";
    fclose($connection);
} else {
    echo "❌ Failed to connect to $host on port $port - $errstr ($errno)";
}
?>
