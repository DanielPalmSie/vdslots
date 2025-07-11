@php
    $currentPath = $app['request_stack']->getCurrentRequest()->getPathInfo();
@endphp

<ul class="nav nav-tabs nav-tabs-custom">
    <li class="nav-item {{ (strpos($currentPath, '/transactions/deposit/') !== false || preg_match('#/transactions/$#', $currentPath)) ? 'border-top border-primary' : '' }}">
        <a class="nav-link {{ (strpos($currentPath, '/transactions/deposit/') !== false || preg_match('#/transactions/$#', $currentPath)) ? 'active' : '' }}"
           href="{{ $app['url_generator']->generate('admin.user-transactions-deposit', ['user' => $user->id]) }}">
            Deposits
        </a>
    </li>
    <li class="nav-item {{ strpos($currentPath, '/transactions/failed-deposit/') !== false ? 'border-top border-primary' : '' }}">
        <a class="nav-link {{ strpos($currentPath, '/transactions/failed-deposit/') !== false ? 'active' : '' }}"
           href="{{ $app['url_generator']->generate('admin.user-transactions-failed-deposit', ['user' => $user->id]) }}">
            Failed Deposits
        </a>
    </li>
    <li class="nav-item {{ strpos($currentPath, '/transactions/manual/') !== false ? 'border-top border-primary' : '' }}">
        <a class="nav-link {{ strpos($currentPath, '/transactions/manual/') !== false ? 'active' : '' }}"
           href="{{ $app['url_generator']->generate('admin.user-transactions-manual', ['user' => $user->id]) }}">
            Manual Deposits
        </a>
    </li>
    <li class="nav-item {{ strpos($currentPath, '/transactions/withdrawal/') !== false ? 'border-top border-primary' : '' }}">
        <a class="nav-link {{ strpos($currentPath, '/transactions/withdrawal/') !== false ? 'active' : '' }}"
           href="{{ $app['url_generator']->generate('admin.user-transactions-withdrawal', ['user' => $user->id]) }}">
            Withdrawals
        </a>
    </li>
    <li class="nav-item {{ strpos($currentPath, '/transactions/other/') !== false ? 'border-top border-primary' : '' }}">
        <a class="nav-link {{ strpos($currentPath, '/transactions/other/') !== false ? 'active' : '' }}"
           href="{{ $app['url_generator']->generate('admin.user-transactions-other', ['user' => $user->id]) }}">
            Other Transactions
        </a>
    </li>
    <li class="nav-item {{ strpos($currentPath, '/transactions/closed-loop/') !== false ? 'border-top border-primary' : '' }}">
        <a class="nav-link {{ strpos($currentPath, '/transactions/closed-loop/') !== false ? 'active' : '' }}"
           href="{{ $app['url_generator']->generate('admin.user-transactions-closed-loop', ['user' => $user->id]) }}">
            Closed Loop Overview
        </a>
    </li>
</ul>
