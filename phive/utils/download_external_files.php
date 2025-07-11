<?php

require_once __DIR__ . '/../phive.php';

if (!isCli()) {
    exit;
}

function shouldDownloadFile(string $filename, int $max_age_hours): bool
{
    $last_changed = filectime($filename);
    //if file doesn't exist
    if ($last_changed === false) {
        //we need to download it
        $directoryPath = dirname($filename);

        if (!is_dir($directoryPath)) {
            // Directory doesn't exist, create it
            if (!mkdir($directoryPath, 0777, true)) {
                //Download process is not possible
                throw new Exception("Failed to create directory: $directoryPath");
            }
        }

        return true;
    }
    if (phive()->subtractTimes(time(), filectime($filename), 'h') >= $max_age_hours) {
        return true;
    } else {
        echo "Info: Update of the database is not needed: $filename\n";
        return false;
    }
}

$IpBlock = phive('IpBlock');
$slack_webhook = $IpBlock->getSetting('slack_webhook');

try {
    if (shouldDownloadFile($IpBlock->country_db, $IpBlock->getSetting('country_db_max_age_hours'))) {
        $IpBlock->downloadGeoIpDatabase();
        echo "Info: Country database updated: $IpBlock->country_db\n";
    }
} catch (Exception $e) {
    echo "Warning: Downloading GeoIP2-Country database failed due to this error: " . $e->getMessage();
    phive('Logger')->error('Downloading GeoIP2-Country database failed.', [$e]);
    // Send Slack notification to #sre-test
    if ($_ENV['APP_ENVIRONMENT'] === 'prod') {
        shell_exec("curl --retry 3 --compressed -sSfL -H 'Content-type: application/json' -d '{\"blocks\":[{\"type\":\"section\",\"text\":{\"type\":\"mrkdwn\",\"text\":\":warning: *Downloading GeoIP2-Country database failed due to this error:*\"}},{\"type\":\"section\",\"block_id\":\"error_message\",\"text\":{\"type\":\"mrkdwn\",\"text\":\"```" . addslashes($e->getMessage()) . "```\"}},{\"type\":\"divider\"}]}' $slack_webhook");
    }
    throw $e; // pipeline should fail
}

try {
    if (shouldDownloadFile($IpBlock->city_db, $IpBlock->getSetting('city_db_max_age_hours'))) {
        $IpBlock->downloadGeoIpDatabase('city');
        echo "Info: City database updated: $IpBlock->city_db\n";
    }
} catch (Exception $e) {
    echo "Warning: Downloading GeoIP2-City database failed due to this error: " . $e->getMessage();
    phive('Logger')->error('Downloading GeoIP2-City database failed.', [$e]);
    // Send Slack notification to #sre-test
    if ($_ENV['APP_ENVIRONMENT'] === 'prod') {
        shell_exec("curl --retry 3 --compressed -sSfL -H 'Content-type: application/json' -d '{\"blocks\":[{\"type\":\"section\",\"text\":{\"type\":\"mrkdwn\",\"text\":\":warning: *Downloading GeoIP2-City database failed due to this error:*\"}},{\"type\":\"section\",\"block_id\":\"error_message\",\"text\":{\"type\":\"mrkdwn\",\"text\":\"```" . addslashes($e->getMessage()) . "```\"}},{\"type\":\"divider\"}]}' $slack_webhook");
    }
    throw $e; // pipeline should fail
}

exit(0);
