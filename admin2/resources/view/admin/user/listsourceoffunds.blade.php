@extends('admin.layout')

@section('header-css')
    @parent
    {{ loadCssFile("/phive/admin/customization/styles/css/documents.css") }}
@endsection

@section('content')
    @include('admin.user.partials.topmenu')

    <div class="card document-list">
        <h3>List Source of Wealth and Proof of Wealth and Proof of Source of Wealth</h3>

            <p>
                Here is a list of players that have a Source of Wealth document, a Proof of Wealth document, or a Proof of Source of Wealth requested:
            </p>
            <p>
                Wait time is either last created, last uploaded, or last status change.
            </p>
        <div class="card-body pt-1">

            <ul class="nav nav-tabs" id="wealth-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active border-left-0" id="pending-tab" data-toggle="tab" href="#pending" role="tab" aria-controls="pending" aria-selected="true">
                        Account Pending
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="frozen-tab" data-toggle="tab" href="#frozen" role="tab" aria-controls="frozen" aria-selected="false">
                        Account Frozen (&gt;30 days or rejected docs)
                    </a>
                </li>
            </ul>

            <div class="tab-content mt-3" id="wealth-tabs-content">
                <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                    @include('admin.user.partials.listsourceoffunds-table', ['documents' => $documents['pending']])
                </div>
                <div class="tab-pane fade" id="frozen" role="tabpanel" aria-labelledby="frozen-tab">
                    @include('admin.user.partials.listsourceoffunds-table', ['documents' => $documents['frozen']])
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            $('#wealth-tabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                $('#wealth-tabs li').removeClass('border-top border-primary');

                $(e.target).closest('li').addClass('border-top border-primary');
            });

            $('#wealth-tabs a.active').closest('li').addClass('border-top border-primary');
        });
    </script>
@endsection


