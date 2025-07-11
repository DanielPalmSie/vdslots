<?php

require_once __DIR__ . '/../phive.php';

if (!isCli()) {
    exit;
}

$owner = phive('Logger')->getSetting('log_file_owner');
$files = [phive('Logger')->getSetting('log_file')];
$loggers = phive('Logger')->getSetting('additional_loggers', []);

foreach ($loggers as $logger) {
    $files[] = $logger['log_file'];
}

foreach ($files as $file) {
    if (empty($file)) {
        continue;
    }
    $folder = pathinfo($file, PATHINFO_DIRNAME);

    phive('Logger')->setupLogFile($file, $owner);

    echo "Created folder $folder and file $file is writable by owner $owner.\n";
}

exit(0);
