<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';

class RedirectBoxBase extends DiamondBox{
  public function init(){
    if(isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId()){
      $this->setAttribute("url", $_POST['url']);
    }
    $this->url = ($this->attributeIsSet("url")) ? $this->getAttribute("url") : "";
  }
  public function getHeadline(){
    if($this->show_headline)
      return t("redirectbox.".$this->getId().".header");
    return null;
  }
  public function printHTML(){
    if(!empty($this->url)){
      header("HTTP/1.1 301 Moved Permanently"); 
      header("Location: ".$this->url); 
      header("Connection: close");
      exit;
    }
  }
  
  public function printCustomSettings(){
  ?>
  <form method="post" action="?editboxes#box_<?= $this->getId()?>">
    <input type="hidden" name="box_id" value="<?=$this->getId()?>"/>
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <p>
      <label for="show_headline">Show headline: </label>
      <select name="show_headline" id="show_headline">
        <option value="1" <?php if($this->show_headline) echo 'selected="selected"'; ?>>Yes</option>
          <option value="0" <?php if(!$this->show_headline) echo 'selected="selected"'; ?>>No</option>
      </select>
    </p>

    <p>
      <label for="link">Redirect To:</label>
      <input type="text" name="url" value="<?php echo $this->url; ?>" id="url"/>
      Box id: <?=$this->getId()?>
    </p>

    <input type="submit" name="save_settings" value="Save and close" id="save_settings"/>
  </form>
  <?php

  }
}
