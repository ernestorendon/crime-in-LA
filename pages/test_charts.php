#!/usr/local/bin/php
<?php

// Include the configuration file
include '../config.php';

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
    die("Database connection failed: " . htmlentities($e['message']));
}

// Query to fetch crime trends over time
$query = "
SELECT 
    TO_CHAR(c.DateOccurred, 'YYYY-MM') AS Month, 
    COUNT(*) AS CrimeCount 
FROM Crime c
JOIN Location l ON c.LocationID = l.LocationID
WHERE l.AreaName = 'Central'
GROUP BY TO_CHAR(c.DateOccurred, 'YYYY-MM')
ORDER BY TO_CHAR(c.DateOccurred, 'YYYY-MM')
";

$statement = oci_parse($connection, $query);
oci_execute($statement);

// Fetch data and prepare JSON
$data = [];
while ($row = oci_fetch_assoc($statement)) {
    $data[] = [
        'month' => $row['MONTH'],
        'count' => (int)$row['CRIMECOUNT']
    ];
}

oci_free_statement($statement);
oci_close($connection);

// Pass data to JavaScript for ZingChart
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crime Trends</title>
    <script src="https://cdn.zingchart.com/zingchart.min.js"></script>
    <link rel="stylesheet" href="../css/style.css">
    
</head>
<body>
    <h1>Crime Trends in Central Over Time</h1>
    <div id="crime-trend-chart" style="width: 100%; height: 500px;"></div>

    <script>
        // Crime data passed from PHP
        const crimeData = <?php echo json_encode($data); ?>;

        // Extract months and counts from the data
        const months = crimeData.map(item => item.month);
        const counts = crimeData.map(item => item.count);

        // ZingChart configuration
        const chartConfig = {
            type: 'line',
            backgroundColor: 'transparent', // Transparent background
            title: {
                text: 'Crime Trends in Central'
            },
            scaleX: {
                label: { text: 'Month' },
                labels: months // X-axis labels
            },
            scaleY: {
                label: { text: 'Number of Crimes' }
            },
            series: [
                {
                    values: counts // Data points
                }
            ]
        };

        // Render the chart
        zingchart.render({
            id: 'crime-trend-chart',
            data: chartConfig,
            height: '100%',
            width: '80%'
        });
    </script>

<!-- Placeholder for footer -->
<div id="footer-placeholder"></div>

<!-- JavaScript to load the footer -->
<script>
    // JavaScript to dynamically load the footer
    // fetch('../footer.html')
        .then(response => response.text())
        .then(data => {
            document.getElementById('footer-placeholder').innerHTML = data;
        })
        .catch(error => console.error('Error loading footer:', error));
</script>
    
</body>
</html>
