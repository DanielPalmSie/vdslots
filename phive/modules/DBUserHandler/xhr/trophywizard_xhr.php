<?php
require_once __DIR__ . '/../../../admin.php';

//  $bkgpic = (empty($_FILES['file']['name']['img_bg'])) ? $_POST['bkg_pic'] : $_FILES['file']['name']['img_bg'];

$p      = $_POST;
$gcols  = phive('SQL')->getColumns('trophies', true);
$ins    = array_intersect_key($p, $gcols);

$ins['award_id'] = $p['trophy_award_id'];
$ins['award_id_alt'] = $p['trophy_award2'];

if(empty($ins['valid_to']))
  $ins['valid_to'] = '2100-01-01';

if(!empty($_POST['id']))
  $ins['id'] = $_POST['id'];

array_walk($ins, 'trim');

if(!empty($_POST['completed_ids'])){
  $cnt = count(phive()->explode($_POST['completed_ids']));
  $ins['threshold'] = $cnt; 
}

$q = phive('SQL')->save("trophies", $ins);

if (!empty($_FILES)) {
  $_FILES['file']['name']['img_color'] = $ins['alias'] . '_event' . substr($_FILES['file']['name']['img_color'], -4);
  $_FILES['file']['name']['img_grey'] = $ins['alias'] . '_event' . substr($_FILES['file']['name']['img_grey'], -4);
  phive('Filer')->multipleUpload(true);
}

echo ($_POST['id'] > 0 && !empty($_POST['id'])) ? "2147483648" : phive('SQL')->insertId();
die();
//alias subtype type threshold time_period time_span game in_row category sub_category hidden award_id award_id_alt trademark repeatable device_type
