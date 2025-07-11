<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">
            Sports Bets and Wins
        </h3>
        <span class="float-right">@include('admin.user.betsandwins.partials.sportsbook-download-button')</span>
    </div>
    <div class="card-body">
        <div class="table-responsive-sm">
            <table id="user-sportsbook-bets-datatable"
                   class="table table-striped table-bordered dt-responsive"
                   cellspacing="0"
            >
                <thead>
                <tr>
                    <th>Bet ID</th>
                    <th>Win ID</th>
                    <th>Event Date</th>
                    <th>Bet Date</th>
                    <th>Sport</th>
                    <th>Type</th>
                    <th>Bet Type</th>
                    <th>Bet Amount ({{ $user->currency }})</th>
                    <th>Win Amount ({{ $user->currency }})</th>
                    <th>End Balance</th>
                    <th>Odds</th>
                    <th>Actual Win</th>
                    <th></th>

                </tr>
                </thead>
                <tbody>
                <?php $bets_sum = 0 ?>
                @foreach($sportsbook_bets as $bet)
                        <?php $bets_sum += $bet->amount ?>
                    <tr>
                        <td>{{ $bet->bet_id }}</td>
                        <td>{{ $bet->win_id }}</td>
                        <td>{{ $bet->event_dates }}</td>
                        <td>{{ $bet->bet_placed_date ?? $bet->bet_date }}</td>
                        <td>{{ $bet->game }}</td>
                        <td>{{ $bet->type }}</td>
                        <td>{{ ucfirst($bet->ticket_type) }}</td>
                        <td>{{ $bet->bet_amount / 100 }}</td>
                        <td>Potential Win: {{ $bet->potential_win / 100 }}</td>
                        <td>{{ $bet->end_balance / 100 }}</td>
                        <td>Total Odd: {{ $bet->odds }}</td>
                        <td>{{ $bet->actual_win / 100 }}</td>
                        <td class="dt-control fa fa-plus"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    $(function () {
    let $orderSelect = $('#select-order');
    let orderValue = ($orderSelect.val() || 'desc').toLowerCase();

    table = $('#user-sportsbook-bets-datatable').DataTable(
        {
            paging: true,
            ordering: true,
            columnDefs: [
                { orderable: false, targets: 11 }
            ],
            order: [[0, orderValue]],
            language: {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            }
        }
    );

    $orderSelect.on('change', function() {
        let newOrderValue = $(this).val().toLowerCase();
        table.order([0, newOrderValue]).draw();
    });

    user = <?= $user->id ?>;
    function format ( d ) {
        var div = $('<div/>')
            .addClass( 'loading' )
            .text( 'Loading...' );

        var betSettlementDetails = $.ajax({
            url: `/admin2/userprofile/${user}/bets-wins/sportsbook-settlement-details/${d[0]}`,
            dataType: 'json',
        });

        var sportsbookDetails = $.ajax({
            url: `/admin2/userprofile/${user}/bets-wins/sportsbook-details/${d[0]}`,
            dataType: 'json',
        });

        $.when(betSettlementDetails, sportsbookDetails).done(function (settlementResult, json) {
            settlementResult = settlementResult[0];
            json = json[0];

            let details_html = '';
            let canReopen = settlementResult.can_reopen === true;
            let canSettle = settlementResult.can_settle === true;
            let settlementHeader = canSettle || canReopen ? "Bet Settlement" : "";
            let settleButton = canSettle ? "Settle Bets" : (canReopen ? "Reopen Bets" : "");

            for (const bet of json) {
                details_html += `
                    <tr>
                        <td id="settle-match-${d[0]}-${bet.match_id}">${bet.match_id}</td>
                        <td id="settle-start-${d[0]}-${bet.match_id}">${bet.event_date || ''}</td>
                        <td id="settle-event-${d[0]}-${bet.match_id}">
                            ${bet.event}
                        </td>
                        <td id="settle-selection-${d[0]}-${bet.match_id}">${bet.selection}</td>
                        <td id="settle-market-${d[0]}-${bet.match_id}">${bet.market}</td>
                        <td id="settle-odds-${d[0]}-${bet.match_id}">
                            ${bet.odds_change_applied === 'true' ?
                                `<s class="text-red"> ${bet.requested_odds}</s>` :
                                ''}
                            ${bet.odds}
                        </td>
                        <td id="settle-result-${d[0]}-${bet.match_id}">${ucfirst(bet.result)}</td>
                `;

                if (canSettle) {
                    details_html += `
                        <td>
                            <select class="form-control select2-class settle-confirmation"
                                    id="settle_as_${d[0]}"
                                    data-select-match-id="${bet.match_id}"
                                    data-event-ext-id="${bet.event_ext_id}"
                                    data-bet-id="${d[0]}"
                                    onchange="addDetailsToConfirmationModal()">
                                <option value=""> Choose </option>
                                <option value="win">Win</option>
                                <option value="loss">Loss</option>
                                <option value="void">Void</option>
                            </select>
                        </td>
                    `;
                }


                if (canReopen) {
                    details_html += `
                        <td id="settle-result-${bet.match_id}-${bet.event_ext_id}">
                            <input type="hidden"
                                   class="form-control reopen-confirmation-${d[0]}"
                                   id="reopen_as_${d[0]}"
                                   data-select-match-id="${bet.match_id}"
                                   data-event-ext-id="${bet.event_ext_id}"
                                   data-bet-id="${d[0]}"
                                   data-bet-action="reopen"
                                   value="reopen">
                        </td>
                    `;
                }

                details_html += `</tr>`;
            }

            if (canSettle || canReopen) {
                details_html += `
                    <tr>
                        <td colspan="7"></td>
                        <td>
                            <button class="btn btn-info"
                                    id="settleConfirm-${d[0]}"
                                    onclick="${canReopen ?
                                        `performReopenModalActions(${d[0]})` :
                                        `showSettleConfirmation('settle', ${d[0]})`}"
                              >
                                ${settleButton}
                            </button>
                        </td>
                    </tr>
                `;
            }

            div.html(`
                <table class="table table-responsive table-striped table-bordered dt-responsive details-table">
                    <thead>
                        <tr>
                            <th>Match ID</th>
                            <th>Start time</th>
                            <th>Event</th>
                            <th>Selection</th>
                            <th>Betting Market</th>
                            <th>Odds</th>
                            <th>Results</th>
                            <th>${settlementHeader}</th>
                        </tr>
                    </thead>
                    ${details_html}
                </table>`
            ).removeClass('loading');
        });

        return div;
    }

    $('#user-sportsbook-bets-datatable tbody').on('click', 'td.dt-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row( tr );

        if ( row.child.isShown() ) {
            row.child.hide();
            tr.removeClass('shown');
            $(this).removeClass('fa-minus');
            $(this).addClass('fa-plus');
        }
        else {
            row.child( format(row.data()) ).show();
            tr.addClass('shown');
            $(this).removeClass('fa-plus');
            $(this).addClass('fa-minus');
        }
    } );

        table.on( 'preDraw', function () {
            $('.details-table').remove();
            $(this).find('tr.shown').removeClass('shown');
            $(this).find('tr td.dt-control').removeClass('fa-minus').addClass('fa-plus');
            });
    });


    function performReopenModalActions(betId) {
        let action = 'reopen'
        addDetailsToConfirmationModal(action, betId)
        showSettleConfirmation(action, betId);
    }

    function addDetailsToConfirmationModal(actionType, betId) {
        //collect all selected option
        const selectedSettleOptions = [];
        const settleOption = (actionType === 'reopen') ?
            document.querySelectorAll(`.reopen-confirmation-${betId}`) :
            document.querySelectorAll(`.settle-confirmation`);

        settleOption.forEach(function (select){
            const selectedValue = select.value;
            const matchId = select.dataset.selectMatchId;
            const betId = select.dataset.betId;

            if(selectedValue){
                if (!selectedSettleOptions[betId]) {
                    selectedSettleOptions[betId] = [];
                }
                selectedSettleOptions[betId].push({
                    betId: betId,
                    betSettlement: selectedValue,
                    matchId: matchId,
                    startTime: document.querySelector(`#settle-start-${betId}-${matchId}`).innerText,
                    event: document.querySelector(`#settle-event-${betId}-${matchId}`).innerText,
                    selection: document.querySelector(`#settle-selection-${betId}-${matchId}`).innerText,
                    market: document.querySelector(`#settle-market-${betId}-${matchId}`).innerText,
                    odds: document.querySelector(`#settle-odds-${betId}-${matchId}`).innerText,
                    result: document.querySelector(`#settle-result-${betId}-${matchId}`).innerText,
                    extId: select.dataset.eventExtId,
                });
            }

        })

        return selectedSettleOptions;
    }

    function showSettleConfirmation(actionType, betId) {
        let settlementConfirmationDetails = addDetailsToConfirmationModal(actionType, betId);

        if (!settlementConfirmationDetails.length){
            return alert("No Confirmation Details Provided");
        }

        settlementConfirmationDetails = settlementConfirmationDetails[betId];

        if (!settlementConfirmationDetails){
            return alert(`No Confirmation Details Provided for Bet with ID ${betId}`);
        }

        let settlementConfirmationModalContent = '';
        settlementConfirmationDetails.forEach(function (option) {
            settlementConfirmationModalContent += '<tr>';
            settlementConfirmationModalContent += `
                <td> ${option.matchId} </td>
                <td> ${option.startTime} </td>
                <td> ${option.event} </td>
                <td> ${option.selection} </td>
                <td> ${option.market} </td>
                <td> ${option.odds} </td>
                <td> ${option.result} </td>`
            settlementConfirmationModalContent += `<td>
                <input class="plainInputDisplay text-center"
                        name="settleStatus[]"
                        type="text"
                        value="${option.betSettlement}"
                        readonly>
                <input type="hidden" name="eventExtId[]" value="${option.extId}" readonly>
                <input type="hidden" name="betId" value="${betId}" readonly>
                <input type="hidden" name="action" value="${actionType}" readonly>
            </td>`;
            settlementConfirmationModalContent += '</tr>';
        });

        document.getElementById('betSettlementConfirmModal')
            .querySelector('.modal-body .confirmationDetails')
            .innerHTML = settlementConfirmationModalContent;

        $('#betSettlementConfirmModal').modal({
            backdrop: 'static',
            keyboard: false
        }, 'show')
    }
</script>
<style>
    #user-sportsbook-bets-datatable>tbody>tr.shown {
        background-color: #eaeaea;
    }
    #user-sportsbook-bets-datatable tr.shown ~ tr:not([role]) > td:first-of-type {
        padding: 0;
    }
    #user-sportsbook-bets-datatable .loading {
        padding: 5px 2px;
    }
    .dt-control {
        text-align: center;
        width: 100%;
        padding: 8px 0;
    }
    .plainInputDisplay {
        outline: none;
        border: none;
        text-transform: uppercase;
        font-weight: bolder;
    }
</style>

<div id="betSettlementConfirmModal" class="modal fade" data-bs-backdrop='static'>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 class="modal-title">Confirm Settlement</h3>
            </div>
            <form method="post"
                  action="{{ $app['url_generator']->generate('sportsbook.manual-ticket-settlement',
                    ['user' => $user->id ]) }}">
                <input type="hidden" name="token" value="{{ $_SESSION['token'] }}">

                <div class="modal-body table-responsive">
                <table class="table table-bordered table-responsive text-center">
                    <tr>
                        <th> Match ID </th>
                        <th> Start Time </th>
                        <th> Event </th>
                        <th> Selection </th>
                        <th> Market </th>
                        <th> Odds </th>
                        <th> Results </th>
                        <th> Bet Settlement </th>
                    </tr>

                    <tbody class="confirmationDetails text-center"></tbody>
                    <tfoot>
                    <tr class="text-center" style="font-weight: bold">
                        <td colspan="3">Change User Balance</td>
                        <td class="text-center">
                            <label>
                                <select class="form-control"
                                        name="changeBalance">
                                    <option value="true">Yes</option>
                                    <option value="false">No</option>
                                </select>
                            </label>
                        </td>
                    </tr>
                    </tfoot>

                </table>


            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-success">Confirm</button>
            </div>
            </form>
        </div>
    </div>
</div>
