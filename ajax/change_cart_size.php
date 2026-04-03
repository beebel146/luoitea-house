<?php
require_once("../config/config.php");
header('Content-Type: application/json');

$id_size = $_POST['id_size'] ?? '';
$new_size = $_POST['new_size'] ?? '';

if (!empty($id_size) && !empty($new_size) && isset($_SESSION['cart'][$id_size])) {
    $qty = $_SESSION['cart'][$id_size];
    
    // Parse the original ID
    $parts = explode('_', $id_size);
    $id = intval($parts[0]);
    $old_size = $parts[1] ?? 'M';
    
    if ($old_size !== $new_size) {
        $new_id_size = $id . '_' . $new_size;
        
        // Remove old size, add qty to new size
        unset($_SESSION['cart'][$id_size]);
        
        if (isset($_SESSION['cart'][$new_id_size])) {
            $_SESSION['cart'][$new_id_size] += $qty;
        } else {
            $_SESSION['cart'][$new_id_size] = $qty;
        }
    }
    
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Thiếu dữ liệu"]);
}
