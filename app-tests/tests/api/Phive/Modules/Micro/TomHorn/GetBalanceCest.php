<?php

namespace Tests\Api\Phive\Modules\Micro\TomHorn;

require_once __DIR__ . '/BaseCest.php';

use \ApiTester;
use \Codeception\Util\HttpCode;

/**
 * Class GetBalanceCest
 *
 * Tests Tom Horn Get Balance.
 *
 * Usage: php vendor/bin/codecept run api Phive/Modules/Micro/TomHorn/GetBalanceCest
 */
class GetBalanceCest extends BaseCest
{
    /**
     * @var array $balance_request
     */
    private $balance_request;

    /**
     * @var array $balance_response
     */
    private $balance_response;

    /**
     * GetBalanceCest constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->url = "/tomhorn.php/GetBalance";
    }

    /**
     * @param ApiTester $I
     * @param array|null $request
     */
    public function getBalance(ApiTester $I, array $request = null)
    {
        $this->balance_request = $request;
        if (empty($this->balance_request)) {
            $this->balance_request = [
                "partnerID" => $this->partnerID,
                "sign" => "",
                "name" => $this->userId,
                "currency" => $this->currency,
                "sessionID" => 0,
                "gameModule" => "",
                "type" => 0,
            ];
        }
        $this->balance_request['sign'] = $this->tomhorn->generateSignFromParams($this->balance_request, $this->balance_request["name"] /* user_id */);

        $this->dbUser = (cu($this->balance_request["name"] ?? 0) ?: null);

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST("/tomhorn.php/GetBalance", json_encode($this->balance_request));

        $this->balance_response = json_decode($I->grabResponse(), true);
    }

    /**
     * @param ApiTester $I
     */
    public function getValidBalance(ApiTester $I)
    {
        $this->getBalance($I);

        $balance = $this->dbUser->getAttr('cash_balance', true);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 0,
            "Message" => "",
            "Balance" => [
                "Amount" => ($balance / 100),
                "Currency" => $this->currency,
            ],
        ]);
    }
}
