<?php
exit;
$conn = mysqli_connect('localhost','default_md8m1','U&pkfrSC[Zz]vkJka~[56#^5','default_md8m1');
if(!$conn){
	echo "Unable to connect database".mysqli_error($conn);die;
}

$query = "DELETE FROM catalog_product_entity_varchar WHERE attribute_id IN (84,85,86)";
mysqli_query($conn, $query);

$query = "SET FOREIGN_KEY_CHECKS = 0";
mysqli_query($conn, $query);

$query = "TRUNCATE catalog_product_entity_media_gallery";
mysqli_query($conn, $query);

$query = "TRUNCATE catalog_product_entity_media_gallery_value";
mysqli_query($conn, $query);

$query = "TRUNCATE catalog_product_entity_media_gallery_value_to_entity";
mysqli_query($conn, $query);

$query = "SET FOREIGN_KEY_CHECKS = 1";
mysqli_query($conn, $query);

echo 'All product images are removed.';