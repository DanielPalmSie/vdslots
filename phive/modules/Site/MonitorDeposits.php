<?php

require_once __DIR__ . '/Monitor.php';

/**
 *
 */
class MonitorDeposits extends Monitor
{
    private const PARAM_TIME_WINDOW = 'time_window';
    private const PARAM_JURISDICTION = 'jurisdiction';
    private const PARAM_PSP = 'psp';

    private SQL $sql;

    public function __construct()
    {
        $this->sql = phive('SQL');
    }

    /**
     * Get Jurisdictions
     * @return mixed|string
     */
    protected function getJurisdictions()
    {
        return phive('Licensed')->getSetting('country_by_jurisdiction_map');
    }

    /**
     * Get Psps
     * @return mixed|string
     */
    protected function getPsps()
    {
        $allPsps = Phive('CasinoCashier')->getSetting('psp_config_2');
        $cardPsps = Phive('CasinoCashier')->getSetting('ccard_psps');
        return array_keys(array_merge($allPsps,$cardPsps));
    }

    /**
     * Get deposit KPIs like total number of deposits and sum of deposits
     *
     * Format is deposits_sum{market="MGA", psp="Worldpay"} 1000000
     * Format is deposits_count{market="MGA", psp="Worldpay"} 1000
     *
     * @param array $params
     *
     * @return array
     * @throws Exception
     */
    public function getDepositsData(array $params): string
    {
        list($queryArgs, $errorArray) = $this->validateParams($params);
        if($errorArray) {
            return [
                'status' => 'error',
                'messages' => $errorArray
            ];
        }

        $queryResult = $this->sql->readOnly()->shs()->loadArray("
            SELECT d.dep_type, count(d.amount) as total
            FROM deposits d
            INNER JOIN users u ON d.user_id = u.id
            WHERE {$this->parseSqlPredicates($queryArgs)}
            GROUP BY d.dep_type;
        ");

        return implode(PHP_EOL, $this->parseResponse($queryArgs[self::PARAM_JURISDICTION], $queryResult));
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    private function validateParams(array $params): array
    {
        $validated_params = [];
        $error_array = [];

        /**Validate time_window parameter**/
        $time_window = (int)$params[self::PARAM_TIME_WINDOW];
        if (empty($time_window)) {
            $validated_params[self::PARAM_TIME_WINDOW] = (new DateTime("-15 minutes"))->format('Y-m-d H:i:s');
        } elseif ($time_window <= 1440 && $time_window > 0) {
            $validated_params[self::PARAM_TIME_WINDOW] = (new DateTime("-{$time_window} minutes"))->format('Y-m-d H:i:s');
        } else {
            $error_array[] = 'A time window of maximum 24 hours (1440 minutes) is allowed';
           // throw new Exception("A time window of maximum 24 hours (1440 minutes) is allowed");
        }

        /**Validate market/jurisdiction parameter**/
        if (empty($params[self::PARAM_JURISDICTION])) {
            $validated_params[self::PARAM_JURISDICTION] = 'all';
        } elseif (in_array($params[self::PARAM_JURISDICTION], $this->getJurisdictions())) {
            $validated_params[self::PARAM_JURISDICTION] = $params[self::PARAM_JURISDICTION];
        } else {
            $error_array[] = 'Jurisdiction is not supported';
           // throw new Exception("Jurisdiction is not supported");
        }

        /**Validate PSP parameter**/
        if (empty($params[self::PARAM_PSP])) {
            $validated_params[self::PARAM_PSP] = 'all';
        }
        elseif (in_array($params[self::PARAM_PSP], $this->getPsps())) {
            $validated_params[self::PARAM_PSP] = $params[self::PARAM_PSP];
        } else {
            $error_array[] = 'PSP is not supported';
            // throw new Exception("PSP is not supported");
        }

        return [$validated_params, $error_array];
    }

    private function parseSqlPredicates(array $queryParams): string
    {
        $predicates = "d.timestamp >= '{$queryParams[self::PARAM_TIME_WINDOW]}'";

        if ($queryParams[self::PARAM_JURISDICTION] !== 'all') {
            if($queryParams[self::PARAM_JURISDICTION] === 'MGA') {
                $allMarkets= $this->getJurisdictions();
                unset($allMarkets['default']);
                $allMarkets = $this->sql->makeIn(array_keys($allMarkets));
                $predicates .= " AND u.country NOT IN ({$allMarkets})";
            } else {
                $country = array_flip($this->getJurisdictions())[$queryParams[self::PARAM_JURISDICTION]];
                $predicates .= " AND u.country = '{$country}'";
            }
        }

        if ($queryParams[self::PARAM_PSP] !== 'all') {
            $predicates .= " AND d.dep_type = '{$queryParams['psp']}'";
        }

        return $predicates;
    }

    /**
     * @param string $jurisdiction
     * @param array $queryResult
     * @return array
     */
    private function parseResponse(string $jurisdiction, array $query_results): array
    {
        $response = $formatted_results =  $total = [];

        foreach($query_results as $psp) {
            $formatted_results[$psp['dep_type']] = [
                'total' => $total[$psp['dep_type']] = ($total[$psp['dep_type']] ?? 0) + $psp['total'],
            ];
        }

        foreach($formatted_results as $dep_type => $result) {
            $response[] = 'deposits_count' . '{jurisdiction="'. $jurisdiction .'" ,psp="'. $dep_type .'"} ' . $result['total'];
        }

        return $response;
    }
}