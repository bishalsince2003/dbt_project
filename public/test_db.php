<?php
// safer require using absolute path
require __DIR__ . '/../config/db.php';

if ($mysqli->connect_errno) {
    echo "❌ Database NOT Connected<br>";
    echo $mysqli->connect_error;
} else {
    echo "✅ Database Connected Successfully!<br>";

    // test query
    $result = $mysqli->query("SHOW TABLES");
    if ($result) {
        echo "Tables found in dbt_demo:<br>";
        while ($row = $result->fetch_array()) {
            echo " - " . htmlspecialchars($row[0]) . "<br>";
        }
    } else {
        echo "Query failed: " . htmlspecialchars($mysqli->error);
    }
}
?>
