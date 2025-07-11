<?php
require_once __DIR__ . '/../../../phive/phive.php';
require_once __DIR__ . '/../../../phive/vendor/autoload.php';
require_once __DIR__ . '/../../../phive/modules/Cashier/Fraud.php';
require_once __DIR__ . '/DataCommon.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DataEnrichment extends DataCommon
{
    public $sql;
    public string $startDate = '';
    public string $endDate = '';
    public int $start = 0;
    public int $length = 100;

    function __construct() {
        $this->sql = Phive("SQL");
        $this->startDate    = $_REQUEST['start_date'] ?? '';
        $this->endDate      = $_REQUEST['end_date'] ?? '';
        $this->start        = $_REQUEST['start'] ?? 0;
        $this->length       = $_REQUEST['length'] ?? 100;

    }

    /**
     * css function
     *
     * @return void
     */
    public function css()
    {
        loadCss("/phive/js/datatable/2.0.6/css/dataTables.dataTables.css");
        loadJs("/phive/js/jquery.min.js");
        loadJs("/phive/js/datatable/2.0.6/js/dataTables.js");
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

            button.red {
                background-color: #f53228;
            }

            button.red:hover {
                background-color: #af1811;
            }

            .w-50 {
                width: 50%;
            }
            .fl {
                float: left;
            }
            .fr {
                float: right;
            }
            .clear-both{
                clear: both;
            }
            .title {
                padding: 0;
                margin: 0;
            }
        </style>
        <?php
    }

    /**
     * js function to execute all javascript code
     *
     * @return void
     */
    public function js()
    {
        ?>
        <script>
            $(document).ready( function () {
                var oTable = new DataTable('#dataEncrichment', {
                    ajax: {
                        type:'GET',
                        url: 'index.php?ajax_call=true',
                        data: function (d) {
                            d.start_date = $('#start_date').val();
                            d.end_date = $('#end_date').val();
                            d.user_id = $('#user_id').val();
                            return d;
                        }
                    },
                    bLengthChange: true,
                    lengthMenu: [
                        [10, 25, 50, -1],
                        [100, 250, 500, 'All']
                    ],
                    infoCallback: function (settings, start, end, max, total, pre) {
                        let fstart = parseInt(start) * 10 - 9;
                        let fend = end * 10;

                        if (settings.json.length < 0) {
                            fend = end;
                        }

                        if (settings.json.data.length < 100) {
                            fend = parseInt(((start * 10) - 9) + settings.json.data.length);
                        }

                        if (settings.json.data.length > 0) {
                            return "Showing "+ fstart +" to "+ fend +" of "+total+" entries";
                        }
                        return "No more records to show";
                    },
                    processing: true,
                    serverSide: true,
                    searching: true,
                    ordering: false,
                    pagingType: 'simple',
                    language: {
                        paginate: {
                            first: 'First page',
                            next: 'Next page',
                            previous: 'Previous Page',
                            last: 'Last page'
                        }
                    },
                    columns: [
                        { data: 'id' },
                        { data: function(a) {
                                return a.display_name+" - "+a.dep_type
                            }
                        },
                        { data: function(a) {
                                return new Date(a.timestamp).toLocaleString('en-GB');
                            }
                        },
                        { data: function (a) {
                                return a.amount;
                            }
                        },
                        { data: function(a, b) {
                                if (a.first_deposit_id) {
                                    return 'Yes';
                                } else {
                                    return 'No';
                                }
                            } },
                        { data: function(a) {
                                return '<a title="View user" href="user.php?user_id='+a.user_id+'">'+a.user_id+'</a>'
                            }
                        },
                        { data: function(a) {
                            if (a.is_gtm_enabled == null) {
                                return "&mdash;";
                            }else if (parseInt(a.is_gtm_enabled)) {
                                return 'Yes';
                            } else {
                                return 'No';
                            }
                        } },
                        { data: function(a) {
                            if (a.is_gtm_blocked == null) {
                                return "&mdash;";
                            }else if (parseInt(a.is_gtm_blocked)) {
                                return 'Yes';
                            } else {
                                return 'No';
                            }
                        } },
                        { data: function (a) {
                            if (a.ga_cookie_id == null) {
                                return '&mdash;';
                            }

                            return a.ga_cookie_id;
                            } },
                        { data: function (a) {
                                if (a.btag == null) {
                                    return '&mdash;';
                                }

                                return a.btag;
                            } },
                        { data: function (a) {
                                if (a.browser == null) {
                                    return '&mdash;';
                                }

                                return a.browser;
                            } },
                        { data: function (a) {
                                if (a.device == null) {
                                    return '&mdash;';
                                }

                                return a.device;
                            } },
                        { data: function (a) {
                                if (a.traffic_source == null) {
                                    return '&mdash;';
                                }

                                return a.traffic_source;
                            } },
                        { data: function (a) {
                                if (a.country_in == null) {
                                    return '&mdash;';
                                }

                                return a.country_in;
                            } }
                    ]
                });

                $('#first_page').on('click', function () {
                    oTable.page('first').draw('page');
                });

                $('#last_page').on('click', function () {
                    oTable.page('last').draw('page');
                });

                $('#apply-filters').click(function(){
                    oTable.draw();
                    return false;
                })

                $('#reset-filters').click(function(){
                    $('#start_date').val("<?= date('Y-m-01') ?>");
                    $('#end_date').val("<?= date('Y-m-d') ?>");
                    $('#user_id').val("");
                    oTable.draw();
                    return false;
                });

                $('#export_excel').click(function () {
                    const date1 = new Date($('#start_date').val());
                    const date2 = new Date($('#end_date').val());
                    const diffTime = Math.abs(date2 - date1);
                    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                    var limitDays = <?= DataCommon::exportDaysLimit ?>;
                    if (diffDays >= limitDays) {
                        alert('You can export maximum of ' + limitDays + ' days of data.');
                        return false;
                    }
                });
            } );
        </script>
        <?php
    }

    /**
     * html function
     *
     * @return void
     */
    public function html()
    {
        ?>
        <html lang="">
        <head>
            <title>Deposits</title>
            <link rel="icon" type="image/png" sizes="16x16" href="/diamondbet/images/<?= brandedCss() ?>mobile/favicon-16x16.png">
        </head>
        <body>
        <div>
            <div class="w-50 fl"><h2 class="title">Deposits</h2></div>
            <div class="w-50 fr"><a style="float: right" href="user.php">Users</a></div>
            <div class="clear-both"></div>
            <form method="post">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?= date('Y-m-01') ?>">

                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?= date('Y-m-d') ?>">
                <input type="hidden" id="user_id" value="<?= $_REQUEST['user_id'] ?? '' ?>">

                <button type="submit" name="apply-filters" id="apply-filters">Apply Filters</button>
                <button type="submit" name="reset-filters" id="reset-filters" class="red">Reset Filters</button>
                <button type="submit" name="download" id="export_excel" style="float: right">Download XLS</button>
            </form>
            <table class="w-full" id="dataEncrichment">
                <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Payment method</th>
                    <th>Timestamp</th>
                    <th>Amount</th>
                    <th>First Deposit</th>
                    <th>User Id</th>
                    <th>GTM Enabled</th>
                    <th>GTM Blocked</th>
                    <th>GA Client Id</th>
                    <th>B-Tag</th>
                    <th>Browser</th>
                    <th>Device</th>
                    <th>Traffic Source</th>
                    <th>Deposit Country</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>Transaction ID</th>
                    <th>Payment method</th>
                    <th>Timestamp</th>
                    <th>Amount</th>
                    <th>First Deposit</th>
                    <th>User Id</th>
                    <th>GTM Enabled</th>
                    <th>GTM Blocked</th>
                    <th>GA Client Id</th>
                    <th>B-Tag</th>
                    <th>Browser</th>
                    <th>Device</th>
                    <th>Traffic Source</th>
                    <th>Deposit Country</th>
                </tr>
                </tfoot>
            </table>
        </div>
        </body>
        </html>
        <?php
    }

    /**
     * Initialize function
     *
     * @return void
     */
    public function init(): void
    {
        if($_REQUEST['ajax_call']) {
            $this->getData();
            die;
        }

        if (isset($_REQUEST['download']) && !isset($_REQUEST['error'])) {
            $earlier = new DateTime($this->startDate);
            $later = new DateTime($this->endDate);

            $daysToExport = $later->diff($earlier)->format("%a");

            if ($daysToExport < DataCommon::exportDaysLimit) {
                $this->processDownload();
            }
        }

        $this->css();
        $this->html();
        $this->js();
    }

    /**
     * getData function to get data from database
     *
     * @param bool $return
     * @return array $usersData if $return is true
     * @return json $datatable if $return is false
     */
    public function getData($return = false)
    {
        $andWhere = [];
        if ($this->startDate) {
            $andWhere[] = "d.timestamp >= '{$this->startDate} 00:00:01'";
        }

        if ($this->endDate) {
            $andWhere[] = "d.timestamp <= '{$this->endDate} 23:59:59'";
        }

        $userId = $_REQUEST['user_id'] ?? false;
        if ($userId) {
            $andWhere[] = "d.user_id = {$userId}";
        }

        $search = $_REQUEST['search']['value'] ?? false;
        if ($search) {
            $search = trim($search);
            $andWhere[] = "d.id LIKE '{$search}%'";
        }

        if ($andWhere) {
            $andWhere = 'WHERE ' .implode(' AND ', $andWhere);
        } else {
            $andWhere = '';
        }

        $pagination = '';
        if ($this->length > 0 && !$return) {
            $pagination = "LIMIT {$this->length} OFFSET {$this->start}";
        }

        $query = "
            SELECT
                d.timestamp,
                a.browser,
                a.device,
                a.traffic_source,
                a.country_in,
                a.btag,
                d.id,
                d.display_name,
                d.dep_type,
                a.ga_cookie_id,
                a.is_gtm_enabled,
                a.is_gtm_blocked,
                d.user_id,
                d.currency,
                d.amount,
                fd.deposit_id AS first_deposit_id
            FROM deposits d
            LEFT JOIN analytics a ON a.model_id = d.id AND a.model = 'deposits'
            LEFT JOIN first_deposits fd ON d.id = fd.deposit_id
            {$andWhere}
            GROUP BY d.id
            ORDER BY d.id DESC
            {$pagination}
        ";

        $depositData = $this->sql->shs('merge')->loadArray($query);
        $countQuery = "
            SELECT
                count(*) AS total
            FROM deposits d
            LEFT JOIN analytics a ON a.model_id = d.id AND a.model = 'deposits'
            LEFT JOIN first_deposits fd ON d.id = fd.deposit_id
            {$andWhere}
            ORDER BY d.id DESC
        ";
        $totalDepositData = current($this->sql->shs('sum')->loadArray($countQuery))['total'];

        $datatable = [
            'draw' => $_REQUEST['draw'],
            'recordsTotal' => $totalDepositData,
            'recordsFiltered' => $totalDepositData,
            'data' => $depositData,
            'start' => $this->start,
            'length' => $this->length,
            'q' => $query
        ];

        if ($return) {
            return $depositData;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datatable);
        die;
    }

    /**
     * Function to process download and export file to XLS
     *
     * @return void
     */
    public function processDownload()
    {
        $config = $this->fileSettings('_deposits_');
        $userData = $this->getData(true);
        $excelFilePath = tempnam(sys_get_temp_dir(), $config['platform'] . $config['file_name'] . $this->startDate.'_to_'.$this->endDate.'_') . '.xlsx';
        $spreadsheet = $this->exportToXls($userData, $excelFilePath);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . urlencode($excelFilePath) . '"');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    /**
     * Function to export components to XLS
     *
     * @param array $userData
     * @param string $excelFilePath
     * @return Spreadsheet
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportToXls(array $userData, string $excelFilePath): object
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Transaction ID',
            'Payment method',
            'Timestamp',
            'Amount',
            'First Deposit',
            'User Id',
            'GTM Enabled',
            'GTM Blocked',
            'Client Id',
            'B-Tag',
            'Browser',
            'Device',
            'Traffic Source',
            'Deposit Country'
        ];

        $sheet->getStyle('A1:N1')->applyFromArray($this->xlsHeaderStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->fromArray($headers);

        $row = 2;
        foreach ($userData as $user) {

            $data = [
                $user['id'],
                $user['display_name']. ' - ' .$user['dep_type'],
                $user['timestamp'] ? date("d/m/Y H:i:s", strtotime($user['timestamp'])) : '',
                $user['amount'],
                $user['first_deposit_id'] ? 'Yes' : 'No',
                $user['user_id'],
                !is_null($user['is_gtm_enabled']) ? ($user['is_gtm_enabled'] == 1 ? 'Yes' : 'No') : '—',
                !is_null($user['is_gtm_blocked']) ? ($user['is_gtm_blocked'] == 1 ? 'Yes' : 'No') : '—',
                !is_null($user['ga_cookie_id']) ? $user['ga_cookie_id'] : '—',
                !is_null($user['btag']) ? $user['btag'] : '—',
                !is_null($user['browser']) ? $user['browser'] : '—',
                !is_null($user['device']) ? $user['device'] : '—',
                !is_null($user['traffic_source']) ? $user['traffic_source'] : '—',
                !is_null($user['country_in']) ? $user['country_in'] : '—',
            ];

            $sheet->fromArray($data, null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'N') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($excelFilePath);

        return $spreadsheet;
    }
}

$dataEnrichment = new DataEnrichment();
$dataEnrichment->init();
