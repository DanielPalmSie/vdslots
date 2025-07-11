<div class="card border border-primary">
    <div class="card-body">
        <a class="text-primary" href="{{ $app['url_generator']->generate('admin.userprofile', ['user' => $user->id]) }}">Overview</a> |
        @if(p('change.contact.info'))
            <a class="text-primary"href="{{ $app['url_generator']->generate('admin.user-edit', ['user' => $user->id]) }}">Edit User</a> |
        @endif
        <a class="text-primary" href="/account/{{ $user->id }}/">To player profile</a> |
        @if(p('view.account.actions'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-actions', ['user' => $user->id]) }}">Actions</a> |
        @endif
        @if(p('view.account.permissions') || p('permission.edit.%') || p('permission.view.%'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-permissions', ['user' => $user->id]) }}">Permissions</a> |
        @endif
        @if(p('view.account.account-history'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-transactions', ['user' => $user->id]) }}">Transactions</a> |
        @endif
        @if(p('view.account.notification-history'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-notifications', ['user' => $user->id]) }}">Notifications</a> |
        @endif
        @if(p('view.account.bonuses'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-bonuses', ['user' => $user->id]) }}">Bonuses</a> |
        @endif
        @if(p('view.account.trophies'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-trophies', ['user' => $user->id]) }}">Trophies</a> |
        @endif
        @if(p('view.account.documents') && phive('UserHandler')->getSetting('new_backoffice_document_page'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-documents', ['user' => $user->id]) }}">Documents</a> |
        @endif
        @if(p('view.account.casino-races'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-casino-races', ['user' => $user->id]) }}">{{ $app['locale']['race.headline']  }}</a>  |
        @endif
        @if(p('view.account.cashbacks'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-casino-cashback', ['user' => $user->id]) }}">Weekend Booster History</a> |
        @endif
        @if(p('view.account.wheel-of-jackpot-history'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-wheel-of-jackpot-history', ['user' => $user->id]) }}">The Wheel
                Of Jackpots History</a> |
        @endif
        @if(p('view.account.limits'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-gaming-limits', ['user' => $user->id]) }}">Gaming Limits</a> |
        @endif
        @if(p('view.account.vouchers'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-vouchers', ['user' => $user->id]) }}">Vouchers</a> |
        @endif
        @if(p('view.account.reward-history'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-reward-history', ['user' => $user->id]) }}">Reward History</a> |
        @endif
        @if(p('view.account.game-sessions'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-game-sessions-historical', ['user' => $user->id]) }}">Game Sessions</a> |
        @endif
        @if(p('view.account.sessions'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-sessions', ['user' => $user->id]) }}">Sessions</a> |
        @endif
        @if(p('view.account.game-history'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-game-history', ['user' => $user->id]) }}">Game History</a> |
        @endif
        @if(p('view.account.betswins'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-betsandwins', ['user' => $user->id]) }}">Bets/Wins</a> |
        @endif
        @if(p('view.account.xp-history'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-xp-history', ['user' => $user->id]) }}">XP History</a> |
        @endif
        @if(p('view.account.game-info'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-game-statistics', ['user' => $user->id]) }}">Game Stats</a> |
        @endif
        @if(p('user.transfer.cash'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-transfer-cash', ['user' => $user->id]) }}">Transfer Money</a> |
        @endif
        @if(p('user.add.deposit'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-add-deposit', ['user' => $user->id]) }}">Add Deposit</a> |
        @endif
        @if(p('user.create.withdrawal'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-insert-withdrawal', ['user' => $user->id]) }}">Create Withdrawal</a> |
        @endif
        @if(p('user.battles'))
            <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-battles', ['user' => $user->id]) }}">View Battles</a>
        @endif
        @if(p('user.liability'))
            | <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-liability', ['user' => $user->id]) }}">Liability</a>
        @endif
        @if(p('user.risk-score'))
            | <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-risk-score', ['user' => $user->id]) }}">Risk score</a>
        @endif
        @if(p('user.responsible-gaming-monitoring') || p('fraud.section.responsible-gaming-monitoring'))
            | <a class="text-primary" href="{{ $app['url_generator']->generate('admin.responsible-gaming-monitoring', ['user' => $user->id]) }}">RG Monitoring</a>
        @endif
        @if(p('user.fraud-aml-monitoring') || p('fraud.section.fraud-aml-monitoring'))
            | <a class="text-primary" href="{{ $app['url_generator']->generate('admin.fraud-aml-monitoring', ['user' => $user->id]) }}">AML Monitoring</a>
        @endif
        @if(p('user.fraud-grs-report') )
            | <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user.grsScore', ['user' => $user->id]) }}">GRS Report</a>
        @endif
{{--        TODO add support for Experian(dob) + ID3 (pep) + Acuris (pep), need to be discussed to see if we need to add extra data on user settings or get it from ext_audit_log /Paolo --}}
        @if(p('user.id3global-result') && $user->repo->getSetting('id3global_res') !== null)
            | <a class="text-primary" href="{{ $app['url_generator']->generate('admin.user-id3global', ['user' => $user->id]) }}">ID3global check</a>
        @endif
    </div>
</div>
