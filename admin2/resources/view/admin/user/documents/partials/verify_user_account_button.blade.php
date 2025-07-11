<div class="row verification-row">
    <div id="user-account-status" class="verification-status-{{ $user->repo->getSetting('verified') ? 'verified' : 'not-verified' }}">
        Verification status: {{ $user->repo->getSetting('verified') ? 'Verified' : 'Not Verified' }}
    </div>
    @if(p('user.verify') || p('user.unverify'))
            <?php // TODO: ajax call ?>
            @if( $user->repo->getSetting('verified') != 1)
                @if(p('user.verify'))
                    <div class="btn">
                        <a href="{{ $app['url_generator']->generate('admin.user-verify', ['user' => $user->id]) }}"
                                class="btn verify-btn btn-default action-ajax-set-btn-2" data-dtitle="Verify User"
                                data-dbody="Are you sure you want to verify user <b>{{ $user->id }}</b>?"
                                id="user-verify-account">Verify {{ $user->id }}
                        </a>
                    </div>
                @endif
            @else
               @if(p('user.unverify'))
                     <div class="btn">
                       <a href="{{ $app['url_generator']->generate('admin.user-unverify', ['user' => $user->id]) }}"
                               class="btn verify-btn btn-default action-ajax-set-btn-2" data-dtitle="Unverify User"
                               data-dbody="Are you sure you want to unverify user <b>{{ $user->id }}</b>?"
                               id="user-unverify-account">Unverify {{ $user->id }}
                       </a>
                     </div>
               @endif
            @endif

    @endif
</div>
