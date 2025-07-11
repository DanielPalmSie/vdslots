<?php
//alias=news.header_image.842&width=586&height=230 (&existing_id=1993)
/*
image_aliases -> alias, image_id. Alias is unique, image_id refer to image_id in image_data
image_data, image_id is NOT unique, can be several so that alias in image_aliases can be joined with all
versions of an image.
 */
ini_set('max_execution_time', '30000');

require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../html/display_base_diamondbet.php';

$im = phive('ImageHandler');

if(!empty($_POST['delete_id'])){
  phive('ImageHandler')->uniqueDelete($_POST['delete_id']);
}


if(!empty($_FILES)){
  $im->uploadMulti();
  die("ok");
}else{
    if(!empty($_REQUEST['existing_id'])) {
        $image_id = $_REQUEST['existing_id'];
    } else {
        $image_id = $im->getID($_REQUEST['alias']);
    }
    $pics = $im->getImageFromId($image_id, true);
}



?>
<?php multiUpload() ?>
<div class="pad10">
  <p>
    Images need to be named on the following form: <strong>some-descrpition_EUR_EN.ext, note the use of a hyphen in some-description, underscores are not allowed here</strong>, <strong>EN</strong> is the language and <strong>EUR</strong> is the currency, ext is one of  <strong>jpg, png or gif</strong>. 
  </p>
  <p>If the language can't be determined it will default to <strong>any</strong>. If the currency can't be determined it will default to <strong><?php echo phive('Currencer')->getSetting('base_currency') ?></strong>.</p>
  <p>To for instance upload an English USD image that should show in all langugas simply name it xxxx_USD_AN.jpg. Since <strong>an</strong> is not a recognized language it will default to <strong>any</strong>.</p>
  <p>
    After uploading, <strong>refresh the page</strong> to see the upoad results. To see the actual results <a href="/admin/clear-cache/" target="_blank" rel="noopener noreferrer">clearing the cache might be necessary</a>.
  </p>
  <table class="stats_table">
    <tr class="stats_header">
      <td>Image ID</td>
      <td>Width</td>
      <td>Height</td>
      <td>Language</td>
      <td>Original</td>
      <td>File Name</td>
      <td>Currency</td>
      <td></td>
    </tr>
    <?php $i = 0; foreach($pics as $pic): ?>
    <tr class="fill-odd <?php echo $i % 2 == 0 ? 'even' : 'odd' ?>">
      <td><?php echo $pic['image_id'] ?></td>
      <td><?php echo $pic['width'] ?></td>
      <td><?php echo $pic['height'] ?></td>
      <td><?php echo $pic['lang'] ?></td>
      <td><?php echo $pic['original'] ?></td>
      <td>
        <a href="<?php echo getMediaServiceUrl(); ?>/image_uploads/<?php echo $pic['filename'] ?>" target="_blank" rel="noopener noreferrer">
          <?php echo $pic['filename'] ?>
        </a>
      </td>
      <td><?php echo $pic['currency'] ?></td>
      <td>
        <form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="POST">
          <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
          <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($pic['id']);?>" />
          <?php dbSubmit('Delete') ?>
        </form>
    </tr>
    <?php $i++; endforeach; ?>
  </table>
</div>
