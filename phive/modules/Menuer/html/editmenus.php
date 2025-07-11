<?php
require_once __DIR__ . '/../../../modules/HierarchySQL/html/HierarchyForm.php';

class MenuForm extends HierarchyForm{
	
	function __construct(){
		$this->form_name 		= 'update_menu';
		$this->add_action 		= 'add_menu';
		$this->add_label 		= 'Add';
		$this->update_action 	= 'update_menu';
		$this->update_label 	= 'Update';
		$this->id_key 			= 'menu_id';
		$this->delete_action 	= 'delete_menu';	
		$this->module_name		= 'Menuer';
		$this->form_name		= 'updatemenu';
	}
	
	function addAdditionalEntries(&$form, $cur_item){
		$pages = phive('Pager')->getListboxData();
		$pages[0] = "Manual address &darr;";
		$form->addEntries(
			new EntryLink('link', array(
				"name"				=> "Link",
				"default"			=> $cur_item['link'],
				"default_page_id"	=> $cur_item['link_page_id'],
				"pages"				=> $pages)),
			new EntryLocalizable('getvariables', array(
				"name"				=> "Get variables",
				"default"			=> $cur_item['getvariables'])),
			new EntryBoolean('new_window', array(
				"caption"			=> "Open in new window",
				"default"			=> $cur_item['new_window'])),
			new EntryBoolean('check_permission', array(
				"caption"			=> "Check permission",
				"default"			=> $cur_item['check_permission'])),
			new EntryBoolean('logged_in', array(
				"caption"			=> "Show only to logged in",
				"default"			=> $cur_item['logged_in'])
            ),
			new EntryBoolean('logged_out', array(
				"caption"			=> "Show only to logged out",
				"default"			=> $cur_item['logged_out'])
			),
            new EntryInput('included_countries', [
                "name"				=> "Included countries",
                "default"			=> $cur_item['included_countries']
            ]),
            new EntryInput('excluded_countries', [
                "name"				=> "Excluded countries",
                "default"			=> $cur_item['excluded_countries']
            ]),
            new EntryInput('excluded_provinces', [
                "name"				=> "Excluded Provinces",
                "default"			=> $cur_item['excluded_provinces']
            ]),
            new EntryInput('icon', [
                "name"				=> "Menu icon",
                "default"			=> $cur_item['icon']
            ])
        );
	}
	
	function renderExtraLabels(){
	?>
		<td>Link</td>
		<td>GETs</td>
	<?php
	}
	
	function renderExtraValues($item){
	?>
		<td><?=($item['link_page_id']?("(page id: ".$item['link_page_id'] .")"):$item['link'])?></td>
		<td><?=$item['getvariables']?></td>
	<?php
	}
}

$menu_form = new MenuForm();
$menu_form->main();
