<?php
require __DIR__ . '/config/database.php';

$res = $conn->query("SHOW COLUMNS FROM productos");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . PHP_EOL;
    }
} else {
    echo "NO_TABLE\n";
}

$conn->close();
