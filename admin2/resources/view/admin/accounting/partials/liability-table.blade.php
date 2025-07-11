<div class="card card-solid card-primary">
        <div class="card-header">
            <h3 class="card-title">Player Liability Report @if(!empty($app['request_stack']->getCurrentRequest()->get('currency')))({{ $app['request_stack']->getCurrentRequest()->get('currency') }}) @endif</h3>
            @if(count($data) > 0 && (p('accounting.section.liability.download.csv') || p('user.liability.report.download.csv')))
                <a target="_blank" class="float-right" href="{{ \App\Helpers\DownloadHelper::generateDownloadPath($query_data, ['breakdown' => 1]) }}"><i class="fas fa-download"></i> Download including breakdown</a>
                <a target="_blank" class="mr-2 float-right" href="{{ \App\Helpers\DownloadHelper::generateDownloadPath($query_data) }}"><i class="fas fa-download"></i> Download</a>
            @endif

        @if($show_liabilities_processed_status)
                @if($liabilities_processed)
                    <button class="btn btn-success btn-sm mr-2 float-right">
                        Liabilities Processed
                        <i class="fa fa-check"></i>&nbsp;&nbsp;
                    </button>
                @else
                    <a href="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}&liabilities_processed=1"
                       class="action-set-btn"
                       data-dtitle="Setting liabilities for the month as processed"
                       data-dbody="Are you sure you would like to mark the month as processed?"
                       data-action-type="liabilities_processed"
                       id="liabilities_processed">
                    <button class="btn btn-default btn-sm mr-2 float-right">
                        Liabilities Not Processed
                        <i class="far fa-edit"></i>
                    </button>
                    </a>
                @endif
            @endif
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered dt-responsive w-100 border-collapse"
                   id="liability-section-currency">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Debit</th>
                    <th>Transactions</th>
                    <th>Credit</th>
                    <th>Transactions</th>
                    <th>Net</th>
                </tr>
                </thead>
                <tbody>
                @if(count($opening_data['net']) > 0)
                <tr>
                    <td><b>Opening Balance</b></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>{{ \App\Helpers\DataFormatHelper::nf($opening_data['net']) }}</td>
                </tr>
                @endif
                @foreach($data as $element)
                    <tr>
                        <td><a
                            data-cat="{{ $element->main_cat }}"
                            data-currency="{{ $element->currency }}"
                            data-country="{{ $element->country }}"
                            data-province="{{ json_encode($app['request_stack']->getCurrentRequest()->get('province', [])) }}"
                            data-type="{{ $element->type }}"
                            data-month="{{ $element->month }}"
                            data-year="{{ $element->year }}"
                            data-source="{{ $element->source }}"
                            @if($user) data-user="{{ $user->id }}" @endif
                            href="#" class="show-child-rows"
                        ><i class="far fa-caret-square-right"></i></a>
                            {{ \App\Repositories\LiabilityRepository::getLiabilityCategoryName($element->main_cat)}}
                            @if(in_array($element->main_cat, [14, 13, 15, 17, 21, \App\Repositories\LiabilityRepository::CAT_LIABILITY_ADJUST,\App\Repositories\LiabilityRepository::CAT_BOOSTER_VAULT_TRANSFER, \App\Repositories\LiabilityRepository::CAT_TAX_DEDUCTION])) {{ $element->type == 'debit' ? '(OUT)' : '(IN)' }} @endif
                        </td>
                        @if ($element->type == 'debit')
                            <td>{{ \App\Helpers\DataFormatHelper::nf(abs($element->amount)) }}</td>
                            <td>{{ $element->transactions == 0 ? 'N/A' : $element->transactions }}</td>
                            <td></td>
                            <td></td>
                            <td>{{ \App\Helpers\DataFormatHelper::nf($element->amount) }}</td>
                        @elseif($element->type == 'credit')
                            <td></td>
                            <td></td>
                            <td>{{ \App\Helpers\DataFormatHelper::nf($element->amount) }}</td>
                            <td>{{ $element->transactions == 0 ? 'N/A' : $element->transactions }}</td>
                            <td>{{ \App\Helpers\DataFormatHelper::nf($element->amount) }}</td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
                @if(count($data) > 0 || abs($closing_data['non_categorized_amount']) > 0)
                <tfoot>
                    <tr>
                        <td><b>Unallocated amount</b></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td @if(abs($closing_data['non_categorized_amount']) > 0)bgcolor="#f7bda8"@endif >
                            {{ \App\Helpers\DataFormatHelper::nf($closing_data['non_categorized_amount']) }}
                            @if(abs($closing_data['non_categorized_amount']) > 0 && empty($user))
                            <a href="{{ $app['url_generator']->generate('accounting-liability', array_merge($app['request_stack']->getCurrentRequest()->query->all(), ['m' => 1])) }}">
                                <b>[Breakdown]</b>
                            </a>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><b>Total Net Liability</b></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($closing_data['net_liability'] + $closing_data['non_categorized_amount']) }}</td>
                    </tr>
                    <tr>
                        <td><b>Closing Player Liability</b></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($closing_data['net_liability'] + $closing_data['non_categorized_amount'] + $opening_data['net']) }}</td>
                    </tr>
                    @if($app['debug'])
                        <tr>
                            <td><b>Closing Balance</b></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>{{ \App\Helpers\DataFormatHelper::nf($closing_data['closing_balance']) }}</td>
                        </tr>
                    @endif
                </tfoot>
                @elseif (count($data) == 0 && $closing_data['closing_balance'] != 0)
                <tfoot>
                    <tr>
                        <td><b>Total Net Liability</b></td>
                        <td colspan="4"></td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf(0) }}</td>
                    </tr>
                    <tr>
                        <td><b>Closing Player Liability</b></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>{{ \App\Helpers\DataFormatHelper::nf($closing_data['closing_balance']) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $('.show-child-rows').on('click', function (e) {
            e.preventDefault();
            var url = "{{ $app['url_generator']->generate('accounting-liability-per-category') }}";
            var self = $(this);
            if (self.hasClass("breakdown-open")) {
                var child_row = '.' + self.data('cat') + '-child';
                $(child_row).remove();
                self.removeClass("breakdown-open");
            } else {
                self.addClass("breakdown-open");
                $.ajax({
                    url: url,
                    type: "POST",
                    data: {
                        cat: self.data('cat'),
                        currency: self.data('currency'),
                        year: self.data('year'),
                        month: self.data('month'),
                        type: self.data('type'),
                        country: self.data('country'),
                        province: self.data('province'),
                        source: self.data('source'),
                        user: self.data('user')
                    },
                    success: function (data, textStatus, jqXHR) {
                        if (data.length > 0) {
                            var parent_row = self.parent().parent();
                            $.each(data, function (item, element) {
                                if (element['type'] == 'credit') {
                                    parent_row.after("<tr class='" + element['main_cat'] + "-child'><td>" + element['sub_cat'] + "</td><td></td><td></td><td>" + element['amount'] + "</td><td>" + element['transactions'] + "</td><td></td></tr>");
                                } else {
                                    parent_row.after("<tr class='" + element['main_cat'] + "-child'><td>" + element['sub_cat'] + "</td><td>" + element['amount'] + "</td><td>" + element['transactions'] + "</td><td></td><td></td><td></td></tr>");
                                }
                            });
                        } else {
                            alert("No breakdown data available under this category.")
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.log(textStatus);
                        console.log(errorThrown);
                        alert('AJAX ERROR');
                    }
                });
            }
        });

        $('.action-set-btn').on('click', function(e) {
            e.preventDefault();

            var dialogTitle = $(this).data("dtitle");
            var dialogMessage = $(this).data("dbody");
            var dialogUrl = $(this).attr('href');
            if($(this).data("disabled") != 1){
                showConfirmBtn2(dialogTitle, dialogMessage, dialogUrl);
            }
        });

    </script>
@endsection
