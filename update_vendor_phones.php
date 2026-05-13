<?php
header("Content-Type: application/json");

$raw  = file_get_contents("php://input");
$data = json_decode($raw);

if (!$data || !isset($data->vendor_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid or missing input"]);
    exit;
}

$vendorId       = (int)$data->vendor_id;
$phones         = $data->phones           ?? [];
$phonesToDelete = $data->phones_to_delete ?? [];

$conn = oci_connect("ECommerceproj", "Projectfager", "localhost/XEPDB1");

if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

foreach ($phonesToDelete as $phone) {
    $phone   = (string)$phone;
    $delSql  = "DELETE FROM vendorphone WHERE vendor_id = :vendor_id AND vendor_phone = :phone";
    $delStmt = oci_parse($conn, $delSql);
    oci_bind_by_name($delStmt, ":vendor_id", $vendorId);
    oci_bind_by_name($delStmt, ":phone",     $phone);

    if (!oci_execute($delStmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($delStmt);
        oci_rollback($conn);
        http_response_code(500);
        echo json_encode(["error" => "Delete phone failed: " . $e['message']]);
        oci_free_statement($delStmt);
        oci_close($conn);
        exit;
    }
    oci_free_statement($delStmt);
}

foreach ($phones as $phone) {
    $phone     = (string)$phone;
    $checkSql  = "SELECT COUNT(*) AS CNT FROM vendorphone WHERE vendor_id = :vendor_id AND vendor_phone = :phone";
    $checkStmt = oci_parse($conn, $checkSql);
    oci_bind_by_name($checkStmt, ":vendor_id", $vendorId);
    oci_bind_by_name($checkStmt, ":phone",     $phone);
    oci_execute($checkStmt);
    $checkRow = oci_fetch_assoc($checkStmt);
    oci_free_statement($checkStmt);

    if ((int)$checkRow['CNT'] === 0) {
        $insSql  = "INSERT INTO vendorphone (vendor_id, vendor_phone) VALUES (:vendor_id, :phone)";
        $insStmt = oci_parse($conn, $insSql);
        oci_bind_by_name($insStmt, ":vendor_id", $vendorId);
        oci_bind_by_name($insStmt, ":phone",     $phone);

        if (!oci_execute($insStmt, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($insStmt);
            oci_rollback($conn);
            http_response_code(500);
            echo json_encode(["error" => "Insert phone failed: " . $e['message']]);
            oci_free_statement($insStmt);
            oci_close($conn);
            exit;
        }
        oci_free_statement($insStmt);
    }
}

oci_commit($conn);

$fetchSql  = "SELECT vendor_phone FROM vendorphone WHERE vendor_id = :vendor_id";
$fetchStmt = oci_parse($conn, $fetchSql);
oci_bind_by_name($fetchStmt, ":vendor_id", $vendorId);
oci_execute($fetchStmt);

$phoneList = [];
while ($row = oci_fetch_assoc($fetchStmt)) {
    $phoneList[] = $row['VENDOR_PHONE'];
}
oci_free_statement($fetchStmt);
oci_close($conn);

echo json_encode([
    "vendor_id"     => $vendorId,
    "vendor_phones" => $phoneList
]);
?>