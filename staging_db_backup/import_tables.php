<?php
ini_set('memory_limit', '-1'); // Unlimited memory
set_time_limit(0); // No timeout

$host = 'localhost';
$username = 'stickerc_blank_staging';
$password = 'G=?L=HwxQq6&';
$dbname = 'stickerc_blank_staging';

mysqli_report(MYSQLI_REPORT_OFF); // Suppress crashing on error

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "✅ Connected to DB successfully<br><br>";

// $folder = __DIR__ . '/06082025';
// $folder = __DIR__ . '/bkp_tables';
// $folder = __DIR__ . '/06082025';
 $folder = __DIR__ . '/db_table';
$files = glob($folder . '/*.sql');
sort($files);

foreach ($files as $file) {
    echo "📂 Importing " . basename($file) . " ... <br>";

    $handle = fopen($file, 'r');
    if (!$handle) {
        echo "❌ Failed to open file: " . basename($file) . "<br>";
        continue;
    }

    $query = '';
    $executedQueries = 0;

    while (($line = fgets($handle)) !== false) {
        $line = trim($line);

        // Skip comments and empty lines
        if ($line === '' || strpos($line, '--') === 0 || strpos($line, '/*') === 0 || strpos($line, '//') === 0) {
            continue;
        }

        $query .= $line . "\n";

        // If line ends with a semicolon, execute the query
        if (substr($line, -1) === ';') {
            @$conn->query("SET FOREIGN_KEY_CHECKS=0;");
            if (!@$conn->query($query)) {
                echo "⚠️ Error in query: " . $conn->error . "<br>";
                echo "<pre>$query</pre>";
            } else {
                $executedQueries++;
            }
            @$conn->query("SET FOREIGN_KEY_CHECKS=1;");
            $query = '';
        }
    }

    fclose($handle);
    echo "✅ Finished " . basename($file) . " — Executed $executedQueries queries.<br><br>";
}

$conn->close();
echo "<br>🎉 All SQL files processed.";
?>
