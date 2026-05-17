<?php
header("Content-Type: application/json");
require_once 'config/db_connect.php';

$productId = $_GET['product_id'] ?? '';

if (empty($productId)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing product_id parameter"]);
    exit;
}

$sql = "SELECT PRODUCT_ID, PRODUCT_NAME, STOCK,
        PRODUCT_PRICE, LIST_DATE, RATING,
        PRODUCT_DESCRIPTION, IMAGES_URL,
        VIEWS, PRODUCT_SUBCATEGORY,
        PRODUCT_CATEGORY
        FROM PRODUCT 
        WHERE PRODUCT_ID = :product_id";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":product_id", $productId);

if (!oci_execute($stid)) {
    echo json_encode(["error" => "Failed to execute query"]);
    exit;
}

$row = oci_fetch_assoc($stid);

if ($row) {
    echo json_encode([
        "name" => $row['PRODUCT_NAME'],
        "rating" => $row['RATING'],
        "stock" => $row['STOCK'],
        "productID" => $row['PRODUCT_ID'],
        "image" => $row['IMAGES_URL'],
        "price" => number_format($row['PRODUCT_PRICE'], 2, '.', ''),
        "description" => $row['PRODUCT_DESCRIPTION'],
        "category" => $row['PRODUCT_CATEGORY'],
        "views" => $row['VIEWS'],
        "date" => $row['LIST_DATE'],
        "subcategory" => $row['PRODUCT_SUBCATEGORY']
    ]);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Product not found"]);
}

oci_free_statement($stid);
oci_close($conn);
?>
