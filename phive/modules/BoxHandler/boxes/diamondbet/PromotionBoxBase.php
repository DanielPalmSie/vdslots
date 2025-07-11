<?php
require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';
class PromotionBoxBase extends DiamondBox {

  protected $iPromotionImgWidth = 316;
  protected $sBtnColor = 'btn-cancel-l';
  protected $bShowTitle = true;
  protected $sLng = '';
  
  function init() {
    $this->handlePost(array (
      'promotions_box_links', 
      'tournaments_box_links' 
    ));
    $this->bh = phive('BoxHandler');
  }

  function getJs(){
    return '
      <script>
      $( document ).ready(function() { 
      $("#promotion-title").text($("#promotion-container-menu ul > li.active > a").text());
    });</script>';
  }
  
  function printHtml() {
    $route = explode('/', $_GET['dir']);
    /**
     * Problem is when url contains the language code, than the array has one key extra so need to check if language is there and
     * if so remove it from the array and add it to all url's
     */
    if(in_array(phive('Localizer')->getLanguage(),$route)){     
      $this->sLng = array_shift($route);
    }
    
    $sIndentifier = preg_replace('/[^\da-z]/i', '.', implode('-', $route));

    //die('gdfgdfsgdsfg'.privileged());
    if(count($route) >= 3){
      echo $this->getJs();
      ?>
      <table id="promotion-table" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
          <td><div id="promotion-container-menu"><?php $this->printMenu('promotion-menu')?></div></td>
          <td>
            <?php if(!empty($sIndentifier) && $this->bShowTitle):?> 
            <!-- <h1 id="promotion-title"></h1>  -->
            <?php endif;?>   
            <div id="promotion-container-image"><?php img("{$sIndentifier}", 646, 206)?></div>
            <div id="promotion-container-content">
            
            <?php et("{$sIndentifier}.html", null, privileged())?>
           </div>
          </td>
        </tr>
      </table><div class="margin-ten-bottom"></div>  
    <?php 
    
    } else {
      $this->cur_page = array_pop($route);
      echo $this->getJs();
    ?>
      <table id="promotion-table" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
          <td><div id="promotion-container-menu"><?php $this->printMenu('promotion-menu')?></div></td>
          <td>
            <?php if(!empty($this->cur_page) && $this->bShowTitle):?> 
                <!--  <h1 id="promotion-title"></h1> -->
            <?php endif;?>    
            <div id="promotion-container-image"><?php img("{$sIndentifier}.{$this->cur_page}", 646, 206)?></div>
            
            <div id="promotion-container" style="color: #888;">
            <?php         
             switch($this->cur_page){
               case $sIndentifier:
                 ?>
                 <div class="blocks">
                 <?php 
                 foreach (explode(',', $this->{$sIndentifier . '_box_links'}) as $link)
                   $this->drawSmallPromoBox($sIndentifier, $link);
                 ?>
                 </div>
                 <?php  
                 break;
                 
              case 'weekly-races':
                $this->bh->boxHtml(913);
                $this->bh->boxHtml(912);
                break;
                
               default:
                 et($sIndentifier.'.' . $this->cur_page . '.html', null, privileged());
                 break;             
           }
           ?>
           </div>
          </td>
        </tr>
      </table>
   <?php
    }
  }

  function drawSmallPromoBox($sPage, $link) {
    $sIndentifier = preg_replace('/[^\da-z]/i', '.', $this->cur_page . '-' . $link);
    ?>
    <div>
      <?php img("{$sPage}.block.img.{$sIndentifier}", $this->iPromotionImgWidth, 100)?>
        <div style="color: #888;">
      <h2><?php et("{$sPage}.block.title.{$sIndentifier}", null, privileged())?></h2>
      <?php et("{$sPage}.block.content.{$sIndentifier}", null, privileged())?>
      <a href="/<?php echo (!empty($this->sLng) ? $this->sLng . '/' : '') . $link; ?>" class="btn btn-l <?php echo $this->sBtnColor;  ?>"> 
	    <?php et("read.more")?> 
	  </a>
      </div>
    </div>
    <?php 
    $link;
  }

  function is404($args) {
    return false;
  }

  function printMenu($menu_alias, $href = '') {
    $menu = phive('Menuer')->forRender($menu_alias, '');
    if (!empty($menu)) {

      echo '<ul>';
      foreach ($menu as $item) { 
        $aItems = explode('/', $item['params']);
        //print_r($aItems);
        echo '<li' . (($this->cur_page !== 'promotions' && (isset($aItems[2]) && $this->cur_page == $aItems[2])) ? ' class="active"' : '') . '>';//.$item['params'];
        $url = preg_match('/href="\/(.+)"/', $item['params'], $match);
        
        if (!empty($url)) {
          echo '<a ' . $item['params'] . '>';
        } else {
          echo '<span>';
        }
        
        if (is_file($_SERVER['DOCUMENT_ROOT'] . '/diamondbet/images/' . brandedCss() . $item['alias'] . '.png')) {
          echo '<img src="/diamondbet/images/' . brandedCss() . $item['alias'] . '.png" />';
        }
        //echo (($item['current']) ? '&raquo;' : '') . $item['txt'] . (($item['current']) ? '&laquo;' : '');
        echo $item['txt'];
        
        if (!empty($url)) {
          echo '</a>';
        } else {
          echo '</span>';
        }
        
        if (!empty($item['alias'])) {
          $this->printMenu($item['alias']);
        } else {
          echo '</li>';
        }
      }
      echo '</ul>';
    }
  }

  public function printExtra() {
    ?>
    <p>Promotion page URL's <em>(comma separated)</em>: <?php dbInput('promotions_box_links', $this->promotions_box_links) ?></p>
    <p>Tournament page URL's <em>(comma separated)</em>: <?php dbInput('tournaments_box_links', $this->tournaments_box_links) ?></p>
<?php
  }
}
