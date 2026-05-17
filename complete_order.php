<?php
header("Content-Type: application/json");
require_once 'config/db_connect.php';

$json = file_get_contents("php://input");
$data = json_decode($json);

if (!$data || !isset($data->customer_id)) {
    http_response_code(400);
    echo json_encode("Invalid request");
    exit;
}

$customerId = (int)$data->customer_id;

$sqlCart = "SELECT PRODUCT_ID, QUANTITY FROM CART WHERE CUSTOMER_ID = :customer_id";
$stidCart = oci_parse($conn, $sqlCart);
oci_bind_by_name($stidCart, ":customer_id", $customerId);
oci_execute($stidCart);

$cartItems = [];
while ($row = oci_fetch_assoc($stidCart)) {
    $cartItems[] = $row;
}

if (empty($cartItems)) {
    echo json_encode("Cart is empty");
    exit;
}


$sqlOrder = "INSERT INTO ORDERS (CUSTOMER_ID) VALUES (:customer_id) RETURNING ORDER_NO INTO :order_no";
$stidOrder = oci_parse($conn, $sqlOrder);
oci_bind_by_name($stidOrder, ":customer_id", $customerId);

$orderNo = "";
oci_bind_by_name($stidOrder, ":order_no", $orderNo);

if (!oci_execute($stidOrder, OCI_NO_AUTO_COMMIT)) {
    $e = oci_error($stidOrder);
    echo json_encode("Failed to create order: " . $e['message']);
    oci_rollback($conn);
    exit;
}

foreach ($cartItems as $item) {
    $sqlOP = "INSERT INTO ORDERPRODUCTS (ORDER_NO, PRODUCT_ID, QUANTITY) VALUES (:order_no, :product_id, :quantity)";
    $stidOP = oci_parse($conn, $sqlOP);
    oci_bind_by_name($stidOP, ":order_no", $orderNo);
    oci_bind_by_name($stidOP, ":product_id", $item['PRODUCT_ID']);
    oci_bind_by_name($stidOP, ":quantity", $item['QUANTITY']);
    
    if (!oci_execute($stidOP, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stidOP);
        echo json_encode("Failed to add products to order: " . $e['message']);
        oci_rollback($conn);
        exit;
    }

    $sqlStock = "UPDATE PRODUCT SET STOCK = STOCK - :qty WHERE PRODUCT_ID = :pid";
    $stidStock = oci_parse($conn, $sqlStock);
    oci_bind_by_name($stidStock, ":qty", $item['QUANTITY']);
    oci_bind_by_name($stidStock, ":pid", $item['PRODUCT_ID']);
    oci_execute($stidStock, OCI_NO_AUTO_COMMIT);
}

$sqlClear = "DELETE FROM CART WHERE CUSTOMER_ID = :customer_id";
$stidClear = oci_parse($conn, $sqlClear);
oci_bind_by_name($stidClear, ":customer_id", $customerId);

if (!oci_execute($stidClear, OCI_NO_AUTO_COMMIT)) {
    $e = oci_error($stidClear);
    echo json_encode("Failed to clear cart: " . $e['message']);
    oci_rollback($conn);
    exit;
}

oci_commit($conn);
echo json_encode("Order completed successfully: $orderNo");

oci_free_statement($stidCart);
oci_free_statement($stidOrder);
oci_free_statement($stidOP);
oci_free_statement($stidClear);
oci_close($conn);
?>
