<?php

namespace Tests\Api\Phive\Modules\Micro\Swintt;

require_once __DIR__ . '/BaseCest.php';

use \ApiTester;
use \Codeception\Example;
use \Codeception\Util\HttpCode;

/**
 * Class GetBalanceCest
 * @package Tests\Api\Phive\Modules\Micro\Swintt
 *
 * Usage: php vendor/bin/codecept run api Phive/Modules/Micro/Swintt/GetBalanceCest:finalizeRequest
 */
class GetBalanceCest extends BaseCest
{
    /**
     * GetBalanceCest constructor.
     *
     * @param null $game_provider_module
     * @param null $sql_module
     * @example new GetBalanceCest(phive('Swintt'), phive('SQL'))
     */
    public function __construct($game_provider_module = null, $sql_module = null)
    {
        parent::__construct($game_provider_module, $sql_module);
    }

    /**
     * @param ApiTester $I
     */
    public function getBalance(ApiTester $I)
    {
        $this->game_provider_module->loadUser($this->user_identifier);
        $balance = $this->game_provider_module->getPlayerBalance();

        $date = date($this->date_format);

        $request_body = <<<EOS
<cw 
  type="getBalanceReq" 
  acctid="{$this->user_prefix}{$this->user_identifier}" 
  cur="EUR"
  timestamp="{$date}"
/>
EOS;

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request_body);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("getBalanceResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));

        $I->assertEquals("EUR", $this->getXmlAttribute($this->response, "cur"));
        $I->assertEquals($balance / 100, $this->getXmlAttribute($this->response, "amt"));
    }

    /**
     * @param ApiTester $I
     */
    public function finalizeRequest(ApiTester $I)
    {
        $this->game_provider_module->loadUser($this->user_identifier);
        $balance = $this->game_provider_module->getPlayerBalance();

        $game_play_id = hexdec(uniqid());
        $date = date($this->date_format);

        $request_body = <<<EOS
<cw 
  type="finalizeReq" 
  acctid="{$this->user_prefix}{$this->user_identifier}" 
  cur="EUR"
  gameid="{$this->game_id}"
  gameplayid="$game_play_id"
  timestamp="{$date}"
/>
EOS;

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request_body);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("finalizeResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));

        $I->assertEquals("EUR", $this->getXmlAttribute($this->response, "cur"));
        $I->assertEquals($balance / 100, $this->getXmlAttribute($this->response, "amt"));
    }

    /**
     * @param ApiTester $I
     */
    public function getBalanceInvalidUser(ApiTester $I)
    {
        $date = date($this->date_format);

        $request_body = <<<EOS
<cw 
  type="getBalanceReq" 
  acctid="{$this->user_prefix}{$this->user_identifier}9999" 
  cur="EUR"
  timestamp="{$date}"
/>
EOS;

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request_body);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("getBalanceResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("1000", $this->getXmlAttribute($this->response, "err"));
    }

    /**
     * @param ApiTester $I
     */
    public function getBalanceInvalidCurrency(ApiTester $I)
    {
        $date = date($this->date_format);

        $request_body = <<<EOS
<cw 
  type="getBalanceReq" 
  acctid="{$this->user_prefix}{$this->user_identifier}" 
  cur="SEK"
  timestamp="{$date}"
/>
EOS;

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request_body);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("getBalanceResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("1001", $this->getXmlAttribute($this->response, "err"));
    }

    /**
     * @param ApiTester $I
     * @param Example $example
     *
     * @dataProvider dataGarbage
     */
    public function handlesGarbage(ApiTester $I, Example $example)
    {
        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $example['request_body']);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $this->response = simplexml_load_string($this->response);
        $I->assertNotEmpty($this->response);

        $I->assertEquals("9999", $this->getXmlAttribute($this->response, "err"));
    }

    /**
     * Data provider.
     *
     * @return array
     */
    protected function dataGarbage(): array
    {
        $date = date($this->date_format);

        return [
            [
                "_label" => "invalid_request_type",
                "request_body" => "<cw type='XXX' acctid='1234' cur='EUR' timestamp='{$date}' />",
            ],

            [
                "_label" => "missing_request_type",
                "request_body" => "<cw acctid='1234' cur='EUR' timestamp='{$date}' />",
            ],

            [
                "_label" => "missing_request_type_and_user",
                "request_body" => "<cw cur='EUR' timestamp='{$date}' />",
            ],

            [
                "_label" => "invalid_xml_attribute",
                "request_body" => "<xxx acctid='1234' cur='EUR' timestamp='{$date}' />",
            ],

            [
                "_label" => "not_xml",
                "request_body" => '{"foo": "baz"}',
            ],
        ];
    }
}
