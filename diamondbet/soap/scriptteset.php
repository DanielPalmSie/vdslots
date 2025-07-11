<?php

require_once __DIR__ . '/../../phive/phive.php';

$sql = Phive("SQL");
if (!isCli()) {
    die("Run me from CLI\n");
}

$mapFile = __DIR__ . '/trophy_award_relations_videoslots.txt';
if (!is_file($mapFile)) die("Mapping file not found: {$mapFile}\n");

$map = [];
foreach (file($mapFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $row) {
    [$old, $new] = preg_split('/\s+/', trim($row), 2);
    if (is_numeric($old) && is_numeric($new)) $map[$old] = $new;
}
echo "Loaded " . count($map) . " mappings\n";

$rows     = $sql->loadArray("SELECT * FROM race_templates");
$patched  = 0;
$todayMinus2 = date('Y-m-d', strtotime('-2 days'));

foreach ($rows as $tpl) {

    $origPrizes = $tpl['prizes'];
    $newPrizes  = $origPrizes;
    $log        = [];

    foreach ($map as $old => $new) {
        $oldEsc = preg_quote($old, '/');
        if (preg_match_all("/\\b{$oldEsc}(?=[:,])/", $newPrizes, $m)) {
            $cnt = count($m[0]);
            $newPrizes = preg_replace("/\\b{$oldEsc}(?=[:,])/", $new, $newPrizes);
            $log[] = "{$old}→{$new} ({$cnt})";
        }
    }

    if ($newPrizes === $origPrizes) continue;

    $updateSQL = "UPDATE race_templates SET prizes = "
        . $sql->escape($newPrizes)
        . " WHERE id = " . (int)$tpl['id'];
    $sql->query($updateSQL);

    $dup     = $tpl;
    unset($dup['id']);
    $dup['recurring_end_date'] = $todayMinus2;

    $dup['prizes'] = $origPrizes;

    $cols   = array_keys($dup);
    $values = array_map([$sql,'escape'], $dup);
    $insertSQL = "INSERT INTO race_templates (" . implode(',', $cols) . ")
                  VALUES (" . implode(',', $values) . ")";
    $sql->query($insertSQL);

    $patched++;
    echo "template {$tpl['id']} patched: " . implode(', ', $log) . "\n";
}

echo "Done – {$patched} rows patched + duplicated\n";
