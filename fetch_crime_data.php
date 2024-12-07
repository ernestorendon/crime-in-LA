#!/usr/local/bin/php
<?php

include 'config.php';

// Get the crime category from the query parameter
$crimeCategory = $_GET['crimeCategory'] ?? 'ASSAULT WITH DEADLY WEAPON, AGGRAVATED ASSAULT';

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

// Query to return crime trends by category over time
$query = "
    SELECT 
        TO_CHAR(c.DateOccurred, 'YYYY-MM') AS Month, 
        COUNT(*) AS CrimeCount 
    FROM Crime c
    WHERE c.CrimeCodeDescription = :crimeCategory
    GROUP BY TO_CHAR(c.DateOccurred, 'YYYY-MM')
    ORDER BY TO_CHAR(c.DateOccurred, 'YYYY-MM')
";

$statement = oci_parse($connection, $query);
oci_bind_by_name($statement, ':crimeCategory', $crimeCategory);
oci_execute($statement);

// Fetch data
$data = [];
while ($row = oci_fetch_assoc($statement)) {
    $data[] = [
        'month' => $row['MONTH'],
        'count' => (int)$row['CRIMECOUNT']
    ];
}

oci_free_statement($statement);
oci_close($connection);

// Output data as JSON
header('Content-Type: application/json');
echo json_encode($data);
?>
