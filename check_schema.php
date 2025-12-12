<?php
include "connectDB.php";

$table = 'comments';
$result = $conn->query("DESCRIBE $table");

if ($result) {
    echo "<h1>Structure of '$table'</h1>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>$val</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error describing table: " . $conn->error;
}
?>
