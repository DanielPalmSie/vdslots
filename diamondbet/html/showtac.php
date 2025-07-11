<?php
require_once __DIR__ . '/../../phive/phive.php';
$article = phive()->getModule("LimitedNewsHandler")->getArticle($_GET['id']);

?>
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8">
		<title><?php echo phive()->getModule('Localizer')->getString("newstop.tac", "en") ?></title>
	</head>
	<body>
		<?php echo $article->getTac() ?>
	</body>
</html>