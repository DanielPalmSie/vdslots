@extends('admin.layout')

@section('content')
    @include('admin.accounting.partials.topmenu')
    @include('admin.accounting.partials.transaction-history-filter')
    <div class="card card-solid card-primary">
        <div class="card-header">
            <ul class="list-inline card-title">
                <li><b>Transaction history</b></li>
            </ul>
            @if(!empty($paginator['data']) && p('accounting.section.transaction-history.download.csv'))
                <a class="transaction-history-btn mr-2 float-right" href="javascript:void(0)" data-export="1"><i class="fa fa-download"></i> Download</a>
                <a class="transaction-history-btn mr-2 float-right" href="javascript:void(0)" data-export="2"><i class="fa fa-user-plus"></i> Send to browse users</a>
            @endif
        </div>
        <div class="card-body">
            <div class='table-responsive'>
                <table id="accounting-section-datatable" class="table table-striped table-bordered dt-responsive w-100 border-collapse">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Exec date</th>
                        <th>User</th>
                        <th>Method</th>
                        <th>Sub Method</th>
                        <th>Details</th>
                        <th>Type</th>
                        <th>Currency</th>
                        <th>Amount</th>
                        <th>Fee</th>
                        <th>Deducted</th>
                        <th>Status</th>
                        <th>External ID</th>
                        <th>Internal ID</th>
                        <th>Loc ID</th>
                        <th>Appr. By</th>
                        <th>Country</th>
                        <th>Province</th>
                        <th>MTS ID</th>
                        @if($status === 'disapproved')
                            <th>Error Reason</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($paginator['data'] as $element)

                        <tr>
                            <td>{{ $element->date }}</td>
                            <td>{{ $element->exec_date }}</td>
                            <td>{{ $element->user_id}}</td>
                            <td>{{ $element->method }}</td>
                            <td>{{ $element->submethod }}</td>
                            <td>{{ $element->details }}</td>
                            <td>{{ $element->type }}</td>
                            <td>{{ $element->currency }}</td>
                            <td>{{ $element->amount }}</td>
                            <td>{{ $element->fee }}</td>
                            <td>{{ $element->deducted }}</td>
                            <td>{{ $element->status }}</td>
                            <td>{{ $element->ext_id }}</td>
                            <td>{{ $element->id }}</td>
                            <td>{{ $element->loc_id }}</td>
                            <td>{{ $element->actor }}</td>
                            <td>{{ $element->country }}</td>
                            <td>{{ $element->province }}</td>
                            <td>{{ $element->mts_id }}</td>
                            @if($status === 'disapproved')
                                <td>{{ $element->transaction_details['transaction_error']['description'] ?? '' }}</td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection


@section('footer-javascript')
    @parent
    <script>
        $(function () {
            //todo quick fix, should move this in the future into a global function
            $('.transaction-history-btn').on( 'click', function (e) {
                e.preventDefault();
                var self = $(this);
                var form = $('#filter-form-transaction-history');
                var input_export = $("<input>").attr("type", "hidden").attr("name", "export").val("1");
                var input_send = $("<input>").attr("type", "hidden").attr("name", "sendtobrowse").val("1");
                if (self.data("export") == 1) {
                    form.append($(input_export));
                } else if (self.data("export") == 2) {
                    form.append($(input_export));
                    form.append($(input_send));
                }
                form.submit();
            });

            var username_url = "{{ \App\Helpers\URLHelper::generateUserProfileLink($app) }}";
            var table_init = {};
            table_init['processing'] = true;
            table_init['serverSide'] = true;
            table_init['ajax'] = {
                "url" : "{{ $app['url_generator']->generate('accounting-transaction-history', ['user' => $user->id]) }}",
                "type" : "POST",
                "data": function(d){
                    d.form = $('#filter-form-transaction-history').serializeArray();
                }
            };
            table_init['columns'] = [
                { "data": "date" },
                { "data": "exec_date" },
                {
                    "data": "user_id",
                    "render": function ( data ) {
                        return '<a target="_blank" href="' + username_url + data + '/">' + data + '</a>';
                    }
                },
                { "data": "method" },
                { "data": "submethod" },
                {
                    "data": "details",
                    "render": data => {
                        if (!data) return "";
                        return data
                            .replace(/\n/g, "<br>")  // Convert new lines to <br>
                            .replace(/ /g, "&nbsp;"); // Preserve spaces
                    }
                },
                { "data": "type" },
                { "data": "currency" },
                { "data": "amount" },
                { "data": "fee" },
                { "data": "deducted" },
                { "data": "status" },
                { "data": "ext_id" },
                { "data": "id" },
                { "data": "loc_id" },
                { "data": "actor" },
                { "data": "country" },
                { "data": "province" },
                { "data": "mts_id" },
                @if($status === 'disapproved')
                    {
                        "data": "transaction_details.transaction_error.description",
                        "orderable": false
                    }
                @endif
            ];
            table_init['searching'] = false;
            table_init['order'] = [ [ 0, 'desc' ] ]; //Order by date
            table_init['language'] = {
                "emptyTable": "No results found.",
                "lengthMenu": "Display _MENU_ records per page"
            };
            /*table_init['responsive'] = { "details" : { "type": 'column' } };*/
            table_init['deferLoading'] = parseInt("{{ $paginator['recordsTotal'] }}");
            table_init['pageLength'] = 25;

            var table = $("#accounting-section-datatable").DataTable(table_init);

        });
    </script>
@endsection
