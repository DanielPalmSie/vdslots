<?php
require_once __DIR__ . '/../../../admin.php';

$bh = phive('BoxHandler');

//rensa bort små race boxar
foreach($bh->getAttrsByName('local_parent') as $little_race){
	if(!$bh->getBoxById($little_race['attribute_value'])){
		echo "Purging orphaned little race-box: ".$little_race['box_id']."<br>";
		$bh->purgeBox($little_race['box_id']);
	}
}

//rensa bort boxar som inte ligger på någon sida

foreach(phive('SQL')->loadArray("SELECT * FROM boxes WHERE page_id NOT IN (SELECT page_id FROM pages)") as $stale){
	echo "Purging orphaned box: ".$stale['box_id']."<br>";
	$bh->purgeBox($stale['box_id']);
}

$active_children = array();
foreach($bh->getBoxesByClass('ContainerBox') as $cont){
	$empty = true;
	foreach(explode(':', $bh->getAttrVal($cont['box_id'], 'box_ids')) as $id){
		if($id != 0){
			$active_children[] = $id;
			if($bh->getBoxById($id) != false)
				$empty = false;
		}
	}
	if($empty){
		echo "Purging empty container-box: ".$cont['box_id']."<br>";
		$bh->purgeBox($cont['box_id']);
	}
		
}

foreach($bh->getAttrsByVal('sub_box', 1) as $sub){
	if(!in_array($sub['box_id'], $active_children)){
		echo "Purging orphaned sub-box: ".$sub['box_id']."<br>";
		$bh->purgeBox($sub['box_id']);
	}
}

echo "Purging orphaned box attributes in general.<br>";

phive('SQL')->query("DELETE FROM boxes_attributes WHERE box_id NOT IN (SELECT box_id FROM boxes)");