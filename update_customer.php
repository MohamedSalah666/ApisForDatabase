<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

$raw  = file_get_contents("php://input");
$data = json_decode($raw);

if (!$data || !isset($data->customer_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid or missing input", "raw" => $raw]);
    exit;
}

$customerId     = (int)$data->customer_id;
$customerName   = $data->customer_name   ?? null;
$customerState  = $data->customer_state  ?? null;
$customerCity   = $data->customer_city   ?? null;
$customerStreet = $data->customer_street ?? null;

require_once 'config/db_connect.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$sql  = "UPDATE customer SET
            customer_name   = :customer_name,
            customer_state  = :customer_state,
            customer_city   = :customer_city,
            customer_street = :customer_street
         WHERE customer_id  = :customer_id";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":customer_name",   $customerName);
oci_bind_by_name($stmt, ":customer_state",  $customerState);
oci_bind_by_name($stmt, ":customer_city",   $customerCity);
oci_bind_by_name($stmt, ":customer_street", $customerStreet);
oci_bind_by_name($stmt, ":customer_id",     $customerId);

if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    oci_rollback($conn);
    http_response_code(500);
    echo json_encode(["error" => "Update failed: " . $e['message']]);
    oci_free_statement($stmt);
    oci_close($conn);
    exit;
}

oci_commit($conn);
oci_free_statement($stmt);

$fetchSql  = "SELECT * FROM customer WHERE customer_id = :customer_id";
$fetchStmt = oci_parse($conn, $fetchSql);
oci_bind_by_name($fetchStmt, ":customer_id", $customerId);
oci_execute($fetchStmt);
$row = oci_fetch_assoc($fetchStmt);
oci_free_statement($fetchStmt);
oci_close($conn);

echo json_encode([
    "customer_id"     => (int)$row['CUSTOMER_ID'],
    "customer_name"   => $row['CUSTOMER_NAME'],
    "customer_state"  => $row['CUSTOMER_STATE'],
    "customer_city"   => $row['CUSTOMER_CITY'],
    "customer_street" => $row['CUSTOMER_STREET']
]);
?>