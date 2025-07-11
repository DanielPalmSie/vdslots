<?
require_once __DIR__ . '/../../../admin.php';
?>

<?
$former = phive('Former');
$localizer = phive('Localizer');
$former->reset();

if ($localizer->languageExists($_GET['arg0']))
	$lang = $_GET['arg0'];
else
	$lang = null;


$renderer_rows = new FormRendererRows();

$form0 = new Form('addlanguage', $renderer_rows);
$form0->addEntries(
	new EntryText('language'),
	new EntrySubmit('add', array('default'=>'Add language', 'action'=>'?arg1')));

$former->addForms($form0);

// $arg = lang, $act = action
$arg = $_GET['arg0'];
$act = $_GET['arg1'];

if ($arg)
	$lang = $arg;
else
	$lang = null;
	
	
$former->handleResponse();

	
if ($lang && $act==='delete')
{
	$ret = $localizer->deleteLanguage($lang);
	if (!$ret)
		echo "<p>Language could not be removed</p>";
	$lang = null;
}

// Pseudo

if ($former->submitted() === 'add')
{
	$new_lang = $former->getValue('language');
	$ret = $localizer->addLanguage($new_lang);
	if (!$ret)
		echo "<p>Language exists</p>";
}

$former->output('addlanguage');

// List all
$languages = $localizer->getAllLanguages();

?>
<hr />
<table border="0">
	<thead>
		<td>Language</td>
	</thead>
<?php foreach ($languages as $language): ?>
	<tr>
		<td>
			<?=$language['language']?> <a href="?arg0=<?=$language['language']?>&amp;arg1=delete">X</a></a>
		</td>
	</tr>
<?php endforeach; ?>
</table>