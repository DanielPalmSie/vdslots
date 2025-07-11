<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class GenericMenuBoxBase extends DiamondBox{
  
  function init(){
    $this->handlePost(array('menu_name', 'box_class'), array('box_class' => ''));
    $this->menu = phive('Menuer')->forRender($this->menu_name, '', true, $_SESSION['mg_username']);
  }

  function drawMenuItem(&$item){ 
  ?>
  <li <?php echo $item['current'] ? 'class="active"' : '' ?>>
    <a <?php echo $item['params']?>>
      <?php echo $item['txt']?>
    </a>
  </li>
  <?php 
  }
  
  function printHTML(){ ?>
  <div id="contactus-menu-container"><ul id="<?php echo $this->box_class ?>">
    <?php
    $that = $this;
    $sMenu = phive()->ob(function() use ($that) {
      foreach($that->menu as $item)
        $that->drawMenuItem($item);
    });
    echo preg_replace('~>\s+<~', '><', $sMenu);
    ?>
  </ul></div>
    <?php
  }

  function printExtra(){ ?>
    <p>
      <label>Menu alias: </label>
      <input type="text" name="menu_name" value="<?php echo $this->menu_name ?>" />
    </p>
    <p>
      <label>Box class: </label>
      <input type="text" name="box_class" value="<?php echo $this->box_class ?>" />
    </p>
    <?php
    }
}
