<?php
header("Content-Type: application/json");

$raw  = file_get_contents("php://input");
$data = json_decode($raw);

if (!$data || !isset($data->customer_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid or missing input"]);
    exit;
}

$customerId     = (int)$data->customer_id;
$phones         = $data->phones           ?? [];
$phonesToDelete = $data->phones_to_delete ?? [];

require_once 'config/db_connect.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

foreach ($phonesToDelete as $phone) {
    $phone   = (string)$phone;
    $delSql  = "DELETE FROM customerphone WHERE customer_id = :customer_id AND customer_phone = :phone";
    $delStmt = oci_parse($conn, $delSql);
    oci_bind_by_name($delStmt, ":customer_id", $customerId);
    oci_bind_by_name($delStmt, ":phone",       $phone);

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
    $checkSql  = "SELECT COUNT(*) AS CNT FROM customerphone WHERE customer_id = :customer_id AND customer_phone = :phone";
    $checkStmt = oci_parse($conn, $checkSql);
    oci_bind_by_name($checkStmt, ":customer_id", $customerId);
    oci_bind_by_name($checkStmt, ":phone",       $phone);
    oci_execute($checkStmt);
    $checkRow = oci_fetch_assoc($checkStmt);
    oci_free_statement($checkStmt);

    if ((int)$checkRow['CNT'] === 0) {
        $insSql  = "INSERT INTO customerphone (customer_id, customer_phone) VALUES (:customer_id, :phone)";
        $insStmt = oci_parse($conn, $insSql);
        oci_bind_by_name($insStmt, ":customer_id", $customerId);
        oci_bind_by_name($insStmt, ":phone",       $phone);

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

$fetchSql  = "SELECT customer_phone FROM customerphone WHERE customer_id = :customer_id";
$fetchStmt = oci_parse($conn, $fetchSql);
oci_bind_by_name($fetchStmt, ":customer_id", $customerId);
oci_execute($fetchStmt);

$phoneList = [];
while ($row = oci_fetch_assoc($fetchStmt)) {
    $phoneList[] = $row['CUSTOMER_PHONE'];
}
oci_free_statement($fetchStmt);
oci_close($conn);

echo json_encode([
    "customer_id"     => $customerId,
    "customer_phones" => $phoneList
]);
?>