#!/usr/local/bin/php
<?php

include 'config.php';

// Connect to Oracle database
$connection = oci_connect(
    $db_config['username'],
    $db_config['password'],
    $db_config['connection_string']
);

if (!$connection) {
    $e = oci_error();
    http_response_code(500);
    echo json_encode(['error' => htmlentities($e['message'])]);
    exit;
}

// Complex trend query with percentage calculation
$query = "
    SELECT 
        TO_CHAR(c.DateOccurred, 'YYYY-MM') AS Month, 
        l.AreaName, 
        COUNT(c.RecordNumber) AS TotalCrimes,
        ROUND(
            100.0 * COUNT(c.RecordNumber) / SUM(COUNT(c.RecordNumber)) OVER (PARTITION BY TO_CHAR(c.DateOccurred, 'YYYY-MM')), 
            2
        ) AS CrimePercentage
    FROM Crime c
    JOIN Location l 
        ON c.LocationID = l.LocationID
    GROUP BY 
        TO_CHAR(c.DateOccurred, 'YYYY-MM'), 
        l.AreaName
    ORDER BY 
        TO_CHAR(c.DateOccurred, 'YYYY-MM'), 
        l.AreaName
";

$statement = oci_parse($connection, $query);
oci_execute($statement);

// Fetch data
$data = [];
while ($row = oci_fetch_assoc($statement)) {
    $data[] = [
        'month' => $row['MONTH'],
        'area' => $row['AREANAME'],
        'totalCrimes' => (int)$row['TOTALCRIMES'],
        'crimePercentage' => (float)$row['CRIMEPERCENTAGE']
    ];
}

oci_free_statement($statement);
oci_close($connection);

// Output data as JSON
header('Content-Type: application/json');
echo json_encode($data);
?>
