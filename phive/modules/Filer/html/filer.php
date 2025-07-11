<?php
require_once __DIR__ . '/../../../admin.php';
phive()->loadApi('filedirext');
$filer = phive('Filer');

if (isset($_GET['delete']) && $_GET['delete']){
  $filer->deleteFile($_GET['delete']);
  $loc = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?'));
  header("Location: " . $loc);
}

if (isset($_GET['delete_all']) && $_GET['delete_all']){
  $filer->deleteFile($_GET['delete_all']);
  $loc = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?'));
  header("Location: " . $loc);
}

if(!empty($_FILES)){
  $new_path = $filer->uploadFile('myfile');
  if($new_path){
    echo "<p>File uploaded</p>";
    if(phive()->moduleExists('Site')){
      $file = array('myfile' => $new_path);
    }
  }else{
  }
}

?>

<script type="text/javascript" charset="utf-8">

// Copy text from default language
function copy_html(id, text)
{
  document.getElementById('copy_html').value = document.getElementById('textarea_' + id).value;
  document.getElementById('copy_html').style.display = 'block';
  if (text)
  {
    document.getElementById('info_text').style.display = 'block';
    document.getElementById('info_text').innerHTML = text;
  }
  else
  {
    document.getElementById('info_text').style.display = 'none';
  }
}

</script>

<?php if(p('filer.upload')): ?>
<!-- The data encoding type, enctype, MUST be specified as below -->
<form enctype="multipart/form-data" action="" method="POST">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
  <table style="padding: 10px; margin: 10px;">
    <tr>
      <td>
        <!-- MAX_FILE_SIZE must precede the file input field -->
          <input type="hidden" name="MAX_FILE_SIZE" value="100000000" />
          <!-- Name of input element determines name in $_FILES array -->
          Upload new file:<br><br>
          <input name="myfile" type="file" />
          <br>
          <br>
          <input type="submit" value="Send File" />
      </td>
      <td>&nbsp;&nbsp;&nbsp;</td>
      <td>
        <?php if(phive()->moduleExists('Site')): ?>
            Send to:<br>
            <?php phive('Site')->sitesSelect() ?>
            <?php endif ?>
      </td>
    </tr>
  </table>
</form>
<?php endif; ?>
<hr />
<table style="width: 700px">
  <thead>
    <tr>
      <th align="left">Filename</th>
      <th align="left">Ext</th>
      <th align="left">Size</th>
      <th align="left">Action</th>
    </tr>
  </thead>
  <tbody>
<?php $i = 0; ?>
<?php foreach ($filer->getFolder() as $file): ?>
    <tr>
      <td><a onclick="window.open(this.href); return false" href="<?=$filer->getPathURI().'/'.$file['name']?>"><?=$file['name']?></a></td>
      <td><?=$file['extension']?></td>
      <td><?=$file['size_readable']?></td>
      <td>
      <?php if(p('filer.delete')): ?>
        <a onclick="return confirm('Are you sure you want to delete this file?')" style="font-family: Arial; color: red; text-decoration: none" href="?delete=<?=$file['name']?>">X</a>
      <?php endif ?>
        <?php if (list($html, $args)=$filer->generateHTML($file)):
          if ($args!==null && !empty($args))
            $text = addslashes("Don't forget to enter appropriate values at " . implode(', ', $args));
          ?>
           /
        <a href="#"
          onclick="copy_html('<?=$i?>', '<?=$text?>'); return false"
          style="font-family: Arial; color: blue; text-decoration: none"
          >HTML</a>
        <?php if(phive()->moduleExists('Site')): ?>
            /
          <a onclick="return confirm('Are you sure you want to delete this file?')"
            style="font-family: Arial; color: red; text-decoration: none"
            href="?delete_all=<?=$file['name']?>"
            >X on all sites</a>
        <?php endif ?>
        <textarea style="display: none" id="textarea_<?=$i?>"><?=htmlspecialchars($html)?></textarea>
        <?php endif; ?>
      </td>
    </tr>
  <?php ++$i ?>
<?php endforeach; ?>
  </tbody>
</table>
<hr />
<p id="info_text" style="display: none"></p>
<textarea id="copy_html" style="display: none; width: 100%; height: 200px"></textarea>
