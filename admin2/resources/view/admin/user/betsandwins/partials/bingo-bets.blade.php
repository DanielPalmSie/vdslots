<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">
            Bingo Bets and Wins
        </h3>
        <span class="float-right">@include('admin.user.betsandwins.partials.bingo-download-button')</span>
    </div>
    <div class="card-body">
        <div class="table-responsive-sm">
            <table id="user-bingo-bets-datatable"
                   class="table table-striped table-bordered dt-responsive"
                   cellspacing="0"
            >
                <thead>
                <tr>
                    <th>Bet ID</th>
                    <th>Bet Date</th>
                    <th>Win ID</th>
                    <th>Round ID</th>
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
                <?php $bets_sum = 0 ?>
                @foreach($bingo_bets as $bet)
                    <?php $bets_sum += $bet->amount ?>
                    <tr>
                        <td>{{ $bet->bet_id }}</td>
                        <td>{{ $bet->bet_date }}</td>
                        <td>{{ $bet->win_id }}</td>
                        <td>{{ $bet->round_id }}</td>
                        <td>{{ $bet->transaction_id }}</td>
                        <td>{{ $bet->ext_transaction_id }}</td>
                        <td class="ucbold text-{{ $class }}">{{ !$is_loss ? $bet->type : 'loss' }}</td>
                        <td>{{ $bet->bet_amount / 100 }}</td>
                        <td>{{ $bet->win_amount / 100 }}</td>
                        <td>{{ $bet->end_balance / 100 }}</td>
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

        table = $('#user-bingo-bets-datatable').DataTable(
            {
                paging: true,
                ordering: true,
                columnDefs: [
                    { orderable: false, targets: 10 }
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

            var bingoDetails = $.ajax({
                url: window.location.origin + `/admin2/bingo-bets/userprofile/${user}/bets-wins/bingo-details/${d[0]}/`,
                dataType: 'json',
            });

            $.when(bingoDetails).done(function (betDetails) {
                let detailsHtml = betDetails.map(bet => `
                    <tr>
                        <td>${bet.bet_id}</td>
                        <td>${bet.created_at || ''}</td>
                        <td>${bet.ext_id}</td>
                        <td>${bet.id}</td>
                        <td><b>${bet.transaction_type || ''}</b></td>
                        <td>${bet.amount / 100}</td>
                        <td>${bet.currency}</td>
                        <td>${bet.user_balance / 100}</td>
                    </tr>`).join('');

                div.html(`
                    <table class="table table-responsive table-striped table-bordered dt-responsive details-table">
                        <thead>
                            <tr>
                                <th>Bet ID</th>
                                <th>Created At</th>
                                <th>Ext Transaction ID</th>
                                <th>Sport Transaction ID</th>
                                <th>Transaction Type</th>
                                <th>Amount</th>
                                <th>Currency</th>
                                <th>End Balance</th>
                            </tr>
                        </thead>
                        ${detailsHtml}
                    </table>`
                ).removeClass('loading');
            });

            return div;
        }

        $('#user-bingo-bets-datatable tbody').on('click', 'td.dt-control', function () {
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
</script>
<style>
    #user-bingo-bets-datatable>tbody>tr.shown {
        background-color: #eaeaea;
    }
    #user-bingo-bets-datatable tr.shown ~ tr:not([role]) > td:first-of-type {
        padding: 0;
    }
    #user-bingo-bets-datatable .loading {
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
