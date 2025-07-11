<!-- tinyMCE -->
<script type="text/javascript" src="<?php echo $this->getSetting("tinymce_path");?>"></script>
<script type="text/javascript">
	tinyMCE.init({
		content_css : "<?php echo $this->getSetting("css_path"); ?>",
		relative_urls : false,
		convert_urls: false,
		mode : "<?php echo $mode; ?>",
		theme : "<?php echo $theme; ?>",
		plugins : "<?php echo $safari; ?>",
		elements : "<?php echo $id; ?>",
		theme_advanced_buttons1 : "<?php echo $theme_advanced_buttons1; ?>",
		theme_advanced_buttons2 : "<?php echo $theme_advanced_buttons2; ?>",
		theme_advanced_buttons3 : "<?php echo $theme_advanced_buttons3; ?>",
		theme_advanced_toolbar_location : "<?php echo $theme_advanced_toolbar_location; ?>",
		theme_advanced_toolbar_align : "<?php echo $theme_advanced_toolbar_align; ?>",
		entity_encoding : "raw",
		extended_valid_elements : <?php echo $valid_elements; ?>
	});
</script>
<!-- /tinyMCE -->
<textarea name="<?php echo $name; ?>" id="<?php echo $id; ?>" cols="" rows="" style="width:<?php echo $width; ?>;height:<?php echo $height; ?>"><?php echo $content; ?></textarea>
