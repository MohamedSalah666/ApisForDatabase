<?php
header("Content-Type: application/json");
require_once 'config/db_connect.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    http_response_code(400);
    echo json_encode("Invalid JSON payload");
    exit;
}

$vendorId = (int)$data->vendor_id;
$name = $data->name;
$description = $data->description;
$category = $data->category;
$subcategory = $data->subcategory;
$price = (float)$data->price;
$stock = (int)$data->stock;
$imageBase64 = $data->image;

$safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);
$imageName = $safeName . "_" . time() . ".jpg";
$imagePath = "images/" . $imageName;

file_put_contents($imagePath, base64_decode($imageBase64));

$imageUrl = "http://192.168.100.9/ecommerce/api/images/" . $imageName;

$sql = "INSERT INTO PRODUCT (VENDOR_ID, PRODUCT_NAME, PRODUCT_DESCRIPTION, PRODUCT_CATEGORY, PRODUCT_SUBCATEGORY, PRODUCT_PRICE, STOCK, IMAGES_URL, RATING, VIEWS) 
        VALUES (:vendor_id, :name, :description, :category, :subcategory, :price, :stock, :images_url, 0, 0)";

$stid = oci_parse($conn, $sql);

oci_bind_by_name($stid, ":vendor_id", $vendorId);
oci_bind_by_name($stid, ":name", $name);
oci_bind_by_name($stid, ":description", $description);
oci_bind_by_name($stid, ":category", $category);
oci_bind_by_name($stid, ":subcategory", $subcategory);
oci_bind_by_name($stid, ":price", $price);
oci_bind_by_name($stid, ":stock", $stock);
oci_bind_by_name($stid, ":images_url", $imageUrl);

if (!oci_execute($stid, OCI_COMMIT_ON_SUCCESS)) {
    $e = oci_error($stid);
    http_response_code(500);
    echo json_encode("Product insert failed: " . $e['message']);
} else {
    echo json_encode("Product Published Successfully");
}

oci_free_statement($stid);
oci_close($conn);
?>