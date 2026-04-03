<?php
require_once(__DIR__ . '/config.php');

$sql_type = "ALTER TABLE notifications ADD COLUMN type VARCHAR(20) DEFAULT 'general'";
$sql_ref = "ALTER TABLE notifications ADD COLUMN ref_id INT NULL";

if(mysqli_query($conn, $sql_type)) {
    echo "Added type column.\n";
} else {
    echo "Error or type already exists: " . mysqli_error($conn) . "\n";
}

if(mysqli_query($conn, $sql_ref)) {
    echo "Added ref_id column.\n";
} else {
    echo "Error or ref_id already exists: " . mysqli_error($conn) . "\n";
}
?>
