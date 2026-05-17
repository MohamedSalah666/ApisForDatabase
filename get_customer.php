<?php
header("Content-Type: application/json");
require_once 'config/db_connect.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$customer_id = $_GET['customer_id'];

if (empty($customer_id)) {
    echo json_encode(["error" => "Customer ID is required"]);
    exit;
}

$sql = "SELECT c.CUSTOMER_ID, c.CUSTOMER_NAME, c.CUSTOMER_STATE,
               c.CUSTOMER_CITY, c.CUSTOMER_STREET, c.JOIN_DATE, cp.CUSTOMER_PHONE
        FROM Customer c
        LEFT JOIN CustomerPhone cp ON c.CUSTOMER_ID = cp.CUSTOMER_ID
        WHERE c.CUSTOMER_ID = :customer_id";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':customer_id', $customer_id);
oci_execute($stmt);

$orderSql = "SELECT COUNT(ORDER_NO) as ORDER_COUNT
             FROM Orders 
             WHERE CUSTOMER_ID = :customer_id";

$orderStmt = oci_parse($conn, $orderSql);
oci_bind_by_name($orderStmt, ':customer_id', $customer_id);
oci_execute($orderStmt);
$orderRow = oci_fetch_assoc($orderStmt);
$orderCount = $orderRow['ORDER_COUNT'] !== null ? (int)$orderRow['ORDER_COUNT'] : 0;
oci_free_statement($orderStmt);

$itemsSql = "SELECT SUM(op.QUANTITY) as ITEM_COUNT
             FROM orderproducts op
             JOIN Orders o ON op.ORDER_NO = o.ORDER_NO
             WHERE o.CUSTOMER_ID = :customer_id";

$itemsStmt = oci_parse($conn, $itemsSql);
oci_bind_by_name($itemsStmt, ':customer_id', $customer_id);
oci_execute($itemsStmt);
$itemsRow = oci_fetch_assoc($itemsStmt);
$itemCount = $itemsRow['ITEM_COUNT'] !== null ? (int)$itemsRow['ITEM_COUNT'] : 0;
oci_free_statement($itemsStmt);

$spentSql = "SELECT SUM(p.PRODUCT_PRICE * op.QUANTITY) as TOTAL_SPENT
             FROM ECommerceproj.orderproducts op
             JOIN ECommerceproj.Orders o ON op.ORDER_NO = o.ORDER_NO
             JOIN ECommerceproj.product p ON op.PRODUCT_ID = p.PRODUCT_ID
             WHERE o.CUSTOMER_ID = :customer_id";

$spentStmt = oci_parse($conn, $spentSql);
oci_bind_by_name($spentStmt, ':customer_id', $customer_id);
oci_execute($spentStmt);
$spentRow = oci_fetch_assoc($spentStmt);
$totalSpent = $spentRow['TOTAL_SPENT'] !== null ? number_format((float)$spentRow['TOTAL_SPENT'], 2) : "0.00";
oci_free_statement($spentStmt);

$customerData = null;
$phones = [];

while ($row = oci_fetch_assoc($stmt)) {
    if ($customerData === null) {
        $customerData = [
            "customer_id"     => (int)$row['CUSTOMER_ID'],
            "customer_name"   => $row['CUSTOMER_NAME'],
            "customer_state"  => $row['CUSTOMER_STATE'],
            "customer_city"   => $row['CUSTOMER_CITY'],
            "customer_street" => $row['CUSTOMER_STREET'],
            "join_date"       => $row['JOIN_DATE']
        ];
    }
    if ($row['CUSTOMER_PHONE'] !== null) {
        $phones[] = $row['CUSTOMER_PHONE'];
    }
}

if ($customerData !== null) {
    $customerData['customer_phones'] = $phones;
    $customerData['order_count']     = $orderCount;
    $customerData['item_count']      = $itemCount;
    $customerData['total_spent']     = $totalSpent;
    echo json_encode($customerData);
} else {
    echo json_encode(["error" => "Customer not found"]);
}

oci_free_statement($stmt);
oci_close($conn);
?>