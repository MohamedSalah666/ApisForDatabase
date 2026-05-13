<?php
header("Content-Type: application/json");
require_once 'config/db_connect.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$username    = $data->account_name;
$password    = $data->account_password;
$accountType = (int)$data->account_type;

$checkSql  = "SELECT COUNT(*) AS CNT FROM accounts WHERE LOWER(account_name) = LOWER(:username)";
$checkStmt = oci_parse($conn, $checkSql);
oci_bind_by_name($checkStmt, ":username", $username);
oci_execute($checkStmt);
$checkRow = oci_fetch_assoc($checkStmt);
oci_free_statement($checkStmt);

if ((int)$checkRow['CNT'] > 0) {
    http_response_code(409);
    echo json_encode(["error" => "Username already taken"]);
    oci_close($conn);
    exit;
}

if ($accountType === 0) {
    $customerName = $data->customer_name;

    $customerSql  = "INSERT INTO customer (customer_name) VALUES (:customer_name) RETURNING customer_id INTO :customer_id";
    $customerStmt = oci_parse($conn, $customerSql);
    oci_bind_by_name($customerStmt, ":customer_name", $customerName);
    oci_bind_by_name($customerStmt, ":customer_id",   $customerId, 8, SQLT_INT);

    if (!oci_execute($customerStmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($customerStmt);
        oci_rollback($conn);
        http_response_code(500);
        echo json_encode(["error" => "Customer insert failed: " . $e['message']]);
        oci_free_statement($customerStmt);
        oci_close($conn);
        exit;
    }
    oci_free_statement($customerStmt);

    $accountSql  = "INSERT INTO accounts (account_name, account_password, account_type, customer_id)
                    VALUES (:account_name, :account_password, :account_type, :customer_id)
                    RETURNING account_id INTO :account_id";
    $accountStmt = oci_parse($conn, $accountSql);
    oci_bind_by_name($accountStmt, ":account_name",     $username);
    oci_bind_by_name($accountStmt, ":account_password", $password);
    oci_bind_by_name($accountStmt, ":account_type",     $accountType);
    oci_bind_by_name($accountStmt, ":customer_id",      $customerId);
    oci_bind_by_name($accountStmt, ":account_id",       $accountId, 8, SQLT_INT);

    if (!oci_execute($accountStmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($accountStmt);
        oci_rollback($conn); // rolls back customer insert too
        http_response_code(500);
        echo json_encode(["error" => "Account insert failed: " . $e['message']]);
        oci_free_statement($accountStmt);
        oci_close($conn);
        exit;
    }
    oci_free_statement($accountStmt);

    oci_commit($conn);

    echo json_encode([
        "account_id"   => (int)$accountId,
        "account_name" => $username,
        "account_type" => $accountType,
        "customer_id"  => (int)$customerId,
        "vendor_id"    => null
    ]);

} else {
    $vendorName  = $data->vendor_name;
    $vendorOwner = $data->vendor_owner;

    $vendorSql  = "INSERT INTO vendor (vendor_name, vendor_owner) VALUES (:vendor_name, :vendor_owner) RETURNING vendor_id INTO :vendor_id";
    $vendorStmt = oci_parse($conn, $vendorSql);
    oci_bind_by_name($vendorStmt, ":vendor_name",  $vendorName);
    oci_bind_by_name($vendorStmt, ":vendor_owner", $vendorOwner);
    oci_bind_by_name($vendorStmt, ":vendor_id",    $vendorId, 8, SQLT_INT);

    if (!oci_execute($vendorStmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($vendorStmt);
        oci_rollback($conn);
        http_response_code(500);
        echo json_encode(["error" => "Vendor insert failed: " . $e['message']]);
        oci_free_statement($vendorStmt);
        oci_close($conn);
        exit;
    }
    oci_free_statement($vendorStmt);

    $accountSql  = "INSERT INTO accounts (account_name, account_password, account_type, vendor_id)
                    VALUES (:account_name, :account_password, :account_type, :vendor_id)
                    RETURNING account_id INTO :account_id";
    $accountStmt = oci_parse($conn, $accountSql);
    oci_bind_by_name($accountStmt, ":account_name",     $username);
    oci_bind_by_name($accountStmt, ":account_password", $password);
    oci_bind_by_name($accountStmt, ":account_type",     $accountType);
    oci_bind_by_name($accountStmt, ":vendor_id",        $vendorId);
    oci_bind_by_name($accountStmt, ":account_id",       $accountId, 8, SQLT_INT);

    if (!oci_execute($accountStmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($accountStmt);
        oci_rollback($conn);
        http_response_code(500);
        echo json_encode(["error" => "Account insert failed: " . $e['message']]);
        oci_free_statement($accountStmt);
        oci_close($conn);
        exit;
    }
    oci_free_statement($accountStmt);

    oci_commit($conn);

    echo json_encode([
        "account_id"   => (int)$accountId,
        "account_name" => $username,
        "account_type" => $accountType,
        "vendor_id"    => (int)$vendorId,
        "customer_id"  => null
    ]);
}

oci_close($conn);
?>