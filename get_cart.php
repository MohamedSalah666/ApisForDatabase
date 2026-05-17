<?php
header("Content-Type: application/json");
require_once 'config/db_connect.php';

$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

if ($customer_id <= 0) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid customer_id", 
        "received_raw" => $_GET['customer_id'] ?? 'null',
        "parsed_id" => $customer_id
    ]);
    exit;
}

$sql = "SELECT P.PRODUCT_ID, P.PRODUCT_NAME, P.STOCK,
        P.PRODUCT_PRICE, P.IMAGES_URL,
        C.QUANTITY AS CART_QUANTITY
        FROM PRODUCT P
        JOIN CART C ON P.PRODUCT_ID = C.PRODUCT_ID
        WHERE C.CUSTOMER_ID = :customer_id
        ORDER BY C.PRODUCT_ID DESC";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":customer_id", $customer_id);

if (!oci_execute($stid)) {
    $e = oci_error($stid);
    echo json_encode(["error" => "Database error", "message" => $e['message']]);
    exit;
}

$items = [];
while ($row = oci_fetch_assoc($stid)) {
    $items[] = [
        "product_id"    => (string)$row['PRODUCT_ID'],
        "name"          => $row['PRODUCT_NAME'],
        "price"         => (float)$row['PRODUCT_PRICE'],
        "stock"         => (int)$row['STOCK'],
        "image_url"     => $row['IMAGES_URL'] ?? "",
        "cart_quantity" => (int)$row['CART_QUANTITY']
    ];
}

echo json_encode($items);

oci_free_statement($stid);
oci_close($conn);
?>
