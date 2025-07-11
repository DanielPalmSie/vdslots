<?php
require_once __DIR__ . '/../../../admin.php';

if(!empty($_FILES)){
  
  $filer = phive('Filer');
  
  $zip = $filer->uploadFile('myfile');
  
  if($zip){
    
    $upload_dir = $filer->getSetting('UPLOAD_PATH').'/temp/';
    
    shell_exec("mkdir $upload_dir");
    
    chmod($upload_dir, 0777);
    
    shell_exec("unzip $zip -d $upload_dir");
    
    $countries = '';
    foreach($_POST['countries'] as $lang)
      $countries .= $lang.' ';
    
    $files = $filer->setDir($upload_dir)->getFolder();
    
    foreach($files as $f){
      list($title, $ext) 	= explode(".", $f['name']);
      $title 				= $filer->fixFileName($title);
      $body 				= file_get_contents($upload_dir.$f['name']);
      $insert 			= array(
	'category_alias' 	=> $_POST['category_alias'],
	'country'			=> $_POST['country'],
	'headline'			=> $title,
	'subheading'		=> substr($body, 0, 100).'...',
	'abstract'			=> substr($body, 0, 200).'...',
	'content'			=> $body,
	'meta_description'	=> $title,
	'url_name'			=> strtolower( str_replace(' ', '-', $title) ),
	'published'			=> 0,
	'site_id'			=> $_POST['sites_list'],
	'countries'			=> $countries);
      if(phive('SQL')->insertArray('limited_news', $insert))
	echo "Success: $title<br>";
      else
	echo "Fail: $title<br>";
    }
    
    shell_exec("rm -rf $upload_dir");
    shell_exec("rm -rf $zip");
  }
}

?>
<form method="post" action="" enctype="multipart/form-data">
  <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
  <table class="stats_table">
    <tr class="fill-odd">
      <td>Language (the language the articles will be indexed in):</td>
      <td> <input type="text" name="country" value="en" /> </td>
    </tr>
    <tr>
      <td>Display languages (languages the articles will be shown in, but not indexed):</td>
      <td>
	<?php foreach(phive('Localizer')->getAllLanguages() as $lang): ?>
	  <?php echo $lang['language'] ?>
	  <input type="checkbox" name="countries[]" value="<?php echo $lang['language'] ?>" checked="yes" />
	<?php endforeach ?>
      </td>	
    </tr>
    <tr class="fill-odd">
      <td>Category alias, <b>make sure this alias exists on target site</b>, ex: news</td>
      <td> <input type="text" name="category_alias" value="news" /> </td>
    </tr>
    <?php if(phive()->moduleExists('Site')): ?>
      <tr>
	<td>Send to:</td>
	<td> 
	  <?php phive('Site')->sitesSelect(false, false, array(), false, false) ?> 
	</td>
      </tr>
    <?php endif ?>
    <tr class="fill-odd">
      <td>
	ZIP with articles, <b>note that you need to name it articles.zip</b>.<br> 
	The name of each file in the ZIP will become the headline/title<br>
	of the article, no dots except for the file extension are allowed, ex of proper name:<br>
	<b>2 Caractéristiques d&#39;un bon joueur de poker.html</b>
      </td>
      <td> <input type="file" name="myfile" /> </td>
    </tr>
    <tr>
      <td colspan="2"> <input type="submit" name="submit" value="Submit" /> </td>
    </tr>
  </table>

</form>
