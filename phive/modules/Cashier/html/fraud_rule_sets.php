<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';
require_once __DIR__ . '/../../../../diamondbet/html/display.php';

$crud = Crud::table('fraud_rules', true);
$crud->renderInterface('id', ['group_id' => ['table' => 'fraud_groups', 'idfield' => 'id', 'dfield' => 'tag']]);

$reg_date = empty($_POST['reg_date']) ? phive()->hisMod('-1 month', '', 'Y-m-d') : $_POST['reg_date'];

if(!empty($_POST['test_tags'])){
    $tags     = explode(',', $_POST['test_tags']);
    $fraud    = phive()->loadClass('Cashier', 'Fraud');
    $fraud->test_ruleset = true;
    $users    = phive('SQL')->shs('merge', '', null, 'users')->arrayWhere('users', "DATE(register_date) > '$reg_date'");
    //print_r($users);
    $unames   = [];
    foreach($users as $ud){
        if($fraud->checkRuleset($tags, $ud, []) === true)
            $unames[] = $ud['username'];
        
    }
}
?>

<div class="pad10">
<br/>
Table structure:
<p>
    <strong>Group id</strong>: connection to the rule group the rules are attached to. We loop all active groups during registration, deposit and game load. If any group matches completely we block the player.
</p>
<strong>Country</strong>: we filter all rules by country, if the player's country doesn't match the rule won't be checked. Leave out country from all rules under a specific rule set / group to have the rules apply to people form all countries. Example: GB.
<br/>
<br/>
<strong>Tbl</strong>: the database table we want to examine / work with. Example: users.
<br/>
<br/>
<strong>Field</strong>: the database table field we want to look at. Example: email.
<br/>
<br/>
<strong>Alternative ids</strong>: ids of rules to be run if a certain rule failes, note that atm you can't just pick any rule, example: 9,10 to run rules with id 9 and 10
<br/>
<br/>
<br/>
<strong style="text-decoration: underline;">The following are mutually exclusive unless stated otherwise, the logic only handles a value in one of them:</strong>
<br/>
<br/>
<strong>Start value and End value</strong>: both need to be present and will make up a range the value needs to be in.
<br/>
<br/>
<strong>Like value</strong>: if we want to match a single value, accepts the SQL wildcard % which matches any character. Example: %yahoo.co.uk
<br/>
<br/>
<strong>Value exists</strong>: just put 1 here to signal that field needs to have a value. Used to figure out if a player has a row in a table at all, example: tbl: users_game_sessions, field: user_id, value exists: 1. This will match if the player has completed a game session.
<br/>
<br/>
<strong>Not like value</strong>: the opposite of <strong>like value</strong>, example: tbl: trophy_award_ownership, field: status, not like value: 1 will match anyone who has a trophy award which is not in use (status = 1). That is anyone with a trophy award that is not used yet (status = 0), used (status = 2) or expired (status = 3). <strong>NOTE</strong> that the match will fail if the SQL query doesn't return anything, so if a fraudster doesn't have any award rows in the database at all he will not be matched by this rule. 
<br/>
<br/>
<strong>Value in</strong>: this is to be used with SQL's IN clause, example: tbl: trophy_award_ownership, field: status, value in: 0,3. This will match anyone who has either an unused or expired trophy award, a common scenario when dealing with criminals, they're not interested in completing 10 freespins. If trying to match a string value the syntax is like this: 'value1','value2'... Note that wildcards are not supported in the values.
<br/>
<br/>
<strong>Value not in</strong>: the opposite of <strong>value in</strong>, we get all rows which are not in the "list", for instance 0,3, ie everyone who are using or has completed a trophy award. <strong>NOTE</strong> that the match will fail if the SQL query doesn't return anything, so if a fraudster doesn't have any award rows in the database at all he will not be matched by this rule. 
<br/>
<br/>
<strong>Value does not exist</strong>: the opposite of value exists. <strong>NOTE</strong> that this rule will match if nothing is returned, if used like in the <strong>value exists</strong> example we match the player if he has never had any game sessions before. 
<br/>
<br/>
<strong style="text-decoration: underline;">Special tables:</strong>
<br/>
<br/>
<strong>micro_games</strong>: this is the games table, this rule is only used when a player tries to play a game and is compared against the game being loaded. <strong>In all other contexts (reg, depoist) it will cause the rule set to not match.</strong>
<br/>
<br/>
<strong>location</strong>: this is not even a database table but is handled in a special way by the fraud logic, it's used to check the position of people by longitued and latitude, <strong>needs to be tested before used as a non-alternative rule.</strong> 
<br/>
<br/>
1.) Start with creating a rule group in a separate interface, leave it non-active so you don't block everyone while you test it out.
<br/>
<br/>
2.) Start creating rules, all rules need to match in order for a block to happen, let's take the "London fraud ring" as an example, we call their group "basic":
<br/>
- All rules need to be under "basic".
<br/>
- They were all from GB so country should always be GB to avoid locking on to innocents.
<br/>
- They always used a birth date from the 1990's so our first rule is tbl: users, field: dob, start value: 1990-01-01, end value: 1999-12-31.
<br/>
- They always used a British yahoo email so our second rule is tbl: users, field: email, like value: %yahoo.co.uk. The % character is SQL for "match anything here" so someone with monkeyboy@yahoo.co.uk would be matched which is what we want.
<br/>
- They almost always had a reg ip in this range: 213.205.194.1 to 213.205.194.254 so our third rule is tbl: users, field: reg_ip, start value: 213.205.194.1, end value: 213.205.194.254 but we also put 9,10 as the alternative ids. This means that if we fail the ip check we instead test 9 and 10 which are location checks which are using the Maximind City database to get the coordinates from an ip. In other words, we were not 100% sure they would always have that ip range and wanted to stop them anyway so we checked if the ip originated in the central part of London if it wasn't in the specified range, if it was from central London the original rule would match anyway.
<br/>
- They had stolen cards starting with 4462 91 (Barclays cards) so our forth rule is tbl: deposits, field: card_hash, like value: 4462 91%.
<br/>
- They all had trophy awards which were unused so our fifth rule is tbl: trophy_award_ownership, field: status, not like value: 1.
<br/>
- Finally, they always tried to generate a fake turnover by playing Black Jack as it is the game with the highest RTP if played right. That's why our last rule was tbl: micro_games, field: tag and like value: blackjack. Since the rule set is being checked upon game launch we inspect the game to be launched and if all other rules match and the game being opened was blackjack they got blocked. 
<br/>

<br/>
<br/>
<br/>
<br/>
</div>




<div class="pad10">
    Test the following groups, separate with a comma, ex: basic,extended,special
    <form method="POST" action="">
    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">

        Tags:
        <?php dbInput('test_tags') ?>
        <br/>
        <br/>
        Registered later than:
        <?php dbInput('reg_date', $reg_date) ?>
        <?php btnDefaultM('Test')  ?>
    </form>
    <?php foreach($unames as $uname): ?>
        <a href="<?php echo getUserBoLink($uname) ?>"><?php echo $uname ?></a><br/>
    <?php endforeach ?>
</div>

