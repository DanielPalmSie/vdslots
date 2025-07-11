<?php


class ZignSecV6 extends ZignSec
{
    public function __construct()
    {
        $this->config = phive('DBUserHandler')->getSetting('zignsec_v6');
    }

    public function auth()
    {
        $url = "https://appapi2.test.bankid.com/rp/v6.0/auth";
        $certPath = "/var/www/videoslots/phive/modules/DBUserHandler/ZignSecTestCerts/FPTestcert4_20230629.pem";
        $caCertPath = "/var/www/videoslots/phive/modules/DBUserHandler/ZignSecTestCerts/issuer.pem";


        $data = array(
            "endUserIp" => "194.168.2.25"
        );

        $headers = array(
            "Content-Type: application/json"
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
        curl_setopt($ch, CURLOPT_CAINFO, $caCertPath);

        // Display cURL request details before execution
        echo "cURL Request Details:\n";
        echo "URL: $url\n";
        echo "Method: POST\n";
        echo "Headers: " . implode(", ", $headers) . "\n";
        echo "SSL Certificate: $certPath\n";
        echo "CA Certificate: $caCertPath\n";
        echo "Data: " . json_encode($data) . "\n";

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        }

        curl_close($ch);

        return $response;
    }
}
