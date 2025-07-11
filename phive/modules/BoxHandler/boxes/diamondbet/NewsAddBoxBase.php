<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';

class NewsAddBoxBase extends DiamondBox{
  public function printInstanceJS(){
?>
  function validate_news_add_form(){
  return validate_news_head() && validate_news_sub() && validate_news_abs() && validate_news_cont();
  }
  <?php
  $this->printErrorJs("news_head",$this->length_head,
                      str_replace("__LENGTH__",$this->length_head,t("newsaddbox.header.toolong")));
  $this->printErrorJs("news_sub",$this->length_sub,
                      str_replace("__LENGTH__",$this->length_sub,t("newsaddbox.subheader.toolong")));
  $this->printErrorJs("news_abs",$this->length_abs,
                      str_replace("__LENGTH__",$this->length_abs,t("newsaddbox.abstract.toolong")));
  $this->printErrorJs("news_cont",$this->length_cont,
                      str_replace("__LENGTH__",$this->length_cont,t("newsaddbox.content.toolong")));
  }

  public function is404($args){
    return false;
  }

  public function printErrorJS($id,$max_length,$error_text){?>
    function validate_<?php echo $id; ?>(){
      var head = document.getElementById('<?php echo $id; ?>');
      if (head.value.length > <?php echo $max_length; ?>){
        head.style.backgroundColor = '#ffcccc';
        document.getElementById('<?php echo $id."_error"; ?>').innerHTML = "<?php echo $error_text; ?>";
        return false;
      }
      else{
        head.style.backgroundColor = '#ffffff';
        document.getElementById('<?php echo $id."_error"; ?>').innerHTML = "";
        return true;
      }
    }
  <?php
  }

  public function init(){
    $this->handlePost(array('show_meta_keywords', 'show_se', 'show_toc'));
    if(isset($_POST['save_settings']) && $_POST['box_id'] == $this->getId())
    {
      $this->setAttribute("length_sub", $_POST['length_sub']);
      $this->setAttribute("length_head", $_POST['length_head']);
      $this->setAttribute("length_abs", $_POST['length_abs']);
      $this->setAttribute("length_cont", $_POST['length_cont']);
    }
    $this->length_sub 		= ($this->attributeIsSet("length_sub"))?$this->getAttribute("length_sub"):60;
    $this->length_head 		= ($this->attributeIsSet("length_head"))?$this->getAttribute("length_head"):45;
    $this->length_abs 		= ($this->attributeIsSet("length_abs"))?$this->getAttribute("length_abs"):600;
    $this->length_cont 		= ($this->attributeIsSet("length_cont"))?$this->getAttribute("length_cont"):10000;

    $this->permit 			= phive('Permission');
    $this->nh 				= phive('LimitedNewsHandler');
    $this->uh 				= phive('UserHandler');
    $this->pager 			= phive('Pager');

    $this->create 			= $this->permit->hasPermission("news.create");
    $this->edit 			= $this->permit->hasPermission("news.edit");
    $this->publish 			= $this->permit->hasPermission("news.publish");

    $this->errors 			= $this->errorCheck($_POST);
    $this->actionPath		= llink($this->pager->getPath());

    if (empty($errors) === FALSE){}
    else if(isset($_POST['preview'])){
      if (isset($_POST['edit_news'])){
        $this->editing = true;
        $this->news_id = $_POST['news_id'];
        $this->news = $this->nh->getArticle($this->news_id);
      }else
        $this->creating  =true;

      $this->preview  =true;
      $this->errors[] = "preview";
    }else if(isset($_POST['create_news']) && $this->create)
      $article = $this->nh->createArticle(cu()->getId());
    else if(isset($_POST['edit_news']))
      $article = $this->nh->getArticle($_POST['news_id']);
    if(isset($_GET['arg0'])){
      if(isset($_GET['arg1']) and $_GET['arg1'] == "delete" && $this->edit){
        $this->news = $this->nh->getArticle($_GET['arg0']);
        $this->news->remove();
        header("Location: /");
        exit();
      } else {
        $this->news = $this->nh->getArticle($_GET['arg0']);
        $this->editing =true;
        $this->news_id = $this->news->getId();
      }
    }else
      $this->creating = true;

    if($article != false){
      $article->setHeadline($_POST['headline']);
      $article->setSubheading($_POST['subheading']);
      $article->setAbstract($_POST['abstract']);

      $article->setStartDate($_POST['start_date']);
      $article->setEndDate($_POST['end_date']);

      $article->setURLName($_POST['url_name']);
      $article->setContent($_POST['content']);
      $article->setMetaDescription($_POST['meta_description']);
      $article->setCategoryId($_POST['category_id']);
      //$article->setTac($_POST['tac']);
      $article->setHeaderFlash($_POST['header_flash']);

      $article->setImageLink($_POST['newsbox_content_image_link']);
      $article->setImageHeadLink($_POST['newsbox_content_header_image_link']);

      $article->setCategoryAlias(
        phive('CategoryHandler')->getPath($_POST['category_id'])
      );

      $article->setCountry(phive('Localizer')->getSubIndependentLang());

      $article->setTimeEdited(phive()->hisNow());

      if ($article->getUser() == null)
          header("Location: /");

      $this->handleImage('news_image', 'setImagePath', $article);
      $this->handleImage('header_image', 'setHeaderImage', $article);

      if(isset($_POST['delete_image']))
        $article->setImagePath(NULL);

      if(isset($_POST['delete_header_image']))
        $article->setHeaderImage(NULL);

      if(isset($_POST['publish']) && $this->publish)
        $article->setStatus("approved");
      else
        $article->setStatus("pending");

      header("Location: /");
      exit;
    }
  }

  public function printHTML(){
  if($this->news || $this->creating && ($this->edit || $this->create)):
  if ($this->preview != false):
  ?>

  <div class="box newsfullbox">
    <div class="top">
      <h1><?php echo $_POST['headline']; ?></h1>
    </div>
    <div class="main">
      <div class="news_content">
        <p class="author">
          <?php echo date("Y-m-d")." • ".cu()->getUsername(); ?>
        </p>
        <p class="article_content"><?php echo $_POST['content']?></p>
      </div>
    </div>
    <div class="bottom">
      &nbsp;
    </div>
  </div>
<?php
  endif;
  ?>
  <div class="box expandablebox">
    <div class="top">
      <div class="header">
        <?php if ($this->editing): ?>
          <?php echo t("newsbox.edit_header"); ?>
        <?php else: ?>
          <?php echo t("newsbox.create_header"); ?>
        <?php endif ?>
      </div>
    </div>
    <div class="main">
      <div class="content">
        <form action="<?php echo $this->actionPath ?>" method="post" enctype="multipart/form-data" accept-charset="utf-8" onsubmit="return validate_news_add_form()">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <?php if ($this->editing): ?>
        <input type="hidden" name="news_id" value="<?php echo $this->news_id; ?>" />
        <input type="hidden" name="edit_news" value="" />
        <?php else: ?>
        <input type="hidden" name="create_news" value="" />
        <?php endif; ?>
        <table class="addnewstable">
          <tr>
            <td colspan="3" class="topper" >
              <?php echo t("newsbox.category"); ?>
              <span class="error_text" id="news_head_error"></span>
            </td>
          </tr>
          <tr>
            <td colspan="3" class="boxer" >
              <?php $cats = phive("LimitedNewsHandler")->getCategories(); ?>
              <select name="category_id">
                <?php foreach ($cats as $c): ?>
                  <?php echo $this->getAttribute("category") ."==". $c['id']; ?>
                  <option value="<?php echo $c['id']; ?>" <?php if(isset($this->news) && $this->news->getCategoryId() == $c['id']) echo 'selected="selected"'; ?>>
                    <?php foreach(array_fill(0, $c['depth'], '&nbsp;-&nbsp;') as $space) echo $space; echo $c['name']; ?>
                  </option>
                <?php endforeach ?>
              </select>
            </td>
          </tr>

          <tr>
            <td colspan="3" class="topper">
              <?php echo t("newsbox.url_name"); ?>
            </td>
          </tr>
          <tr>
            <td colspan="3" class="boxer">
              <span class="url_name">http<?php echo phive()->getSetting('http_type') ?>://<?=$_SERVER['HTTP_HOST']?>/category/sub-category/###/ </span>
              <input class="url_name" type="text" name="url_name" id="url_name" value="<?php echo ($this->editing && empty($this->errors))?$this->news->getURLName():$_POST['url_name']; ?>" onfocus="url_on_focus()" />
            </td>
          </tr>

          <tr>
            <td colspan="3" class="topper" >
              <?php echo t("newsbox.headline"); ?>
              <span class="error_text" id="news_head_error"></span>
            </td>

          <tr>
            <td colspan="3" class="boxer" >
              <input onblur="validate_news_head()" onkeyup="validate_news_head()" class="text" type="text" name="headline" value="<?php echo ($this->editing && empty($this->errors))?$this->news->getHeadline():$_POST['headline']; ?>" id="news_head" style="width: 300px"/>
            </td>
          </tr>
          <tr>
            <td colspan="3" class="topper">
              <?php echo t("newsbox.subheading"); ?>
              <span class="error_text" id="news_sub_error"></span>

            </td>
          </tr>
          <tr>
            <td colspan="3" class="boxer">
              <input class="text" onkeyup="validate_news_sub()" type="text" name="subheading" value="<?php echo ($this->editing && empty($this->errors))?$this->news->getSubheading():$_POST['subheading']; ?>" id="news_sub" style="width: 300px"/>
            </td>
          </tr>
          <?php if($this->show_meta_keywords == 1): ?>
            <tr>
              <td colspan="3" class="topper">
                <?php echo t("newsbox.keywords"); ?>
                <span class="error_text" id="news_keywords_error"></span>

              </td>
            </tr>
            <tr>
              <td colspan="3" class="boxer">
                <input class="text" type="text" name="meta_keywords" value="<?php echo ($this->editing && empty($this->errors))?$this->news->getMetaKeywords():$_POST['meta_keywords']; ?>" style="width: 300px"/>
              </td>
            </tr>
          <?php endif ?>
          <tr>
            <td colspan="3" class="topper">
              <?php echo t("newsbox.meta_description"); ?>
            </td>
          </tr>
          <tr>
            <td colspan="3" class="boxer">
              <input class="text" type="text" name="meta_description" value="<?php echo ($this->editing && empty($this->errors))?$this->news->getMetaDescription():$_POST['meta_description']; ?>" id="meta_description" style="width: 300px"/>
            </td>
          </tr>

          <tr>
            <td colspan="3" class="topper">
              <?php echo t("newsbox.abstract"); ?>
              <span class="error_text" id="news_abs_error"></span>

            </td>
          </tr>
          <tr>
            <td colspan="3" class="boxer" id="news_abstract">
              <?php phive('InputHandler')->printTextArea("large","abstract","abstract",($this->editing && empty($this->errors))?$this->news->getAbstract():$_POST['abstract'],"300px","400px"); ?>
            </td>
          </tr>
          <?php if($this->show_tac == 1): ?>
            <tr>
              <td colspan="3" class="topper">
                <?php echo t("newsbox.tac"); ?>
                <span class="error_text" id="news_abs_error"></span>
              </td>
            </tr>
            <tr>
              <td colspan="3" class="boxer" id="news_tac">
                <?php phive('InputHandler')->printTextArea("large","tac","tac",($this->editing && empty($this->errors))?$this->news->getTac():$_POST['tac'],"300px","400px"); ?>
              </td>
            </tr>
          <?php endif ?>
          <tr>
            <td colspan="3" class="topper">
              <?php echo t("newsbox.content.text"); ?>
              <span class="error_text" id="news_cont_error"></span>

            </td>
          </tr>
          <tr>
            <td colspan="3" class="boxer" >
              <?php phive('InputHandler')->printTextArea("large","content","content",($this->editing && empty($this->errors))?$this->news->getContent():$_POST['content'],"300px","400px"); ?>
            </td>
          </tr>

          <?php if ($err=phive('ImageHandler')->getError()): ?>
          <tr>
            <td>
              <p class="pfb_settings_error"><?=t($err)?></p>
            </td>
          </tr>
          <?php endif; ?>

          <tr>
            <td colspan="3" class="topper">
              <?php echo t("newsbox.content.image"); ?>
            </td>
          </tr>

          <tr>
            <td colspan="3" class="boxer" >
              <table class="pfb_settings_table">
                <tr>
                  <td rowspan="2" style="width: 60px">
                    <?php if ($this->editing) img($this->news->getImagePath(), 60, 60, 'thumb'); ?>
                  </td>
                  <td>
                    <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
                    <input name="news_image" type="file" />
                    <input type="checkbox" name="delete_image" value="" id="delete_image">
                    <label for="delete_image"><?php echo t("newsaddbox.delete_image"); ?></label>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td colspan="3" >
            <table>
              <tr>
                <td style="width:60px"></td>
                <td>
                  <input name="newsbox_content_image_link" type="input" value="<?php print ($this->editing && empty($this->errors))?$this->news->getImageLink():$_POST['newsbox_content_image_link'] ?>" />
                </td>
                <td>
                  <?php echo t("newsbox.content.image.link"); ?>
                </td>
              </tr>
            </table>
            </td>
          </tr>


          <tr>
            <td colspan="3" class="topper">
              <?php echo t("newsbox.content.header_image"); ?>
            </td>
          </tr>
          <tr>
            <td colspan="3" class="boxer" >
              <table class="pfb_settings_table">
                <tr>
                  <td rowspan="2" style="width: 60px">
                    <?php if ($this->editing) img($this->news->getHeaderImage(), 60, 60, 'thumb'); ?>
                  </td>
                  <td>
                      <input name="header_image" type="file" />
                    <input type="checkbox" name="delete_header_image" value="" id="delete_header_image">
                    <label for="delete_header_image"><?php echo t("newsaddbox.delete_header_image"); ?></label>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td colspan="3" >
            <table>
              <tr>
                <td style="width:60px"></td>
                <td>
                  <input name="newsbox_content_header_image_link" type="input" value="<?php print ($this->editing && empty($this->errors))?$this->news->getHeaderImageLink():$_POST['newsbox_content_header_image_link'] ?>"/>
                </td>
                <td>
                  <?php echo t("newsbox.content.header_image.link"); ?>
                </td>
              </tr>
            </table>
            </td>
          </tr>

          <tr>
            <td colspan="3" class="topper" >
              <?php echo t("newsbox.header_flash"); ?>
            </td>
          </tr>
          <tr>
            <td colspan="3" class="boxer" >
              <input class="text" type="text" name="header_flash" value="<?php echo ($this->editing && empty($this->errors))?$this->news->getHeaderFlash():$_POST['header_flash']; ?>" style="width: 300px"/>
            </td>
          </tr>
          <?php if($this->show_se == 1): ?>
            <tr>
              <td colspan="3" class="topper" >
                <?php echo t("newsbox.start_date"); ?>
              </td>
            </tr>
            <tr>
              <td colspan="3" class="boxer" >
                <input class="text" type="text" name="start_date" value="<?php echo ($this->editing && empty($this->errors))?$this->news->getStartDate():$_POST['start_date']; ?>" style="width: 300px"/>
              </td>
            </tr>

            <tr>
              <td colspan="3" class="topper" >
                <?php echo t("newsbox.end_date"); ?>
              </td>
            </tr>
            <tr>
              <td colspan="3" class="boxer" >
                <input class="text" type="text" name="end_date" value="<?php echo ($this->editing && empty($this->errors))?$this->news->getEndDate():$_POST['end_date']; ?>" style="width: 300px"/>
              </td>
            </tr>
          <?php endif ?>
          <?php if($this->publish): ?>

          <tr>
            <td><input type="checkbox" name="publish" value="publish" checked="checked" id="publish"> <label for="publish"><?php echo t("newsbox.publish_text"); ?></label>
            </td>
          </tr>
          <?php endif; ?>

          <tr>
            <td>
              <table>
                <tr>
                  <td>
                    <?php dbSubmit(t("newsbox.submit"), 'submit') ?>
                  </td>
                  <td style="width:10px">&nbsp;</td>
                  <td>
                    <?php dbSubmit(t("newsbox.preview"),'preview'); ?>
                  </td>
                  <?php if ($this->editing): ?>
                  <td>
                    <?php dbButton("Delete","delete/","left",null,"onclick=\"return confirm('Are you sure you want to delete?');\"") ?>
                  <?php endif ?>
                </tr>
              </table>
            </td>
          </tr>

        </table>
        </form>
      </div>
    </div>
    <div class="bottom"></div>

  </div>
  <?php
  endif;
  }
  public function printCustomSettings(){?>
    <form method="post" action="?editboxes#box_<?= $this->getId()?>">
      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
      <input type="hidden" name="box_id" value="<?=$this->getId()?>"/>
      <p>
        <label for="length_head">Max length header: </label>
        <input type="text" name="length_head" value="<?=$this->length_head?>" id="length_head"/>
      </p>
      <p>
        <label for="length_sub">Max length subheader: </label>
        <input type="text" name="length_sub" value="<?=$this->length_sub?>" id="length_sub"/>
      </p>
      <p>
        <label for="length_abs">Max length abstract: </label>
        <input type="text" name="length_abs" value="<?=$this->length_abs?>" id="length_abs"/>
      </p>
      <p>
        <label for="length_cont">Max length content: </label>
        <input type="text" name="length_cont" value="<?=$this->length_cont?>" id="length_cont"/>
      </p>
      <p>
        <label for="length_cont">Show start end: </label>
        <input type="text" name="show_se" value="<?=$this->show_se?>" id="show_se"/>
      </p>
      <p>
        <label for="length_cont">Show meta keywords: </label>
        <input type="text" name="show_meta_keywords" value="<?=$this->show_meta_keywords?>" id="show_meta_keywords"/>
      </p>
      <p>
        <label for="length_cont">Show terms and conditions: </label>
        <input type="text" name="show_toc" value="<?=$this->show_toc?>" id="show_toc"/>
      </p>
      <input type="submit" name="save_settings" value="Save and close" id="save_settings"/>
    </form>
  <?php
  }

  public function errorCheck($post){
    return array();
  }
}
