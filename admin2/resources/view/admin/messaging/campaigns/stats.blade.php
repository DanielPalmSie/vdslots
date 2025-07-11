
@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.messaging.partials.topmenu')

        <div class="card">
            <div class="nav-tabs-custom">
                @include('admin.messaging.partials.submenu')
                <div class="tab-content">
                    <div class="tab-pane active">
                        @include('admin.messaging.campaigns.partials.stats-progress')
                        @if($progress['queue'] > 0)
                            @include('admin.messaging.campaigns.partials.stats-queue-list')
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#sms-campaigns-list-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ 2, "desc"]]
            });
        });
    </script>

@endsection
