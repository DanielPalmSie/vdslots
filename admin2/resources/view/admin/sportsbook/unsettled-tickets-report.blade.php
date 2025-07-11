@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Bet Settlement Report</h1>
            </div>

            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('sportsbook.index') }}">Sportsbook</a></li>
                    <li class="breadcrumb-item active">Unsettled Bets</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.sportsbook.partials.topmenu')

        @if($error)
            <div class="alert alert-danger"> {{ $data }} </div>
        @endif

        @if(!$error && is_string($data))
            <div class="alert alert-info"> {!! $data !!} </div>
        @endif

        <div class="card card-primary">
            <div class="card-header py-4">
                <form method="GET"
                    action="{{ $app['url_generator']->generate('sportsbook.generate-unsettled-tickets-report') }}">

                    <div class="row">
                        <div class="col-md-4">
                            <label> From (Optional) </label>
                            <input type="date" name="fromDate" value="{{$_SESSION['reports_bets_from']}}"
                                class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label> To </label>
                            <input type="date" name="toDate"
                                value="{{$_SESSION['reports_bets_to'] ?? \Carbon\Carbon::today()->toDateString()}}"
                                class="form-control"
                                required>
                        </div>

                        <div class="col-md-4">
                            <label> Brand </label>
                            <select name="brandId"
                                    class="form-control">
                                <option value=""> All</option>
                                @foreach(\App\Services\Sportsbook\Constants\Brands::SportsbookBrands() as $brandId => $brand)
                                    <option
                                        value="{{ $brandId }}"
                                        {{ (($_GET['brandId'] == $brandId ||
                                            $_SESSION['brand_id'] == $brandId)  ?
                                            "selected" : "") }}>
                                        {{ ucfirst($brand) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <button type="submit" class="btn btn-info btn-lg mt-4">Run Report</button>
                        </div>
                    </div>
                </form>
            </div>
            @if(!$error && is_array($data) && count($data) > 0)

                <div class="card-body">
                    <div class="text-right mb-4">
                        <a href="{{ $app['url_generator']->generate('sportsbook.download-unsettled-tickets-report') }}"
                        class="btn btn-info btn-lg">Download</a>
                    </div>

                    <div class="table-responsive">
                        <table id="bets-datatable" class="table table-striped text-center">
                            <thead>
                                <tr>
                                    <th> #</th>
                                    <th> event_ext_id</th>
                                    <th> event_status</th>
                                    <th> producer_ext_id</th>
                                    <th> user_id</th>
                                    <th> ticket_ext_id</th>
                                    <th> ticket_id</th>
                                    <th> ticket_status</th>
                                    <th> ticket_selection_id</th>
                                    <th> brand_id</th>
                                    <th> bet_placed_at </th>
                                    <th> created_at </th>
                                </tr>
                            </thead>

                            <tbody>
                            @php $x = 1; @endphp
                            @foreach($data as $rec => $bet)
                                <tr>
                                    <td>{{ $x }}</td>
                                    <td>{{ $bet["event_ext_id"] }}</td>
                                    <td>{{ $bet["event_status"] }}</td>
                                    <td>{{ $bet["producer_ext_id"] }}</td>
                                    <td>{{ $bet["user_id"] }}</td>
                                    <td>{{ $bet["ticket_ext_id"] }}</td>
                                    <td>{{ $bet["ticket_id"] }}</td>
                                    <td>{{ $bet["ticket_status"] }}</td>
                                    <td>{{ $bet["ticket_selection_id"] }}</td>
                                    <td>{{ $bet["brand_id"] }}</td>
                                    <td>{{ $bet["bet_placed_at"] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($bet["created_at"])->toDateTimeString() }}</td>
                                </tr>

                                @php $x++; @endphp
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#bets-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[5, "asc"]],
                "columnDefs": [{"targets": 15, "orderable": false, "searchable": false}],
                "drawCallback": function (settings) {
                    $(this).wrap("<div class='table-responsive'></div>");
                }
            });
        });
    </script>
@endsection

