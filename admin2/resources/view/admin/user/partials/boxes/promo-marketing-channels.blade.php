@section('header-css')
    @parent
    <style>

    </style>
@endsection

<div class="card card-outline card-warning @if($promo_marketing_channels_box == 1) collapsed-card @endif" id="promo-marketing-channels-box">
    <div class="card-header">
        <h3 class="card-title text-lg">
            Promotional marketing channels
        </h3>
        <div class="card-tools">
            <button class="btn btn-tool" data-boxname="promo-marketing-channels-box" id="promo-marketing-channels-box-btn"
                    data-widget="collapse" data-toggle="tooltip" title="Collapse">
                <i class="fa fa-{{ $promo_marketing_channels_box == 1 ? 'plus' : 'minus' }}"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach(collect($user->getPrivacySettings())->chunk(4) as $chunk)
            <div class="col-12 col-sm-6 col-md-6 col-lg-4 col-xl-4">
                <ul class="list-group list-group-unbordered">

                    @foreach($chunk as $setting)
                    <li class="list-group-item d-flex justify-content-between">
                        <b>{{$setting['label']}}</b>
                        <p class="mb-0">
                            {{!empty($setting['value']) ? 'Yes' : 'No'}}
                        </p>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
    </div>
</div>

@section('footer-javascript')
    @parent
    <script>

    </script>
@endsection
