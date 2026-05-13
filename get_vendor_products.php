<?php

header("Content-Type: application/json");

require_once 'config/db_connect.php';

$vendor_id = $_GET['vendor_id'];

$sql = "
SELECT
    p.PRODUCT_ID,
    p.PRODUCT_NAME,
    p.STOCK,
    p.PRODUCT_PRICE,
    p.LIST_DATE,
    p.RATING,
    p.PRODUCT_DESCRIPTION,
    p.VIEWS,
    p.STOCK,
    p.PRODUCT_SUBCATEGORY,
    p.PRODUCT_CATEGORY,
    p.IMAGES_URL
FROM PRODUCT p
WHERE p.VENDOR_ID = :vendor_id
ORDER BY p.PRODUCT_ID DESC
";

$stid = oci_parse($conn, $sql);

oci_bind_by_name($stid, ":vendor_id", $vendor_id);

if (!oci_execute($stid)) {
    echo json_encode(["error" => "Failed to execute query"]);
    exit;
}

$vendorProducts = [];
$productStock = [];
$prices = [];
$dates = [];
$ratings = [];
$descriptions = [];
$views = [];
$stock = [];
$subcategories = [];
$categories = [];
$productIDs = [];
$images = [];

while ($row = oci_fetch_assoc($stid)) {

    $vendorProducts[] = $row['PRODUCT_NAME'];

    $productStock[] = $row['STOCK'];

    $prices[] = number_format($row['PRODUCT_PRICE'], 2, '.', '');

    $dates[] = $row['LIST_DATE'];

    $ratings[] = $row['RATING'];

    $descriptions[] = $row['PRODUCT_DESCRIPTION'];

    $views[] = $row['VIEWS'];

    $stock[] = $row['STOCK'];

    $subcategories[] = $row['PRODUCT_SUBCATEGORY'];

    $categories[] = $row['PRODUCT_CATEGORY'];

    $productIDs[] = $row['PRODUCT_ID'];

    $images[] = $row['IMAGES_URL'];
}

echo json_encode([
    "vendorProducts" => $vendorProducts,
    "vendorId" => intval($vendor_id),
    "productStock" => $productStock,
    "prices" => $prices,
    "dates" => $dates,
    "ratings" => $ratings,
    "descriptions" => $descriptions,
    "views" => $views,
    "stock" => $stock,
    "subcategories" => $subcategories,
    "categories" => $categories,
    "productID" => $productIDs,
    "images" => $images
]);

oci_free_statement($stid);
oci_close($conn);

?>