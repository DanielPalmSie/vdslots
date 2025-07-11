<?
exit;
//delete this file, remove from hg

require_once __DIR__ . '/../../../admin.php';
?>

<?

function setup_form($alias)
{
  $former = phive('Former');
  $localizer = phive('Localizer');

  $renderer_rows = new FormRendererRows();

  $form0 = new Form('addremove', $renderer_rows);
  $form0->addEntries(
    new EntrySubmit('new_country', array(
      "default"=>'New country',
      "action"=>'?arg0=new')));

  if ($alias)
  {
    $form0->addEntries(
      new EntryHidden('country', $alias),
      new EntrySubmit('delete_country', array(
	"default"=>'Delete this country',
	"action"=>'?arg0=' . $alias . '&arg1=delete')));		
  }
  
  $former->addForms($form0);
  
  if ($alias!==null)
  {
    $form = new Form('editcountry');
    $data = null;
    $s = $localizer->getCountriesTableStructure($table_id);
    if ($alias)
      $data = $localizer->getCountryData($alias);

    $form->addEntries( new EntryHidden('country', $alias));

    foreach ($s as $col)
    {
      $form->addEntries(
	new EntryText($col['Field'], 
		      array(
	    'name'=>$col['Field'], 
	    'default'=>$data[$col['Field']], 
	    'comment'=>$col['Comment'])));
    }

    if ($alias)
    {
      $action = 'update_country';
      $str = 'Update country';
    }
    else
    {
      $action = 'add_country';
      $str = 'Add country';
    }

    $form->addEntries(new EntrySubmit($action, $str));

    $former->addForms($form);
  }
}

// $arg = id, $act = action
$former = phive('Former');
$localizer = phive('Localizer');
$arg = $_GET['arg0'];
$act = $_GET['arg1'];

if ($arg === null || $arg === '0')
  $alias = null;
else
  if ($arg === 'new')
    $alias = 0;
else
  if ($arg)
    $alias = $arg;
else
  $alias = null;



if ($alias && $act==='delete')
{
  $msg = $localizer->deleteCountry($alias);
  if ($msg->getType() & PHM_SIMPLE_FATAL)
  {
    echo $msg->getMessage();
  }
  else $alias = 0;
}

// Pseudo
setup_form($alias);
$ret = $former->handleResponse();

if ($ret && $former->submitted() === 'update_country')
{
  $country = $former->getArray();
  $ret = $localizer->updateCountry($country);
  if (!$ret)
    echo "Could not update country";
  setup_form($alias);	
}
else
  if ($ret &&	$former->submitted() === 'add_country')
{
  $country = $former->getArray();
  $ret = $localizer->updateCountry($country);
  if ($ret)
    echo "<meta http-equiv='refresh' content='0;url=?arg0={$country['country']}'>";
  else
    echo "<p>Could not add country</p>";
}

$former->output();

// List all pages (as a hierarchy)
$countries = $localizer->getAllCountries();

?>
<hr />
<table>
  <?php $first = true; 
  foreach ($countries as $country):
	   if ($first):
	       $first=false; ?>
  <tr>
    <?php 		foreach ($country as $key=>$element): ?>
    <th align="left"><?=$key?></th>
    <?php 		endforeach; ?>
    <?	endif; ?>
  </tr>
  <tr>

    <?		foreach ($country as $key=>$element): ?>
    <td>
      <?php if ($key=='country'): ?>
	<a href="?arg0=<?=$element?>"><?=$element?></a>
      <?php elseif ($key=='language'): ?>
	<?
					if ($localizer->languageExists($element))
					  $color = "green";
	else
	  $color = "red";
	
	echo '<font color="' . $color . '">' . $element . '</font>';
	?>
      <?php else: ?>
	<?=$element?>
      <?php endif; ?>
    </td>
		<?php endforeach; ?>
  </tr>
                <?php endforeach; ?>
</table>
