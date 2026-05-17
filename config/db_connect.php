<?php
$conn = oci_connect('ECommerceproj', 'Projectfager', 'localhost/XEPDB1');
if (!$conn) {
    $e = oci_error();
    echo json_encode(["error" => $e['message']]);
    exit;
}
?>