@extends('admin.layout')

@section('content-header')
    @include('admin.rg.partials.content-header', array("active" => "User Interaction Result Report"))
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.rg.partials.topmenu')

        <form id="obj-datatable_filter" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}" method="get">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title">Filters</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-4 col-lg-2">
                            @include('admin.filters.month-picker')
                        </div>
                        <div class="form-group col-4 col-lg-2">
                            @include('admin.filters.country-filter')
                        </div>
                    </div>
                </div><!-- /.card-body -->
                <div class="card-footer">
                    <button type="submit" class="btn btn-info">Search</button>
                </div><!-- /.card-footer-->
            </div>
        </form>


        @foreach($view_data as $month => $classified_interaction_result)
            <div class="card card-solid card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ $month }}</h3>
                </div>
                <div class="card-body">
                    <div id="obj-datatable_wrapper" class="dataTables_wrapper form-inline dt-bootstrap no-footer">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="table-responsive" >
                                    @if(empty($classified_interaction_result))
                                        No data
                                    @endif
                                    @foreach($classified_interaction_result as $action_name => $classified_row)
                                        <table class="interaction-results table table-bordered" >
                                            <tbody>
                                                <tr>
                                                    <th>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['action']) }}">{{ \App\Models\Action::NAME_MAP[$action_name] }}
                                                            <span class="badge pull-right">{{count($classified_row['action'])}}</span>
                                                        </a>
                                                    </th>
                                                    <td>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['active']) }}">Active in
                                                            <span class="badge pull-right">{{count($classified_row['active'])}}</span>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['not_active']) }}">Not active in
                                                            <span class="badge pull-right">{{count($classified_row['not_active'])}}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['has_limit']) }}">Has a limit now
                                                            <span class="badge pull-right">{{count($classified_row['has_limit'])}}</span>
                                                        </a></td>
                                                    <td>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['self_locked']) }}">Self-lock
                                                            <span class="badge pull-right">{{count($classified_row['self_locked'])}}</span>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['self_excluded']) }}">Self-exclusion
                                                            <span class="badge pull-right">{{count($classified_row['self_excluded'])}}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['deposit_decrease']) }}">Decreased 10% or more total deposit amount
                                                            <span class="badge pull-right">{{count($classified_row['deposit_decrease'])}}</span>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['loss_decrease']) }}">Decreased 10% or more in total losses
                                                            <span class="badge pull-right">{{count($classified_row['loss_decrease'])}}</span>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['time_spent_decrease']) }}">Decreased 10% or more in total time spent
                                                            <span class="badge pull-right">{{count($classified_row['time_spent_decrease'])}}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['deposit_increase']) }}">Increased 10% or more total deposit amount
                                                            <span class="badge pull-right">{{count($classified_row['deposit_increase'])}}</span>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['loss_increase']) }}">Increased 10% or more in total losses
                                                            <span class="badge pull-right">{{count($classified_row['loss_increase'])}}</span>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['time_spent_increase']) }}">Increased 10% or more in total time spent
                                                            <span class="badge pull-right">{{count($classified_row['time_spent_increase'])}}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['deposit_same']) }}">Same deposit amount (below 10% increase or decrease)
                                                            <span class="badge pull-right">{{count($classified_row['deposit_same'])}}</span>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['loss_same']) }}">Same total loss amount (below 10% increase or decrease)
                                                            <span class="badge pull-right">{{count($classified_row['loss_same'])}}</span>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="#" data-ids="{{ implode(',',$classified_row['time_spent_same']) }}">Same total time spent (below 10% increase or decrease)
                                                            <span class="badge pull-right">{{count($classified_row['time_spent_same'])}}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <br>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        <form id="hidden-user-form" method="post" action="{{ $app['url_generator']->generate('user.search') }}" target="_blank">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            <input type="hidden" name="user[id]" class="form-control">
        </form>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function() {
            $('.interaction-results a').click(function(e) {
                e.preventDefault();
                var ids = $(this).data('ids');
                if (ids) {
                    $('#hidden-user-form > input[name="user[id]"]').val(ids);
                    $('#hidden-user-form').submit();
                }
            });
        });
    </script>
@endsection
