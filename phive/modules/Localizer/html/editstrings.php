<?php
global $phAdminPermission;

$phAdminPermission = "translate.%";
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../ChatGPT/ChatGPT.php';

if (phive()->moduleExists('Permission')){
    if (!p('translate.'.$_REQUEST['arg0']))
        die ("You don't have permission to translate this language");

    if(!pIfExists("string.".$_REQUEST['arg1']) || str_ends_with($_REQUEST['arg1'], '.prev'))
        die("You are not allowed to edit this string.");
}

/**
 * @var Former $former
 */
$former = phive('Former');

/**
 * @var Localizer $localizer
 */
$localizer = phive('Localizer');

$exception_error_msg = '';
try {
    /**
     * @var ChatGPT $chatgpt
     */
    $chatgpt = phive('Localizer/ChatGPT/ChatGPT');
} catch (\Exception $e) {
    $exception_error_msg = "General Error: " . $e->getMessage();
}

if (!empty($exception_error_msg) ) {
    $exception_error_msg = "<h2>$exception_error_msg</h2>";
}

$error = "";

$alias = $_REQUEST['arg1'];
$lang = $_REQUEST['arg0'];

if(!empty($_REQUEST['CopyContentFromDefault'])) {
    // This part is only used for bonus code texts

    // Determine the default alias
    $stripped_alias = str_replace('.html', '', $alias);
    $position = strrpos($stripped_alias, '.');
    $alias = substr_replace($stripped_alias, '.default.html', $position);

    $msg = 'Content from default copied into the editor. Please make your changes and then click the Save button';
}

$string = "";
$languages = [];

if ($lang && $alias) {
    $languages = array_column($localizer->getAllLanguages(), 'language');

    $strings = array();
    foreach($languages as $language) {
        $strings[ $language ] = '';
    }

    $rest = $localizer->getString($alias, 'all', true, false, true);
    $restPrev = $localizer->getPreviousAliasValuesForAllLangs($alias);

    foreach($rest as $l => $s){
        $strings[$l] = $s;
        $stringPrev[$l] = $restPrev[$l];
    }

    if (empty($rest)){
        $new = true;
        header("Location: /admin/translator/?list=$lang");
    } else {
        $string = $strings[$lang];
        unset($strings[$lang]);

        $strings = array_merge(array($lang => $string), $strings);
    }
}

$former->reset();

$form0 = new Form('deletestring', new FormRendererRows());

if(p('translate.deleteall')){
    $form0->addEntries(
        new EntryHidden('alias', $alias),
        new EntryHidden('language', $lang),
        new EntrySubmit('delete', 'Delete string in all languages'));
}

$former->addForms($form0);

if($_POST['action'] === 'save'){
    $content = str_replace(array('/phive/modules/Localizer/html/'), array(''), $_POST['value']);
    $result = $localizer->editString($_POST['arg1'], $content, $_POST['lang']);

    echo json_encode($result);
    exit;
} elseif ($_POST['action'] === 'translate') {
    $source_lang = $_POST['source_lang'];
    $source_value = trim($_POST['source_value']);
    $target_lang = $_POST['target_lang'];

    $translation = $chatgpt->translate($source_value, $source_lang, $target_lang);

    echo json_encode(['translation' => $translation]);
    exit;
} elseif ($_POST['action'] === 'translate-all') {
    $source_lang = $_POST['source_lang'];
    $source_value = trim($_POST['source_value']);
    $current_lang_value_map = $_POST['current_lang_value_map'];

    $untranslated_lang_value_map = array_filter($current_lang_value_map, function($value) {
        return trim($value) === '';
    });
    $untranslated_langs = array_keys($untranslated_lang_value_map);
    $translated_lang_value_map = [];

    foreach ($untranslated_langs as $untranslated_lang) {
        $translated_lang_value_map[$untranslated_lang] = $chatgpt->translate($source_value, $source_lang, $untranslated_lang);
    }

    echo json_encode(['translations' => $translated_lang_value_map]);
    exit;
} else if(!empty($_REQUEST['EntrySubmit|deletestring|delete'])){
    if(p('translate.deleteall')){
        $localizer->deleteString($_REQUEST['EntryHidden|deletestring|alias']);
        $msg = "<p>String removed</p>";
        $strings = array();
    }else
    $msg = "<p>String could not be removed</p>";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
          "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
        <title>Translate</title>
    </head>
    <body>

        <?php
        echo $exception_error_msg;
        ?>
        <?php
        echo $msg;
        ?>

        <button id="translate-all" type="button" onclick="translateAll()">Translate All</button>
        <button id="save-all" type="button" onclick="saveAll()">Save All</button>
        <button id="revert-all" type="button" class="hidden" onclick="revertAll()">Revert All</button>
        <button id="copy-all" type="button" onclick="copyAllToClipboard()">Copy All</button>
        <button id="paste-all" type="button" onclick="pasteAllFromClipboard()">Paste All</button>
        <span id="global-msg"></span>

        <?php if(strpos($alias, 'bannertext') === 0 && strpos($alias, 'default') === false) {  // string position needs to be on position 0 ?>
        <form method="post" name="copyfromdefault">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>"> <!-- probably not needed we are using Ajax POST calls -->
            <input name="CopyContentFromDefault" value="Copy content from default string into editor" type="submit"></input>
        </form>
        <?php } ?>

        <h3><?php echo $_REQUEST['arg1'] ?></h3>
        <table width="100%" style="border-spacing: 0 30px;">
            <?php
            foreach($strings as $lang => $value):
                $valueSC = htmlspecialchars($value);
                $prevValue = $stringPrev[$lang];
                $prevValueSC = htmlspecialchars($prevValue);
            ?>

            <?php // Always show English/default, but don't show the save button if the user does not have permission to edit English/default  ?>

	        <?php if(p('translate.'.$lang) || $lang == 'en'): ?>
	            <tr>
	                <td style="width:20px;background-color: rgba(246, 246, 246, 1);border: 1px solid #ccc;border-right: none;padding: 5px;">
                        <strong> <?php echo $lang ?>: </strong>
                    </td>
	                <td valign="top" width="875px">
                        <textarea
                            id="<?php echo $lang ?>-editor"
                            style="width:875px;"
                            class="trans-field"
                        >
                            <?php echo $value ?>
                        </textarea>
                        <textarea
                            id="<?php echo $lang ?>-prev-value"
                            style="display: none"
                        >
                            <?= $prevValue ?>
                        </textarea>
	                </td>
	                <td>
                        <?php if(p('translate.'.$lang)): ?>
                            <button onclick="onTranslate('<?php echo $lang ?>')" id="translate_<?php echo $lang ?>">Translate</button>
                            <button onclick="onLangSave('<?php echo $lang ?>')" id="save_<?php echo $lang ?>" class="save-but">Save</button>
                            <button style="display: none" class="revert-but" id="revert_<?php echo $lang ?>">Revert</button>
                        <?php endif ?>
	                    <span id="<?php echo $lang ?>-msg"></span>
	                </td>
	            </tr>
	        <?php endif ?>
            <?php endforeach ?>
        </table>

        <br>
        <?php $former->execute(); ?>
        <br>

        <?php loadJs("/phive/js/jquery.min.js"); ?>
        <?php loadJs("/phive/js/utility.js");?>
        <?php loadJs("/phive/js/multibox.js") ?>
        <?php loadJs("/phive/js/mg_casino.js");?>

        <?php loadCss("/diamondbet/css/" . brandedCss() . "fancybox.css") ?>

        <?php loadJs("/phive/js/jquery.min.js"); ?>
        <script type="text/javascript" src="/phive/js/jquery.html.js"></script>
        <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['token'];?>"/>
        <script type="text/javascript">
            // this page is not including common.php which load all the js lib and do some global settings so i'm duplicating here the code needed for Ajax POST request to work
            // See config at http://api.jquery.com/jquery.ajax/
            var mboxMsgTitle = "<?php et('msg.title') ?>";
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': document.getElementById('csrf_token').value
                }
                , statusCode: {
                    // response 403 returned by csrf token verification for ajax calls
                    403: function(response) {
                        var message,json;
                        // to avoid json parsing errors if something else than json is returned on 403
                        try {
                            json = /json/.test(response.getResponseHeader('content-type')) ? response.responseJSON : JSON.parse(response.responseText);
                        } catch(e) {
                            json = {};
                        }
                        if(json.error) {
                            switch(json.error) {
                                case 'invalid_origin':
                                case 'invalid_token':
                                    message = json.message; break;
                                default:
                                    message = 'Something went wrong.'
                            }
                        }
                        fancyShow(message);
                    }
                }
            });

            $(document).ready(function() {
                function adjustTextareaHeight($textarea) {
                    $textarea.css('height', 'auto');
                    const newHeight = Math.min(Math.max($textarea[0].scrollHeight, 21), 513);
                    $textarea.css('height', newHeight + 'px');
                }

                const $textareas = $('textarea');

                $textareas.each(function() {
                    const $textarea = $(this);
                    $textarea.on('input', function() {
                        adjustTextareaHeight($textarea);
                    });
                    adjustTextareaHeight($textarea);
                });
            });
        </script>
        <script type="text/javascript" src="<?php echo phive('InputHandler')->getSetting("tinymce_v5_path") ?>"></script>
        <script>
            tinyMCE.init({
                selector : "textarea",
                skin: 'grey',
                relative_urls: false,
                convert_urls: false,
                valid_children: '+body[style],a[div|p|h1|h2|h3|img]',
                toolbar: 'undo redo | styleselect | bold italic | align | image | anchor | numlist bullist | table | indent | removeformat | charmap | searchreplace | preview | code | fullscreen',
                plugins: "media image link anchor lists table charmap searchreplace preview code fullscreen wordcount quickbars autoresize",
                force_p_newlines : false,
                forced_root_block : '',
                entity_encoding : 'raw',
                max_height: 600,
                branding: false,
                link_context_toolbar: true,
                quickbars_selection_toolbar: 'bold italic | formatselect | quicklink anchor | blockquote',

                // These settings allow adding JS to editor contents.
                // It's fine here since we are in admin context, but should be avoided in regular user context.
                cleanup: false,
                verify_html: false,
                extended_valid_elements: 'script[language|type]',
            });
        </script>

        <script type="text/javascript">
            const langs = <?= json_encode(array_values(array_filter($languages, function ($language) {
                return p('translate.'.$language);
            }))); ?>;
            $(document).ready(function (){
                prevValueRevision();
                revertClickAction();
            });

            function prevValueRevision(){
                let showRevertAll = false;
                langs.forEach(lang => {
                    const hasPrevValue = !!getPrevValueForLang(lang);

                    if (hasPrevValue) {
                        $('#revert_' + lang).show();
                        showRevertAll = true;
                    } else {
                        $('#revert_' + lang).hide();
                    }
                });

                if (showRevertAll) {
                    $('#revert-all').show();
                } else {
                    $('#revert-all').hide();
                }
            }

            function revertClickAction(){
                langs.forEach(lang => {
                    $('#revert_' + lang).on('click', () => {
                        const prevVal = getPrevValueForLang(lang);
                        const currentVal = getValueForLang(lang);

                        setPrevValueForLang(currentVal, lang);
                        setValueForLang(prevVal, lang);

                        onLangSave(lang);
                    });
                });
            }

            function onLangSave(lang) {
                resetAllMessages();
                submitLang(lang);
            }

            function onTranslate(lang) {
                resetAllMessages();
                translate(lang);
            }

            function submitLang(lang) {
                $('#save-all').prop('disabled', true);
                $('#revert-all').prop('disabled', true);
                $('#save_' + lang).prop('disabled', true);
                $('#revert_' + lang).prop('disabled', true);
                let value = getValueForLang(lang);
                value = decHTMLifEnc(value);

                $.post("/phive/modules/Localizer/html/editstrings.php", {
                    arg0: '<?php echo $_REQUEST['arg0'] ?>',
                    arg1: '<?php echo $_REQUEST['arg1'] ?>',
                    alias: "<?php echo $alias ?>",
                    value: value,
                    action: 'save',
                    lang: lang
                }, function (res) {
                    let prev = res.previous;
                    if (prev && res.current !== prev) {
                        setPrevValueForLang(prev, lang);
                    }

                    prevValueRevision();
                    setMessageForLang('Content has been saved successfully', lang);
                    $('#save_' + lang).prop('disabled', false);
                    $('#revert_' + lang).prop('disabled', false);

                    let save_in_progress = false;
                    langs.forEach(lang => {
                        if ($('#save_' + lang).prop('disabled')) {
                            save_in_progress = true;
                        }
                    });

                    if (!save_in_progress) {
                        $('#save-all').prop('disabled', false);
                        $('#revert-all').prop('disabled', false);
                    }
                }, 'json');
            }

            function saveAll() {
                resetAllMessages();
                langs.forEach(lang => submitLang(lang));
            }

            function revertAll(){
                $(".revert-but").click();
            }

            function translate(lang) {
                let translate_button = $('#translate_' + lang);
                translate_button.prop('disabled', true);
                const sourceValue = getSourceValueForAutoTranslation();

                $.post("/phive/modules/Localizer/html/editstrings.php", {
                    arg0: '<?php echo $_REQUEST['arg0'] ?>',
                    arg1: '<?php echo $_REQUEST['arg1'] ?>',
                    alias: "<?php echo $alias ?>",
                    action: 'translate',
                    source_value: sourceValue.value,
                    source_lang: sourceValue.lang,
                    target_lang: lang,
                }, function (res) {
                    res = JSON.parse(res);
                    const translation = res.translation;

                    if (translation.text) {
                        setValueForLang(translation.text, lang);
                    }

                    if (translation.error) {
                        setMessageForLang(translation.error, lang);
                        translate_button.prop('disabled', false);
                        return;
                    }

                    setMessageForLang("Content has been translated from the 'en' string to '" + lang + "'. Please press 'Save' or 'Save All' to confirm changes", lang);
                    translate_button.prop('disabled', false);
                });
            }

            function translateAll() {
                $('#translate-all').prop('disabled', true);
                resetAllMessages();

                const sourceValue = getSourceValueForAutoTranslation();
                const currentLangValueMap = getLangValueMap();

                $.post("/phive/modules/Localizer/html/editstrings.php", {
                    arg0: '<?php echo $_REQUEST['arg0'] ?>',
                    arg1: '<?php echo $_REQUEST['arg1'] ?>',
                    alias: "<?php echo $alias ?>",
                    action: 'translate-all',
                    source_value: sourceValue.value,
                    source_lang: sourceValue.lang,
                    current_lang_value_map: currentLangValueMap,
                }, function (res) {
                    res = JSON.parse(res);

                    const translations = res.translations;
                    const untranslated = getLangValueMap();
                    Object.entries(translations).forEach(([lang, translation]) => {
                        if (untranslated.hasOwnProperty(lang)) {
                            delete untranslated[lang];
                        }
                        if (translation.text) {
                            setValueForLang(translation.text, lang);
                        }
                        let message = translation.error ? translation.error : "Content has been translated from the 'en' string to '" + lang + "'. Please press 'Save' or 'Save All' to confirm changes";
                        setMessageForLang(message, lang);
                    });

                    Object.entries(untranslated).forEach(([lang, translation]) => {
                        setMessageForLang('Value already exists, translation not updated.', lang);
                    });

                    $('#translate-all').prop('disabled', false);
                });
            }

            function copyAllToClipboard() {
                const serializedLangValueMap = JSON.stringify(getLangValueMap());

                navigator.clipboard.writeText(serializedLangValueMap).then(function() {
                    setGlobalMessage('Translations copied to clipboard');
                }).catch(function(err) {
                    setGlobalMessage('Failed to copy to clipboard: ' + err.message);
                });
            }

            function pasteAllFromClipboard() {
                navigator.clipboard.readText().then(function(clipboardText) {
                    const langValueMap = JSON.parse(clipboardText);

                    Object.entries(langValueMap).forEach(([lang, translation]) => {
                        setValueForLang(translation, lang);
                    });

                    setGlobalMessage('Translations pasted from clipboard');
                }).catch(function(err) {
                    setGlobalMessage('Failed to read from clipboard: ' + err.message);
                });
            }

            function getValueForLang(lang) {
                return tinyMCE.get(lang + '-editor').getContent();
            }

            function setValueForLang(value, lang) {
                return tinyMCE.get(lang + '-editor').setContent(value);
            }

            function getPrevValueForLang(lang) {
                return $('#' + lang + '-prev-value').val().trim();
            }

            function setPrevValueForLang(value, lang) {
                return $('#' + lang + '-prev-value').val(value);
            }

            function setGlobalMessage(message) {
                return $('#global-msg').html(message);
            }

            function setMessageForLang(message, lang) {
                return $("#" + lang + "-msg").html(message);
            }

            function resetAllMessages() {
                langs.forEach(lang => setMessageForLang('', lang));
            }

            function getLangValueMap() {
                return langs.reduce((translations, lang) => {
                    return {
                        ...translations,
                        [lang]: getValueForLang(lang),
                    };
                }, {});
            }

            function getSourceValueForAutoTranslation() {
                const preferredSourceLang = 'en';
                const preferredValue = getValueForLang(preferredSourceLang);

                return {
                    value: preferredValue,
                    lang: preferredSourceLang,
                };
            }
        </script>

    </body>
</html>
