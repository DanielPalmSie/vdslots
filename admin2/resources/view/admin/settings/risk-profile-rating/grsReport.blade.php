@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2 align-items-end">
            <div class="col-sm-4">
                <h1 class="m-0">
                    {{ strtoupper($menu) }} Global Score Rating
                </h1>
            </div>
            <div class="col-sm-8">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('/') }}">Responsible Gaming</a></li>
                    <li class="breadcrumb-item active" aria-current="page">RG Global Risk Score Report</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        @php
            if($menu == 'aml'){
            $menu ='fraud';
            }
        @endphp
        @if(!empty($user))
            @include('admin.user.partials.header.actions')
            @include('admin.user.partials.header.main-info')
        @else

            @include("admin.$menu.partials.topmenu")
        @endif

        @include('admin.settings.risk-profile-rating.partials.grsReport-filters')
        <div class="card card-solid card-primary border border-primary">
            <div class="card-header">
                <h3 class="card-title">Global risk Score Report</h3>
            </div><!-- /.card-header -->
            <div class="card-body">

                <table id="user-datatable" class="table table-striped table-bordered dt-responsive"
                    cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        @foreach( $columns as $col => $value)
                            @if(!in_array($col, $exclude_columns))
                            <td>{{ $value}}</td>
                            @endif
                        @endforeach
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/jquery-ui/jquery-ui.min.js"></script>
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $("#select-type").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('type') }}").change();
        });

        $(function () {
            var table = $('#user-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}",
                    data: function (d) {
                        d.form = $('#grs-report').serializeArray();
                    }
                },
                columns: [
                        @if(empty($user))
                    { data: 'user_id', render: function (data) {
                            return '<a target="_blank" href="{{ \App\Helpers\URLHelper::generateUserProfileLink($app) }}' + data + '/">' + data + '</a>';
                        }},
                    { data: 'rating_tag', render: function (data, type, row) {
                            return row.rating_tag ?? row.rating;
                        }},
                    { data: 'country' },
                    { data: 'created_at' },
                        @else
                    { data: 'created_at' },
                    { data: 'rating_type' },
                    { data: 'rating_tag', render: function (data, type, row) {
                            return row.rating_tag ?? row.rating;
                        }},
                        @endif
                    {
                        data: null,
                        orderable: false,
                        render: function () {
                            return '<div class="text-right"><button class="btn btn-default">+</button></div>';
                        }
                    }
                ],
                order: [[1, 'desc']],
                pageLength: 25
            });
            $('#grs-report').on('change', 'input, select', function() {
                table.ajax.reload();
            });
            $('#user-datatable tbody').on('click', 'button', function () {
                var tr = $(this).closest('tr');
                var row = table.row(tr);

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    $(this).text('+');
                } else {
                    row.child(format(row.data())).show();
                    tr.addClass('shown');
                    $(this).text('-');
                }
            });

            function format(d) {
                // `d` is the original data object for the row
                // Return the HTML to be shown in the child row
                var content = '<table class="table table-striped table-bordered dt-responsive">' +
                    '<thead>' +
                    '<tr>' +
                    '<th><strong>Influenced by:</strong></th>' +
                    '<th><strong>Score:</strong></th>' +
                    '</tr>' +
                    '</thead>' +
                    '<tbody>';

                $.each(d.influenced_by, function(key, value) {
                    content += '<tr><td>' + key + '</td><td>' + value + '</td></tr>';
                });

                content += '</tbody></table>';

                return content;
            }
        });
    </script>
@endsection
