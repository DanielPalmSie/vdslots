<?php
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

phive('Localizer')->setLanguage($_POST['lang']);

$user 		= cu();
if(empty($user))
    die('no user');
$attr 		= $_POST['attribute'];
$already 	= $user->getSetting('changed.'.$attr);

switch($_POST['action']){
    case 'get-edit':
	drawEditAttr($attr);
	break;
    case 'set-attr':
	if(!empty($already))
	    die('<span>'.t("err.$attr.changed.already").'</span>');
	if(empty($_POST['val']))
	    die(t('err.empty'));
	else{
	    $old = $user->getAttribute($attr);
	    $user->setAttribute($attr, $_POST['val']);
	    $user->setSetting('changed.'.$attr, $old);
	    die('<span>'.t("attr.changed.successfully").'</span>');
	}
	break;
}


function drawEditAttr($attr){ ?>
    <script>
			function submitAttr(){
				$.post('/phive/modules/DBUserHandler/html/edit_attribute.php', {attribute: "<?php echo $attr ?>", action: "set-attr", val: $("#<?php echo $attr ?>").val(), lang: "<?php echo phive('Localizer')->getLanguage() ?>"}, function(res){
					if(res.indexOf("<") == 0) {
						$("#attr-verify-start").replaceWith(res);
					} else {
						$("#infotext").html(res);
					}
				});
			}
    </script>
    <div id="attr-verify-start" class="margin-ten mobileVer">
	<center>
	    <b><?php et("user.verify$attr.start") ?></b>
	</center>
	<br/>
	<center>
	    <input id="<?php echo $attr ?>" class="cashierDefaultInput" type="text">
	    <br/>
	    <div class="cashierBtnOuter">
		<div class="cashierDefaultBtnInner" onclick="submitAttr()">
		    <h4><?php et('submit'); ?></h4>
		</div>
	    </div>
	    <div id="infotext" class="errors"></div>
	</center>
    </div>
<?php }
