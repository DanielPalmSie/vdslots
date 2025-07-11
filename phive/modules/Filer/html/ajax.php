<?php
require_once __DIR__ . '/../../../admin.php';
phive()->loadApi('filedirext');
$filer = phive('Filer');

switch($_REQUEST['action']){
  case 'listfolder':
    $sub = empty($_REQUEST['sub']) ? '' : '/'.$_REQUEST['sub'];
    $subdir = $filer->getSetting('UPLOAD_PATH').$sub;
    $files = FileDirExt::list_files_in_dir($subdir);
    asort($files);
    ?>
      <table class="stats_table">
        <?php $i = 0; foreach($files as $f):
          $path = $filer->getSetting('UPLOAD_PATH_URI').$sub.'/'.$f; 
          $relpath = $sub.'/'.$f; 
        ?>
        <tr id="<?php echo "row-".$i ?>" class="<?php echo $i % 2 == 0 ? "fill-odd" : "fill-even" ?>">
          <td>
            <div class="fakea" onclick="showHtml('<?php echo $path ?>')">
              HTML
            </div>
          </td>
          <td>
            <a href="<?php echo $path ?>" target="_blank" rel="noopener noreferrer">
              <?php echo $path ?>
            </a>
          </td>
          <td>
            <div class="fakea" onclick="deleteFile('<?php echo $relpath ?>', '<?php echo $i ?>')">
              Delete
            </div>
          </td>
        </tr>
        <?php $i++; endforeach; ?>
      </table>
    <?php 
    break;
  case 'deletefile':
    $path = $filer->getSetting('UPLOAD_PATH').'/'.$_REQUEST['fname'];
    unlink($path);
    echo $path;
	  break;
}
