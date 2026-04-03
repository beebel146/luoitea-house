<?php
require_once(__DIR__ . '/config.php');

try {
    // Check if price column still exists, handle it
    $result = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'price'");
    $price_exists = (mysqli_num_rows($result)) ? true : false;
    
    if($price_exists) {
        $queries = [
            "ALTER TABLE products CHANGE price price_m INT NOT NULL DEFAULT 0;",
            "ALTER TABLE products ADD price_s INT NOT NULL DEFAULT 0 AFTER price_m;",
            "ALTER TABLE products ADD price_l INT NOT NULL DEFAULT 0 AFTER price_s;"
        ];
        
        foreach($queries as $q) {
            if(!mysqli_query($conn, $q)) {
                 echo "Error executing $q : " . mysqli_error($conn) . "\n";
            } else {
                 echo "Successfully executed $q\n";
            }
        }
        
        // Find capping categories. Looking for '%topping%' or similar.
        // Assuming category 'Topping' exists.
        $res = mysqli_query($conn, "SELECT id FROM categories WHERE LOWER(name) LIKE '%topping%'");
        $topping_ids = [];
        while($row = mysqli_fetch_assoc($res)) {
            $topping_ids[] = $row['id'];
        }
        
        $topping_condition = empty($topping_ids) ? "-1" : implode(',', $topping_ids);
        
        $update_topping = "UPDATE products SET price_s = price_m - 2000, price_l = price_m + 2000 WHERE category_id IN ($topping_condition)";
        $update_tea = "UPDATE products SET price_s = price_m - 5000, price_l = price_m + 5000 WHERE category_id NOT IN ($topping_condition)";
        
        mysqli_query($conn, $update_topping);
        echo "Updated topping prices. Rows affected: " . mysqli_affected_rows($conn) . "\n";
        
        mysqli_query($conn, $update_tea);
        echo "Updated milktea prices. Rows affected: " . mysqli_affected_rows($conn) . "\n";
        
        echo "Migration complete.\n";
    } else {
        echo "Columns seem to be already migrated.\n";
    }
} catch (Exception $e) {
    echo "Error: ". $e->getMessage() ."\n";
}
?>
