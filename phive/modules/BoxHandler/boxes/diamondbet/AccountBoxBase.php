<?php
//require_once __DIR__.'/../../../../admin.php';
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class AccountBoxBase extends DiamondBox{
	/*
	public function init(){
		parent::init();
		$this->page = array_pop( explode('/', $_GET['dir']) );
		$this->usr_id = 3190572;
	}
	
	public function getHeadline(){
		return parent::getHeadline("accountbox.".$this->getId().".header");
	}
	
	public function printHTML(){
		$func = 'account'.ucfirst($this->page);
		$boss = phive('BossGds');
		
		if(method_exists($boss, $func)){
			list($cols, $els) = phive('BossGds')->$func($this->usr_id);
			$cols = explode(',', $cols);
			$header_row = array();
			foreach($cols as $col)
				$header_row[] = t("accountbox.".$this->page.$col.".header");
			printExpandableTable($els, $header_row);
		}else
			echo "Action impossible.";
	}
	*/
}
