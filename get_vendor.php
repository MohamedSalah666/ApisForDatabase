<?php
header("Content-Type: application/json");
require_once 'config/db_connect.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$vendor_id = $_GET['vendor_id'];

if (empty($vendor_id)) {
    echo json_encode(["error" => "Vendor ID is required"]);
    exit;
}

$sql = "SELECT v.VENDOR_ID, v.VENDOR_NAME, v.VENDOR_OWNER, v.VENDOR_STATE, 
               v.VENDOR_CITY, v.VENDOR_STREET, v.JOIN_DATE, vp.VENDOR_PHONE
        FROM ECommerceproj.Vendor v
        LEFT JOIN ECommerceproj.VendorPhone vp ON v.VENDOR_ID = vp.VENDOR_ID
        WHERE v.VENDOR_ID = :vendor_id";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':vendor_id', $vendor_id);
oci_execute($stmt);

$orderSql = "SELECT COUNT(DISTINCT op.ORDER_NO) as ORDER_COUNT
             FROM ECommerceproj.orderproducts op
             JOIN ECommerceproj.product p ON op.PRODUCT_ID = p.PRODUCT_ID
             WHERE p.VENDOR_ID = :vendor_id";

$orderStmt = oci_parse($conn, $orderSql);
oci_bind_by_name($orderStmt, ':vendor_id', $vendor_id);
oci_execute($orderStmt);
$orderRow = oci_fetch_assoc($orderStmt);
$orderCount = $orderRow['ORDER_COUNT'];

oci_free_statement($orderStmt);

$productSql = "SELECT COUNT(*) as PRODUCT_COUNT
               FROM ECommerceproj.product
               WHERE VENDOR_ID = :vendor_id";

$productStmt = oci_parse($conn, $productSql);
oci_bind_by_name($productStmt, ':vendor_id', $vendor_id);
oci_execute($productStmt);
$productRow = oci_fetch_assoc($productStmt);
$productCount = $productRow['PRODUCT_COUNT'];

oci_free_statement($productStmt);

$ratingSql = "SELECT AVG(p.RATING) as AVG_RATING
              FROM ECommerceproj.product p
              WHERE p.VENDOR_ID = :vendor_id
              AND p.RATING IS NOT NULL";

$ratingStmt = oci_parse($conn, $ratingSql);
oci_bind_by_name($ratingStmt, ':vendor_id', $vendor_id);
oci_execute($ratingStmt);
$ratingRow = oci_fetch_assoc($ratingStmt);

$ratingSql = "SELECT AVG(p.RATING) as AVG_RATING
              FROM ECommerceproj.product p
              WHERE p.VENDOR_ID = :vendor_id
              AND p.RATING IS NOT NULL";

$ratingStmt = oci_parse($conn, $ratingSql);
oci_bind_by_name($ratingStmt, ':vendor_id', $vendor_id);
oci_execute($ratingStmt);
$ratingRow = oci_fetch_assoc($ratingStmt);


$avgRating = $ratingRow['AVG_RATING'] !== null
    ? round((float)$ratingRow['AVG_RATING'], 2)
    : 0;
oci_free_statement($ratingStmt);

$vendorData = null;
$phones = [];

while ($row = oci_fetch_assoc($stmt)) {
    if ($vendorData === null) {
        $vendorData = [
            "vendor_id"     => $row['VENDOR_ID'],
            "vendor_name"   => $row['VENDOR_NAME'],
            "vendor_owner"  => $row['VENDOR_OWNER'],
            "vendor_state"  => $row['VENDOR_STATE'],
            "vendor_city"   => $row['VENDOR_CITY'],
            "vendor_street" => $row['VENDOR_STREET'],
            "join_date"     => $row['JOIN_DATE']
        ];
    }
    if ($row['VENDOR_PHONE'] !== null) {
        $phones[] = $row['VENDOR_PHONE'];
    }
}


if ($vendorData !== null) {
    $vendorData['vendor_phones'] = $phones;
    $vendorData['order_count']   = $orderCount;
    $vendorData['product_count'] = $productCount;
    $vendorData['avg_rating'] = number_format((float)$avgRating, 1);
    echo json_encode($vendorData);
} else {
    echo json_encode(["error" => "Vendor not found"]);
}

oci_free_statement($stmt);
oci_close($conn);
?>