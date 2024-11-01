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
if ($connection) {
    echo "Connection to the Oracle database was successful!";
} else {
    $e = oci_error();
    echo "Connection failed: " . htmlentities($e['message']);
}

// Define a query (replace 'your_table' with your table name)
$query = "SELECT NAME, CODE, CAPITAL FROM COUNTRY";
$statement = oci_parse($connection, $query);
oci_execute($statement);

// Display data in an HTML table
echo "<table border='1'>";
echo "<tr><th>NAME</th><th>CODE</th><th>CAPITAL</th></tr>"; // Replace headers with actual column names

while ($row = oci_fetch_array($statement, OCI_ASSOC+OCI_RETURN_NULLS)) {
    echo "<tr>";
    echo "<td>" . htmlentities($row['NAME'], ENT_QUOTES) . "</td>"; // Replace COLUMN1 with actual column name
    echo "<td>" . htmlentities($row['CODE'], ENT_QUOTES) . "</td>"; // Replace COLUMN2 with actual column name
    echo "<td>" . htmlentities($row['CAPITAL'], ENT_QUOTES) . "</td>"; // Replace COLUMN2 with actual column name
    echo "</tr>";
}
echo "</table>";

// Free resources and close connection
oci_free_statement($statement);
oci_close($connection);
?>