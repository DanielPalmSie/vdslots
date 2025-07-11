<?php 
if (isset($_SESSION['language']) && $_SESSION['language'])
    $lang = $_SESSION['language'];
else
    $lang = phive()->getModule('Localizer')->getLanguage();

$page_id = phive()->getModule('Pager')->getId();
$untranslated = phive()->getModule('Localizer')->numUntranslatedByPage($page_id, $lang);
?>

<div id="admin_topbox">
    <ul>
        <?php 
        $menuItems = phive()->getModule("Menuer")->getChildren("admin_toolbar");
        foreach($menuItems as $mi):
                     $highlight = ($mi['alias']=='editboxes' && $EDITBOXES) || ($mi['alias']=='editcontent' && $EDITCONTENT && !isset($_GET['sim_loggedout'])) || ($mi['alias']=='editcontent_sim' && $EDITCONTENT && isset($_GET['sim_loggedout']));
        ?>
            <li>
                <a href="<?php echo $mi['getvariables'] ?>" <?php if ($highlight) echo 'style="color: #A32; font-weight: bold; text-decoration: underline"' ?>>
                    <?=$mi['name']?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <span style="color: #555; margin-left: 50px; float: left">
        Language: <?=$lang?>
        <?php if ($untranslated>0): ?>
            (<a href="/admin/translator/?list=<?=$lang?>&amp;page=<?=$page_id?>"><?=$untranslated?> untranslated</a>)
        <?php endif; ?>
    </span>

    <!-- Link menu -->
    <ul style="float: right">
        <?php $menuItems = phive()->getModule("Menuer")->getChildren("admin_linkbar");
        foreach($menuItems as $mi): ?>
            <li><a <?=$mi['linkparams']?>><?=$mi['name']?></a></li>
        <?php endforeach; ?>
    </ul>
    <!-- Translating Menu -->
    <?php if (false && isset($_SESSION['language']) && 
        $_SESSION['language']!=='denied' &&
        $_SESSION['language']): ?>
        <div style="float: right; margin-left: 30px">
            <a href="<?=Pager::getGets(array('language'=>'', 'editstrings'=>null))?>">&larr;</a>
            Translating: <?=($_SESSION['language']) ?>
            <?php if (($untran = phive()->getModule('Localizer')->getUntranslatedStringCount($_SESSION['language']))>0):?>
	        (<a href="/admin/translator?list=<?=$_SESSION['language']?>&amp;count=all"><font color="red"><?=$untran?> untranslated</font>) 
            <?php endif; ?>
            - 
            <?php if (isset($_GET['editstrings'])): ?>
	        <?php unset($_GET['editstrings']) ?>
	        <b><a style="color: red" href=".<?=Pager::getGets()?>">TURN OFF</a></b>
            <?php else: ?>
	        <b><a style="color: green" href=".<?=Pager::getGets(array('editstrings'=>''))?>">TURN ON</a></b>
            <?php endif; ?>
        </div>
    <?php elseif (false):
    $ret = phive()->getModule('Permission')->searchPermission("translate.%");
    if ($ret): ?>
            <div style="float: right">Translate: <?
                                                 foreach ($ret as $lang):
                                                 $l = substr($lang, strlen('translate.'), strlen($lang)); ?>
                <b><a href="<?=Pager::getGets(array("language"=>$l))?>"><?=$l?></a></b>
            <?php endforeach; ?>
            </div>
    <?php endif; endif; ?>
</div>
<div style="height:0px;width:100%">&nbsp;</div>
