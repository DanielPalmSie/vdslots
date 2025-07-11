<?php
require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/inputfilter/class.inputfilter.php5';
class InputHandler extends PhModule{
	/**
	 * outputs an TinyMCE-textarea with the selected $type
	 * example: printTextArea("light","my_textarea","textarea_id","Text to be edited","100%","200px");
	 *
	 * @param string $type 
	 * @param string $name 
	 * @param string $id 
	 * @param string $content 
	 * @param string $width 
	 * @param string $height 
	 * @return void
	 * @author Viljam
	 */
	
	public function printTextArea($type,$name,$id,$content,$width,$height){
		if($this->getSetting("USE_TINYMCE")){
			switch($type){
			case "large":
				$mode = "exact";
				$theme = "advanced";
				$plugin = "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template";
				$theme_advanced_buttons1 = "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect";
				$theme_advanced_buttons2 = "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor";
				$theme_advanced_buttons3 = "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen";
				$theme_advanced_buttons4 = "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak";
				$theme_advanced_toolbar_location = "bottom";
				$theme_advanced_toolbar_align = "center";
				$valid_elements = file_get_contents($this->getSetting("xhtml_set_path"));
				//echo "TABORT".$valid_elements;
				include("tinymce_template.php");
				break;
			case "light":
				$mode = "exact";
				$theme = "advanced";
				$plugin = "safari,bbcode";
				$theme_advanced_buttons1 = "bold,italic,underline,separator,link,unlink,image";
				$theme_advanced_buttons2 = "";
				$theme_advanced_buttons3 = "";
				$theme_advanced_toolbar_location = "bottom";
				$theme_advanced_toolbar_align = "center";
				$valid_elements = "a[name|href|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],span[class|align|style]";
				include("tinymce_template.php");
				break;
			case "test":
				?>
				<!-- tinyMCE -->
				<script type="text/javascript" src="<?php echo $this->getSetting("tinymce_path");?>"></script>
				<script type="text/javascript">
					tinyMCE.init({
						mode : "textareas",
						theme : "advanced",
						relative_urls : false,
					});
				</script>
				<!-- /tinyMCE -->
				<textarea name="<?php echo $name; ?>" id="<?php echo $id; ?>" cols="80" rows="20" style="width:<?php echo $width; ?>;height:<?php echo $height; ?>;"><?php echo $content; ?></textarea>
				<?
				break;
			default:
				return false;
				break;
			}
		}
		else{ ?>
		<textarea name="<?php echo $name; ?>" id="<?php echo $id; ?>" cols="80" rows="20" style="width:<?php echo $width; ?>;height:<?php echo $height; ?>;"><?php echo $content; ?></textarea>
		<?php	
		}
	}
	/**
	 * Filters the $string_to_filter from any tag but the $allowedTags, and every attribute but the $allowedAttributes
	 *
	 * @param string $string_to_filter 
	 * @param string $allowedTags 
	 * @param string $allowedAttributes 
	 * @return void
	 * @author Viljam
	 */
	
	public function filterInput($string_to_filter,$allowedTags,$allowedAttributes){
		$filter = new InputFilter($allowedTags,$allowedAttributes,0,0,1);
		return $filter->process($string_to_filter);
	}
}
?>
