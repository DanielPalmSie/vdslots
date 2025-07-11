<?php

use Videoslots\Mts\MtsClient;
use GuzzleHttp\Exception\BadResponseException;
use Videoslots\Mts\Url\UrlHandler;

class SelectAccountService
{
    private $supplier;
    private $userObj;

    public function __construct($supplier)
    {
        $this->supplier = $supplier;
        $this->userObj = cuPl();
    }

    public function selectAccount()
    {
        try {
            $redirectUrls = new UrlHandler($this->userObj, $this->supplier);

            $mtsClient = new MtsClient(
                phive('Cashier')->getSetting('mts'),
                phive('Logger')->channel('payments')
            );

            $payload = $this->buildPayload($redirectUrls);

            $result = $mtsClient->selectAccount($this->supplier, $payload);

            return $this->jsonSuccess([
                'url' => $result['data']['url'],
            ]);
        } catch (BadResponseException $e) {
            return $this->jsonFail($e->getResponse()->getBody()->getContents());
        } catch (Throwable $e) {
            return $this->jsonFail('');
        }
    }

    private function buildPayload($redirectUrls)
    {
        $userData = $this->userObj->getData();

        return [
            'userId' => $this->userObj->getId(),
            'successUrl' => $redirectUrls->getSelectAccountSuccessReturnUrl(),
            'failUrl' => $redirectUrls->getSelectAccountFailReturnUrl(),
            'country' => $userData['country'],
            'ip' => $userData['cur_ip'],
            'firstName' => $userData['firstname'],
            'lastName' => $userData['lastname'],
            'email' => $userData['email'],
            'locale' => $userData['preferred_lang'] . '_' . $userData['country'],
            'personId' => $this->userObj->getNid(),
        ];
    }

    private function jsonSuccess($data)
    {
        return json_encode(['success' => true, 'data' => $data]);
    }

    private function jsonFail($message)
    {
        return json_encode(['success' => false, 'message' => $message]);
    }
}
