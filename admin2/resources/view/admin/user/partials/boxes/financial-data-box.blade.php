<?php
/**
 * @var \App\Models\User $user
 */
?>

<div class="card card-outline card-warning @if($financial_data_collapse == 1) collapsed-box @endif" id="financial-data-box">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center flex-grow-1">
            <h3 class="card-title text-lg mr-2">
                Financial data -
            </h3>
            <input
                type="text"
                id="financial-daterange-btn"
                name="daterange"
                class="form-control w-50"
                data-url="{{ $app['url_generator']->generate('admin.user-get-financial-data-ajax', ['user' => $user->id]) }}"
                data-target="#ajax-container-financial"
                value="{{ \Carbon\Carbon::now()->subMonths(12)->format('Y-m-d') }} - {{ \Carbon\Carbon::now()->format('Y-m-d') }}"
            />
            <h3 class="card-title text-lg ml-2" id="initial-label-financial">Last 12 months</h3>
        </div>
        <div class="card-tools">
            <button class="btn btn-tool"
                    id="financial-data-box-btn"
                    data-boxname="financial-data-box"
                    data-widget="collapse"
                    data-toggle="tooltip"
                    title="Collapse">
                <i class="fas fa-{{ $financial_data_collapse == 1 ? 'plus' : 'minus' }}"></i>
            </button>
        </div>
    </div>
    <div class="card-body" id="ajax-container-financial">
        @include('admin.user.partials.boxes.financial-data', [
            'deposits' => $user->repo->getDepositsList(\Carbon\Carbon::now()->subMonths(12), \Carbon\Carbon::now()),
            'withdrawals' => $user->repo->getWithdrawalsList(\Carbon\Carbon::now()->subMonths(12), \Carbon\Carbon::now())
        ])
    </div>
</div>

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            manageFilteredData('financial-daterange-btn', true, 'initial-label-financial');
        });
    </script>
@endsection
