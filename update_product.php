<?php
header("Content-Type: application/json");
require_once 'config/db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['product_id'])) {
    http_response_code(400);
    echo json_encode("Invalid input");
    exit;
}

$productId   = $data['product_id'];
$name        = $data['name'];
$description = $data['description'];
$category    = $data['category'];
$subcategory = $data['subcategory'];
$price       = $data['price'];
$stock       = $data['stock'];
$imageBase64 = $data['image'] ?? null;

$imageUrl = null;
if ($imageBase64) {
    $image_data = base64_decode($imageBase64);
    $filename = "prod_" . time() . "_" . $productId . ".jpg";
    $filepath = "uploads/" . $filename;
    
    if (!file_exists("uploads")) {
        mkdir("uploads", 0777, true);
    }
    
    file_put_contents($filepath, $image_data);
    $imageUrl = "http://" . $_SERVER['HTTP_HOST'] . "/ecommerce/api/" . $filepath;
}

$sql = "UPDATE PRODUCT SET 
        PRODUCT_NAME = :name, 
        PRODUCT_DESCRIPTION = :description, 
        PRODUCT_CATEGORY = :category, 
        PRODUCT_SUBCATEGORY = :subcategory,
        PRODUCT_PRICE = :price, 
        STOCK = :stock";


if ($imageUrl) {
    $sql .= ", IMAGES_URL = :image_url";
}

$sql .= " WHERE PRODUCT_ID = :product_id";

$stid = oci_parse($conn, $sql);

oci_bind_by_name($stid, ":name", $name);
oci_bind_by_name($stid, ":description", $description);
oci_bind_by_name($stid, ":category", $category);
oci_bind_by_name($stid, ":subcategory", $subcategory);
oci_bind_by_name($stid, ":price", $price);

oci_bind_by_name($stid, ":stock", $stock);
oci_bind_by_name($stid, ":product_id", $productId);

if ($imageUrl) {
    oci_bind_by_name($stid, ":image_url", $imageUrl);
}

if (oci_execute($stid)) {
    echo json_encode("Product updated successfully");
} else {
    $e = oci_error($stid);
    echo json_encode("Error: " . $e['message']);
}

oci_free_statement($stid);
oci_close($conn);
?>
