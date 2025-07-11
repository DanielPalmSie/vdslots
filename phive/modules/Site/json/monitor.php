<?php
require_once __DIR__ . '/../../../api.php';

/** @var Monitor $monitor */
$monitor = phive('Site/Monitor');

$monitor->validateIP();

$metric_type = $_GET['metric'] ?? null;

$metric_report = '';
switch ($metric_type) {
    case 'deposits':
        $metric_report = phive('Site/MonitorDeposits')->getDepositsData($_GET);
        break;
    case 'dga_report':
        $metric_report = phive('Site/MonitorDGAReport')->getReportData($_GET);
        break;
    case 'dgoj_report':
        $metric_report = phive('Site/MonitorDGOJReport')->getReportData($_GET);
        break;
    default:
        $metric_report = $monitor->exec($_GET);
}

echo json_encode($metric_report);

exit;
