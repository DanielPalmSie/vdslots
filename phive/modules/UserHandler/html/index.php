<?php
require_once __DIR__ . '/../../../admin.php';

$pages = array('listusers', 'editusers', 'editgroups', 'usergroups');
if (phive()->moduleExists('Permission'))
  $pages2 = array('userpermissions', 'grouppermissions');
else
  $pages2 = array();

echo "<ul>";
$all = array_merge($pages, $pages2);
foreach ($all as $link)
  echo '<li><a href="?p='.$link.'">'.$link.'</a>';

echo "</ul>";

if (in_array($_GET['p'], $pages2))
  include __DIR__ . '/../../Permission/html/' . $_GET['p'] . '.php';
else if (in_array($_GET['p'], $pages))	
  include __DIR__ . '/' . $_GET['p'] . '.php';
