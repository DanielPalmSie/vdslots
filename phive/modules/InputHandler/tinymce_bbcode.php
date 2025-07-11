<!-- tinyMCE -->
<script type="text/javascript" src="<?php echo $this->getSetting("tinymce_path");?>"></script>
<script type="text/javascript">
	tinyMCE.init({
		theme : "advanced",
		mode : "exact",
		elements : "<?php echo $id; ?>",
		plugins : "bbcode",
		theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor,styleselect,removeformat,cleanup,code",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "",
		theme_advanced_toolbar_location : "bottom",
		theme_advanced_toolbar_align : "center",
		theme_advanced_styles : "Code=codeStyle;Quote=quoteStyle",
		content_css : "bbcode.css",
		entity_encoding : "raw",
		add_unload_trigger : false,
		remove_linebreaks : false
	});</script>
<!-- /tinyMCE -->
<textarea name="<?php echo $name; ?>" id="<?php echo $id; ?>" cols="" rows="" style="width:<?php echo $width; ?>;height:<?php echo $height; ?>"><?php echo $content; ?></textarea>
