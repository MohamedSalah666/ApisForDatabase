<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

$raw  = file_get_contents("php://input");
$data = json_decode($raw);

if (!$data || !isset($data->vendor_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid or missing input", "raw" => $raw]);
    exit;
}

$vendorId     = (int)$data->vendor_id;
$vendorName   = $data->vendor_name   ?? null;
$vendorOwner  = $data->vendor_owner  ?? null;
$vendorState  = $data->vendor_state  ?? null;
$vendorCity   = $data->vendor_city   ?? null;
$vendorStreet = $data->vendor_street ?? null;

$conn = oci_connect("ECommerceproj", "Projectfager", "localhost/XEPDB1");

if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$sql  = "UPDATE vendor SET
            vendor_name   = :vendor_name,
            vendor_owner  = :vendor_owner,
            vendor_state  = :vendor_state,
            vendor_city   = :vendor_city,
            vendor_street = :vendor_street
         WHERE vendor_id  = :vendor_id";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":vendor_name",   $vendorName);
oci_bind_by_name($stmt, ":vendor_owner",  $vendorOwner);
oci_bind_by_name($stmt, ":vendor_state",  $vendorState);
oci_bind_by_name($stmt, ":vendor_city",   $vendorCity);
oci_bind_by_name($stmt, ":vendor_street", $vendorStreet);
oci_bind_by_name($stmt, ":vendor_id",     $vendorId);

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

$fetchSql  = "SELECT * FROM vendor WHERE vendor_id = :vendor_id";
$fetchStmt = oci_parse($conn, $fetchSql);
oci_bind_by_name($fetchStmt, ":vendor_id", $vendorId);
oci_execute($fetchStmt);
$row = oci_fetch_assoc($fetchStmt);
oci_free_statement($fetchStmt);
oci_close($conn);

echo json_encode([
    "vendor_id"     => (int)$row['VENDOR_ID'],
    "vendor_name"   => $row['VENDOR_NAME'],
    "vendor_owner"  => $row['VENDOR_OWNER'],
    "vendor_state"  => $row['VENDOR_STATE'],
    "vendor_city"   => $row['VENDOR_CITY'],
    "vendor_street" => $row['VENDOR_STREET']
]);
?>