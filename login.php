<?php
header("Content-Type: application/json");
require_once 'config/db_connect.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$username = $data->account_name;
$password = $data->account_password;

$sql = "SELECT * FROM accounts WHERE account_name = :username";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":username", $username);
oci_execute($stmt);

$account = oci_fetch_assoc($stmt);

if ($account && $account['ACCOUNT_PASSWORD'] === $password) {
    echo json_encode([
        "account_id"       => $account['ACCOUNT_ID'],
        "account_name"     => $account['ACCOUNT_NAME'],
        "account_type"     => (int)$account['ACCOUNT_TYPE'],
        "vendor_id"        => $account['VENDOR_ID'],
        "customer_id"      => $account['CUSTOMER_ID'],
        "account_password" => null
    ]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "Invalid credentials"]);
}

oci_free_statement($stmt);
oci_close($conn);
?>