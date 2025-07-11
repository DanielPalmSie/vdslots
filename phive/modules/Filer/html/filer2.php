<?php
require_once __DIR__ . '/../../../admin.php';
phive()->loadApi('filedirext');
$filer = phive('Filer');

$dirs = FileDirExt::listDirsInDir($filer->getSetting('UPLOAD_PATH'), array('user-files'));  // TODO: image_service

if(!empty($_FILES["files"])){
  $filer->uploadMulti();
}
?>
<?php if(p('filer.upload')): ?>
  <div class="pad10">    
    <script>
      var ajaxUrl = '/phive/modules/Filer/html/ajax.php';
      function showHtml(p){
        window.prompt("Copy to clipboard: Ctrl+C, Enter", '<img src="'+p+'" />');
      }
      
      function deleteFile(rp, i){
        $.post(ajaxUrl, {action: 'deletefile', fname: rp}, function(res){
          $("#row-"+i).hide(); 
        });
      }
      
      $(document).ready(function(){
        $("#listfolder").change(function(){
          $.post(ajaxUrl, {action: 'listfolder', sub: $(this).val()}, function(res){
            $("#file-list").html(res);
          });
        });
      });
    </script>
    <br/>
    Choose folder to upload to, <strong>do this before you select files</strong>, default is file_uploads:<br/>
    <?php dbSelect('folder', array_combine($dirs, $dirs), '', array('', 'Select')) ?>
    <br/>
    <?php multiUpload(array('extra' => array('folder'))) ?>
    <br/>
    <br/>
    <hr />
    <br/>
    <p>Choose folder to list:</p>
    <?php dbSelect('listfolder', array_combine(array_merge($dirs, array('')), array_merge($dirs, array('file_uploads'))), '', array('', 'Select')) // TODO: image_service ?>
    
    <br/>
    <hr />
    <br/>
    <div id="file-list"></div>
  </div>
<?php endif ?>
