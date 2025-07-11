<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">
            PoolX Bets and Wins
        </h3>
        <span class="float-right">@include('admin.user.betsandwins.partials.poolx-download-button')</span>
    </div>
    <div class="card-body">
        <div class="table-responsive-sm">
            <table id="user-poolx-bets-datatable"
                   class="table table-striped table-bordered dt-responsive"
                   cellspacing="0"
            >
                <thead>
                <tr>
                    <th>Bet ID</th>
                    <th>Bet Date</th>
                    <th>Win ID</th>
                    <th>Round ID</th>
                    <th>Player Round ID</th>
                    <th>Transaction ID</th>
                    <th>Ext Transaction ID</th>
                    <th>Type</th>
                    <th>Bet Amount ({{ $user->currency }})</th>
                    <th>Actual Win Amount ({{ $user->currency }})</th>
                    <th>End Balance</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @php $bets_sum = 0; @endphp
                @foreach($poolX_bets as $p_bet)
                    @php
                        $is_loss = false;
                        $class = '';
                            switch ($p_bet->type) {
                                case 'bet':
                                    if ($p_bet->ticket_settled) {
                                        $class = 'warning';
                                        $is_loss = true;
                                    } else {
                                        $class = 'dark';
                                    }
                                    break;
                                case 'void':
                                    $class = 'danger';
                                    break;
                                case 'win':
                                    $class = 'success';
                                    break;
                                default:
                                    $class = 'dark';
                            }
                        $bets_sum += $p_bet->amount;
                    @endphp
                    <tr>
                        <td>{{ $p_bet->bet_id }}</td>
                        <td>{{ $p_bet->bet_date }}</td>
                        <td>{{ $p_bet->win_id }}</td>
                        <td>{{ $p_bet->round_id }}</td>
                        <td>{{ $p_bet->player_round_id }}</td>
                        <td>{{ $p_bet->transaction_id }}</td>
                        <td>{{ $p_bet->ext_transaction_id }}</td>
                        <td class="ucbold text-{{ $class }}">{{ !$is_loss ? $p_bet->type : 'loss' }}</td>
                        <td>{{ $p_bet->bet_amount / 100 }}</td>
                        <td>{{ $p_bet->win_amount / 100 }}</td>
                        <td>{{ $p_bet->end_balance / 100 }}</td>
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

        let table = $('#user-poolx-bets-datatable').DataTable({
            paging: true,
            ordering: true,
            columnDefs: [
                {orderable: false, targets: 11}
            ],
            order: [[0, orderValue]],
            language: {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            }
        });

        $orderSelect.on('change', function() {
            let newOrderValue = $(this).val().toLowerCase();
            table.order([0, newOrderValue]).draw();
        });

        let user = {{ json_encode($user->id) }};

        function format(d) {
            let keys = [
                'bet_id', 'bet_date', 'win_id', 'round_id',
                'player_round_id', 'transaction_id', 'ext_transaction_id',
                'type', 'amount', 'win_amount'
            ];

            let formattedData = {};
            keys.forEach((key, i) => {
                formattedData[key] = d[i];
            });

            let bet_id = formattedData['bet_id'];
            let div = $('<div/>').addClass('loading').text('Loading...');

            $.getJSON(`/admin2/poolx-bets/userprofile/${user}/bets-wins/poolx-details/${bet_id}`, function (bet_details) {
                let details_html = bet_details.map(tx => `
                    <tr>
                        <td>${tx.bet_id}</td>
                        <td>${tx.created_at}</td>
                        <td>${tx.ext_transaction_id}</td>
                        <td>${tx.id}</td>
                        <td>${tx.player_round_id}</td>
                        <td>${tx.round_id}</td>
                        <td class="ucbold">${tx.type}</td>
                        <td>${tx.amount / 100}</td>
                        <td>${tx.user_balance / 100}</td>
                    </tr>`).join('');

                div.html(`
                    <table class="table table-responsive table-striped table-bordered dt-responsive details-table">
                        <thead>
                            <tr>
                                <th>Bet ID</th>
                                <th>Bet Date</th>
                                <th>Ext Transaction ID</th>
                                <th>Transaction ID</th>
                                <th>Player Round ID</th>
                                <th>Round ID</th>
                                <th>Transaction Type</th>
                                <th>Amount</th>
                                <th>End Balance</th>
                            </tr>
                        </thead>
                        <tbody>${details_html}</tbody>
                    </table>`).removeClass('loading');
            });

            return div;
        }

        $('#user-poolx-bets-datatable tbody').on('click', 'td.dt-control', function () {
            let tr = $(this).closest('tr');
            let row = table.row(tr);

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                $(this).removeClass('fa-minus').addClass('fa-plus');
            } else {
                row.child(format(row.data())).show();
                tr.addClass('shown');
                $(this).removeClass('fa-plus').addClass('fa-minus');
            }
        });

        table.on('preDraw', function () {
            $('.details-table').remove();
            $(this).find('tr.shown').removeClass('shown');
            $(this).find('tr td.dt-control').removeClass('fa-minus').addClass('fa-plus');
        });
    });
</script>
<style>
    #user-poolx-bets-datatable > tbody > tr.shown {
        background-color: #eaeaea;
    }

    #user-poolx-bets-datatable tr.shown ~ tr:not([role]) > td:first-of-type {
        padding: 0;
    }

    #user-poolx-bets-datatable .loading {
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

    .ucbold {
        font-weight: bolder;
        text-transform: uppercase;
    }
</style>
