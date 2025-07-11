<?php
require_once __DIR__ . '/../../../admin.php';
$ih = phive('ImageHandler');
if(isset($_FILES["image_picker_file"])){
  if(isset($_POST['locale']))
    $locale = $_POST['locale'];
  else
    $locale = IMAGE_LOCALE_ANY;

  if(isset($_POST['existing_id']))
    $existing_id = $_POST['existing_id'];
  else
    $existing_id = null;

  $id = $ih->createImageFromUpload("image_picker_file", $existing_id, $locale, $_POST['currency']);

   if ($err=$ih->getError())
    echo $err;
  else{
    if(!empty($_POST['alias']) && !empty($id))
      $ih->createAlias($_POST['alias'], $id);
    $picked = true;
    echo "<script>window.opener.location.reload(); window.close(); </script>";
    exit();
  }
}
                                                                      
if(isset($_POST['delete'])) {
  if($_POST['locale'] == '_all_'){
    $ih->deleteImage($_POST['existing_id'], null, $_POST['currency']);}
  else
    $ih->deleteImage($_POST['existing_id'], $_POST['locale'], $_POST['currency']);
  ?>
  <script>window.opener.location.reload(); window.close(); </script>
  <?php
  exit();
}
?>
<html>
<head>
  <meta http-equiv="Content-type" content="text/html; charset=utf-8">
  <title>Upload image</title>
</head>
<body id="uploader" onload="">
  <p>
    <a href="<?php echo "/admin/image-uploader/?".http_build_query($_GET) ?>" target="_blank" rel="noopener noreferrer">Try the new uploader.</a>
  </p>
  Target is a <?=htmlspecialchars($_GET['width'])?>x<?=htmlspecialchars($_GET['height'])?> box</br>
  <form enctype="multipart/form-data" method="POST" style="background: #eee; width: 400px;" >
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <input type="hidden" name="alias" value="<?=htmlspecialchars($_GET['alias'])?>" id="alias">
    <p>
      <?php if (isset($_GET['existing_id'])): ?>
        <input type="hidden" name="existing_id" value="<?=htmlspecialchars($_GET['existing_id'])?>" id="existing" />
        <?php
        $i = $_GET['existing_id'];
        list($picker_width, $picker_height) = $ih->scaleSize($i, 150, 150, IMAGE_LOCALE_ANY);
        if(false===($uri = $ih->getURI($i, $picker_width, $picker_height, IMAGE_LOCALE_ANY))) {
          $ih->createScaledVersion($i, $picker_width, $picker_height, IMAGE_LOCALE_ANY);
          $uri = $ih->getURI($i, $picker_width, $picker_height, IMAGE_LOCALE_ANY);
        }
        ?>
        Reference: <img display="block" src="<?=$uri?>" />
      <?php endif ?>
      <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
      <input name="image_picker_file" type="file" />
      <?php
      if (phive()->moduleExists('Localizer')):
      $langs = phive('Localizer')->getLanguages();
      array_unshift($langs, IMAGE_LOCALE_ANY);
      ?>
        Localization: <select name="locale">
          <?php foreach ($langs as $lang): ?>
            <option value="<?=$lang?>" <?php if (isset($_GET['locale']) && $_GET['locale'] == $lang): ?>selected="selected"<?php endif ?> ><?=$lang?></option>
          <?php endforeach ?>
        </select>
      <?php endif; ?>
      <?php if(phive()->moduleExists('Currencer') && phive("Currencer")->getSetting('multi_currency') && function_exists('cisosSelect')): ?>
        <?php cisosSelect(true, '', 'currency', '', array(), false, true) ?>
      <?php endif ?>
      <input type="submit" value="Upload &uarr;" />
    </p>
  </form>
  <br/>
  <?php if (isset($_GET['existing_id'])): ?>
  Delete image:
  <form method="post" accept-charset="utf-8">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
    <input type="hidden" name="existing_id" value="<?=$_GET['existing_id']?>" id="existing_id">
    <p>
      Localization: <select name="locale">
        <option value="_all_">All localizations</option>
        <?php foreach ($ih->getLocales($_GET['existing_id']) as $version): ?>
          <option value="<?=$version?>"><?=$version?></option>
        <?php endforeach ?>
      </select>
    </p>
    <?php if(phive()->moduleExists('Currencer') && phive("Currencer")->getSetting('multi_currency') && function_exists('cisosSelect')): ?>
      <p>
        Currency: <?php cisosSelect(true, '', 'currency', '', array(), false, true) ?>
      </p>
    <?php endif ?>
    <p><input type="submit" name="delete" value="Delete image"></p>
  </form>
  <?php endif ?>
  <!--a href="picker.php?<?=$_SERVER['QUERY_STRING']?>">Or pick an existing</a-->
</body>
</html>
