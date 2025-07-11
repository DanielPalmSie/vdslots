<?php


/**
 *
 */
class Monitor extends PhModule
{
    /** @var array $config */
    protected $config;

    protected array $metrics = [];

    /**
     * Get Jurisdictions
     * @return mixed|string
     */
    protected function getJurisdictions()
    {
        return phive('Licensed')->getSetting('country_by_jurisdiction_map');
    }

    /**
     * Monitor constructor.
     */
    public function __construct()
    {
        $this->config = phive('Distributed')->getSetting('monitoring');
    }

    /**
     * Executes actions based on what is passed on metrics
     *
     * @param array $params
     * @return mixed
     */
    public function exec(array $params)
    {
        if (empty($params['metric']) || method_exists($this, $params['metric'])) {
            try {
                $result = $this->{$params['metric']}($params);
                $status = $this->getStatus('1');
                if (empty($result)) {
                    return $status;
                } else {
                    return $status . PHP_EOL . implode(PHP_EOL, $result);
                }
            } catch (Exception $e) {
                error_log("Monitor error: ". $e->getMessage());
                return $this->getStatus('0');
            }
        }

        die('Metric not supported');
    }

    /**
     * We check the IP of the source machine, if there is no whitelisting we don't allow until at least 1 IP is set
     */
    public function validateIP()
    {
        if (empty($this->config['ips']) || !in_array(remIp(), $this->config['ips'])) {
            die(remIp() . 'IP not allowed');
        }
    }

    /**
     * Get date for registration KPIs like new registrations, completed and first time depositors
     *
     * Format is new_registrations{country="DE", brand="VS"} 10
     *
     * @param $params
     *
     * @return array
     * @throws Exception
     */
    public function registration($params): array
    {

        if (empty($params['start']) || empty($params['end'])) {
            $start = (new DateTime("-1 hour"))->format('Y-m-d H:00:00');
            $end = (new DateTime("-1 hour"))->format('Y-m-d H:59:59');
        } else {
            $start = $params['start'];
            $end = $params['end'];
        }

        if (abs(round((strtotime($end) - strtotime($start))/3600, 1)) > 2) {
            throw new Exception("Max is 2 hours time difference for the registration monitor");
        }

        $doRegistrationQuery = function ($where) use ($start, $end) {
            return phive('SQL')->shs()->loadArray("SELECT a.target, u.country
                        FROM actions a
                         LEFT JOIN users u ON u.id = a.target
                        WHERE {$where}
                          AND a.created_at BETWEEN '{$start}' AND '{$end}';");
        };

        $metrics_result = [];

        $started = $doRegistrationQuery("tag = 'registration_in_progress' AND descr LIKE '%set registration_in_progress to 1%'");

        $metrics_result = array_merge($metrics_result, $this->prepareResult($started, 'new_registrations', 'country'));

        $completed = $doRegistrationQuery("a.tag = 'registration'");

        $metrics_result = array_merge($metrics_result,
            $this->prepareResult($completed, 'completed_registrations', 'country'));

        $first_deposits = phive('SQL')->shs()->loadArray("SELECT fd.user_id, u.country 
                                FROM first_deposits as fd
                                LEFT JOIN users u ON u.id = fd.user_id
                                WHERE fd.timestamp BETWEEN '{$start}' AND '{$end}'");

        $metrics_result = array_merge($metrics_result,
            $this->prepareResult($first_deposits, 'first_deposit', 'country'));

        return $metrics_result;
    }

    /**
     * Get Data for Customer Logins KPI per country / jurisdiction
     *
     * Format returned
     * unique_logins{country="SE", brand=""} 3
     * jurisdiction_logins{jurisdiction="MGA", brand=""} 22306
     * @param $params
     * @return array
     */
    public function login($params)
    {
        $metric_result = [];

        if (empty($params['start']) || empty($params['end'])) {
            $start = phive()->hisNow('-15 min');
            $end = phive()->hisNow();
        } else {
            $start = $params['start'];
            $end = $params['end'];
        }

        $logins =  phive('SQL')->readOnly()->shs()->loadArray($this->getLoginQuery($start, $end));
        //country results
        $metric_result = array_merge($metric_result, $this->prepareResult($logins, 'unique_logins', 'country'));

        //jurisdiction results
        $jurisdictions = $this->getJurisdictions();
        $jur_results = array_fill_keys($jurisdictions, 0);
        foreach($logins as $login){
            $jur = $jurisdictions[$login['country']] ?? $jurisdictions['default']; //check jurisdiction for the country, or the default one
            $jur_results[$jur]++;
        }
        $metric_result = array_merge($metric_result, $this->prepareAggregated($jur_results, 'jurisdiction_logins', 'jurisdiction'));

        return $metric_result;
    }

    /**
     * Get login Query
     *
     * @param $start
     * @param $end
     * @return string
     */
    private function getLoginQuery($start, $end)
    {
        return "SELECT
                us.user_id, u.country
                FROM users u
                INNER JOIN users_sessions us ON u.id = us.user_id
                WHERE
                us.created_at BETWEEN '{$start}' AND '{$end}';";
    }
    
    /**
     * Get Data for Customer documents submitted
     *
     * Format returned
     * @param array $params
     * @return array
     */
    public function document(array $params = []): array
    {
        if (empty($params['start']) || empty($params['end'])) {
            $start = phive()->hisNow('-15 min');
            $end = phive()->hisNow();
        } else {
            $start = $params['start'];
            $end = $params['end'];
        }

        $documents =  phive('SQL')->doDb('dmapi')->loadArray($this->getDocumentQuery($start, $end));
        return $this->prepareResult($documents, 'documents', 'type');
    }

    /**
     * Get document Query
     * @param string $start
     * @param string $end
     * @return string
     */
    private function getDocumentQuery(string $start, string $end): string
    {
        return "SELECT
                user_id, tag as type
                FROM
                    documents d
                WHERE
                    d.created_at BETWEEN '{$start}' AND '{$end}';";
    }

    /**
     * Prepares the result to be in the prometheus format
     *
     * @param array $query_res
     * @param string $metric_key
     * @param string $metric_sub_key
     * @return array
     */
    protected function prepareResult(array $query_res, string $metric_key, string $metric_sub_key): array
    {
        $data = [];
        foreach ($query_res as $row) {
            $data[$row[$metric_sub_key]]++;
        }

        return $this->prepareAggregated($data, $metric_key, $metric_sub_key);
    }

    /**
     * Prepares the aggregated result to be in the prometheus format
     *
     * @param array $data
     * @param string $metric_key
     * @param string $metric_sub_key
     * @return array
     */
    private function prepareAggregated(array $data, string $metric_key, string $metric_sub_key)
    {
        $res = [];
        foreach ($data as $k => $v) {
            $res[] = $metric_key . '{' . $metric_sub_key . '="' . $k . '", brand="' . $this->config['brand'] . '"} ' . $v;
        }
        return $res;
    }

    /**
     * @param string $status
     * @return string
     */
    private function getStatus($status): string
    {
        return "monitor_status_registration_metrics_". $this->config['brand']  ." {$status}";
    }

    /**
     * Parse the response
     *
     * @return string
     */
    protected function parseResponseWithMultipleLabels(): string
    {
        $metrics_response = [];

        foreach ($this->metrics as $metric_name => $metric_values) {
            if(isset($metric_values['sub_labels'])) {
                $metrics_response[] = $this->convertResponseFormat($metric_name, $metric_values);
            } else {
                foreach($metric_values as $value) {
                    $metrics_response[] = $this->convertResponseFormat($metric_name, $value);
                }
            }
        }

        return implode(PHP_EOL, $metrics_response);
    }

    /**
     * Convert the response to Prometheus/Grafana format
     *
     * @param string $metric_name
     * @param array $metric_value
     * @return string
     */
    protected function convertResponseFormat(string $metric_name, array $metric_value): string
    {
        $sub_labels = array_map(function ($key, $value) {
            return $key . '="' . $value . '"';
        }, array_keys($metric_value['sub_labels']), array_values($metric_value['sub_labels']));
        $sub_labels = implode(',', $sub_labels);

        return $metric_name . " {" . $sub_labels . "} " . $metric_value['value'];
    }
}