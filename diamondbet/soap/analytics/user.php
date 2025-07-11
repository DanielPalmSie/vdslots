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
        $this->sql          = Phive("SQL");
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
                        url: 'user.php?ajax_call=true',
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
                        { data: function(a) {
                                return '<a title="View user\'s all payment done" href="index.php?user_id='+a.id+'">'+a.id+'</a>'
                            } },
                        { data: function(a) {
                                return new Date(a.register_date).toLocaleDateString('en-GB');
                            }
                        },
                        { data: function(a) {
                                if (a.total_deposits > 0) {
                                    return '<a title="View all payment done" href="index.php?user_id=' + a.id + '">' + a.total_deposits + '</a>';
                                }
                                return a.total_deposits;
                            }
                        },
                        { data: function(a) {
                                let gtm_enabled = a.is_gtm_enabled;
                                if (a.registration_in_progress && a.registration_in_progress != 3) {
                                    gtm_enabled = a.pr_is_gtm_enabled;
                                }
                                if (gtm_enabled == null) {
                                    return "&mdash;";
                                }else if (parseInt(gtm_enabled)) {
                                    return 'Yes';
                                } else {
                                    return 'No';
                                }
                            } },
                        { data: function(a) {
                                let gtm_blocked = a.is_gtm_blocked;
                                if (a.registration_in_progress && a.registration_in_progress != 3) {
                                    gtm_blocked = a.pr_is_gtm_blocked;
                                }

                                if (gtm_blocked == null) {
                                    return "&mdash;";
                                }else if (parseInt(gtm_blocked)) {
                                    return 'Yes';
                                } else {
                                    return 'No';
                                }
                            } },
                        { data: function (a) {
                                let ga_cookie_id = a.r_ga_cookie_id;
                                if (a.registration_in_progress && a.registration_in_progress != 3) {
                                    ga_cookie_id = a.pr_ga_cookie_id;
                                }

                                if (ga_cookie_id == null) {
                                    return '&mdash;';
                                }
                                return ga_cookie_id;
                            } },
                        { data: function (a) {
                                let btag = a.r_btag;
                                if (a.registration_in_progress && a.registration_in_progress != 3) {
                                    btag = a.pr_btag;
                                }

                                if (btag == null) {
                                    return '&mdash;';
                                }
                                return btag;
                            } },
                        { data: function (a) {
                                let browser = a.r_browser;
                                if (a.registration_in_progress && a.registration_in_progress != 3) {
                                    browser = a.pr_browser;
                                }
                                if (browser == null) {
                                    return '&mdash;';
                                }

                                return browser;
                            } },
                        { data: function (a) {
                                let device = a.r_device;
                                if (a.registration_in_progress && a.registration_in_progress != 3) {
                                    device = a.pr_device;
                                }
                                if (device == null) {
                                    return '&mdash;';
                                }

                                return device;
                            } },
                        { data: function(a) {
                                if (a.nationality == null) {
                                    return '&mdash;';
                                }

                                return a.nationality;
                            }
                        },
                        { data: function(a) {
                                if (a.country_in == null) {
                                    return '&mdash;';
                                }

                                return a.country_in;
                            }
                        },
                        { data: function(a) {
                                if (a.registration_in_progress && a.registration_in_progress != 3) {
                                    return 'Yes';
                                }
                                return 'No';
                            }
                        },
                        { data: function(a) {
                                if (a.registration_end_date) {
                                    return 'Yes';
                                }
                                return 'No';
                            }
                        },
                        { data: function(a) {
                                if (a.registration_end_date) {
                                    return new Date(a.registration_end_date).toLocaleString('en-GB');
                                }
                                return '';
                            }
                        }
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
            <title>Users</title>
            <link rel="icon" type="image/png" sizes="16x16" href="/diamondbet/images/<?= brandedCss() ?>mobile/favicon-16x16.png">
        </head>
        <body>
        <div>
            <div class="w-50 fl"><h2 class="title">Users</h2></div>
            <div class="w-50 fr"><a style="float: right" href="index.php">Deposits</a></div>
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
                    <th>User Id</th>
                    <th>Register Date</th>
                    <th>Total Deposits</th>
                    <th>GTM Enabled</th>
                    <th>GTM Blocked</th>
                    <th>GA Client Id</th>
                    <th>B-Tag</th>
                    <th>Browser</th>
                    <th>Device</th>
                    <th>Nationality</th>
                    <th>Registered Country</th>
                    <th>Partial Registrations</th>
                    <th>Completed Registrations</th>
                    <th>Completed Registrations Date</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>User Id</th>
                    <th>Register Date</th>
                    <th>Total Deposits</th>
                    <th>GTM Enabled</th>
                    <th>GTM Blocked</th>
                    <th>GA Client Id</th>
                    <th>B-Tag</th>
                    <th>Browser</th>
                    <th>Device</th>
                    <th>Nationality</th>
                    <th>Registered Country</th>
                    <th>Partial Registrations</th>
                    <th>Completed Registrations</th>
                    <th>Completed Registrations Date</th>
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

        if (isset($_POST['download'])) {
            $earlier = new DateTime($this->startDate);
            $later = new DateTime($this->endDate);

            $daysToExport = $later->diff($earlier)->format("%a");

            if ($daysToExport < DataCommon::exportDaysLimit) {
                $this->processDownload();
                die;
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
        $userId = $_REQUEST['user_id'] ?? false;

        $andWhere = [];
        if ($this->startDate && !$userId) {
            $andWhere[] = "u.register_date >= '{$this->startDate}'";
        }

        if ($this->endDate && !$userId) {
            $andWhere[] = "u.register_date <= '{$this->endDate}'";
        }

        if ($userId) {
            $andWhere[] = "u.id = {$userId}";
        }

        $search = $_REQUEST['search']['value'] ?? false;
        if ($search) {
            $search = trim($search);
            $andWhere[] = "(u.id LIKE '{$search}%' OR u.email = '%{$search}%')";
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
                u.id,
                u.email,
                u.register_date,
                us.value AS registration_in_progress,
                us1.value AS registration_end_date,
                us2.value AS nationality,
                us3.value AS sms_code_verified,
                us4.value AS email_code_verified,
                us5.value AS uagent,
                a.id AS pr_id,
                a.browser AS pr_browser,
                a.device AS pr_device,
                a.traffic_source AS pr_traffic_source,
                a.country_in,
                a.is_gtm_enabled AS pr_is_gtm_enabled,
                a.is_gtm_blocked AS pr_is_gtm_blocked,
                a.ga_cookie_id AS pr_ga_cookie_id,
                a.btag AS pr_btag,
                a1.id AS r_id,
                a1.browser AS r_browser,
                a1.device AS r_device,
                a1.traffic_source AS r_traffic_source,
                a1.is_gtm_enabled,
                a1.is_gtm_blocked,
                a1.ga_cookie_id AS r_ga_cookie_id,
                a1.btag AS r_btag,
                (SELECT count(d.id) FROM deposits d WHERE d.user_id = u.id) AS total_deposits
            FROM
                users u
            LEFT JOIN users_settings us ON u.id = us.user_id AND us.setting = 'registration_in_progress'
            LEFT JOIN users_settings us1 ON	u.id = us1.user_id AND us1.setting = 'registration_end_date'
            LEFT JOIN users_settings us2 ON	u.id = us2.user_id AND us2.setting = 'nationality'
            LEFT JOIN users_settings us3 ON u.id = us3.user_id AND us3.setting = 'sms_code_verified'
            LEFT JOIN users_settings us4 ON	u.id = us4.user_id AND us4.setting = 'email_code_verified'
            LEFT JOIN users_settings us5 ON	u.id = us5.user_id AND us5.setting = 'uagent'
            LEFT JOIN analytics a ON u.id = a.model_id AND a.model = 'users' AND a.slug = 'partially-registered'
            LEFT JOIN analytics a1 ON u.id = a1.model_id AND a1.model = 'users' AND a1.slug = 'registered'
            {$andWhere}
            GROUP BY u.id
            ORDER BY u.id DESC
            {$pagination}
        ";

        $usersData = $this->sql->shs('merge')->loadArray($query);

        array_walk($usersData, function(&$ud){
            if (empty($ud['r_browser']) && !empty($ud['uagent'])) {
                $user = cu($ud['id']);
                $ud['uagent'] = $user->getBrowser(false, $ud['uagent']);
            }
        });

        $countQuery = "
            SELECT
                COUNT(*) AS total
            FROM
                users u
            LEFT JOIN users_settings us ON u.id = us.user_id AND us.setting = 'registration_in_progress'
            LEFT JOIN users_settings us1 ON	u.id = us1.user_id AND us1.setting = 'registration_end_date'
            LEFT JOIN users_settings us2 ON	u.id = us2.user_id AND us2.setting = 'nationality'
            LEFT JOIN users_settings us3 ON u.id = us3.user_id AND us3.setting = 'sms_code_verified'
            LEFT JOIN users_settings us4 ON	u.id = us4.user_id AND us4.setting = 'email_code_verified'
            LEFT JOIN users_settings us5 ON	u.id = us5.user_id AND us5.setting = 'uagent'
            LEFT JOIN analytics a ON u.id = a.model_id AND a.model = 'users' AND a.slug = 'partially-registered'
            LEFT JOIN analytics a1 ON u.id = a1.model_id AND a1.model = 'users' AND a1.slug = 'registered'
            {$andWhere}
            ORDER BY u.id DESC
        ";

        $totalDepositData = current($this->sql->shs('sum')->loadArray($countQuery))['total'];

        $datatable = [
            'draw' => $_REQUEST['draw'],
            'recordsTotal' => $totalDepositData ?? 0,
            'recordsFiltered' => $totalDepositData ?? 0,
            'data' => $usersData,
            'start' => $this->start,
            'length' => $this->length,
            'q' => $query
        ];

        if ($return) {
            return $usersData;
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
        $config = $this->fileSettings('_users_');
        $userData = $this->getData(true);
        $excelFilePath = tempnam(sys_get_temp_dir(), $config['platform'] . $config['file_name']. $this->startDate.'_to_'.$this->endDate.'_') . '.xlsx';
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
            'User Id',
            'Register Date',
            'Total Deposits',
            'GTM Enabled',
            'GTM Blocked',
            'Client Id',
            'B-Tag',
            'Browser',
            'Device',
            'Nationality',
            'Registered Country',
            'Partial Registrations',
            'Completed Registrations',
            'Completed Registrations Date',
        ];

        $sheet->getStyle('A1:N1')->applyFromArray($this->xlsHeaderStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->fromArray($headers);

        $row = 2;
        foreach ($userData as $user) {

            $gtm_enabled = !is_null($user['is_gtm_enabled']) ? ($user['is_gtm_enabled'] == 1 ? 'Yes' : 'No') : '—';
            $gtm_blocked = !is_null($user['is_gtm_blocked']) ? ($user['is_gtm_blocked'] == 1 ? 'Yes' : 'No') : '—';
            $ga_cookie_id = !is_null($user['r_ga_cookie_id']) ? ($user['r_ga_cookie_id'] ?? '') : '—';
            $browser = !is_null($user['r_browser']) ? ($user['r_browser'] ?? $user['uagent']) : '—';
            $device = !is_null($user['r_device']) ? ($user['r_device'] ?? '') : '—';
            $btag = !is_null($user['r_btag']) ? ($user['r_btag'] ?? '') : '—';
            if ($user['registration_in_progress'] && $user['registration_in_progress'] !== 3) {
                $gtm_enabled = !is_null($user['pr_is_gtm_enabled']) ? ($user['pr_is_gtm_enabled'] == 1 ? 'Yes' : 'No') : '—';
                $gtm_blocked = !is_null($user['pr_is_gtm_blocked']) ? ($user['pr_is_gtm_blocked'] == 1 ? 'Yes' : 'No') : '—';
                $ga_cookie_id = !is_null($user['pr_ga_cookie_id']) ? ($user['pr_ga_cookie_id'] ?? '') : '—';
                $browser = !is_null($user['pr_browser']) ? ($user['pr_browser'] ?? $user['uagent']) : '—';
                $device = !is_null($user['pr_device']) ? ($user['pr_device'] ?? '') : '—';
                $btag = !is_null($user['pr_btag']) ? ($user['pr_btag'] ?? '') : '—';
            }

            $data = [
                $user['id'],
                $user['register_date'] ? date("d/m/Y", strtotime($user['register_date'])) : '',
                $user['total_deposits'] ?? 0,
                $gtm_enabled,
                $gtm_blocked,
                $ga_cookie_id,
                $btag,
                $browser,
                $device,
                $user['nationality'] ?? '—',
                $user['country_in'] ?? '—',
                $user['registration_in_progress'] ? 'Yes' : 'No',
                $user['registration_end_date'] ? 'Yes' : 'No',
                $user['registration_end_date'] ? date("d/m/Y H:i:s", strtotime($user['registration_end_date'])) : '',
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
