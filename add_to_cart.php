<?php
header("Content-Type: application/json");
require_once 'config/db_connect.php';

$json = file_get_contents("php://input");
$data = json_decode($json);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON payload", "received" => $json]);
    exit;
}

if (!isset($data->customer_id) || !isset($data->product_id) || !isset($data->quantity)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Missing parameters",
        "customer_id_set" => isset($data->customer_id),
        "product_id_set" => isset($data->product_id),
        "quantity_set" => isset($data->quantity)
    ]);
    exit;
}

$customerId = (int)$data->customer_id;
$productId = (int)$data->product_id;
$quantity = (int)$data->quantity;

if ($customerId <= 0 || $productId <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid ID values", "customer_id" => $customerId, "product_id" => $productId]);
    exit;
}

if ($quantity <= 0) {
    $sqlDelete = "DELETE FROM CART WHERE CUSTOMER_ID = :customer_id AND PRODUCT_ID = :product_id";
    $stidDelete = oci_parse($conn, $sqlDelete);
    oci_bind_by_name($stidDelete, ":customer_id", $customerId);
    oci_bind_by_name($stidDelete, ":product_id", $productId);
    if (oci_execute($stidDelete)) {
        echo json_encode(["success" => true, "message" => "Removed from cart"]);
    } else {
        $e = oci_error($stidDelete);
        echo json_encode(["success" => false, "error" => $e['message']]);
    }
    oci_free_statement($stidDelete);
    oci_close($conn);
    exit;
}

// Check stock
$sqlStock = "SELECT STOCK FROM PRODUCT WHERE PRODUCT_ID = :product_id";
$stidStock = oci_parse($conn, $sqlStock);
oci_bind_by_name($stidStock, ":product_id", $productId);
oci_execute($stidStock);
$row = oci_fetch_assoc($stidStock);

if (!$row) {
    http_response_code(404);
    echo json_encode("Product not found");
    oci_free_statement($stidStock);
    oci_close($conn);
    exit;
}

$stock = (int)$row['STOCK'];
if ($quantity > $stock) {
    echo json_encode("Quantity exceeds stock (available: $stock)");
    oci_free_statement($stidStock);
    oci_close($conn);
    exit;
}

// Check if item is already in cart
$sqlCart = "SELECT QUANTITY FROM CART WHERE CUSTOMER_ID = :customer_id AND PRODUCT_ID = :product_id";
$stidCart = oci_parse($conn, $sqlCart);
oci_bind_by_name($stidCart, ":customer_id", $customerId);
oci_bind_by_name($stidCart, ":product_id", $productId);
oci_execute($stidCart);
$cartRow = oci_fetch_assoc($stidCart);

if ($cartRow) {
    $sqlMerge = "UPDATE CART SET QUANTITY = :qty WHERE CUSTOMER_ID = :customer_id AND PRODUCT_ID = :product_id";
} else {
    $sqlMerge = "INSERT INTO CART (CUSTOMER_ID, PRODUCT_ID, QUANTITY) VALUES (:customer_id, :product_id, :qty)";
}

$stidMerge = oci_parse($conn, $sqlMerge);
oci_bind_by_name($stidMerge, ":qty", $quantity);
oci_bind_by_name($stidMerge, ":customer_id", $customerId);
oci_bind_by_name($stidMerge, ":product_id", $productId);

if (oci_execute($stidMerge)) {
    echo json_encode("Cart updated");
} else {
    $e = oci_error($stidMerge);
    echo json_encode("Error: " . $e['message']);
}

oci_free_statement($stidStock);
oci_free_statement($stidCart);
oci_free_statement($stidMerge);
oci_close($conn);
?>
