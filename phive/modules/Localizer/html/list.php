<?php
// This file is like editstrings.php, but with aim to translate the next
//  n (arbitrary number) untranslated entries. It will only show the
//  translation of the principal language (default_lang in Localizer)
//  and it won't translate any strings with the .html suffix.
define('MAX_RESULTS_PER_PAGE', 50000);
global $phAdminPermission;
$phAdminPermission = "translate.%";
require_once __DIR__ . '/../../../admin.php';

if (phive()->moduleExists('Permission') && !p('translate.'.$_GET['list'])):
    echo "<p>You don't have permission to translate this language</p>";
else:
?>

<!-- JS here -->
<script type="text/javascript" charset="utf-8">

 function copy_text(alias){
     document.getElementById('textarea|' + alias).value = document.getElementById('default|' + alias).value;
 }

 function translate_checkbox(alias){
     if (document.getElementById('textarea|' + alias).value)
         document.getElementById('checkbox|' + alias).checked = true;
 }

 function updatePagedBy(value) {
     let url = new URL(window.location.href);
     url.searchParams.set("pagedby", value);
     url.searchParams.delete("p");
     window.location.href = url.toString();
 }
</script>

<table width="100%" id="localizer"><tr><td valign="top">
    <?php
    $localizer = phive('Localizer');
    $error = "";
    $lang = $_GET['list'];
    $max = 50;
    $limitCount = isset($_GET['pagedby']) ? intval($_GET['pagedby']) : 25;
    $currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $offset = ($currentPage - 1) * $limitCount;
    $order = isset($_GET['order']) ? (strtolower($_GET['order']) == 'desc' ? 'DESC' : 'ASC') : 'ASC';
    $orderBy = "{$order}";
    $excludedStrings = [];
    if ($_GET['include_sportsbook'] != 1) {
        $excludedStrings[] = 'sb.';
    }

    if (!isset($_GET['count']) || !is_numeric($_GET['count']) || $_GET['count'] > $max) {
        $p = (int)$_GET['p'];
        $limit = ($p && $p > 1) ? "{$orderBy} LIMIT {$limitCount} OFFSET " . (($p - 1) * $offset) : "{$orderBy} LIMIT {$limitCount}";
    } else {
        $limit = "{$orderBy} LIMIT {$_GET['count']}";
    }

    if (isset($_GET['ref']))
        $default_lang = $_GET['ref'];
    else
        $default_lang = $localizer->getDefaultLanguage();

    // Get total count of strings
    if ($_GET['type'] == 'translated') {
        $totalStrings = $localizer->removeStrings('alias', $excludedStrings)->getStringCount($lang);
    } else if ($_GET['type'] == 'all') {
        $lang = (!empty($lang)) ? $lang : $default_lang;
        $totalStrings = $localizer->removeStrings('alias', $excludedStrings)->getAllStrings($lang, "", true, $_GET['filterby'] ?? '', $_GET['filterbyval'] ?? '', 'alias');
    } else {
        $totalStrings = $localizer->removeStrings('alias', $excludedStrings)->getUntranslatedStringCount($lang);
    }

    $totalCount = is_array($totalStrings) ? count($totalStrings) : $totalStrings;

    // Handle pagination
    $limitCount = isset($_GET['pagedby']) ? ($_GET['pagedby'] === 'all' ? min($totalCount, MAX_RESULTS_PER_PAGE) : intval($_GET['pagedby'])) : 25;
    $totalPages = ceil($totalCount / $limitCount);
    $currentPage = isset($_GET['p']) ? max(1, min(intval($_GET['p']), $totalPages)) : 1;
    $offset = ($currentPage - 1) * $limitCount;

    $sql_extra = " LIMIT $limitCount OFFSET $offset";

    // Fetch strings for display
    if ($_GET['type'] == 'translated') {
        $strings = $localizer->removeStrings('alias', $excludedStrings)->getAllStrings($lang, $sql_extra);
        $def_strings = $localizer->removeStrings('alias', $excludedStrings)->getAllStrings($default_lang, $sql_extra);
    } else if ($_GET['type'] == 'all') {
        $strings = $localizer->removeStrings('alias', $excludedStrings)->getAllStrings($lang, $sql_extra, true, $_GET['filterby'] ?? '', $_GET['filterbyval'] ?? '', 'alias');
        $def_strings = $localizer->removeStrings('alias', $excludedStrings)->getAllStrings($default_lang, $sql_extra, true, $_GET['filterby'] ?? '', $_GET['filterbyval'] ?? '', 'alias');

        foreach($strings as $alias => &$r)
            $r['def_value'] = $def_strings[$alias]['value'] ?? '';
    } else {
        $strings = $localizer->removeStrings('alias', $excludedStrings)->getUntranslatedStrings($lang, $sql_extra);
    }

    $previous = ($currentPage == 1) ? $_SERVER['REQUEST_URI'] : modifyUrlParameter(['p' => $currentPage - 1]);
    $next = modifyUrlParameter(['p' => $currentPage + 1]);
    ?>

    <div id="filter-form">
        <span>Filter pages by: </span>
        <?php
        $options = [
            25 => '25',
            50 => '50',
            100 => '100',
            'all' => $totalCount > MAX_RESULTS_PER_PAGE ? "First " . MAX_RESULTS_PER_PAGE . " results" : "All results in 1 page"
        ];

        foreach ($options as $value => $label) { ?>
            <label class="custom-radio">
                <input type="radio" name="pagedby" value="<?= $value; ?>" <?= $limitCount == $value || ($value === 'all' && $limitCount === min($totalCount, MAX_RESULTS_PER_PAGE)) ? 'checked' : ''; ?> onclick="updatePagedBy(this.value)">
                <span class="checkmark">X</span>
                <?= $label; ?>
            </label>
        <?php } ?>
    </div>

    <table class="stats_table">
        <thead class="stats_header">
        <th>Index</th>
        <th>Alias</th>
        <th>Requested</th>
        <th>In def language.</th>
        <th>In selected language.</th>
        <th></th>
        </thead>
        <?php $i = ($currentPage - 1) * $limitCount + 1; foreach($strings as $alias => $r): ?>
            <tr class="<?php echo $i % 2 == 0 ? 'fill-even' : ''; ?>">
                <td><?php echo $i; ?></td>
                <td><?php echo $alias ?></td>
                <td><?php echo $r['requested'] ?></td>
                <td><?php echo phive()->chop(htmlspecialchars($r['def_value']), 50) ?></td>
                <td><?php echo phive()->chop(htmlspecialchars($r['value']), 50) ?></td>
                <td>
                    <a href="/phive/modules/Localizer/html/editstrings.php?arg0=<?php echo $lang; ?>&arg1=<?php echo $alias ?>" target="_blank" rel="noopener noreferrer">-></a>
                </td>
            </tr>
            <?php $i++; endforeach ?>
    </table>

    <div class="pagination">
        <a href="<?php echo $currentPage > 1 ? modifyUrlParameter(['p' => 1]) : '#'; ?>"
           class="pag-button corner-button left-button <?php echo $currentPage == 1 ? 'disabled' : ''; ?>"
            <?php echo $currentPage == 1 ? 'aria-disabled="true"' : ''; ?>
        >&lt;&lt; First Page</a>
        <div class="center-buttons">
            <a href="<?php echo $currentPage > 1 ? $previous : '#'; ?>"
               class="pag-button prev-next <?php echo $currentPage == 1 ? 'disabled' : ''; ?>"
                <?php echo $currentPage == 1 ? 'aria-disabled="true"' : ''; ?>
            >&lt; Previous Page</a>
            <span class="pag-info">Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?></span>
            <a href="<?php echo $currentPage < $totalPages ? $next : '#'; ?>"
               class="pag-button prev-next <?php echo $currentPage == $totalPages ? 'disabled' : ''; ?>"
                <?php echo $currentPage == $totalPages ? 'aria-disabled="true"' : ''; ?>
            >Next Page &gt;</a>
        </div>
        <a href="<?php echo $currentPage < $totalPages ? modifyUrlParameter(['p' => $totalPages]) : '#'; ?>"
           class="pag-button corner-button right-button <?php echo $currentPage == $totalPages ? 'disabled' : ''; ?>"
            <?php echo $currentPage == $totalPages ? 'aria-disabled="true"' : ''; ?>
        >Last Page &gt;&gt;</a>
    </div>
</td></tr></table>
<?php endif; ?>
