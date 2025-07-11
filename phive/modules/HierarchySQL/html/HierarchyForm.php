<?php
require_once __DIR__ . '/../../../admin.php';

class HierarchyForm{
	
	function __construct(){
		$this->form_name 		= '';
		$this->add_action 		= '';
		$this->add_label 		= '';
		$this->update_action 	= '';
		$this->update_label 	= '';
		$this->id_key 			= '';
		$this->delete_action 	= '';	
		$this->module_name		= '';
		$this->form_name		= '';
	}
	
	/**
	 * 
	 * @param $post $_POST
	 * @param $id_key menu_id
	 * @param $module_name Menuer
	 * @return unknown_type
	 */
    function validate_parent_id($post){
	if ($post[$this->id_key] == 0 || // This means new menu and any parent should be fine.
			            phive($this->module_name)->isValidParent($post[$this->id_key], $post['parent_id']))
			                return new PhMessage(PHM_OK);
	else
	    return new PhMessage(PHM_ERROR, "Improper parent-child relation (possibly causing infinite loop)");
    }
	
	function setup_forms($id, $cur_item, &$error){
		$url 	= phive('Pager')->getPathNoTrailing();
		$items 	= phive($this->module_name)->getListboxData();
		$former = phive('Former');
		$former->reset();
		
		if ($cur_item === false){
			$id = null;
			$error = "No $this->module_name item by that ID.";
		}
		
		$renderer_rows = new FormRendererRows();
	
		$form0 = new Form('addremove', $renderer_rows);
		$form0->addEntries(
			new EntrySubmit('new_menu', array(
				"default"	=> "New {$this->module_name} item",
				"action"	=> '?arg0=new')));
		if($id){
			$form0->addEntries(
				new EntryHidden($this->id_key, $id),
				new EntrySubmit($this->delete_action, array(
					"default"	=> "Delete this {$this->module_name} item",
					"action"	=> "?arg0=$id&arg1=delete")));		
		}
		
		$former->addForms($form0);
		
		$form = $this->getForm($id, $cur_item, $items);
		
		$former->addForms($form);
		
	}
	
	function addAdditionalEntries(&$form, $cur_item){}
	
	function getForm($id, $cur_item, $items){
		
		if($id !== null){
			$form = new Form($this->form_name);
			$form->addEntries(
				new EntryHidden($this->id_key, $id),
				new EntryLocalizable('name', array(
					"name"				=> "Name",
					"default"			=> $cur_item['name'])),
				new EntryLocalizable('alias', array(
					"name"				=> "Alias",
					"default"			=> $cur_item['alias'],
					"mandatory"			=> true)),
				new EntryList('parent_id', array(
					"name"				=> "Parent",
					"options"			=> $items,
					"validation"		=> "validate_parent_id",
					"class"				=> get_class($this),
					"default"			=> $cur_item['parent_id'],
					"mandatory"			=> true)));
				
			$this->addAdditionalEntries($form, $cur_item);
				
			if ($id == 0)
				$form->addEntries(new EntrySubmit($this->add_action, $this->add_label));
			else
				$form->addEntries(new EntrySubmit($this->update_action, $this->update_label));
				
			return $form;
		}
		return false;
	}
	
	function main(){
		$error = "";

		$cur_module = phive($this->module_name);
		
		$former 	= phive('Former');
		$arg 		= $_GET['arg0'];
		$act 		= $_GET['arg1'];
		
		if($arg === null || $arg === '0')
			$id = null;
		else if($arg === 'new')
			$id = 0;
		else if((float)$arg !== 0)
			$id = (float)$arg;
		else
			$id = null;
			
		if ($id && $act === 'delete'){
			$msg = $cur_module->deleteEntry($id);
			if($msg->getType() & PHM_SIMPLE_FATAL)
				$error = $msg->getMessage();
			else 
				$id = 0;
		}else if ($id && $act === 'moveup') {
			$cur_module->move('up', $id);
			$id = null;
		}else if ($id && $act === 'movedown'){
			$cur_module->move('down', $id);
			$id = null;
		}
		
		if ($id !== null){
			if ($id > 0)
				$cur_item = $cur_module->getEntry($id);
			else
				$cur_item = array();
		}	
		
		$this->setup_forms($id, $cur_item, $error);
		
		$ret = $former->handleResponse();
		
		// Edit menu ($id=0: new menu)
		if($ret && $former->submitted() === $this->update_action){
			$cur_item 	= $former->getArray();
			$ret 		= $cur_module->updateEntry($cur_item);
			if(!$ret)
				$error 	= "{$this->module_name} could not be updated.";
			$this->setup_forms($id, $cur_item, $error);
		}else if($ret && $former->submitted() === $this->add_action){
			$cur_item 	= $former->getArray();
			$newid 		= $cur_module->updateEntry($cur_item);
			if (!$newid)
				$error 	= "{$this->module_name} could not be added.";
			else
				echo "<meta http-equiv='refresh' content='0;url=?arg0=$newid'>";
		}
		
		if($error)
			echo "<p>" . $error . "</p>";
		
		$former->output();
		$this->render($cur_module->getHierarchy());
	}
	
	function render($items){
		?>
		<hr />
		<table border="0">
			<thead>
				<td></td>
				<td>Alias</td>
				<td>Name</td>
				<?php $this->renderExtraLabels(); ?>
			</thead>
		<?php
		foreach ($items as $item){
			$indent = $item['depth']*7;
			$style = "text-decoration: none; color: black";
		?>
			<tr>
				<td>
					<a style="<?=$style?>" href="?arg0=<?=$item[$this->id_key]?>&amp;arg1=moveup">&uarr;</a>&nbsp;<a style="<?=$style?>" href="?arg0=<?=$item[$this->id_key]?>&amp;arg1=movedown">&darr;</a>
				</td>
				<td style="padding-left: <?=$indent?>px">
					<a href="?arg0=<?=$item[$this->id_key]?>"><?=$item['alias']?></a>
				</td>
				<td>
					<?=$item['name']?>
				</td>
				<?php $this->renderExtraValues($item); ?>
			</tr>
		<?php
		}
		?>
		</table>
		<?php
	}
	
	function renderExtraLabels(){}
	function renderExtraValues($item){}
	
}
?>
