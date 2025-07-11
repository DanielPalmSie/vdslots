<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
class NewsBase extends DiamondBox{
  
  function is404($args){
    if(!empty($_GET['start_date']))
      return false;
    if(empty($this->news))
      return true;
    if(count($args) > 2)
      return true;
    return false;
  }
  
  function setHeaderVals($iwidth, $iheight, $fwidth, $fheight){
    $this->iwidth = $iwidth;
    $this->iheight = $iheight;
    $this->fwidth = $fwidth;
    $this->fheight = $fheight;
  }
  
  function printBanner(){
    if(empty($this->news)){
      $this->render = false;
      return;
    }
    $this->printHeaderImage();
    $header_flash = $this->news->getHeaderFlash();
    if(!empty($header_flash)){
      $this->printHeaderFlash($header_flash);
      $this->flashHeaderJs($header_flash);
    }
  } 
  
  function hasBanner(){
    if(!empty($this->news)){
      $header_img = $this->news->getHeaderImage();
      $header_flash = $this->news->getHeaderFlash();
      return !empty($header_img) || !empty($header_flash);
    }else
    return false;
  }
  
  public function getHeadline(){
    return empty($this->news) ? null : h($this->news->getHeadline());
  }
  
  function printHeaderImage(){
    $header_img = $this->news->getHeaderImage();
    $imgLink = phive('Localizer')->getCurNonSubLang()."/";
    if(!empty($header_img) and strlen($this->news->getHeaderImageLink()) > 0){
      echo "<a href='".llink($this->news->getHeaderImageLink())."'>";
      img($header_img, $this->iwidth, $this->iheight); 
      echo "</a>";
    }elseif(!empty($header_img)){
      img($header_img, $this->iwidth, $this->iheight); 
    }
  }
  
  function flashHeaderJs($header_flash){ ?>
<?php if(!empty($header_flash)): ?>
  <script type="text/javascript">
   swfobject.embedSWF(
     "http<?php echo phive()->getSetting('http_type') ?>://<?php echo $_SERVER['HTTP_HOST'].'/'.$header_flash ?>", 
     "dbrotator", 
     "<?php echo $this->fwidth ?>", 
     "<?php echo $this->fheight ?>", 
     "9.0.0", 
     "expressInstall.swf",
     {},
     {
       allowFullScreen: "false",
       scale: "noscale",
       menu: "false",
       wmode: "transparent"
     }
   );
  </script>
<?php endif ?>
<?php }

function printHeaderFlash($header_flash){
?>
<div id="flashContainer" style="background-color: #000000; text-align:center; auto 0; width:<?php echo $this->fwidth ?>px; height:<?php echo $this->fheight ?>px;">
  <div id="dbrotator"><strong>You need to upgrade your Flash Player.</strong></div>
</div>
<?php
}

public function getArticleDate($news, $full_month = false){
    $stamp = strtotime($news->getTimeCreated());
    if ($full_month) {
        return ucfirst(strftime("%d", $stamp)) .' '. t(date("n", $stamp). ".month") .' '. strftime("%G", $stamp);
    } else {
        return ucfirst(t(date("M", $stamp))) .' '. strftime("%d", $stamp) .' '. strftime("%G", $stamp);
    }
}

function drawArticleInfo($news, $stamp = '', $cls = "article_info"){ ?>
<div class="<?php echo $cls ?> "> 
  <span class="header-big"> <?php echo t('posted.in') ?> </span>  
  <a class="a-big" href="<?php echo phive('Localizer')->langLink('', '/'.$news->getAttr('category_alias')) ?>"> 
    <?php et('cat'.$news->getCategoryId()) ?> 
  </a>
  <span class="header-big">
    &bull; <?php echo $this->getArticleDate($news)  ?>
    
    <?php $status = $news->getStatus(); if($status): ?>	
    &bull; <span class="bigNewsStatus" style="color:<?php echo $status[1]; ?>; display:none;"><?php echo $status[0]; ?></span>
<?php endif ?>
  </span>
</div>	
<?php }

    public function  getNewsCategory()
    {
        $categoryAttribute = $this->getAttribute("category");
        if ($this->attributeIsSet("category") && $categoryAttribute) {
            return $this->category = explode(',', $categoryAttribute);
        }
        return $this->category = "ALL";
    }
}

