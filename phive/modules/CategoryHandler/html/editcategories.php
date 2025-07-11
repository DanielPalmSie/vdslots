<?php
require_once __DIR__ . '/../../../modules/HierarchySQL/html/HierarchyForm.php';

class CategoryForm extends HierarchyForm{
	function __construct(){
		$this->form_name 		= 'update_category';
		$this->add_action 		= 'add_category';
		$this->add_label 		= 'Add';
		$this->update_action 	= 'update_category';
		$this->update_label 	= 'Update';
		$this->id_key 			= 'id';
		$this->delete_action 	= 'delete_category';	
		$this->module_name		= 'CategoryHandler';
		$this->form_name		= 'updatecategory';
	}
}

$form = new CategoryForm();
$form->main();