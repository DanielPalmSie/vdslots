<a
    class="btn btn-primary mt-1"
    data-rg-action="{{$action}}"
    data-follow-up="Yes"
    href="{{$app['url_generator']->generate('admin.user-set-rg-monitoring-setting', ['user' => $user->id, 'setting' => $setting])}}"
    data-modalbody="{{$message}}"
    @if($user->repo->hasSetting($setting))
    disabled="disabled"
    @endif
>
{{-- TODO make the icon configurable via a param too /Paolo --}}
    <i class="fas fa-money-bill"></i> {{$action}}
</a>

