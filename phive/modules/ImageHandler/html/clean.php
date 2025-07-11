<?php
require_once __DIR__ . '/../../../admin.php';
$ih = phive("ImageHandler");
$img_path = $ih->getSetting('UPLOAD_PATH').'/';
$sql = phive('SQL');
foreach($sql->loadArray("SELECT * FROM image_data") as $img){
	if(!is_file($img_path.$img['filename'])){
		echo "{$img['filename']} doesn't exist so deleting record.<br>";
		$sql->query("DELETE FROM image_data WHERE filename = '{$img['filename']}'");
	}else{
		echo "{$img['filename']} exists so keeping record.<br>";
	}
}

foreach($sql->loadArray("SELECT * FROM image_aliases") as $img){
	$imgd = $ih->getImageFromId($img['image_id']);
	if(empty($imgd)){
		echo "Image with id {$img['image_id']} doesn't exist so deleting alias {$img['alias']} <br>";
		$ih->deleteAlias($img['alias']);
	}else{
		echo "Image with id {$img['image_id']} exists so keeping alias {$img['alias']} <br>";
	}
}