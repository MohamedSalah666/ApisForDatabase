<?php

header("Content-Type: application/json");

require_once 'config/db_connect.php';

$order_no  = $_GET['order_no'];
$vendor_id = $_GET['vendor_id'];

$sql = "SELECT o.ORDER_NO,o.ORDER_DATE,c.CUSTOMER_NAME,
    c.CUSTOMER_STATE,c.CUSTOMER_CITY,v.VENDOR_NAME,
    p.PRODUCT_ID,p.PRODUCT_NAME,oi.QUANTITY,
    (p.PRODUCT_PRICE * oi.QUANTITY) AS PRODUCT_TOTAL
    FROM ORDERS o
    JOIN CUSTOMER c ON o.CUSTOMER_ID = c.CUSTOMER_ID
    JOIN ORDERPRODUCTS oi ON o.ORDER_NO = oi.ORDER_NO
    JOIN PRODUCT p ON oi.PRODUCT_ID = p.PRODUCT_ID
    JOIN VENDOR v ON p.VENDOR_ID = v.VENDOR_ID
    WHERE o.ORDER_NO = :order_no 
    AND p.VENDOR_ID = :vendor_id
    ORDER BY o.ORDER_NO DESC ";

$stid = oci_parse($conn, $sql);

oci_bind_by_name($stid, ":order_no",  $order_no);
oci_bind_by_name($stid, ":vendor_id", $vendor_id);

if (!oci_execute($stid)) {
    $e = oci_error($stid);
    echo json_encode(["error" => $e['message']]);
    exit;
}

$orderItems  = [];
$orderPrices = [];
$orderCounts = [];
$productIds  = [];

$customerName  = "";
$customerState = "";
$customerCity  = "";
$vendorName    = "";
$orderDate     = "";
$total         = 0;

while ($row = oci_fetch_assoc($stid)) {
    $customerName  = $row['CUSTOMER_NAME'];
    $customerState = $row['CUSTOMER_STATE'];
    $customerCity  = $row['CUSTOMER_CITY'];
    $vendorName    = $row['VENDOR_NAME'];
    $orderDate     = $row['ORDER_DATE'];

    $orderItems[]  = $row['PRODUCT_NAME'];
    $orderPrices[] = number_format($row['PRODUCT_TOTAL'], 2, '.', '');
    $orderCounts[] = (string)$row['QUANTITY'];
    $productIds[]  = (string)$row['PRODUCT_ID'];

    $total += $row['PRODUCT_TOTAL'];
}

echo json_encode([
    "customer_name"  => $customerName,
    "customer_state" => $customerState,
    "customer_city"  => $customerCity,
    "vendor_name"    => $vendorName,
    "orderItems"     => $orderItems,
    "orderDate"      => $orderDate,
    "total"          => number_format($total, 2, '.', ''),
    "orderPrices"    => $orderPrices,
    "orderCounts"    => $orderCounts,
    "productId"      => $productIds
]);

oci_free_statement($stid);
oci_close($conn);
?>