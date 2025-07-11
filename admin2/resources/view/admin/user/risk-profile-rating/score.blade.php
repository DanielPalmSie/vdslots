<div class="row justify-content-center">
    <div class="col-12 text-center">
        <div class="row justify-content-center align-items-center mb-1">
            @foreach ($rating_score as $rating)
                <div class="mr-1">
                    <div class="risk-indicator {{ $rating['active'] ? 'risk-indicator-active' : '' }} d-flex align-self-center align-items-center justify-content-center text-xs-custom text-break"
                         style="background-color: {{ \App\Helpers\GrsHelper::getGlobalScoreColor($app, $rating['title']) }};">
                        <span>{{ $rating['title'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
