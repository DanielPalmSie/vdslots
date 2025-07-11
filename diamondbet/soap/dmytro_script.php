<?php

require_once __DIR__ . '/../../phive/phive.php';

$is_test = false;


if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}

// Check if test mode is enabled
$isTestMode = $is_test;
if ($isTestMode) {
    echo "Running in TEST MODE - no changes will be made to the database\n\n";
}

// Read game_id mapping file (for trophies and templates)
$gameMappingFile = __DIR__ . "/game_id_mapping_mrv.txt";
$gameMappingContent = file_get_contents($gameMappingFile);


if ($gameMappingContent === false) {
    die("Error: Could not read game mapping file at: " . $gameMappingFile . PHP_EOL);
}

// Create game mapping array (text format, pipe-separated)
$gameMapping = [];
$lines = explode("\n", $gameMappingContent);
foreach ($lines as $line) {
    if (empty(trim($line))) continue;
    list($oldId, $newId) = explode("|", $line);
    $oldId = trim($oldId);
    $newId = trim($newId);
    if (!empty($oldId) && !empty($newId)) {
        $gameMapping[$oldId] = $newId;
    }
}

echo "Loaded " . count($gameMapping) . " game mappings\n";
echo "Sample of game mappings:\n";
$sampleMappings = array_slice($gameMapping, 0, 5, true);
foreach ($sampleMappings as $oldId => $newId) {
    echo "  {$oldId} -> {$newId}\n";
}

// Read trophy award relations mapping (for ladder)
$relationsFile = __DIR__ . "/trophy_award_relations.txt";
$relationsContent = file_get_contents($relationsFile);

if ($relationsContent === false) {
    die("Error: Could not read trophy award relations file at: " . $relationsFile . PHP_EOL);
}

// Create award relations mapping (numeric format, space-separated)
$awardMapping = [];
$lines = explode("\n", $relationsContent);
foreach ($lines as $line) {
    if (empty(trim($line))) continue;
    list($oldId, $newId) = explode(" ", trim($line));
    $oldId = trim($oldId);
    $newId = trim($newId);
    if (!empty($oldId) && !empty($newId) && is_numeric($oldId) && is_numeric($newId)) {
        $awardMapping[$oldId] = $newId;
    }
}

echo "\nLoaded " . count($awardMapping) . " trophy award relations\n";
echo "Sample of award mappings:\n";
$sampleMappings = array_slice($awardMapping, 0, 5, true);
foreach ($sampleMappings as $oldId => $newId) {
    echo "  {$oldId} -> {$newId}\n";
}

// Run all updates in sequence
echo "\n=== Starting sequential updates ===\n";

$sql = phive('SQL');

doTrophies($sql, $gameMapping, 0);
doTpls    ($sql, $gameMapping, 0);

// 2. Update Ladder (using award mapping)
updateLadder($awardMapping);

$sql->loopShardsSynced(function ($sql, $sh, $id)
use ($gameMapping) {
    doTrophies($sql, $gameMapping, $id);
    doTpls   ($sql, $gameMapping, $id);
});

echo "\n=== All updates completed ===\n";

function doTrophies($db, array $mapping, int $sid)
{
    $in  = $db->makeIn(array_keys($mapping));
    $rows= $db->loadArray(
        "SELECT id, game_ref, sub_category, category
         FROM trophies WHERE game_ref IN ($in)"
    );
    echo "Shard #$sid → ".count($rows)." trophies\n";

    foreach ($rows as $r) {
        $set = $det = [];

        if (isset($mapping[$r['game_ref']])) {
            $newEsc = $db->escape($mapping[$r['game_ref']]);
            $set[]  = "game_ref = $newEsc";
            $det[]  = "game_ref→$newEsc";

            if ($r['sub_category'] === $r['game_ref']) {
                $set[] = "sub_category = $newEsc";
                $det[] = "sub_category";
            }
        }

        if ($r['category'] === 'games-red') {
            $set[] = "category = 'games-evo'";
            $det[] = 'category';
        }

        if (!$set) continue;

        $sql = "UPDATE trophies SET ".implode(', ', $set).
            " WHERE id = {$r['id']}";

        try   {
            $db->query($sql);

            echo "Trophy updated id {$r['id']}: {$r['game_ref']} → {$mapping[$r['game_ref']]}\n";
        }
        catch (Exception $e) { echo "ERR id {$r['id']}: ".$e->getMessage()."\n"; }
    }
}

function updateLadder($mapping) {
    echo "\n=== Cloning Tournament Award Ladder ===\n";

    if (empty($mapping)) {
        echo "No mappings found, nothing to update\n";
        return;
    }

    // Get old IDs from mapping (these are the IDs we'll search for)
    $oldIds = array_keys($mapping);
    $award_ids_list = phive('SQL')->makeIn($oldIds);

    // Find all rows to clone - search only by old IDs
    $selectSql = "SELECT * FROM tournament_award_ladder
                  WHERE award_id IN ({$award_ids_list})
                  OR alternative_award_id IN ({$award_ids_list})";

    echo "\nExecuting query:\n{$selectSql}\n";

    $ladderEntries = phive('SQL')->loadArray($selectSql);
    if (phive('SQL')->last_error_message) {
        echo "DB error: " . phive('SQL')->last_error_message . "\n";
        return;
    }

    if (empty($ladderEntries)) {
        echo "No rows found for cloning.\n";
        return;
    }

    echo "Found " . count($ladderEntries) . " rows to clone.\n";

    // Debug: Show records that will be cloned
    echo "\nRecords that will be cloned:\n";
    foreach ($ladderEntries as $entry) {
        $willClone = false;
        $cloneInfo = [];

        // Check if award_id matches any old ID
        if (!empty($entry['award_id']) && isset($mapping[$entry['award_id']])) {
            $willClone = true;
            $cloneInfo[] = "award_id: {$entry['award_id']} -> {$mapping[$entry['award_id']]}";
        }

        // Check if alternative_award_id matches any old ID
        if (!empty($entry['alternative_award_id']) && isset($mapping[$entry['alternative_award_id']])) {
            $willClone = true;
            $cloneInfo[] = "alternative_award_id: {$entry['alternative_award_id']} -> {$mapping[$entry['alternative_award_id']]}";
        }

        if ($willClone) {
            echo "ID: {$entry['id']}\n";
            echo "  tag: {$entry['tag']} -> {$entry['tag']}-oss\n";
            echo "  " . implode("\n  ", $cloneInfo) . "\n\n";
        }
    }

    $clonedCount = 0;
    $errorCount = 0;
    $awardIdUpdates = 0;
    $alternativeAwardUpdates = 0;

    foreach ($ladderEntries as $entry) {
        $updates = [];
        $updateDetails = [];

        // Update award_id if it matches an old ID
        if (!empty($entry['award_id']) && isset($mapping[$entry['award_id']])) {
            $newAwardId = $mapping[$entry['award_id']];
            $updates[] = "award_id = {$newAwardId}";
            $updateDetails[] = "award_id: {$entry['award_id']} -> {$newAwardId}";
            $awardIdUpdates++;
        }

        // Update alternative_award_id if it matches an old ID
        if (!empty($entry['alternative_award_id']) && isset($mapping[$entry['alternative_award_id']])) {
            $newAlternativeAwardId = $mapping[$entry['alternative_award_id']];
            $updates[] = "alternative_award_id = {$newAlternativeAwardId}";
            $updateDetails[] = "alternative_award_id: {$entry['alternative_award_id']} -> {$newAlternativeAwardId}";
            $alternativeAwardUpdates++;
        }

        if (!empty($updates)) {

            // Prepare INSERT query by removing id field
            $fields = array_keys($entry);
            $fields = array_filter($fields, function($field) {
                return $field !== 'id';
            });

            $insertFields = implode(', ', $fields);
            $insertValues = [];
            foreach ($fields as $field) {
                $value = $entry[$field];
                if ($field === 'tag') {
                    $value = $value . '-oss';
                }
                if ($field === 'award_id' && isset($mapping[$entry['award_id']])) {
                    $value = $mapping[$entry['award_id']];
                }
                if ($field === 'alternative_award_id' && isset($mapping[$entry['alternative_award_id']])) {
                    $value = $mapping[$entry['alternative_award_id']];
                }
                if (is_string($value)) {
                    $value = phive('SQL')->escape($value);
                }
                $insertValues[] = $value;
            }
            $insertValues = implode(', ', $insertValues);

            $insertQuery = "INSERT INTO tournament_award_ladder
                           ({$insertFields})
                           VALUES ({$insertValues})";

            try {
                phive('SQL')->query($insertQuery);
                if (phive('SQL')->last_error_message) {
                    $errorCount++;
                    echo "DB error cloning id={$entry['id']}: " . phive('SQL')->last_error_message . "\n";
                } else {
                    $clonedCount++;
                    echo "Cloned id={$entry['id']}: " . implode(", ", $updateDetails) . "\n";
                }
            } catch (Exception $e) {
                $errorCount++;
                echo "Error cloning ladder entry {$entry['id']}: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\nUpdate Statistics:\n";
    echo "Total ladder entries processed: " . count($ladderEntries) . "\n";
    echo "  - award_id updates: " . $awardIdUpdates . "\n";
    echo "  - alternative_award_id updates: " . $alternativeAwardUpdates . "\n";
    echo "Errors encountered: " . $errorCount . "\n";
}

function doTpls($db, array $mapping, int $sid)
{
    $in  = $db->makeIn(array_keys($mapping));
    $src = $db->loadArray(
        "SELECT * FROM tournament_tpls WHERE game_ref IN ($in)"
    );
    echo "Shard #$sid → ".count($src)." tpl rows\n";

    foreach ($src as $r) {
        $newEsc = $db->escape($mapping[$r['game_ref']]);
        $fields=array_keys($r);
        $fields=array_filter($fields,fn($f)=>$f!=='id'&&$f!=='game_ref');
        $cols=implode(', ',$fields);
        $vals=implode(', ',array_map(
            fn($f)=>is_string($r[$f])?$db->escape($r[$f]):$r[$f],
            $fields));

        echo "  clone id {$r['id']} → $newEsc\n";

        $sql="INSERT INTO tournament_tpls (game_ref, $cols)
              VALUES ($newEsc, $vals)";
        try{$db->query($sql);}catch(Exception $e){
            echo "    ERR: ".$e->getMessage()."\n";
        }
    }
}
