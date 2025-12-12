<?php
$mysqli = new mysqli("localhost", "root", "root", "dbt_demo", 8889);

if ($mysqli->connect_errno) {
    die("Database Connection Failed: " . $mysqli->connect_error);
}
?>
