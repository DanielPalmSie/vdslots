@php
    $currentPath = $app['request_stack']->getCurrentRequest()->getPathInfo();
@endphp

<ul class="nav nav-tabs nav-tabs-custom">
    <li class="nav-item {{ str_ends_with($currentPath, '/bonuses/') ? 'border-top border-primary' : '' }}">
        <a class="nav-link {{ str_ends_with($currentPath, '/bonuses/') ? 'active' : '' }}"
           href="{{ $app['url_generator']->generate('admin.user-bonuses', ['user' => $user->id]) }}">
            Rewards not yet activated
        </a>
    </li>
    <li class="nav-item {{ strpos($currentPath, '/bonuses/rewards/') !== false ? 'border-top border-primary' : '' }}">
        <a class="nav-link {{ strpos($currentPath, '/bonuses/rewards/') !== false ? 'active' : '' }}"
           href="{{ $app['url_generator']->generate('admin.user-bonuses-rewards', ['user' => $user->id]) }}">
            Rewards
        </a>
    </li>
    <li class="nav-item {{ strpos($currentPath, '/bonuses/transactions/') !== false ? 'border-top border-primary' : '' }}">
        <a class="nav-link {{ strpos($currentPath, '/bonuses/transactions/') !== false ? 'active' : '' }}"
           href="{{ $app['url_generator']->generate('admin.user-bonuses-transactions', ['user' => $user->id]) }}">
            Reward transactions
        </a>
    </li>
    @if(p('add.bonus'))
        <li class="nav-item {{ strpos($currentPath, '/addbonus/') !== false ? 'border-top border-primary' : '' }}">
            <a class="nav-link {{ strpos($currentPath, '/addbonus/') !== false ? 'active' : '' }}"
               href="{{ $app['url_generator']->generate('admin.user-bonuses-add-bonus', ['user' => $user->id]) }}"><i class="fa fa-plus-square"></i> Add bonus
            </a>
        </li>
    @endif
    @if(p('give.reward'))
        <li class="nav-item {{ strpos($currentPath, '/addreward/') !== false ? 'border-top border-primary' : '' }}">
            <a class="nav-link {{ strpos($currentPath, '/addreward/') !== false ? 'active' : '' }}"
               href="{{ $app['url_generator']->generate('admin.user-bonuses-add-reward', ['user' => $user->id]) }}">
                <i class="fa fa-plus-square"></i> Add reward
            </a>
        </li>
    @endif
</ul>
