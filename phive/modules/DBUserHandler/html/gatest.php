<?php
require_once __DIR__ . '/../../../phive.php';

phive()->sessionStart();

if($_REQUEST['pwd'] != 'sliema')
  die('no access');

if ( empty( $_GET[ 'id' ] ) )
  $user_id = uid();
else
  $user_id = $_GET[ 'id' ];

$arr = 
$arr = array(
    mKey($user_id, $_REQUEST) => 'yes',
    mKey($user_id, "{$_REQUEST['action']}-amount") => $_REQUEST['amount'],
    mKey($user_id, "{$_REQUEST['action']}-id") => $_REQUEST['id']
);

foreach($arr as $key => $val)
  phMset($key, $val);
?>
<html>
  <head></head>
<title>GA Test</title>
<body>
  <pre>
    <?php print_r($arr) ?>
  </pre>  
</body>
</html>
