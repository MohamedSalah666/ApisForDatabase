<?php
header("Content-Type: application/json");
require_once 'config/db_connect.php';

if (!isset($_GET['product_id'])) {
    http_response_code(400);
    echo json_encode("Missing product_id");
    exit;
}

$productId = (int)$_GET['product_id'];

$sql = "UPDATE PRODUCT SET VIEWS = VIEWS + 1 WHERE PRODUCT_ID = :product_id";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":product_id", $productId);

if (oci_execute($stid)) {
    echo json_encode("Views incremented");
} else {
    $e = oci_error($stid);
    echo json_encode("Error: " . $e['message']);
}

oci_free_statement($stid);
oci_close($conn);
?>
