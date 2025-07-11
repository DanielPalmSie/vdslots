<?php

$isAjax = isset($_REQUEST['action']);

if ($isAjax && $_REQUEST['action'] === 'count_untranslated') {
    require_once __DIR__ . '/../../../admin.php';
    $untranslated_count = phive('Localizer')->getUntranslatedStringCount($_GET['lang']);
    echo $untranslated_count;
    exit;
}

$login = !headers_sent();

// Requires UserHandler as well, because we need to set session variables
global $phAdminPermission;
$phAdminPermission = "translate.%";
require_once __DIR__ . '/../../../admin.php';

$uh 		= phive('UserHandler');
$localizer 	= phive('Localizer');
$former 	= phive('Former');

$former->handleResponse();
if ($former->submitted() == 'delete_nulls'){
    $localizer->deleteNulls();
    echo "Done.";
}

$user = $login ? $uh->login() : cu();

if (isset($_GET['list'])){
    echo '<p>&larr; <a class="back-to-search" href=".">back to search</a></p>';
    include __DIR__ . '/list.php';
}else if (0){
    /*
    echo '<p>&larr; <a href="?">Back to overview</a> | ';

    if ($_GET['list']){
        $list = $localizer->getUntranslatedStrings($_GET['list']);
        echo '<a href="?listall=' . $_GET['list'] . '">List all translated strings</a>';
    }else{
        $list = $localizer->getAllStrings($_GET['listall']);
        echo '<a href="?list=' . $_GET['listall'] . '">List all untranslated strings</a>';
    }

    echo "</p>";

    if (!$list || (is_array($list) && empty($list)))
        echo "<p>Language not found or no more strings to translate</p>";
    else{
        // Switch to translator mode, and set language to $_GET['list']
        $old_mode = $localizer->getTranslatorMode();
        $localizer->setTranslatorMode(true);
        echo "<p>Click to translate</p>";
        echo "<hr />";
        echo "<table>";
        foreach ($list as $string){
            if ($_GET['list'])
	        echo "<tr><td>" . t($string['alias'], $_GET['list']) . "</td></tr>";
            else{
	        echo "<tr><td>" . $string['alias'] . "</td>";
	        echo "<td>" . phive('Localizer')->getString($string['alias'], $_GET['listall'], false, true) . "</td></tr>";
            }
        }
        echo "</table>";
    }
    */
} else {
    $array = $localizer->getAllLanguages();

    if (!$user):
             echo "<p>You need to be logged in for translator mode</p>";
    else:
                  if (isset($_GET['language']) && $_SESSION['language'] !== 'denied'){
                      if (!$_GET['language'])
                          unset($_SESSION['language']);
                      else
                          $_SESSION['language'] = $_GET['language'];
                  }

    $lang = $localizer->getLanguage();

        if ($_SESSION['language'] === 'denied'){
        $color = red;
        $text = "You don't have the permission to translate this language";
    }else if ($_SESSION['language']) {
        $color = "#99DFAE";
        $path = phive()->getPath();
        $text = <<<HTML
			Language is temporarily set to <b>{$_SESSION['language']}</b>
			<div style="background: white; width: 190px; margin: 10px 0 10px 0; border: 1px solid gray; text-align: left; font-size: 13px; padding: 10px">
				<p><a href="?list={$_SESSION['language']}&amp;count=20">Translate 20 strings</a></p>
				<p><a href="?list={$_SESSION['language']}&amp;count=all&amp;p=1">List all untranslated strings</a></p>
				<p><a href="?list={$_SESSION['language']}&amp;type=translated&amp;count=all&amp;p=1">List all translated strings</a><p>
				<p><a href="?list={$_SESSION['language']}&amp;type=all&amp;count=all&amp;p=1">List all strings</a><p>
			</div>
			<span style="font-size: 10px"><a href="?language">stop translating</a></span>
HTML;
    }else if ($lang) {
        $color = "#CCC";
        $text = "Language is set to <b>" . $lang . '</b>';
    }else{
        $color = "#CCC";
        $text = "Not translating";
    }
?>

    <div style="border: 1px solid gray; float: right;  background: <?=$color?>; font-size: 16px; color: black; text-align: center; padding: 12px"><?=$text?></div>

    <?php
    $langs = array();
        foreach ($array as $l)
        $langs[] = '<a href="?language=' . $l['language'] . '">' . $l['language'] . '</a>';

    ?>

    <table>
        <tr>
            <td>
                <p style="font-size: 20px">Translate to <?=implode(', ', $langs)?>
                </p>
            </td>
        </tr>
    </table>

<?php endif; ?>

<?php
    $counts = array();
    $missing = array();
    $unique = $localizer->getTotalStringCount();
    $languagesArray = array_column($array, 'language');

    $storedUntranslatedStringsCount = $_SESSION['LOCALIZER']['languagesUntranslatedStringCount'];
    if ($storedUntranslatedStringsCount && count($storedUntranslatedStringsCount) > 0) {
        $languagesUntranslatedStringCount = $storedUntranslatedStringsCount;
    } else {
        $languagesUntranslatedStringCount = $localizer->getUntranslatedStringCount("en", "", $languagesArray);
        $_SESSION['LOCALIZER']['languagesUntranslatedStringCount'] = $languagesUntranslatedStringCount;
    }

    $storedStringsCount = $_SESSION['LOCALIZER']['languagesStringCount'];
    if ($storedStringsCount && count($storedStringsCount) > 0) {
        $languagesStringCount = $storedStringsCount;
    } else {
        $languagesStringCount = $localizer->getStringCount("en", $languagesArray);
        $_SESSION['LOCALIZER']['languagesStringCount'] = $languagesStringCount;
    }

    foreach ($languagesArray as $lang) {
        $counts[$lang] = $languagesStringCount[0][$lang . '_string_count'];
        $missing[$lang] = $languagesUntranslatedStringCount[0][$lang . '_untranslated_count'];
    }
?>
<hr />
<form action="" method="get">
    <table>
	<tr>
	    <td>Filter by part of alias:</td>
	    <td><input type="text" name="filterby" /></td>
	</tr>
	<tr>
	    <td>Filter by part of the content:</td>
	    <td><input type="text" name="filterbyval" /></td>
	</tr>
	<tr>
	    <td>Language:</td>
	    <td><input type="text" name="list" /></td>
	</tr>
    <tr>
        <td>Include sportsbook(starts with sb.):</td>
        <td><input type="checkbox" name="include_sportsbook" value="1"/></td>
    </tr>
	<tr>
	    <td>
		<input type="hidden" name="count" value="all" />
		<input type="hidden" name="type" value="all" />
	    </td>
	    <td><input type="submit" name="submit" value="Submit" /></td>
	</tr>
    </table>
</form>

Unique entries: <?=$unique?>
<table id="translator_table">
    <tr>
	<th>Language</th>
	<th>Entries</th>
	<th>Untranslated</th>
    <th>Untranslated</th>
    <th>
        <?php echo '<u class="plain">
                        <span onclick="getAllUntranslatedCount()" class="plain pointer" id="calculate_all">
                            <font color="dodgerblue"><- -> Calculate All</font>
                        </span>
                    </u>'
        ?>
    </th>
    </tr>
    <?php if (!empty($array)) foreach ($array as $l):?>
	<tr>
	    <td>
		<?=$l['language']?>
	    </td>
	    <td>
		<?php echo $counts[$l['language']] ?>
	    </td>
        <td>
            <?php echo '<u class="plain">
                            <a href="?list=' . $l['language'] . '&count=all" class="plain">
                                <font color="dodgerblue">Link</font>
                            </a>
                        </u>'
            ?>
        </td>
	    <td>
		<?php
        echo '<u class="plain">
                  <span onclick="getUntranslatedCount(\'' . $l['language'] . '\')" class="plain pointer" id="' . $l['language'] . '_untranslated_count">
                      <font color="dodgerblue"><- -> Calculate</font>
                  </span>
              </u>';
		?>
	    </td>
	</tr>
    <?php endforeach; ?>
</table>
<hr style="clear: right" />
<div style="float: right">
    <?
    $form = new Form('delete_nulls', new FormRendererRows());
    $form->addEntries(
	new EntrySubmit('delete_nulls', "Delete nulls"));
    $former->addForms($form);
    $former->output();
    ?>
</div>
<script>
    function getUntranslatedCount(language) {
        var count_id = language + '_untranslated_count';
        var count = $('#' + count_id);
        var count_text = count.find('font').text();
        count.find('font').html(count_text.replace('<- ->', '<img width="12" height="12" src="/phive/images/ajax-loader.gif">'));

        $.ajax({
            url: '/phive/modules/Localizer/html/translator.php?action=count_untranslated&lang=' + language,
            method: 'GET',
            dataType: 'json',
        })
        .done(function (response) {
            var count = $('#' + count_id);
            count.replaceWith(function () {
                return '<a id="' + count_id + '" href="?list=' + language + '&count=all"><font color="red">' + response + '</font></a>';
            });
            if ($('#translator_table').find('img:not(#all_loader)').length === 0 && $('#calculate_all').find('font').html().includes('ajax-loader')) {
                $('#calculate_all').hide();
            }
        });
    }

    function getAllUntranslatedCount() {
        var languages = <?php echo json_encode($languagesArray) ?>;
        var calculate_all = $('#calculate_all');
        var calculate_all_text = calculate_all.find('font').text();
        calculate_all.find('font').html(calculate_all_text.replace('<- ->', '<img id="all_loader" width="12" height="12" src="/phive/images/ajax-loader.gif">'));

        languages.forEach(function (language) {
            getUntranslatedCount(language);
        });
    }
</script>
        <?php
        }
        ?>
