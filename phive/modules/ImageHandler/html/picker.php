<?php
require_once __DIR__ . '/../../../admin.php';
$ih = phive("ImageHandler");

if(isset($_FILES["image_picker_file"])){
  $id = $ih->createImageFromUpload("image_picker_file");
  $ih->createAlias($_GET['alias'], $id);
  $picked = true;
}
else if(isset($_GET['id'])){
  $ih->createAlias($_GET['alias'], $_GET['id']);
  $picked = true;
}
?>
<html>
  <head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <title>Image picker</title>
    <script type="text/javascript" charset="utf-8">
     function checkOpener(){
       if(!opener)
	 alert("You should not navigate to this page directly.");
       return !!opener;
     }
     function pick(pid){
       window.location.href = "?alias=<?=$_GET['alias']?>&id="+pid;
     }
     <?php if ($picked) { ?>
     opener.location.reload();
     //window.close();
     <?php
		} ?>
	</script>
</head>
<body id="picker" onload="checkOpener()">
	Choose an existing file:
	<table cellpadding="10" style="width:100%;">
	<?php	$is = $ih->searchImages(); ?>
		<tr>
		<?php for($j = 0; $j<count($is); ++$j): $i = $is[$j]; 
			if($j != count($is) && !($j%4))
				echo '</tr><tr>'; //new row
			list($picker_width, $picker_height) = $ih->scaleSize($i, 150, 150, IMAGE_LOCALE_ANY);
			if(false===($uri = $ih->getURI($i, $picker_width, $picker_height, IMAGE_LOCALE_ANY))) {
				$ih->createScaledVersion($i, $picker_width, $picker_height, IMAGE_LOCALE_ANY);
				$uri = $ih->getURI($i, $picker_width, $picker_height, IMAGE_LOCALE_ANY);
			}
			?>
			<td style="font-size: 11px; text-align: center; background: #dfdfdf;">
				<a href="javascript: pick(<?=$i?>);">
					<img style="display:block; margin-left: auto; margin-right: auto;" src="<?=$uri?>" />
				</a>
			</td>
		<?php endfor;?>
		</tr>
	</table>
	<a href="uploader.php?<?=$_SERVER['QUERY_STRING']?>">Upload a new file</a>
</body>
</html>
