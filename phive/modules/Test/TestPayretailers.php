<?php
require_once 'TestCasinoCashier.php';
class TestPayretailers extends TestCasinoCashier{

    function __construct(){
        parent::__construct();
        $this->mts->setSupplier('payretailers');
        $this->db->truncate('trans_log');
    }

    function notification($mts_tr, $u, $state = "APPROVED"){
        $id_key = $mts_tr['type'] == 1 ? 'externalReference' : 'trackingId';
        $params = [
            $id_key  => $mts_tr['id'],
            'status' => $state
        ];

        if((int)$mts_tr['type'] === 1 && $state != 'APPROVED'){
            // A failed withdrawal
            $params['statusTypeCode'] = 'ERROR';
        } else {
            // A successful withdrawal
            $params['statusTypeCode'] = 'FINISHED';
        }
        
        $url = $this->mts_base_url."user/transfer/deposit/confirm/?supplier=payretailers";
        $headers = ['Authorization: Basic ' . base64_encode('12346082:28e1bd0aa8b172a96c4b8e0df25dd4fd892332f5d301d505f7bed9b902ee2209')];

        $res = phive()->post($url, $params, 'application/json', $headers, 'mts-payretailers-notification');
        print_r(['notification_result' => $res]);
    }

}
