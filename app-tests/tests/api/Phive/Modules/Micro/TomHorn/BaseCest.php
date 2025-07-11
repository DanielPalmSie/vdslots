<?php

namespace Tests\Api\Phive\Modules\Micro\TomHorn;

use \ApiTester;

/**
 * Class BaseCest
 */
abstract class BaseCest
{
    /**
     * @var string $url. The URL to test.
     */
    protected $url;

    /**
     * @var mixed $tomhorn. The Tomhorn module.
     */
    protected $tomhorn;

    static public $theirTransactionID;

    /**
     * Request data.
     */
    protected $partnerID = "videoslots_stage_02";

    protected $userId = "5541343";    // devtestmt

    protected $currency = "EUR";

    /**
     * @var DBUserHandler $dbUser
     */
    protected $dbUser;

    /**
     * BaseCest constructor.
     */
    public function __construct()
    {
        $this->tomhorn = phive('Tomhorn');
    }

    /**
     * @return array
     */
    protected function defaultRequest(): array
    {
        self::$theirTransactionID++;

        return [
            "partnerID" => $this->partnerID,
            "sign" => "",
            "name" => $this->userId,
            "amount" => "1",
            "currency" => $this->currency,
            "reference" => intval(sprintf("%s%s", self::$theirTransactionID, rand(1, 100))),     // transaction ID
            "sessionID" => rand(1, 1000000),
            "gameRoundID" => intval(sprintf("%s%s", self::$theirTransactionID, rand(1, 100))),
            "gameModule" => "VS243Crystal_TNP",     // Crystal Fruits
            "type" => 0,
            "fgbCampaignCode" => null,
        ];
    }

    /**
     * Creates a new request.
     *
     * @param mixed $overrides. Array of key - values to add or override the defaults.
     * @param array|null $removeKeys. Array of keys to remove from the request.
     * @return array
     *
     * @example newRequest($codeceptionExample, ['sessionID', 'gameRoundID', 'fgbCampaignCode'])
     */
    protected function newRequest($overrides = null, array $removeKeys = null): array
    {
        self::$theirTransactionID++;

        $request = [
            "partnerID" => $this->partnerID,
            "sign" => "",
            "name" => $this->userId,
            "amount" => "1",
            "currency" => $this->currency,
            "reference" => intval(sprintf("%s%s", self::$theirTransactionID, rand(1, 100))),     // transaction ID
            "sessionID" => rand(1, 1000000),
            "gameRoundID" => intval(sprintf("%s%s", self::$theirTransactionID, rand(1, 100))),
            "gameModule" => "VS243Crystal_TNP",     // Crystal Fruits
            "type" => 0,
            "fgbCampaignCode" => null,
        ];

        // Unfortunately \Codeception\Example cannot be cast to an array.
        if (!empty($overrides)) {
            foreach ($overrides as $k => $v) {
                $request[$k] = $v;
            }
        }

        // '_output_tag' is a custom field to make test output more readable.
        $request = array_diff_key($request, array_flip(array_merge($removeKeys ?: [], ['_output_tag'])));

        $request['sign'] = $this->tomhorn->generateSignFromParams($request, $request['name']);

        // Sets the DBUserHandler object for the specified user ID.
        $this->dbUser = cu($request["name"]);

        return $request;
    }

    /**
     * @param ApiTester $I
     * @param \Codeception\Example $example
     *
     * @example { "partnerID": null }
     * @example { "partnerID": "0" }
     */
    public function handlesMissingPartnerID(ApiTester $I, \Codeception\Example $example)
    {
        $request = $this->newRequest($example);

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 2,
            "Message" => "Missing request parameter: partnerID.",
        ]);
    }

    /**
     * @param ApiTester $I
     * @param \Codeception\Example $example
     *
     * @example { "partnerID": "XX" }
     */
    public function handlesInvalidPartnerID(ApiTester $I, \Codeception\Example $example)
    {
        $request = $this->newRequest($example);

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 4,
            "Message" => "Unknown partner or partner is disabled.",
        ]);
    }

    /**
     * @param ApiTester $I
     */
    public function handlesInvalidAuthenticationSign(ApiTester $I)
    {
        $request = $this->newRequest();
        $request['sign'] = 'XX';

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 3,
            "Message" => "Check if valid key was used and if the data was sent in a valid format.",
        ]);
    }

    /**
     * @param ApiTester $I
     */
    public function handlesMissingAuthenticationSign(ApiTester $I)
    {
        $request = $this->newRequest();
        unset($request['sign']);

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 2,
            "Message" => "Missing request parameter: sign.",
        ]);
    }

    /**
     * @param ApiTester $I
     */
    public function handlesMismatchingAuthenticationSign(ApiTester $I)
    {
        $request = $this->newRequest();
        $request['amount'] = strval(2 * floatval($request['amount']));

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 3,
            "Message" => "Check if valid key was used and if the data was sent in a valid format.",
        ]);
    }
}

BaseCest::$theirTransactionID = time();