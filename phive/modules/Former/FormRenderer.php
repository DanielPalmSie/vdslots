<?
require_once __DIR__ . '/Form.php';

// This class renders the form. To create a new theme, this
//  class is overridden. The layout of overriding the Form
//  class could also be used, but then different styles would
//  be considered different modules and clutter the modules
//  setup.
class FormRenderer
{
	public function formHeader($form)
	{
?>
<form name="<?=$form->getName()?>" method="post">
<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
<?
	}
	
	public function output($form)
	{
		$submit = true;
		if (!($form instanceof Form))
		{
			trigger_error("FormRenderer::output() parameter not of type Form.", E_USER_ERROR);
			return;
		}
		
		$this->formHeader($form);
?>
	<table border="0" cellspacing="4px" cellpadding="0">
		<tr>
<?
		foreach ($form->getEntries() as $entry)
		{
			if ($entry instanceof EntryButton)
				$submit = false;

			if ($entry->getError())
			{
?>
			<td></td>
			<td><font color="#800" size="1"><?=$entry->getError()?></font></td>
		</tr>
		<tr>
<?
			}
?>
			<td valign="top"><label style="margin-right: 50px"><?=$entry->getSetting("name")?></label></td>
<?
			
			// Style
			if ($entry->getError())
				$style = "border: 1px dashed #800; display: block; float: left";
			else
				$style = "margin: 1px";
?>
				<td valign="top"><div style="<?=$style?>"><div style="margin: 3px">
<?
			$entry->output();
?>
				</div></div></td>
			</tr>
<?
		}
?>
	</table>
</form>
<?		
	}
}
?>