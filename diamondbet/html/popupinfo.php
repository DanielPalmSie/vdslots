<?
require_once __DIR__ . '/../../phive/phive.php';

$pager = phive()->getModule('Pager');

global $global_onlysetup;
$global_onlysetup = true;
include __DIR__ . '/../generic.php';
if(!isset($_GET['text']))
	return;
$text = $_GET['text'];
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<?php $langtag = phive()->getModule('Localizer')->getCountryValue('langtag') ?>
<html id="popup" xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$langtag?>" lang="<?=$langtag?>">
<head>
	<?php
	genericCSSOutput("topmenu.css");
	genericCSSOutput("popup.css");
	?>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<title>
		<?php echo t("popupinfo.".$text.".title"); ?>
	</title>
</head>
<body>
<?php
?>
<?	if (phive()->getModule('Permission')->hasPermission('translate.'.phive()->getModule('Localizer')->getLanguage()))
	{
		phive()->getModule('Localizer')->setTranslatorMode(true);
		echo t("popupinfo.".$text.".title");
		echo "<br/>";
		echo t("popupinfo.".$text.".content.html");
	}
	else{
		echo t("popupinfo.".$text.".content.html");
	}
?>

	
</body>
</html>