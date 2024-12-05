#!/usr/local/bin/php
<?php

// Include the configuration file
include 'config.php';

// Check if credentials are properly configured
if (is_null($db_config['username']) || is_null($db_config['password'])) {
    die("Please update config.php with your database credentials.");
}

// Connect to Oracle database using credentials from config.php
$connection = oci_connect(
    $db_config['username'],
    $db_config['password'],
    $db_config['connection_string']
);

// Check if the connection was successful
if (!$connection) {
    $e = oci_error();
    die("Connection failed: " . htmlentities($e['message']));
}

// Example query
$query = "
SELECT 
    c.RecordNumber, 
    TO_CHAR(c.DateOccurred, 'YYYY-MM-DD HH24:MI:SS') AS DateOccurred, 
    c.CrimeCodeDescription, 
    l.AreaName, 
    l.Street
FROM Crime c
JOIN Location l ON c.LocationID = l.LocationID
WHERE l.AreaName = 'Central'
AND ROWNUM <= 100
";
$statement = oci_parse($connection, $query);
oci_execute($statement);

// Fetch and display data dynamically
echo "<table border='1'>";

// Dynamically fetch column names for the header row
echo "<tr>";
$num_columns = oci_num_fields($statement); // Get number of columns
for ($i = 1; $i <= $num_columns; $i++) {
    echo "<th>" . htmlentities(oci_field_name($statement, $i), ENT_QUOTES) . "</th>";
}
echo "</tr>";

// Dynamically display rows
while ($row = oci_fetch_array($statement, OCI_ASSOC+OCI_RETURN_NULLS)) {
    echo "<tr>";
    foreach ($row as $cell) {
        echo "<td>" . htmlentities($cell, ENT_QUOTES) . "</td>";
    }
    echo "</tr>";
}

echo "</table>";

// Free resources and close connection
oci_free_statement($statement);
oci_close($connection);
?>
