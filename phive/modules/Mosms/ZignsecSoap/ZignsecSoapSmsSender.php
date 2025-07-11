<?php

namespace Mosms\ZignsecSoap;

require_once __DIR__ . '/../../Raker/Parser.php';

use Mosms\MosmsLoggerTrait;
use Mosms\SmsResult;
use Mosms\SmsSenderInterface;
use Parser;

/**
 * Class to send Sms via legacy Zignsec SOAP service
 *
 * @link https://api.zignsec.com/v2/ZignSecWebService.asmx
 */
class ZignsecSoapSmsSender implements SmsSenderInterface
{
    use MosmsLoggerTrait;

    private array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function sendSms(string $country_code, string $mobile, string $mobile_full, string $message): SmsResult
    {
        $body = $this->getBody($country_code, $mobile, $message);

        $result = $this->getSmsResult(
            $this->zSsend('SendOTPCode', $body)
        );

        $this->logRequest($body, $result);

        return $result;
    }

    /**
     * Function that send the SMS via ZignSec API.
     *
     * @param string $func
     * @param string $body
     * @return array
     */
    private function zSsend(string $func, string $body): array
    {
        $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
        <soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">
         <soap:Body>
           <$func xmlns=\"http://api.zignsec.com/\">
             <request>
                 <Auth>
                       <MerchantId>{$this->settings['merchant_id']}</MerchantId>
                       <Username>{$this->settings['username']}</Username>
                     <Password>{$this->settings['password']}</Password>
                 </Auth>
                 $body
             </request>
           </$func>
         </soap:Body>
        </soap:Envelope>";

        $parser = new Parser();
        $headers = array("SOAPAction: http://api.zignsec.com/$func");

        $res = phive()->post($this->settings['url'], $xml, 'text/xml', $headers, 'mosms', 'POST', 120);

        $response_body = $res;
        $response_status_code = $parser->setDom($res)->domTag('Error')->domTag('Code')->content[0];

        return [$response_body, $response_status_code];
    }

    private function getSmsResult(array $response): ZignsecSoapSmsResult
    {
        [$response_body, $response_status_code] = $response;

        return new ZignsecSoapSmsResult($response_body, $response_status_code);
    }

    /**
     * Generate the body for the SMS, contains all the info we need to send the MSG to the player.
     *
     * @param string $country_code - calling_code (country specific)
     * @param string $mobile - without ccode
     * @param string $msg - content of the SMS
     * @param int $code - code that can be used in alternative way of sending OTP messages using {0} placeholder
     *                    (see details https://docs.zignsec.com/api/v2/api-sms/)
     *
     * @return string
     */
    private function getBody(string $country_code, string $mobile, string $msg, int $code = 1): string
    {
        $exp_date = date('c', time() + 30);
        $msg = htmlspecialchars($msg);
        $body = "<CountryCode>$country_code</CountryCode>
            <Mobile>$mobile</Mobile>
            <MobileIncluded>true</MobileIncluded>
            <UserId>00000000-0000-0000-0000-000000000000</UserId>
            <UserIdIncluded>false</UserIdIncluded>
            <CustomOTPCode>$code</CustomOTPCode>
            <SmsConfig>
                <ExpiryDate>$exp_date</ExpiryDate>
                <MaxAttempts>{$this->settings['sms_attempts']}</MaxAttempts>
                <CustomSmsText>$msg</CustomSmsText>
                <CustomSender>{$this->settings['from']}</CustomSender>
            </SmsConfig>";

        return $body;
    }

    private function logRequest(string $body, SmsResult $result, string $action = 'SMS send'): void
    {
        $this->log("$action request (ZignsecSoapSmsSender)", [
            'payload' => $body,
            'response' => $result->getResponseBody(),
            'response_status_code' => $result->getResponseStatus(),
        ], 'debug');

        if ($result->isFailure()) {
            $this->log("Error during $action request (ZignsecSoapSmsSender)", [
                'payload' => $body,
                'response' => $result->getResponseBody(),
                'response_status_code' => $result->getResponseStatus(),
            ], 'error');
        }
    }
}
