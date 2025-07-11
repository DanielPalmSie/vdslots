<?php

require_once __DIR__ . '/../../phive/phive.php';
require_once __DIR__ . '/../../phive/vendor/autoload.php';
require_once(__DIR__ . '/../../phive/modules/Cashier/Fraud.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$sql = phive('SQL');

/**
 * Function to load and process component data
 *
 * @param $sql
 * @return array|mixed
 */
function loadAndProcessComponents($sql): array
{
	$sqlWhere = " WHERE ";
    
	if ($_SERVER["REQUEST_METHOD"] == "POST" && (!empty($_POST['start_date']) || !empty($_POST['end_date']))) {
        
		if(!empty($_POST['start_date'])) {
			$sqlWhere .= " created_at >= '{$_POST['start_date']}'";
		}
        
		if(!empty($_POST['end_date'])) {

			if(!empty($_POST['start_date'])) {
				$sqlWhere .= " AND ";
			}
            
			$sqlWhere .= " created_at <= '{$_POST['end_date']}'";
		}
	} else {
        // Showing data from prev year if current month is January
		$currentYear = date("Y");
		$monthNumber = date("n");

		if ($monthNumber == 1) {
			$currentYear--;
		}
        
        $sqlWhere .= " created_at >= '{$currentYear}-01-01'";
    }
    
	$componentTempData = $sql->loadArray("SELECT * FROM smt_components $sqlWhere ORDER BY created_at DESC");
    if (empty($componentTempData)) {
		return [];
	}
    
    foreach ($componentTempData as $component) {
        $components[$component['id']] = $component;
    }

	// Load related data only if components exist
	$relatedData = loadRelatedData($sql);

	if (!empty($relatedData['related_components'])) {
		$relatedComponents = [];
		foreach ($relatedData['related_components'] as $relatedComponent) {
			$relatedComponents[$relatedComponent['parent_component_id']][] = $relatedComponent['child_component_id'];
		}

		$relatedComponentNames = [];

		foreach ($relatedComponents as $parentComponentId => $childComponentIds) {

			if (!isset($relatedComponentNames[$parentComponentId])) {
				$relatedComponentNames[$parentComponentId] = '';
			}

			foreach ($childComponentIds as $childComponentId) {
				$component = $components[$childComponentId];
                
                if (empty($component)) {
                    continue;
                }
                
				$relatedComponentNames[$parentComponentId] .= $component['name'] . " (unique: " . $component['unique_id'] . " V " . $component['version'] . ")";
			}
		}

		if (!empty($relatedComponentNames[$parentComponentId])) {
			$components[$parentComponentId]['related_components'] = $relatedComponentNames[$parentComponentId];
		}
	}
    
	foreach ($components as &$component) {
		enrichComponentData($component, $relatedData);
	}

	return $components;
}

/**
 * Function to load related data
 *
 * @param $sql
 * @return array
 */
function loadRelatedData($sql): array
{
	$tables = [
		'accountabilities', 'avaialabilities', 'categories',
		'confidentialities', 'criticalities', 'integrities',
		'related_components'
	];

	$relatedData = $tempData = [];
	foreach ($tables as $table) {
		$tempData[$table] = $sql->loadArray("SELECT * FROM smt_component_$table");
	}

	foreach ($tempData as $tableName => $tableData) {
        foreach ($tableData as $item) {
            $relatedData[$tableName][$item['id']] = $item;
        }
	}

	return $relatedData;
}

/**
 * Function to enrich component data with related data
 *
 * @param $component
 * @param $relatedData
 * @return void
 */
function enrichComponentData(&$component, $relatedData): void
{
    $parentCategory = $relatedData['categories'][$component['component_category_id']]['parent_id'] ?? null;
	$category = $relatedData['categories'][$parentCategory] ?? [];
    
	$component['accountability'] = $relatedData['avaialabilities'][$component['accountability_id']] ?? null;
	$component['availability'] = $relatedData['avaialabilities'][$component['availability_id']] ?? null;
	$component['category'] = $category;
	$component['sub_category'] = $relatedData['categories'][$component['component_category_id']];
	$component['confidentiality'] = $relatedData['confidentialities'][$component['confidentiality_id']];
	$component['criticality'] = $relatedData['criticalities'][$component['criticality_id']];
	$component['integrity'] = $relatedData['integrities'][$component['integrity_id']];
	$component['criticality'] = calculateCriticality($component);
}

/**
 * Function to calculate criticality
 *
 * @param $component
 * @return mixed
 */
function calculateCriticality($component): int
{
	return (int)max(
		$component['confidentiality_id'],
		$component['integrity_id'],
		$component['accountability_id'],
		$component['availability_id']
	);
}

/**
 * Function to handle data import
 *
 * @param $sql
 * @param $data
 * @return void
 * @throws Exception
 */
function importComponentData($sql, $data): void
{
	$rows = explode("\n", $data);
	$rows = array_unique($rows);

	echo "Total rows: " . count($rows) . "\n";
	$count = 0;

	if (!empty($rows)) {

		$nameMap = [
			'Backoffice (configuration)' => 'Backoffice configuration',
			'Core' => 'Core module',
			'Core (configuration)' => 'Core configuration',
			'Documents Service (configuration)' => 'Documents Service configuration',
			'Money Transfer Service (configuration)' => 'Money Transfer Service configuration',
			'User Service (configuration)' => 'User service config',
			'Website Backend' => 'Website backend module',
			'Website Backend(license-italy)' => 'Website backend module (license-italy)',
			'User Registration' => 'User Registration Module',
			'Website Version 1' => 'Website Version 1 (diamondbet)',
			'Website Version 1(license-italy)' => 'Website Version 1 (diamondbet) (license-italy)'
		];

		foreach ($rows as $row) {

			$row = trim($row);

			if (empty($row)) {
				continue;
			}

			$row = explode(";", $row);

			if (!is_array($row)) {
				echo "Row is not an array";
				continue;
			}

			if (array_key_exists($row[1], $nameMap)) {
				$row[1] = $nameMap[$row[1]];
			}

			// find component by name
			$name = $row[1];
			$component = $sql->loadAssoc("SELECT * FROM smt_components WHERE name = '$name' ORDER BY id DESC LIMIT 1");
			if (!$component) {
				echo "Component not found: " . $row[1] . "\n";
				continue;
			}

			$component['hash'] = $row[3];
			$component['version']++;
			$component['updated_at'] = $row[0];
			$component['created_at'] = $row[0];

			$keys = array_keys($component);
			$values = array_values($component);

			unset($keys[0], $values[0]); // Remove id from keys and values

			$values = array_map(function ($value) {
				return "'" . addslashes($value) . "'";
			}, $values);

			$sql->query("INSERT INTO smt_components (" . implode(',', $keys) . ") VALUES (" . implode(',', $values) . ")");

			echo "Component updated: " . $row[1] . "\n";

			$count++;
		}
	}

	echo "Total components updated: " . $count . "\n";
	echo "Done!\n";
}

/**
 * Function to send a monthly report email
 * 
 * @param $sql
 * @return void
 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
 * @throws \PhpOffice\PhpSpreadsheet\Exception
 */
function sendComponentReport($sql): void
{
	$components = loadAndProcessComponents($sql);
	$currentDate = date('Y-m-d');
	$fileName = "Critical_Component_List-{$currentDate}";
    $excelFilePath = tempnam(sys_get_temp_dir(), $fileName) . '.xlsx';
	$spreadsheet = exportComponentsToXls($components, $excelFilePath);
    
	$to = 'ProductTeam@videoslots.com';
	$reply_to = '';
	$subject = 'Monthly Components Report';
	$message = 'Please find attached the monthly components report.';

	$emailId = phive('MailHandler2')->queueMail($to, $reply_to, $subject, strtr($message, array("€" => "&euro;")), html_entity_decode(strip_tags($message), ENT_QUOTES, "UTF-8"), 1, $to );
	phive('MailHandler2' )->addAttachments($emailId, $excelFilePath, file_get_contents($excelFilePath), 'application/vnd.ms-excel');

	if ($emailId) {
		echo "Email sent successfully with the report.\n";
	} else {
		echo "Failed to send the email.\n";
	}

	echo "Excel file generated: " . $excelFilePath . "\n";
	unlink($excelFilePath);
}

if (isCli()) {
	if (date('j') == 15) {
		echo("Starting with the components report cron job!\n");
        
		try {
			sendComponentReport($sql);
		} catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
            echo 'Error: ' . $e->getMessage() . "\n";
		}
        
        die("Done!\n");
	}
} else {
	if (!empty($_POST['data'])) {
		try {
			importComponentData($sql, $_POST['data']);
			echo 'Imported successfully!';
		} catch (Exception $e) {
			echo 'Import Error: ' . $e->getMessage() . '<br>';
		}

		die("Done!\n");
	} else {
		$components = loadAndProcessComponents($sql);
        if (isset($_POST['download'])) {
			$user = cu();
			$firstname = $user->data['firstname'];
			$lastname = $user->data['lastname'];
			
            // Create clean filename without path
			$currentDate = date('Y-m-d');
			$fileName = "Critical_Component_List_{$_POST['start_date']}-{$_POST['end_date']}_exporter:{$firstname}_{$lastname}_{$currentDate}";
    		$excelFilePath = tempnam(sys_get_temp_dir(), $fileName) . '.xlsx';
			$cleanFileName = basename($fileName . '.xlsx');
    
			$spreadsheet = exportComponentsToXls($components, $excelFilePath);

			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment; filename="' . urlencode($cleanFileName) . '"');

			$writer = new Xlsx($spreadsheet);
			$writer->save('php://output');
			exit;
		}
	}
}

/**
 * Function to export components to XLS
 *
 * @param array $components
 * @param string $excelFilePath
 * @return Spreadsheet
 * @throws \PhpOffice\PhpSpreadsheet\Exception
 * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
 */
function exportComponentsToXls(array $components, string $excelFilePath): object
{
	$spreadsheet = new Spreadsheet();
	$sheet = $spreadsheet->getActiveSheet();

	$headers = [
		'Unique ID',
		'Internal ID',
		'Version',
		'Serial Number',
		'Name',
		'Category',
		'Subcategory',
		'Description',
		'Hash',
		'Changesets',
		'Repository',
		'Valid Until',
		'Confidentiality',
		'Integrity',
		'Accountability',
		'Availability',
		'Criticality',
		'Location (HW only)',
		'Related components',
		'Assigned roles',
		'Created At',
		'Updated At',
	];

	$headerStyleArray = [
		'font' => [
			'bold' => true,
			'name' => 'Arial',
			'size' => 14
		],
		'alignment' => [
			'horizontal' => Alignment::HORIZONTAL_CENTER,
			'vertical' => Alignment::VERTICAL_CENTER
		],
		'borders' => [
			'allBorders' => [
				'borderStyle' => Border::BORDER_THIN,
				'color' => ['argb' => 'FF000000'],
			],
		],
		'fill' => [
			'fillType' => Fill::FILL_SOLID,
			'startColor' => ['argb' => 'b0a46c']
		]
	];

	$sheet->getStyle('A1:Z1')->applyFromArray($headerStyleArray);
	$sheet->getRowDimension(1)->setRowHeight(35);
	$sheet->fromArray($headers);

	$row = 2;
	foreach ($components as $component) {
		$data = [
			$component['unique_id'] ?? '',
			$component['id'],
			$component['version'],
			$component['serial_number'] ?? '',
			$component['name'],
			$component['category']['name'],
			$component['sub_category']['name'],
			$component['description'],
			$component['hash'],
			getChangesetName($component),
			getRepositoryName($component),
			$component['version_valid_until'],
			$component['confidentiality']['name'] ?? '',
			$component['integrity']['name'] ?? '',
			$component['accountability']['name'] ?? '',
			$component['availability']['name'] ?? '',
			$component['criticality'],
			$component['location'],
			$component['related_components'],
			$component['assigned_roles'] ?? '',
			$component['created_at'],
			$component['updated_at']
		];

		$sheet->fromArray($data, null, 'A' . $row);
		$row++;
	}

	foreach (range('A', 'Z') as $columnID) {
		$sheet->getColumnDimension($columnID)->setAutoSize(true);
	}

	$writer = new Xlsx($spreadsheet);
	$writer->save($excelFilePath);

	return $spreadsheet;
}

/**
 * Get changeset name
 * 
 * @param $component
 * @return string
 */
function getChangesetName($component): string
{
    $result = '';
	if (!empty($component['changeset'])) {
		$changeset = [];
		$changesets = json_decode($component['changeset'], true);
		foreach ($changesets as $item) {
			$changeset[] = $item['repository'] . ':' . $item['hash'];
		}
        
        $result = implode(',', $changeset);
	}
    
    return $result;
}

/**
 * Get formatted repository name
 * 
 * @param $component
 * @return string
 */
function getRepositoryName($component): string
{
	$result = '';
	if (!empty($component['repository'])) {
		$result = implode(',', json_decode($component['repository'], true));
	}

	return $result ?? '';
}
// Check if user is have admin permissions
if (!$isUserAdmin = p('admin')) {
    http_response_code(403);
    exit();
}
?>


<style lang="postcss" scoped>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }

    h1 {
        color: #333;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        margin-bottom: 20px;
    }

    th, td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left;
    }

    th {
        background-color: #f2f2f2;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tr:hover {
        background-color: #f1f1f1;
    }

    button {
        background-color: #4CAF50;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin-top: 20px;
    }

    button:hover {
        background-color: #45a049;
    }
</style>

<html lang="">
    <body>
        <div>
            <form method="post">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : ''; ?>">

                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo isset($_POST['end_date']) ? $_POST['end_date'] : ''; ?>">
				<input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">

                <button type="submit" name="apply-filters">Apply Filters</button>
                <button type="submit" name="download" style="float: right">Download XLS</button>
            </form>
            <table class="w-full">
                <thead>
                <tr>
                    <th>Unique ID</th>
                    <th>Internal ID</th>
                    <th>Version</th>
                    <th>Serial Number</th>
                    <th>Component Name</th>
                    <th>Category</th>
                    <th>Subcategory</th>
                    <th>Description</th>
                    <th>Hash</th>
                    <th>Changesets</th>
                    <th>Repository</th>
                    <th>Valid until</th>
                    <th>Confidentiality</th>
                    <th>Integrity</th>
                    <th>Accountability</th>
                    <th>Availability</th>
                    <th>Criticality</th>
                    <th>Location (HW only)</th>
                    <th>Related components</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($components)): ?>
                    <tr>
                        <td colspan="18">No components available</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($components as $component): ?>
                        <tr>
                            <td><?php echo $component['unique_id']; ?></td>
                            <td><?php echo $component['id']; ?></td>
                            <td><?php echo $component['version']; ?></td>
                            <td><?php echo $component['serial_number'] ? 'Serial Number ' . $component['serial_number'] : ''; ?></td>
                            <td><?php echo $component['name']; ?></td>
                            <td><?php echo $component['category']['name'] ?? ''; ?></td>
                            <td><?php echo $component['sub_category']['name'] ?? ''; ?></td>
                            <td><?php echo $component['description']; ?></td>
                            <td><?php echo $component['hash']; ?></td>
                            <td><?php echo getChangesetName($component); ?></td>
                            <td><?php echo getRepositoryName($component); ?></td>
                            <td><?php echo $component['version_valid_until']; ?></td>
                            <td><?php echo $component['confidentiality']['name'] ?? ''; ?></td>
                            <td><?php echo $component['integrity']['name'] ?? ''; ?></td>
                            <td><?php echo $component['accountability']['name'] ?? ''; ?></td>
                            <td><?php echo $component['availability']['name'] ?? ''; ?></td>
                            <td><?php echo $component['criticality']; ?></td>
                            <td><?php echo $component['location']; ?></td>
                            <td><?php echo $component['related_components']; ?></td>
                            <td><?php echo $component['created_at']; ?></td>
                            <td><?php echo $component['updated_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </body>
</html>
