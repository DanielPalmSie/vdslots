<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

const NETWORK_BETRADAR = 'betradar';
const NETWORK_ALTENAR = 'altenar';
const NETWORK_POOLX = 'poolx';
const ALTENAR_CASHOUT = 'T';
const ALTENAR_PARTIAL_CASHOUT = 'Q';

$user_id = null;
$id = null;
$logged_in_user_id = null;
$bet = null;
$win = null;
$void = null;
$ticket_type = $ticket_type ?? null;
$is_betradar = $is_betradar ?? false;
$cashout = $cashout ?? [];
$selections = [];
$ticket_status = '';
$is_cashout = false;
$is_partial_cashout = false;

// Initialize custom error logging
setupCustomErrorLogging();

try {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $logged_in_user_id = $_SESSION['user_id'] ?? null;

    validateRequest($user_id, $id, $logged_in_user_id);

    $user = cuPl($user_id);
    setCur($user);

    $mbox = new MboxCommon();
    $sql = phive('SQL');

    $query = "SELECT * FROM sport_transactions WHERE id = {$id} AND ignore_sportsbook_history IS NULL";
    $bet = $sql->sh($user)->loadAssoc($query);

    if (!$bet) {
        phive('Logger')->getLogger('sportsbook')->error('Bet not found or restricted access', [
            'user_id' => $user_id, 'bet_id' => $id
        ]);
        throw new Exception('Bet not found or access restricted.');
    }

    $win = $sql->sh($user)->arrayWhere(
        'sport_transactions',
        ['bet_type' => 'win', 'ticket_id' => $bet['ticket_id']]
    );

    $void_query = "SELECT * FROM sport_transactions
                   WHERE network = '{$bet['network']}'
                   AND bet_type = 'void'
                   AND ticket_id = {$bet['ticket_id']}
                   AND ignore_sportsbook_history IS NULL";

    $void = $sql->sh($user)->loadArray($void_query);

    $cashout = [];
    $is_cashout = $is_partial_cashout = false;
    $ticket_status = '';
    $is_betradar = ($bet['network'] === NETWORK_BETRADAR);

    [$selections, $ticket_type] = getSportTransactionDetails($sql, $user, $id, $bet['created_at']);

    if (empty($selections)) {
        $bet_void_factor = handleBetResultAndVoidFactor($bet, $win, $void);
        [$selections, $cashout, $ticket_type, $is_cashout, $is_partial_cashout] =
            getSportTransactionInfo($sql, $user, $bet, $bet_void_factor);
        $ticket_status = getTicketStatus($bet, $win, $void);
    }

} catch (Throwable $e) {
    phive('Logger')->getLogger('sportsbook')->error('Exception during bet receipt parsing', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'user_id' => $user_id ?? 'N/A',
        'bet_id' => $id ?? 'N/A'
    ]);

    http_response_code(500);
    echo json_encode([
        'error' => 'An internal error occurred. Please try again later.',
        'details' => getenv('APP_DEBUG') ? $e->getMessage() : null
    ]);
}
function getSportTransactionDetails($sql, object $user, int $id, string $created_at): array
{
    $query = "SELECT * FROM sport_transaction_details WHERE sport_transaction_id = {$id}";
    $selections = $sql->sh($user)->loadArray($query);

    $selections = array_map(
        function ($selection) use ($created_at) {
            $selection['event_info'] = (array)json_decode($selection['event_info']);
            if (empty($selection['event_info']['event_date'])) {
                $selection['event_info']['event_date'] = $created_at;
            }
            return $selection;
        },
        $selections
    );

    $ticket_type = count($selections) > 1 ? 'multi' : 'single';

    return [$selections, $ticket_type];
}

function getSportTransactionInfo($sql, object $user, array $bet, array $bet_result): array
{
    $selections = [];
    $cashout = [];
    $is_cashout = false;
    $is_partial_cashout = false;
    $ticket_type = $bet['ticket_type'];

    switch ($bet['network']) {
        case NETWORK_ALTENAR:
            $selections = getAltenarSelections($sql, $user, $bet, $bet_result);
            $cashout = getAltenarCashoutDetails($sql, $user, $bet['ticket_id']);
            $ticket_type = count($selections) > 1 ? 'multi' : 'single'; //Due to of system type!
            $is_cashout = !empty($cashout);
            $is_partial_cashout = $is_cashout && ($cashout['type'] === 'partial-cashout');
            break;

        case NETWORK_POOLX:
            // TODO: Implement PoolX logic.
            break;

        default:
            break;
    }

    return [$selections, $cashout, $ticket_type, $is_cashout, $is_partial_cashout];
}

function getAltenarSelections($sql, object $user, array $bet, array $bet_result): array
{
    $query = "SELECT json_data FROM sport_transaction_info
              WHERE network = '" . NETWORK_ALTENAR . "'
              AND transaction_type = 'PlaceBet'
              AND sport_transaction_id = '{$bet['id']}' LIMIT 1";
    $result = $sql->sh($user)->loadAssoc($query);

    if (!empty($result['json_data'])) {
        $json_data = decodeJson($result['json_data']);
        if ($json_data) {
            return getAltenarBetDetails($json_data, $bet_result);
        }
    }

    return [];
}

function getAltenarBetDetails(array $bet_details, array $bet_result): array
{
    $events = $bet_details['Bet']['EventList']['Event'] ?? [];
    $is_multi = (int)($bet_details['Bet']['EventCount']['Value'] ?? 0) > 1;

    $process_event = function ($event) use ($bet_result) {
        return [
                'competitor_name' => $event['Value'] ?? null,
                'type' => $event['Market']['Value'] ?? null,
                'outcome' => $event['Market']['Outcome']['Value'] ?? null,
                'odds' => $event['Market']['Odds']['Value'] ?? null,
                'event_ext_id' => 'home',
                'outcome_id' => 'sr:competitor',
                'event_info' => [
                    'event_date' => $event['EventDate']['Value'] ?? null,
                    'outcome_competitor_original_name' => $event['Market']['Outcome']['Value'] ?? null,
                    'competitor_name' => $event['Value'] ?? null,
                ],
            ] + $bet_result;
    };

    if ($is_multi) {
        return array_map($process_event, $events);
    } elseif (!empty($events)) {
        return [$process_event($events)];
    }

    return [];
}

function getAltenarCashoutDetails($sql, object $user, string $ticket_id): array
{
    $query = "SELECT json_data FROM sport_transaction_info
              WHERE network = '" . NETWORK_ALTENAR . "'
              AND ticket_id = '{$ticket_id}'
              ORDER BY id DESC LIMIT 1";
    $result = $sql->sh($user)->loadAssoc($query);

    if (!empty($result['json_data'])) {
        $json_data = decodeJson($result['json_data']);
        if ($json_data) {
            $bet_status = $json_data['BetStatus']['Value'] ?? null;
            $cashout_amount = $json_data['CashoutAmount']['Value'] ?? null;

            if ($bet_status === ALTENAR_PARTIAL_CASHOUT && !is_null($cashout_amount)) {
                $cashout_amount = getAltenarPartialCashoutAmount($sql, $user, $ticket_id);
                return [
                    'status' => 'open',
                    'type' => 'partial-cashout',
                    'name' => t('sports-history.receipt.partial-cashout'),
                    'amount' => $cashout_amount,
                ];
            } elseif ($bet_status === ALTENAR_CASHOUT && !is_null($cashout_amount)) {
                return [
                    'status' => 'void',
                    'type' => 'cashout',
                    'name' => t('sports-history.receipt.cashout'),
                    'amount' => $cashout_amount,
                ];
            }
        }
    }

    return [];
}

function getAltenarPartialCashoutAmount($sql, object $user, string $ticket_id): int
{
    $query = "SELECT json_data FROM sport_transaction_info
              WHERE network = '" . NETWORK_ALTENAR . "'
              AND ticket_id = '{$ticket_id}'
              AND transaction_type = 'CashoutBet'
              ORDER BY id DESC";
    $result = $sql->sh($user)->loadArray($query);

    $cashout_amount = 0;
    foreach ($result as $row) {
        if (!empty($row['json_data'])) {
            $json_data = decodeJson($row['json_data']);
            if (is_array($json_data)) {
                $bet_status = $json_data['BetStatus']['Value'] ?? null;
                $amount = $json_data['CashoutAmount']['Value'] ?? null;

                if ($bet_status === ALTENAR_PARTIAL_CASHOUT && is_numeric($amount)) {
                    $cashout_amount += (int)$amount;
                }
            }
        }
    }

    return $cashout_amount;
}

function decodeJson(string $json): ?array
{
    $decoded = json_decode($json, true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
}

function handleBetResultAndVoidFactor($bet, ?array $win, ?array $void): array
{
    $ticket_status = getTicketStatus($bet, $win, $void);

    if ($ticket_status === 'open') {
        return [];
    }

    $result = $bet['result'];
    $void_factor = null;
    if ($bet['result'] !== 1) {
        $void_factor = ($ticket_status === 'void') ? 1 : 0;
        $result = ($ticket_status === 'win') ? 1 : $result;
    }

    return ['result' => $result, 'void_factor' => $void_factor];
}

function sT(string $alias, string $specifiers, $competitors = []): string
{
    return phive('Sportsbook')->translateStringUsingSpecifiersAndCompetitors($alias, $specifiers, $competitors);
}

function getTicketStatus($ticket, $win = null, $void = null): string
{
    if ((int)$ticket['ticket_settled'] === 1 || $win || $void) {
        if ($win) {
            return 'win';
        }
        if ($void) {
            return 'void';
        }
        return 'lost';
    }
    return 'open';
}

function getSelectionStatus($selection): string
{
    if (isset($selection['result'])) {
        if ((int)$selection['result'] === 1) {
            return 'win';
        }

        if (!empty($selection["void_factor"]) && (float)$selection["void_factor"] > 0) {
            return 'void';
        }

        return 'lost';
    }

    return 'open';
}

function formatEventDate($event_date): string
{
    $event_time = phive()->lcDate(phive()->fDate($event_date, 'Y-m-d H:i:s'), '%H:%M');
    $event_date = phive()->fDate($event_date);
    $today = phive()->today();
    $today_translated = t('sb.datetime.today');
    $tomorrow = date('Y-m-d', strtotime($today . "+1 day"));
    $tomorrow_translated = t('sb.datetime.tomorrow');
    $current_year = phive()->fDate($today, 'Y');
    $format_current_year = phive()->lcDate($event_date, '%d %b');
    $format_year = phive()->lcDate($event_date, '%d %b %Y');
    $event_year = phive()->fDate($event_date, 'Y');

    $result = "";

    if ($today == $event_date) {
        return "{$today_translated} {$event_time}";
    }

    if ($event_date == $tomorrow) {
        return "{$tomorrow_translated} {$event_time}";
    }

    if ($event_year > $current_year || $event_date < $today) {
        return "{$format_year} {$event_time}";
    }

    if ($current_year == $event_year && !$event_date < $today) {
        return "{$format_current_year} {$event_time}";
    }

    return $result;
}

function calculateDeadHeatOf(float $factor): string
{
    $deadHeat = round(1 / $factor);
    return strval($deadHeat);
}

/**
 * @throws Exception
 */
function validateRequest(int $user_id, int $id, int $logged_in_user_id) {
    if (!$user_id || !$id) {
        phive('Logger')->getLogger('sportsbook')->error('Invalid input parameters', [
            'user_id' => $user_id, 'bet_id' => $id
        ]);
        throw new InvalidArgumentException('Invalid input parameters.');
    }

    if ($user_id !== $logged_in_user_id && !(p('account.admin') || p('view.account.account-sport-history'))) {
        phive('Logger')->getLogger('sportsbook')->error('Unauthorized access attempt', [
            'user_id' => $user_id, 'logged_in_user_id' => $logged_in_user_id
        ]);
        throw new Exception('Access denied.');
    }
}

function setupCustomErrorLogging() {
    $logger = phive('Logger')->getLogger('sportsbook');

    set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($logger) {
        $logger->error("PHP Error [$errno]: $errstr in $errfile on line $errline");
        return false;
    });

    set_exception_handler(function ($exception) use ($logger) {
        $logger->error("Uncaught Exception: " . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    });

    register_shutdown_function(function () use ($logger) {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $logger->error("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");
        }
    });
}
?>

<div class="lic-mbox-wrapper bet-receipt bet-receipt--<?= $ticket_type ?> <?= phive()->isMobile() ? "bet-receipt--mobile" : "" ?>"
     xmlns="http://www.w3.org/1999/html">
    <?php
        $top_part_data = (new TopPartFactory())->create('mbox-msg', "sports-history.receipt.title.$ticket_type", false, false);
        $mbox->topPart($top_part_data);
    ?>
    <div class="lic-mbox-container">
        <div class="bet-receipt__general-info" >
            <span class="bet-receipt__general-info-item bet-receipt__general-info-item--left"><?= t("sports-history.betslip.id").": {$bet['ticket_id']}" ?></span>
            <span class="bet-receipt__general-info-item bet-receipt__general-info-item--right"><?= lcDate($bet['bet_placed_at'] ?? $bet['created_at']) ?></span>
        </div>

        <?php if (!$is_betradar): ?>
        <div class="bet-receipt__general-info" style="margin-top: -35px">
            <span class="bet-receipt__general-info-item bet-receipt__general-info-item--left"></span>
            <span class="bet-receipt__general-info-item bet-receipt__general-info-item--right">
            <?php if ($is_cashout): ?>
                <span class="small"><?= $cashout['name'] ?></span>
                (<span class="bet-receipt-selection__value bet-receipt-selection__value--market bet-receipt-selection__value--status-<?= htmlspecialchars($cashout['status'], ENT_QUOTES, 'UTF-8') ?>" style="display: inline">
                    <?= t("sports-history.receipt.$ticket_status") ?>
                </span>)
            <?php else: ?>
                <span class="bet-receipt-selection__value bet-receipt-selection__value--market bet-receipt-selection__value--status-<?= htmlspecialchars($cashout['status'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= t("sports-history.receipt.$ticket_status") ?>
                </span>
            <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>

        <div class="bet-receipt__selections">
            <?php
                foreach ($selections as $selection){
                    $teams = [];
                    $competitors = !empty($selection['competitors']) ? json_decode($selection['competitors']) : [];
                    foreach ($competitors as $competitor) {
                        $key = $competitor->competitor_qualifier === 'home' ? 'competitor1' : 'competitor2';
                        $teams[$key] = $competitor->competitor_name;
                    }

                    $selection_status = getSelectionStatus($selection);
                    $is_outright = !str_contains($selection['event_ext_id'], 'match');
                    $is_outcome_competitor = str_contains($selection['outcome_id'], 'sr:competitor');
            ?>
                <div class="bet-receipt__selection">
                    <div class="bet-receipt-selection__labels">
                        <div class="bet-receipt-selection__label bet-receipt-selection__label--extended">
                            <span class="bet-receipt-selection-label__name">
                                <?= $is_outcome_competitor
                                    ? $selection['event_info']['outcome_competitor_original_name']
                                    : sT("sb.market_outcome.{$selection['event_info']['market_outcome_ext_id']}.name", $selection['specifiers'], $teams);
                                ?>
                            </span>
                            <?php if(!empty($selection['event_info']['producer_ext_id']) && $selection['event_info']['producer_ext_id'] === '1'): ?>
                                <div class="bet-receipt-selection-label__marker"><?= t('sb.betslip.selection.live') ?></div>
                            <?php endif ?>
                        </div>
                        <?php if (!empty($selection['competitor_name'])): ?>
                            <span class="bet-receipt-selection__label bet-receipt-selection__label--event left"><?= $selection['competitor_name'] ?></span>
                        <?php elseif($is_outright): ?>
                            <span class="bet-receipt-selection__label bet-receipt-selection__label--market bet-receipt-selection__label--status-<?= $selection_status ?>"><?= $selection['market'] ?></span>
                        <?php else: ?>
                            <span class="bet-receipt-selection__label bet-receipt-selection__label--event"><?= $teams['competitor1'] . " " . t("sb.vs") . " " . $teams['competitor2'] ?></span>
                            <span class="bet-receipt-selection__label bet-receipt-selection__label--market bet-receipt-selection__label--status-<?= $selection_status ?>"><?= sT("sb.market.{$selection['event_info']['market_ext_id']}.name", $selection['specifiers'], $teams) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="bet-receipt-selection__values">
                        <?php if(!empty($selection['odds'])): ?>
                        <span class="bet-receipt-selection__value bet-receipt-selection__value--name" data-odds="<?php echo $selection['odds'] ?>"><?= number_format($selection['odds'], 2) ?></span>
                        <?php endif ?>
                        <?php if(!empty($selection['dead_heat_factor'])): ?>
                            <span title="<?= t('sb.betslip.my_bets.dead_heat_factor_explanation') ?>" class="bet-receipt-selection__value bet-receipt-selection__value--dead-heat dead-heat-element">
                                <?= t('sb.betslip.my_bets.dead_heat_of') ?>
                                <strong><?= calculateDeadHeatOf($selection['dead_heat_factor']) ?></strong>
                            </span>
                        <?php endif ?>
                        <?php if(!empty($selection['event_info']['event_date'])): ?>
                        <span class="bet-receipt-selection__value bet-receipt-selection__value--event"><?= formatEventDate($selection['event_info']['event_date']) ?></span>
                        <?php endif ?>
                        <?php if ($is_betradar) :?>
                        <span class="bet-receipt-selection__value bet-receipt-selection__value--market bet-receipt-selection__value--status-<?= $selection_status ?>"><?= t("sports-history.receipt.$selection_status") ?></span>
                        <?php endif ?>
                    </div>
                </div>
            <?php
                }
            ?>
        </div>

        <div class="bet-receipt__summary">
            <?php if($ticket_type === 'single'): ?>
                <div class="bet-receipt-summary__labels">
                <?php if ($is_partial_cashout):?>
                    <span class="bet-receipt-summary__label"><?= t("sports-history.receipt.cashout-stake") ?></span>
                <?php endif ?>
                    <span class="bet-receipt-summary__label"><?= t("sports-history.receipt.single-bet") ?> <?= efEuro($bet['amount'], true) ?></span>
                </div>
                <div class="bet-receipt-summary__values">
                    <?php if ($is_partial_cashout):?>
                        <span class="bet-receipt-summary__value text-black"><?= efEuro($cashout['amount'], true) ?></span>
                    <?php endif?>
                    <span class="bet-receipt-summary__value"><?= efEuro($bet['amount'], true) ?></span>
                </div>
            <?php else: ?>
                <div class="bet-receipt-summary__labels">
                    <span class="bet-receipt-summary__label"><?= t("sports-history.receipt.no-bets") ?></span>
                    <span class="bet-receipt-summary__label"><?= t("sports-history.receipt.unit-stake") ?></span>
                    <?php if ($is_partial_cashout):?>
                    <span class="bet-receipt-summary__label"><?= t("sports-history.receipt.cashout-stake") ?></span>
                    <?php endif ?>
                    <span class="bet-receipt-summary__label"><?= t("sports-history.receipt.total-stake") ?></span>
                </div>
                <div class="bet-receipt-summary__values">
                    <span class="bet-receipt-summary__value"><?= count($selections) ?></span>
                    <?php if ($is_partial_cashout):?>
                        <span class="bet-receipt-summary__value"><?= efEuro($bet['amount'] + $cashout['amount'], true) ?></span>
                        <span class="bet-receipt-summary__value text-black"><?= efEuro($cashout['amount'], true) ?></span>
                    <?php else:?>
                        <span class="bet-receipt-summary__value"><?= efEuro($bet['amount'], true) ?></span>
                    <?php endif ?>
                    <span class="bet-receipt-summary__value"><?= efEuro($bet['amount'], true) ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
            if ($void) {
                ?>
                <div class="bet-receipt__result bet-receipt__result--<?= getTicketStatus($bet, $win, $void) ?>"><?= t("sports-history.receipt.net-winnings") ?> <?= efEuro($void[0]['amount'] ?? 0, true) ?></div>
                <?
            } else {
                ?>
                <div class="bet-receipt__result bet-receipt__result--<?= getTicketStatus($bet, $win) ?>"><?= t("sports-history.receipt.net-winnings") ?> <?= efEuro($win[0]['amount'] ?? 0, true) ?></div>
                <?php
            }
        ?>
    </div>
</div>

<script type="text/javascript">
    $('.dead-heat-element').tooltip();

    $(function(){
        let elements = $('.bet-receipt-selection__value--name');
        for(let i = 0; i < elements.length; i++) {
            let decimalOdds = elements[i].getAttribute('data-odds');
            elements[i].innerHTML = getOddsDefaultFormat(decimalOdds);
        }
    });

    function getOddsDefaultFormat(decimalOdds) {
        let showInFraction = "<?php echo lic('getLicSetting', ['odds_format_fraction']); ?>";
        let localStorageKey = "<?php echo phive('Localizer')->getSetting('ls_odds_format_key'); ?>";
        let oddsFormat = JSON.parse(window.localStorage.getItem(localStorageKey))?.value;

        if (oddsFormat) {
            showInFraction = oddsFormat != 'decimal' ? true : false;
        }

        if (!showInFraction) {
            return toFixedDecimal(decimalOdds);
        }

        return getCustomFractionalOdds(decimalOdds) || convertToFractionalOdds(decimalOdds);
    }
</script>
