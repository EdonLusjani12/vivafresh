<?php
$serverName = "91.187.123.126,1433";
$connectionOptions = [
    "Database" => "wtrgksvfmk",
    "Uid" => "prodgen  ",
    "PWD" => "apt1991"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

$sql = "SELECT * FROM your_table";
$stmt = sqlsrv_query($conn, $sql);

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo $row['column_name'] . "<br>";
}

sqlsrv_close($conn);
?>
