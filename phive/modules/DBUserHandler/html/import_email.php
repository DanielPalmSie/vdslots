<?php
require_once __DIR__ . '/../../../admin.php';

if(!empty($_FILES['csv_file']['tmp_name'])){
	phive('DBUserHandler')->importMailList($_FILES['csv_file']['tmp_name'], $_POST['tag']);
}

?>
<html>
<head>
</head>
<body>
<a href="/admin">Back</a>
<br>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
CSV File (One e-mail address on each line):
<br>
<input type="file" name="csv_file"/>
<br>
Tag:
<input type="text" name="tag"/>
<br>
<input type="submit" value="Upload"/>
</form>
</body>
</html>