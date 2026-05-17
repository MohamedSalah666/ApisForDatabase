<?php

header("Content-Type: application/json");

require_once 'config/db_connect.php';

$category = $_GET['category'] ?? '';

if (empty($category)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing category parameter"]);
    exit;
}

$sql = "SELECT PRODUCT_ID,PRODUCT_NAME,STOCK,
        PRODUCT_PRICE,LIST_DATE,RATING,
        PRODUCT_DESCRIPTION,IMAGES_URL,
        VIEWS,PRODUCT_SUBCATEGORY,
        PRODUCT_CATEGORY, VENDOR_ID
        FROM PRODUCT 
        WHERE PRODUCT_CATEGORY = :category
        ORDER BY PRODUCT_ID DESC";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":category", $category);

if (!oci_execute($stid)) {
    echo json_encode(["error" => "Failed to execute query"]);
    exit;
}

$products = [];
while ($row = oci_fetch_assoc($stid)) {
    $products[] = [
        "name"        => $row['PRODUCT_NAME'],
        "rating"      => $row['RATING'],
        "stock"       => $row['STOCK'],
        "productID"   => $row['PRODUCT_ID'],
        "image"       => $row['IMAGES_URL'],
        "price"       => number_format($row['PRODUCT_PRICE'], 2, '.', ''),
        "description" => $row['PRODUCT_DESCRIPTION'],
        "category"    => $row['PRODUCT_CATEGORY'],
        "views"       => $row['VIEWS'],
        "date"        => $row['LIST_DATE'],
        "subcategory" => $row['PRODUCT_SUBCATEGORY'],
        "vendorID"    => $row['VENDOR_ID']
    ];
}

echo json_encode($products);

oci_free_statement($stid);
oci_close($conn);
?>
