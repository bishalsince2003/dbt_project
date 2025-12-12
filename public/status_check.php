<?php
require __DIR__ . '/../config/db.php';

$kisan_id = trim($_POST['kisan_id'] ?? '');

if ($kisan_id === '' || !preg_match('/^\d{13}$/', $kisan_id)) {
    die("<p>Invalid Kisan ID. <a href='status_check_form.html'>Try again</a></p>");
}

// Check if farmer exists
$stmt = $mysqli->prepare("SELECT name, farmer_id FROM users WHERE kisan_id = ? LIMIT 1");
$stmt->bind_param("s", $kisan_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    die("<p>No farmer found with this Kisan ID. <a href='status_check_form.html'>Try again</a></p>");
}

$stmt->bind_result($name, $farmer_id);
$stmt->fetch();
$stmt->close();

// Get all applications
$stmt = $mysqli->prepare("SELECT app_id, scheme_code, scheme_name, applied_on, status FROM applications WHERE kisan_id = ?");
$stmt->bind_param("s", $kisan_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Applications for: <strong>$name</strong></h2>";
echo "<p>Farmer ID: <strong>$farmer_id</strong><br>";
echo "Kisan ID: <strong>$kisan_id</strong></p>";

if ($result->num_rows === 0) {
    echo "<p>No applications found.</p>";
    echo "<p><a href='apply_form.html'>Apply Now</a></p>";
} else {
    echo "<table>
            <tr>
              <th>Application ID</th>
              <th>Scheme Code</th>
              <th>Scheme Name</th>
              <th>Applied On</th>
              <th>Status</th>
            </tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['app_id']}</td>
                <td>{$row['scheme_code']}</td>
                <td>{$row['scheme_name']}</td>
                <td>{$row['applied_on']}</td>
                <td>{$row['status']}</td>
              </tr>";
    }
    echo "</table>";
}

echo "<p><a href='status_check_form.html'>Check Another</a></p>";
$stmt->close();
$mysqli->close();
