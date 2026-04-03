<?php
require_once(__DIR__ . '/config.php');

mysqli_query($conn, "ALTER TABLE order_items ADD size VARCHAR(5) NOT NULL DEFAULT 'M' AFTER product_id");
echo "Added size to order_items.\n";
?>
