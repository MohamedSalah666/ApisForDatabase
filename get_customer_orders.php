<?php

header("Content-Type: application/json");

require_once 'config/db_connect.php';

$customer_id = $_GET['customer_id'];

$sql = "
SELECT
    o.ORDER_NO,
    SUM(op.QUANTITY) AS PRODUCT_COUNT,
    SUM(op.QUANTITY * p.PRODUCT_PRICE) AS TOTAL_PRICE,
    o.ORDER_DATE
FROM ORDERS o
JOIN ORDERPRODUCTS op
    ON o.ORDER_NO = op.ORDER_NO
JOIN PRODUCT p
    ON op.PRODUCT_ID = p.PRODUCT_ID
WHERE o.CUSTOMER_ID = :customer_id
GROUP BY o.ORDER_NO, o.ORDER_DATE
ORDER BY o.ORDER_NO DESC
";

$stid = oci_parse($conn, $sql);

oci_bind_by_name($stid, ":customer_id", $customer_id);

if (!oci_execute($stid)) {
    echo json_encode(["error" => "Failed to execute query"]);
    exit;
}

$vendorOrders = [];
$productCount = [];
$price = [];
$date = [];

while ($row = oci_fetch_assoc($stid)) {

    $vendorOrders[] = $row['ORDER_NO'];
    $productCount[] = $row['PRODUCT_COUNT'];
    $price[] = number_format($row['TOTAL_PRICE'], 2, '.', '');
    $date[] = $row['ORDER_DATE'];
}

echo json_encode([
    "vendorOrders" => $vendorOrders,
    "productCount" => $productCount,
    "price" => $price,
    "date" => $date
]);

oci_free_statement($stid);
oci_close($conn);

?>