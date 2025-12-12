<?php
include 'connectDB.php';
$result = $conn->query("SHOW COLUMNS FROM sets");
echo "Columns in 'sets' table:\n";
while($row = $result->fetch_assoc()){
    echo $row['Field'] . "\n";
}
echo "\nColumns in 'users' table:\n";
$result = $conn->query("SHOW COLUMNS FROM users");
while($row = $result->fetch_assoc()){
    echo $row['Field'] . "\n";
}
?>
