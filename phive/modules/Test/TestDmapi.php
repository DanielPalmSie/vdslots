<?php
class TestDmapi extends TestPhive{

    function __construct(){
        $this->db           = phive('SQL');
        $this->mts_db       = phive('SQL')->doDb('mts');
        $this->dmapi_db     = phive('SQL')->doDb('dmapi');
        $this->c            = phive('Cashier');
        $this->casino       = phive('Casino');
        $this->mts          = new Mts();
        $this->mts_base_url = $this->c->getSetting('mts')['base_url'];
        $this->dmapi        = phive('Dmapi');
    }

    function testCardIdempotency($u, $supplier = 'wirecard'){
        $card_num = uniqid();
        $ext_id = phive()->uuid();
        foreach(range(0, 1) as $num){
            phive('Dmapi')->createEmptyDocument($u->getId(), $supplier, 'document', $card_num, $ext_id);
        }
    }

    function testIdempotency($u, $supplier = 'wirecard'){
        $card_num = uniqid();
        foreach(range(0, 1) as $num){
            phive('Dmapi')->createEmptyDocument($u->getId(), $supplier, 'document', '', phive()->uuid());
        }
    }

    function testCreateEmptyBankDoc($u, $psp){
        if($u->getCountry() == 'SE'){
            $args = [
                'clearnr' => '5356',
                'accnumber' => '12'.rand(111111, 999999),
            ];
        }else{
            $args = [
                'iban' => $u->getCountry().'0000'.rand(111111, 999999),
                'bic' => 'ESSESS'
            ];
        }

        $this->dmapi->createEmptyBankDocument($u, $args, $psp);

    }

  /*
+---------------------+
| tag                 |
+---------------------+
| addresspic          |
| aktiapic            |
| alandsbankenpic     |
| bankaccountpic      |
| bankpic             |
| citadelpic          |
| creditcardpic       |
| cubitspic           |
| danske_bankpic      |
| ecopayzpic          |
| entercashpic        |
| handelsbankenpic    |
| idcard-pic          |
| idealpic            |
| instadebitpic       |
| internaldocumentpic |
| lansforsakringarpic |
| netellerpic         |
| nordeapic           |
| omasppic            |
| op-pohjolapic       |
| pic                 |
| polipic             |
| pop_pankkipic       |
| proofofwealthpic    |
| s-pankkipic         |
| saastopankkipic     |
| sebpic              |
| skandiabankenpic    |
| skrillpic           |
| sourceoffundspic    |
| sparbankenpic       |
| swedbankpic         |
| trustlypic          |
+---------------------+

select *, count(*) as cnt from documents where subtag like '%*%' group by subtag, user_id having cnt > 1

This one has 4 identical cards: SELECT * FROM `documents` WHERE `subtag` LIKE '4017 95** **** 0535'

select subtag from documents where subtag not like '%*%' group by subtag

select tag,subtag from documents d
join
(
	select user_id, tag, external_id, count(*) as NumDuplicates
	from documents
	where deleted_at is null
	group by user_id, tag, external_id
	having NumDuplicates > 1
) tsum
on d.user_id = tsum.user_id and d.tag = tsum.tag and d.external_id = tsum.external_id
group by tag

select d.tag, d.subtag from documents d join ( select user_id, tag, external_id, count(*) as NumDuplicates from documents where deleted_at is null group by user_id, tag, external_id having NumDuplicates > 1 ) tsum on d.user_id = tsum.user_id and d.tag = tsum.tag and d.external_id = tsum.external_id group by tag

+---------------------+---------------------+
| tag                 | subtag              |
+---------------------+---------------------+
| bankaccountpic      | 74372168081059      |
| bankpic             |                     |
| citadelpic          |                     |
| creditcardpic       | 4659 02** **** 9033 |
| cubitspic           |                     |
| danske_bankpic      |                     |
| ecopayzpic          |                     |
| idcard-pic          |                     |
| idealpic            |                     |
| internaldocumentpic |                     |
| nordeapic           |                     |
| omasppic            |                     |
| op-pohjolapic       |                     |
| pop_pankkipic       |                     |
| s-pankkipic         |                     |
| sebpic              |                     |
| skrillpic           |                     |
| sparbankenpic       |                     |
| swedbankpic         |                     |
+---------------------+---------------------+



*
*/




}
