<?php
require_once __DIR__ . '/../../admin.php';
require_once __DIR__ . '/../../vendor/autoload.php';

echo "Download of CSV files is disabled.";
phive('Logger')->info('someone tried download csv file_uploads/csv/ file', [
    'user_id' => cu()->getId(),
    'file' => $_GET['fbody'],
]);
exit;

$csv = new ParseCsv\Csv();
$out_file = phive('Filer')->getSetting('UPLOAD_PATH').'/csv/'.$_GET['fbody'].'.csv';
if(!file_exists($out_file)){
  echo "Something went wrong, no file to fetch.";
  exit;
}
header('Content-type: application/csv');
header('Content-Disposition: inline; filename="stats.csv"');
echo file_get_contents($out_file);
