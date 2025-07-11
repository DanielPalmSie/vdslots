@extends('admin.layout')

@section('content')
    @include('admin.messaging.partials.topmenu')
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.bonus.list') }}">List bonus code templates</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.vouchers.list') }}">List voucher code templates</a></li>
            <li class="nav-item"><a class="nav-link active"><i class="fa fa-plus-square"></i> Create bonus code template</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.vouchers.create-template') }}"><i class="fa fa-plus-square"></i> Create voucher code template</a></li>
        </ul>
        <div class="tab-content p-3">
            <div class="tab-pane active">
                @if($app['request_stack']->getCurrentRequest()->get('step') == 1)
                    <div class="card">
                        <div class="card-header with-border">
                            <h3 class="card-title">Step 1 - Select a bonus</h3>
                        </div>
                        <div class="card-body">
                            @include('admin.messaging.bonus.bonustype-list')
                        </div>
                    </div>
                @elseif($app['request_stack']->getCurrentRequest()->get('step') == 2 or $app['request_stack']->getCurrentRequest()->get('action') == 'edit')
                    @include('admin.messaging.bonus.template-form')
                @else
                    <p>No step selected.</p>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script>
        function setRewardField(reward_id, reward_name) {
            $("#award-desc-input").val(reward_name);
            $("#award-id-input").val(reward_id);
            $("#bonus-desc-input").val('');
            $("#bonus-id-input").val('');
        }
        $(function () {

        });
    </script>

@endsection