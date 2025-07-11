<?
require_once __DIR__ . '/Form.php';

// This class renders the form. To create a new theme, this
//  class is overridden. The layout of overriding the Form
//  class could also be used, but then different styles would
//  be considered different modules and clutter the modules
//  setup.
class FormRendererRows extends FormRenderer
{
  public function output($form)
  {
    $submit = true;
    if (!($form instanceof Form))
    {
      trigger_error("FormRenderer::output() parameter not of type Form.", E_USER_ERROR);
      return;
    }
    
?>
<form name="<?=$form->getName()?>" method="post" style="margin: 0px">
<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
  <table border="0" cellspacing="2px" cellpadding="0" style="margin: 0px">
    <?
    // If errors have occured
    if ($form->getNumErrors())
    {
    ?>
      <tr>
        <?
	foreach ($form->getEntries() as $entry)
	{
	  if ($entry->isRendered())
	  {
        ?>
	  <td><font color="#800" size="1"><?=$entry->getError()?></font></td>
        <?
	  }
	}
        ?>
      </tr>
    <?
    }
    ?>
    <tr>
      <?
      $first = true;
      foreach ($form->getEntries() as $entry)
      {
	// Take the titles from the first entry
	if ($entry instanceof EntryButton)
	  $submit = false;
      ?>
        <?
	if ($entry->isRendered())
	{
	  // Style
	  if ($entry->getError())
	    $style = "border: 1px dashed #800; display: block; float: left";
	  else
	    $style = "margin: 1px";
        ?>
	  <td><div style="<?=$style?>"><div style="margin: 3px">
	    <?php $entry->output() ?>
	  </div></div></td>
	<?
	}
	else
	{
        ?>
	  <?php $entry->output() ?>
        <?
	}
	}
        ?>
  </table>
</form>
      <?		
      }
      }
      ?>
