<?php
/**
 * index.php
 *
 * @author  Antonio Blázquez
 * email    antonio.blazquez@videoslots.com
 * @version 2.0
 * Purpose  Testing UI for Battle of Slots
 * Usage    Navigate to vidoslots.loc/bostools/index.php
 *****************************************************************/
// echo '<pre>'; var_dump(realpath(__DIR__ . '/../../phive/phive.php')); echo "</pre>"; die;
ini_set('max_execution_time', '30000');
ini_set('memory_limit', '500M');
error_reporting(E_ERROR);
require_once __DIR__ . '/../phive/phive.php';
require_once __DIR__ . '/../phive/modules/Test/TestPhive.php';
// require_once __DIR__ . '/../../phive/modules/Test/TestPhive.php';
require_once __DIR__ . '/Models/BoSTestTournament.php';
require_once __DIR__ . '/Form/BulmaFormBuilder.php';

$databaseProfiles = new SQLite3(__DIR__ . '/Form/profiles.sqlite');
$query = 'CREATE TABLE IF NOT EXISTS tournaments_profiles (profile varchar(255),field_name varchar(255), value varchar(255), type varchar(255),   PRIMARY KEY (profile, field_name) )';

$result = $databaseProfiles->exec($query);
if (!$result) {
    echo '<pre>';
    var_dump($databaseProfiles->lastErrorMsg());
    echo "</pre>";
    die;
}

function getProfiles($databaseProfiles)
{
    $result = $databaseProfiles->query('SELECT * FROM tournaments_profiles WHERE 1 ORDER BY profile');
    $ret = [];
    while ($ret[] = $result->fetchArray(SQLITE3_ASSOC)) {
    }
    return $ret;
}

function getProfileNames($databaseProfiles)
{
    $result = $databaseProfiles->query('SELECT DISTINCT(profile)  FROM tournaments_profiles');
    $ret = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $ret[] = $row['profile'];
    }
    return $ret;
}

function dd()
{
    array_map(function ($x) {
        var_dump($x);
    }, func_get_args());
    die;
}

function generateCallTrace()
{
    $e = new Exception();
    $trace = explode("\n", $e->getTraceAsString());
    // reverse array to make steps line up chronologically
    $trace = array_reverse($trace);
    array_shift($trace); // remove {main}
    array_pop($trace); // remove call to this method
    $length = count($trace);
    $result = array();

    for ($i = 0; $i < $length; $i++) {
        $result[] = ($i + 1) . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
    }

    return "\t" . implode("\n\t", $result);
}


function _paginateListing($tournaments, $page_start, $page_limit)
{
    return array_slice($tournaments, $page_start, $page_limit);
}

//1 SNG with registered_players, sort by amount of registered players
//2 MTT sorted by mtt start
//3 SNG with zero registered players
//4 Full SNGs
function _sortListing($tournaments)
{
    $arr1 = phive()->sort2d(array_filter($tournaments, function ($el) {
        return !empty($el['registered_players']) && strtolower($el['start_format']) == 'sng' && $el['registered_players'] < $el['max_players'];
    }), 'registered_players', 'desc');

    $arr2 = phive()->sort2d(array_filter($tournaments, function ($el) {
        return strtolower($el['start_format']) == 'mtt';
    }), 'mtt_start', 'asc');

    $arr3 = array_filter($tournaments, function ($el) {
        return strtolower($el['start_format']) == 'sng' && empty($el['registered_players']);
    });

    $arr4 = array_filter($tournaments, function ($el) {
        return !empty($el['registered_players']) && strtolower($el['start_format']) == 'sng' && $el['registered_players'] == $el['max_players'];
    });

    return array_merge($arr1, $arr2, $arr3, $arr4);
}

/*
 This query mimics the SQL query that the tournaments GraphQL query generates
*/
function getTournaments()
{
    $default_filter = ['in.progress', 'late.registration', 'registration.open', 'upcoming'];
    $statuses = implode($_GET['filter_tournaments'] ?? $default_filter, "','");
    $onlyNetent = $_GET['only_netent'] ?? false;
    $network = '';
    if ($onlyNetent) {
        $network = " AND network = 'netent' ";
    }
    // As loaded in battle of slots lobby
    $sql = "SELECT t.*, g.game_name, g.blocked_countries, g.game_id, g.network FROM tournaments t
        LEFT JOIN micro_games AS g ON t.game_ref = g.ext_game_name AND g.device_type = 'flash'
        WHERE t.status IN('$statuses') AND t.included_countries = '' $network ORDER BY t.id DESC LIMIT 0, 1000";
    $tournaments = phive("SQL")->loadArray($sql);
    $tournaments = _sortListing($tournaments);
    if (!empty($_GET['displayed'])) {
        $tournaments = _paginateListing($tournaments, 0, $_GET['displayed']);
    }
    return $tournaments;
}

function execCron()
{
    $oTestTournament = TestPhive::getModule('Tournament');
    $oTestTournament->everyMinCron();
    header("Location: /bostools/index.php");
    die();
}

// After submitting the SUBSCRIBE USERS form
if (!empty($_POST)) {

    $action = $_POST['action'];
    $registered = false;
    switch ($action) {
        case 'register_user':

            $newPlayer = new BoSTester($_POST['tid'], $_POST['user_id']);
            $registered = $newPlayer->registerUserInTournament();
            break;
        case 'create-battle':
            $tournament = new BoSTestTournament(null, $_POST);
            break;
        default:
            $t = TestPhive::getModule('Tournament');
            // echo '<pre>'; var_dump($_POST); echo "</pre>"; die;
            $tournament = phive('Tournament');
            $included = $_POST['included'] ?? [];
            $inserts = [];
            $errors = [];
            foreach ($_POST['battle'] as $key => $battle) {
                if (!in_array($battle, $included)) {
                    continue;
                }
                $tournament = new BoSTestTournament($battle);
                $limit = $_POST['limit'][$key] == 0 ? 1 : $_POST['limit'][$key];
                for ($i = 0; $i < $limit; $i++) {
                    try {
                        $newPlayer = new BoSTester($battle, '', $limit);
                        $result = $newPlayer->registerUserInTournament();
                        if (!is_array($result)) {
                            $errors[$battle][] = [
                                'user_id' => $result
                            ];
                        } else {
                            $inserts[] = $newPlayer->getUser()->userId;
                        }
                    } catch (Exception $e) {
                        $errors[$battle][] = ['user_id' => $result, 'Exception' => $e->getMessage()];
                    }
                }
            }
            break;
    }
}

if (isset($_GET['action']) && $_GET['action'] == "simulatePlayAndFinish" && isset($_GET['tournament'])) {
    $tournament = new BoSTestTournament($_GET['tournament']);
    $tournament->simulatePlayAndFinish();
}

if (isset($_GET['exec_cron'])) {
    execCron();
}

if (isset($_GET['end_all_tournaments'])) {
    $tournaments = getTournaments();
    foreach ($tournaments as $tournament) {
        phive('Tournament')->endTournament($tournament['id'], '');
    }
    header("Location: /bostools/index.php");
    die();
}

if (isset($_GET['endTournament'])) {
    phive('Tournament')->endTournament($_GET['tournament'], '');
    header("Location: /bostools/index.php");
    die();
}

/* ENTRY POINT */
$tournaments = getTournaments();
$aGames = BoSTestTournament::getNamedGames();
$profiles = getProfiles($databaseProfiles);
$profileNames = getProfileNames($databaseProfiles);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>::BOS::DUMMIES</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.1/css/bulma.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet"/>
    <script
            src="https://code.jquery.com/jquery-3.3.1.min.js"
            integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
    <script defer src="https://use.fontawesome.com/releases/v5.1.0/js/all.js"></script>
</head>
<body>
<section class="section">
    <div class="container is-fluid">

        <h1 class="title">
            BATTLE OF SLOTS:
        </h1>
        <p class="subtitle">
            Create dummy users for <strong>battle</strong>!
        </p>

        <div class="box">
            <div class="level">
                <div class="level-left">
                    <div class="level-item">
                        <form method="GET">
                            <div class="field-body">
                                <div class="field has-addons">
                                    <div class="control">
                                        <input class="input" type="text" placeholder="Limit battles displayed"
                                               name="displayed" <?= $_GET['displayed'] ? 'value="' . $_GET['displayed'] . '"' : ''; ?>>
                                    </div>
                                    <div class="control">
                                        <button class="button is-info">
                                            Limit
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </form>

                    </div>

                </div>
                <div class="level-right">
                    <div class="level-item">
                        <a class="button is-primary " href="#newTournament">New Tournament</a>
                    </div>
                    <div class="level-item">
                        <a class="button is-primary " href="/bostools/index.php?exec_cron=true">Tournament Cron</a>
                    </div>
                    <div class="level-item">
                        <a class="button is-danger "
                           href="/bostools/index.php?end_all_tournaments=true&filter_tournaments[]=in.progress&filter_tournaments[]=registration.open&filter_tournaments[]=late.registration&filter_tournaments[]=upcoming&filter_tournaments[]=finished&filter_tournaments[]=canceled">End
                            all tournaments</a>
                    </div>
                </div>
            </div>

            <div class="level">
                <div class="level-left">
                    <div class="level-item">
                        <form method="GET">
                            <div class="field is-grouped">
                                <div class="field">
                                    <label class="checkbox">
                                        <input type="checkbox" name="filter_tournaments[]" id="filters-in.progress" value="in.progress">
                                        In Progress
                                    </label>
                                </div>
                                <div class="field">
                                    <label class="checkbox">
                                        <input type="checkbox" name="filter_tournaments[]" id="filters-registration.open" value="registration.open">
                                        Registration Open
                                    </label>
                                </div>
                                <div class="field">
                                    <label class="checkbox">
                                        <input type="checkbox" name="filter_tournaments[]" id="filters-late.registration" value="late.registration" ¡>
                                        Late Registration
                                    </label>
                                </div>
                                <div class="field">
                                    <label class="checkbox">
                                        <input type="checkbox" name="filter_tournaments[]" id="filters-upcoming" value="upcoming">
                                        Upcoming
                                    </label>
                                </div>
                                <div class="field">
                                    <label class="checkbox">
                                        <input type="checkbox" name="filter_tournaments[]" id="filters-finished" value="finished">
                                        Finished
                                    </label>
                                </div>
                                <div class="field">
                                    <label class="checkbox">
                                        <input type="checkbox" name="filter_tournaments[]" id="filters-canceled" value="canceled">
                                        Canceled
                                    </label>
                                </div>

                            </div>
                            <div class="field-group">
                                <div class="field">
                                    <label class="checkbox">
                                        <input type="checkbox" name="only_netent" <?= isset($_GET['only_netent']) ? 'checked' : null; ?> >
                                        Show Only Netent Tournaments
                                    </label>
                                </div>
                            </div>
                            <div class="field">

                                <div class="control">
                                    <button class="button is-primary">Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


        </div>

        <p>Subscribes N users to a battle, from the last user who was inscribed in the battle</p>


        <form method="POST">


            <div class="box">
                <div class="columns">
                    <div class="column">
                        <div class="field is-horizontal">
                            <div class="field-body">
                                <div class="field is-grouped">
                                    <div class="control">
                                        <label class="checkbox">
                                            <input id="checkall" type="checkbox" name="checkall"
                                                   value="0" <?= isset($_POST['checkall']) ? 'checked' : '' ?>>
                                            Check All
                                        </label>
                                    </div>
                                    <div class="control is-right">
                                        <label class="checkbox">
                                            <input type="checkbox" name="debug" value="1" <?= isset($_POST['debug']) ? 'checked' : '' ?>>
                                            Debug
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="field is-horizontal">
                            <div class="field-body">
                                <div class="field">
                                    <div class="control">
                                        <button class="button is-link">Subscribe Users</button>

                                    </div>
                                    <p class="help">Remember to check the tournament checkboxes</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="column">

                    </div>
                </div>


                <ul>
                    <div class="columns is-multiline is-desktop">
                        <?php foreach ($tournaments as $key => $tournament): ?>
                            <?php

                            // echo '<pre>'; var_dump($tournament); echo "</pre>"; die;

                            ?>

                            <div class="column is-half">


                                <div class="box">
                                    <div class="field is-horizontal">
                                        <div class="field-body">
                                            <!-- CHECKBOX -->
                                            <div class="field ">
                                                <div class="control">
                                                    <label class="checkbox">
                                                        <input type="checkbox" id="included-<?= $tournament['id'] ?>" name="included[]"
                                                               value="<?= $tournament['id'] ?>">
                                                    </label>
                                                </div>
                                            </div>
                                            <!-- battle -->
                                            <div class="field">
                                                <div class="control">
                                                    <input class="input" value="<?= $tournament['id'] ?>" name="battle[]" required>
                                                    <input type="hidden" name="total_cost[]" value="<?= $tournament['total_cost'] ?>">
                                                </div>
                                                <p class="has-text-primary has-text-weight-bold"><?= $tournament['network'] . " :: " . $tournament['tournament_name'] . " :: " . $tournament['game_name'] ?></p>
                                                <span class="is-italic">Registered: <?= $tournament['registered_players']; ?></span>
                                                <span class="is-italic">Status: <?= $tournament['status']; ?></span>
                                                <p class="is-italic">Battle will be canceled if registered users < <?= $tournament['min_players'] ?>
                                                    .</p>
                                                <p class="is-italic">Should
                                                    Start: <?= $tournament['start_format'] === 'mtt' ? $tournament['mtt_start'] : 'SNG' ?></p>
                                                <p class="is-italic">Start: <?= $tournament['start_time'] ?></p>
                                                <p class="is-italic">
                                                    Finish: <?= date('Y-m-d H:i:s', phive('Tournament')->getEndTime($tournament)); ?></p>

                                            </div>

                                            <div class="field ">
                                                <div class="control">
                                                    <input class="input" type="number"
                                                           value="1" <?= isset($_POST['limit'][$key]) ? 'value="' . $_POST['limit'][$key] . '"' : null; ?>
                                                           placeholder="Number of users" name="limit[]" required>
                                                </div>
                                                <p class="help">Number of users</p>
                                            </div>
                                        </div>
                                    </div>
                                    <a class="button is-primary " href="/bostools/chat.vue.php?tournament=<?= $tournament['id'] ?>">Test
                                        Chat</a>

                                    <a class="button is-primary " href="/bostools/users.php?tournament=<?= $tournament['id'] ?>">Users</a>

                                    <a class="button is-primary"
                                       href="/bostools/index.php?tournament=<?= $tournament['id'] . '&action=simulatePlayAndFinish' ?>">Simulate
                                        Play and Finish</a>

                                    <a class="button is-danger"
                                       href="/bostools/index.php?tournament=<?= $tournament['id'] . '&endTournament=true' ?>">End
                                        tournament</a>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </ul>
                <div class="box" style="margin-top: 25px;">
                    <div class="field is-horizontal">
                        <div class="field-body">
                            <div class="field">
                                <div class="control">
                                    <button class="button is-link">Subscribe Users</button>

                                </div>
                                <p class="help">Remember to check the tournament checkboxes</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>
</section>
<section class="section" id="newTournament">
    <div class="container is-fluid">
        <div class="columns">
            <div class="column">
                <div class="box">
                    <h1 class="subtitle">Register a single user</h1>
                    <form method="POST">
                        <input type="hidden" name="action" value="register_user">
                        <div class="field">
                            <label class="label">User id</label>
                            <div class="control">
                                <input class="input" type="text" placeholder="user id" name="user_id" required>
                            </div>
                        </div>
                        <div class="field">
                            <label class="label">Tournament</label>
                            <div class="control">
                                <input class="input" type="text" placeholder="tournament id" name="tid" required>
                            </div>
                        </div>
                        <div class="control">
                            <button class="button is-primany">Submit</button>
                        </div>
                        <?php
                        ?>
                        <?php if (isset($registered) && $registered != false): ?>
                            <p class="help is-success">This user has been registered</p>

                        <?php endif ?>
                    </form>
                </div>
            </div>
            <div class="column">
                <div class="box">
                    <h1 class="title">Create a new battle</h1>
                    <form method="POST" action="/bostools/index.php" id="create-battle__form">
                        <input class="input" type="hidden" placeholder="placeholder" name="action" value="create-battle">
                        <div class="level">
                            <div class="level-left">
                                <div class="level-item">
                                    <h2>Select profile</h2>
                                </div>
                                <div class="level-item">
                                    <div class="field">
                                        <div class="control">
                                            <div class="select is-primary">
                                                <select id="profile-choose" class="profile-choose" name="profile">
                                                    <?php foreach ($profileNames as $key => $profile): ?>
                                                        <option name="<?= $profile ?>" value="<?= $profile ?>"><?= $profile ?></option>
                                                    <?php endforeach ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="level-right">
                                <div class="level-item">
                                    <a href="edit_profiles.php">
                                        Create/edit profiles
                                    </a>
                                </div>
                                <div class="level-item">
                                    <div class="control">
                                        <button class="button is-primary create-battle__submit">Submit</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="field">
                            <div class="control">
                                <div class="select is-primary">
                                    <select class="game-ref-choose" name="game_ref">
                                        <?php foreach ($aGames as $game_ref => $game_name): ?>
                                            <option value="<?= $game_ref ?>"><?= $game_name . " :: " . $game_ref ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- PROFILE INPUTS -->
                        <?php
                        $formBuilder = new BulmaFormBuilder();
                        $tmpProfile = '';
                        $first = true;
                        $newBox = true;
                        $importantOnes = ['category', 'cost', 'total_cost', 'rebuy_times', 'duration_minutes', 'free_pot_cost', 'house_fee', 'max_bet', 'min_bet', 'max_players', 'min_players', 'mtt_reg_duration_minutes', 'mtt_show_hours_before', 'mtt_start_date', 'mtt_start_time', 'pot_cost', 'rebuy_cost', 'rebuy_house_fee', 'prize_amount', 'guaranteed_prize_amount'];
                        foreach ($profiles

                        as $profile){
                        if ($profile){
                        if ($profile['profile'] != $tmpProfile){
                        $tmpProfile = $profile['profile'];
                        $newBox = true;
                        if ($newBox && !$first): ?>
                </div>
                <?php endif;
                ?>
                <div id="profile-table-<?= $tmpProfile ?>" class="box profile-tables is-hidden">
                    <h1 class="title"><?= $tmpProfile ?></h1>
                    <?php
                    }
                    if (in_array($profile['field_name'], $importantOnes)) {
                        $profile['extraClass'] = 'has-text-danger';
                    }

                    $formBuilder->input($profile);

                    if ($newBox) {
                        $newBox = false;
                        $first = false;
                    }
                    }
                    }
                    ?>

                </div>


                <div class="control">
                    <button class="button is-primary create-battle__submit">Submit</button>
                </div>
                </form>
            </div>

        </div>
    </div>
    </div>
</section>


<!-- DEBUG -->
<section class="section">
    <?php if (count($inserts)): ?>


    <?php endif ?>
    <div class="container is-fluid">
        <div class="notification">

            <?= "Registered: " . count($inserts) . ' - ' . "Errors: " . count($errors) ?>
        </div>
    </div>
    <?php if (isset($_POST['debug'])): ?>
        <div class="container is-fluid">
          <pre>
          <?= var_dump($inserts); ?>
          <?= var_dump($errors); ?>
          </pre>
        </div>
        <div class="container is-fluid">
          <pre>
          <h1>POST params:</h1> <?= print_r($_POST, true) ?>

          </pre>
        </div>
    <?php endif ?>
    <section>


        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>

        <script type="text/javascript">
            $(document).ready(function () {
                // Check post checkboxes
                var included = <?= json_encode(isset($_POST['included']) ? $_POST['included'] : []); ?>;
                var filters = <?= json_encode(isset($_GET['filter_tournaments']) ? $_GET['filter_tournaments'] : ['in.progress', 'registration.open', 'late.registration']); ?>;

                $('#checkall').change(function () {
                    // Check/uncheck all checkboxes
                    $('input[name="included[]"]').each(function () {
                        $(this).prop("checked", $('#checkall').is(":checked"));
                    });
                });
                if ($('#checkall').is(":checked")) {
                    $('#checkall').change();
                }
                for (let i = 0, length1 = included.length; i < length1; i++) {
                    $('input[id="included-' + included[i] + '"]').prop("checked", true);
                }

                for (let i = 0, length1 = filters.length; i < length1; i++) {
                    $('input[id="filters-' + filters[i] + '"]').prop("checked", true);
                }


                // Form prefill
                const freespinProfile = [];
                const normalProfile = [];
                normalProfile['cost'] = 200

                const profiles = [normalProfile, freespinProfile];

                profiles.forEach(profile => {
                    profile.forEach((value, index, self) => {
                        console.log(value, index, self, self[value]);
                        $("input[name='" + value + "']").value(self[value]);
                    });
                });

                // Profile selector
                $('#profile-choose').change(function (e) {
                    const profile = $(this).val();

                    $('.profile-tables').addClass('is-hidden');
                    $('#profile-table-' + profile).removeClass('is-hidden');
                    $('.profile-tables input, .profile-tables select').attr('disabled', 'disabled');
                    $('#profile-table-' + profile + ' input, #profile-table-' + profile + ' select').removeAttr('disabled');
                });

                let now = moment().utc();
                $('#profile-choose').val('normal').change();
                // set a default date and time for the tournament in order to make easier updating it
                $('input[name="mtt_start_date"]').val(now.format('YYYY-MM-DD'));
                $('input[name="mtt_start_time"]').val(now.add(50, 'seconds').format('HH:mm:ss'));

                $('.create-battle__submit').click(e => {
                    e.preventDefault();
                    let date = moment.utc($('input[name="mtt_start_date"]:not(:disabled)').val() + ' ' + $('input[name="mtt_start_time"]:not(:disabled)').val());
                    let now = moment().utc();
                    if (now > date) { // update the time start if we forgot to set it after the actual time
                        $('input[name="mtt_start_time"]:not(:disabled)').val(now.add(20, 'seconds').format('HH:mm:ss'));
                    }
                    $('#create-battle__form').submit();
                });

                // set select 2

                $('.game-ref-choose').select2();

            });
        </script>
</body>
</html>
