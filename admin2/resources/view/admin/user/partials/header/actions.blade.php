<?php
/**
 * @var \App\Models\User $user
 * @var \App\Classes\Settings $settings
 */
$settings = $user->repo->getAllSettings();
?>

@include('admin.user.partials.header.sections-menu')

<div class="card border border-primary">
    <div class="card-body">
        <div class="btn-group btn-group-sm flex-wrap">
            {{--
            normal block1
            p('user.block') || p('user.super.block')
            --}}
            @if($user->block_repo->showActivateBtn())
                <div class="btn-group">
                    <button
                        type="button"
                        class="btn btn-default dropdown-toggle"
                        data-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                        @if (!(p('user.block') || p('user.super.block')))
                            disabled
                        @endif
                    >
                            Blocked profile <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        @if($user->block_repo->wasSelfExcluded())
                            @if($user->country == 'GB')
                                <li class="dropdown-item">
                                    <a href="{{ $app['url_generator']->generate('admin.user-block-extend-one-day', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block"
                                       data-dtitle="Activate user profile"
                                       data-dbody="Are you sure you want to activate the previously self-excluded account: <b>{{ $user->id }}</b>?
                                        <br/>As UK player, 24 hours cooling off period will be applied."
                                       id="user-activate">Apply 24 hours cooling off period
                                    </a>
                                </li>
                            @else
                                <li class="dropdown-item">
                                    <a href="{{ $app['url_generator']->generate('admin.user-unblock', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block"
                                       data-dtitle="Activate user profile"
                                       data-dbody="Are you sure you want to activate the previously self-excluded account: <b>{{ $user->id }}</b>?"
                                       id="user-activate">Activate
                                    </a>
                                </li>
                            @endif
                        @else
                            @if($user->block_repo->activate_btn_reason)
                                <li class="dropdown-item disabled"><a href="#"><b>Locked due to: {{ $user->block_repo->activate_btn_reason }}</b></a></li>
                            @endif
                            <li class="dropdown-item">
                                <a href="{{ $app['url_generator']->generate('admin.user-unblock', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block"
                                   data-dtitle="Activate user profile"
                                   data-dbody="Are you sure you want to activate the account <b>{{ $user->id }}</b>?"
                                   id="user-activate">Activate
                                </a></li>
                        @endif
                    </ul>
                </div>
            @else
                {{-- normal block2 --}}
                @if($user->block_repo->showBlockBtn())
                    <div class="btn-group">
                        <button
                            type="button"
                            class="btn btn-default btn-sm dropdown-toggle"
                            data-toggle="dropdown"
                            aria-haspopup="true"
                            aria-expanded="false">
                            {{ $user->active ? 'Non-blocked Profile' : 'Blocked Profile' }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @if($user->active == 1)
                                <li class="dropdown-item">
                                    <a href="{{ $app['url_generator']->generate('admin.user-block', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block"
                                       data-dtitle="Blocking user profile"
                                       data-dbody="Are you sure you want to block user <b>{{ $user->id }}</b>?"
                                       data-rg-action-type="block"
                                       id="user-block">
                                        Block this profile
                                    </a>
                                </li>
                            @else
                                <li class="dropdown-item">
                                    <a href="{{ $app['url_generator']->generate('admin.user-unblock', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block"
                                       data-dtitle="Unblocking user profile"
                                       data-dbody="Are you sure you want to unblock user <b>{{ $user->id }}</b>?"
                                       id="user-unblock">Unblock this profile
                                    </a>
                                </li>
                                @if($user->block_repo->canBeExtendedSevenDays())
                                    <li class="dropdown-item">
                                        <a href="{{ $app['url_generator']->generate('admin.user-block-extend', ['user' => $user->id]) }}"
                                           class="action-set-btn text-sm text-muted d-block"
                                           data-dtitle="Change unlock date"
                                           data-dbody="{{ $user->block_repo->getDialogMessageBody('extend-self-lock') }}"
                                           data-rg-action-type="block"
                                           id="user-extend">Apply {{ lic('getSelfLockCoolOffDays', [], $user->getKey()) }} days cooling off period
                                        </a>
                                    </li>
                                @endif
                            @endif
                        </ul>
                    </div>
                @elseif (!$user->block_repo->isSuperBlocked() && $user->active != 1 && !$user->block_repo->isUKSelfLock())
                    <div class="btn-group">
                        <button type="button"
                                class="btn btn-default btn-sm dropdown-toggle @if(!$user->block_repo->canBeExtendedSevenDays()) disabled @endif"
                                data-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false">
                            {{ $user->active ? 'Non-blocked Profile' : $user->block_repo->isSelfLocked() ? 'Self-locked profile' : 'Blocked Profile' }} @if($user->block_repo->canBeExtendedSevenDays())<span class="caret"></span>@endif
                        </button>
                        <ul class="dropdown-menu">
                            @if($user->block_repo->canBeExtendedSevenDays())
                                <li class="dropdown-item">
                                    <a href="{{ $app['url_generator']->generate('admin.user-block-extend', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block"
                                       data-dtitle="Change unlock date"
                                       data-dbody="{{ $user->block_repo->getDialogMessageBody('extend-self-lock') }}"
                                       id="user-extend">Apply {{ lic('getSelfLockCoolOffDays', [], $user->getKey()) }} days cooling off period
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif
            @endif
            {{--super block--}}
            <div class="btn-group">
                <button type="button"
                        class="btn btn-default btn-sm dropdown-toggle"
                        data-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false" @if(($user->repo->hasSetting('super-blocked') && !p('user.super.unlock')) || !p('user.super.block')) disabled @endif>
                    {{ ($user->repo->hasSetting('super-blocked')) ? 'Super blocked account' : 'Non super blocked account' }}
                    <span class="caret"></span>
                </button>
                @if (p('user.super.unlock') || p('user.super.block'))
                    <ul class="dropdown-menu">
                        @if(($user->repo->hasSetting('super-blocked')) && p('user.super.unlock'))
                            <li class="dropdown-item">
                                <a href="{{ $app['url_generator']->generate('admin.user-lift-superblock', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block"
                                   data-dtitle="Lift super block"
                                   data-dbody="Are you sure you want to lift the super block to the user <b>{{ $user->id }}</b>?"
                                   id="user-superblock">Lift super block
                                </a>
                            </li>
                        @endif
                        @if(p('user.super.block') && !($user->repo->hasSetting('super-blocked')))
                            <li class="dropdown-item">
                                <a href="{{ $app['url_generator']->generate('admin.user-superblock', ['user' => $user->id]) }}"
                                   class="action-set-btn text-muted text-sm d-block"
                                   data-dtitle="Super block"
                                   data-dbody="Are you sure you want to super block user <b>{{ $user->id }}</b>?"
                                   data-rg-action-type="super-block"
                                   id="user-superblock">Super block this profile
                                </a>
                            </li>
                        @endif
                    </ul>
                @endif
            </div>
            {{-- self exclusion --}}
            @if(!$user->block_repo->isSuperBlocked())
                @if($user->block_repo->showSelfExclusionBtn())
                    <button type="button" class="btn btn-default btn-sm disabled">Self-excluded account</button>
                @else
                    @include('admin.user.limits.partials.self-exclusion-modal', compact('self_exclusion_options'))
                    <div class="btn-group">
                        <button
                            type="button"
                            class="btn btn-default btn-sm dropdown-toggle"
                            data-toggle="dropdown"
                            aria-haspopup="true"
                            aria-expanded="false"
                            @if (!(p('edit.gaminglimits') || p('edit.account.limits.block') || p('view.gaminglimits')))
                                disabled
                            @endif
                        >
                            Not self-excluded account <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li class="dropdown-item">
                                <a data-key="self-exclude"
                                   data-title="Set self-exclusion"
                                   data-type="set-self"
                                   data-toggle="modal"
                                   data-target="#self-exclusion-modal"
                                   data-label="Exclude duration"
                                   data-newplaceholder="Months"
                                   class="self-exclusion-limit-link text-sm text-muted">Set self-exclusion
                                </a>
                            </li>
                        </ul>
                    </div>
                @endif
            @endif

                {{--play block--}}
                @if(p('user.play.block'))
                    <div class="btn-group">
                        <button type="button"
                                class="btn btn-default btn-sm dropdown-toggle"
                                data-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false">
                            {{ $settings->play_block ? 'Not allowed to play' : 'Allowed to play' }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @if($settings->play_block == 1)
                                <li class="dropdown-item">
                                    <a href="{{ $app['url_generator']->generate('admin.user-allow-to-play', ['user' => $user->id]) }}"
                                       class="action-set-btn text text-muted d-block"
                                       data-dtitle="Allow user to play"
                                       data-dbody="Are you sure you want to allow user to play <b>{{ $user->id }}</b>?"
                                       id="user-allow-to-play">Allow to play
                                    </a>
                                </li>
                            @else
                                <li class="dropdown-item">
                                    <a href="{{ $app['url_generator']->generate('admin.user-disallow-to-play', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block"
                                       data-dtitle="Disallow user to play"
                                       data-dbody="Are you sure you want to disallow user to play <b>{{ $user->id }}</b>?"
                                       data-rg-action-type="play-block"
                                       id="user-disallow-to-play">Disallow
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                    @endif

                    {{--deposit block--}}
                    @if(p('user.deposit.block'))
                    <div class="btn-group">
                        <button type="button"
                                class="btn btn-default btn-sm dropdown-toggle"
                                data-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false">
                            {{ $settings->deposit_block ? 'Not allowed to deposit' : 'Allowed to deposit' }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @if($settings->deposit_block == 1)
                            <li class="dropdown-item">
                                <a href="{{ $app['url_generator']->generate('admin.user-allow-to-deposit', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block"
                                   data-dtitle="Allow user to deposit"
                                   data-dbody="Are you sure you want to allow user to deposit <b>{{ $user->id }}</b>?"
                                   id="user-allow-to-deposit">Allow
                                </a>
                            </li>
                            @else
                            <li class="dropdown-item">
                                <a href="{{ $app['url_generator']->generate('admin.user-disallow-to-deposit', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Disallow user to deposit"
                                   data-dbody="Are you sure you want to disallow user to deposit <b>{{ $user->id }}</b>?"
                                   data-rg-action-type="deposit-block"
                                   id="user-disallow-to-deposit">Disallow</a></li>
                            @endif
                        </ul>
                    </div>
                    @endif

                    {{--withdraw block--}}
                    @if(p('user.withdraw.block'))
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false">
                                {{ $settings->withdrawal_block ? 'Not allowed to withdraw' : 'Allowed to withdraw' }} <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                @if($settings->withdrawal_block == 1)
                                    <li class="dropdown-item">
                                        <a href="{{ $app['url_generator']->generate('admin.user-allow-to-withdraw', ['user' => $user->id]) }}"
                                           class="action-set-btn text-sm text-muted d-block" data-dtitle="Allow user to withdraw"
                                           data-dbody="Are you sure you want to allow user to withdraw <b>{{ $user->id }}</b>?"
                                           id="user-allow-to-withdraw">Allow</a></li>
                                @else
                                    <li class="dropdown-item">
                                        <a href="{{ $app['url_generator']->generate('admin.user-disallow-to-withdraw', ['user' => $user->id]) }}"
                                           class="action-set-btn text-sm text-muted d-block" data-dtitle="Disallow user to withdraw"
                                           data-dbody="Are you sure you want to disallow user to withdraw <b>{{ $user->id }}</b>?"
                                           data-rg-action-type="withdrawal-block"
                                           id="user-disallow-to-withdraw">Disallow</a></li>
                                @endif
                            </ul>
                        </div>
                    @endif

                    {{-- restrict --}}
                    @if(p('user.restrict'))
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            {{ $settings->restrict ? 'Restricted' : 'Unrestricted' }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @if($settings->restrict == 1)
                            <li class="dropdown-item">
                                <a href="{{ $app['url_generator']->generate('admin.unrestrict', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Unrestrict"
                                   data-dbody="Are you sure you want to unrestrict <b>{{ $user->id }}</b>?"
                                   id="unrestrict">Unrestrict</a></li>
                            @else
                            <li class="dropdown-item">
                                <a href="{{ $app['url_generator']->generate('admin.restrict', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Restrict"
                                   data-dbody="Are you sure you want to restrict <b>{{ $user->id }}</b>?"
                                   data-rg-action-type="restrict"
                                   id="restrict">Restrict</a></li>
                            @endif
                        </ul>
                    </div>
                    @endif


                {{--verify email--}}
                @if(p('user.verify.email'))
                @if($settings->email_code_verified != 'yes')
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            {{ $settings->email_code_verified == 'yes' ? 'Email verified' : 'Email not verified' }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @if($settings->email_code_verified != 'yes')
                                <li class="dropdown-item">
                                    <a href="{{ $app['url_generator']->generate('admin.user-verify-email', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Verify User Email"
                                       data-dbody="Are you sure you want to verify user <b>{{ $user->id }}</b> email?"
                                       id="user-verify-email">Verify email</a></li>
                            @else
                                <li class="dropdown-item disabled"><a href="{{ $app['url_generator']->generate('admin.user-unverify-email', ['user' => $user->id]) }}"
                                       data-disabled="1"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Unverify User Email"
                                       data-dbody="Are you sure you want to unverify user <b>{{ $user->id }}</b> email?"
                                       id="user-unverify-email">Unverify email</a></li>
                            @endif
                        </ul>
                    </div>
                @else
                    <button type="button" class="btn btn-default btn-sm disabled">Email verified</button>
                @endif
                @endif
                {{--verify account--}}
                @if(p('user.verify'))
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            {{ $settings->verified ? 'Account verified' : 'Account not verified' }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @if($settings->verified != 1)
                                <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-verify', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Verify User"
                                       data-dbody="Are you sure you want to verify user <b>{{ $user->id }}</b>?"
                                       id="user-verify">Verify {{ $user->id }}</a></li>
                            @else
                                <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-unverify', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Unverify User"
                                       data-dbody="Are you sure you want to unverify user <b>{{ $user->id }}</b>?"
                                       id="user-unverify">Unverify {{ $user->id }}</a></li>
                            @endif
                        </ul>
                    </div>
                @endif
            {{--verify phone--}}
                @if(p('user.verify.phone'))
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            {{ $user->verified_phone ? 'Phone verified' : 'Phone not verified' }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @if($user->verified_phone != 1)
                                <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-verify-phone', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Verify user phone"
                                       data-dbody="Are you sure you want to verify user <b>{{ $user->id }}</b> phone?"
                                       id="user-verify-phone">Verify user phone</a></li>
                            @else
                                <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-unverify-phone', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Unverify user phone"
                                       data-dbody="Are you sure you want to unverify user <b>{{ $user->id }}</b> phone?"
                                       id="user-unverify-phone">Unverify user phone</a></li>
                            @endif
                        </ul>
                    </div>
                @endif
            {{--phone date--}}
            @if(p('user.phonedate'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        {{ $settings->{'phoned-date'} ? "Last phoned date {$settings->{'phoned-date'} }" : 'No phoned date yet' }} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-set-phoned-date-to-now', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Set user phoned date to now"
                                   data-dbody="Are you sure you want to set Phoned date to now for user <b>{{ $user->id }}</b>?"
                                   id="user-set-phoned-date">Set phoned date to now</a></li>
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-delete-phoned-date', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Delete phoned date"
                                   data-dbody="Are you sure you want to delete the phoned date for user <b>{{ $user->id }}</b>?"
                                   id="user-unset-phoned-date">Delete phoned date</a></li>
                    </ul>
                </div>
            @endif
            {{--clear ips--}}
            @if(p('user.clear.ips'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        IP log <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-clear-ip-log', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Clear IP log"
                                   data-dbody="Are you sure you want to clear the Ip log user <b>{{ $user->id }}</b>?"
                                   id="user-clear-ip-log">Clear IP log</a></li>
                    </ul>
                </div>
            @endif
            {{--show bank--}}
            @if(p('user.show.bank'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle {{ $settings->{'show_bank'} ? 'disabled' : null }}"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Show bank: {{ $settings->{'show_bank'} ? '[Yes]' : "[No]" }}
                        @if(!$settings->{'show_bank'}) <span class='caret'></span> @endif
                    </button>
                    <ul class="dropdown-menu">
                        @if($settings->{'show_bank'} != 1)
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-show-bank', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Show bank"
                                   data-dbody="Are you sure you want to show bank to this user <b>{{ $user->id }}</b>?"
                                   id="user-show-bank">Show bank</a></li>
                        @endif
                    </ul>
                </div>
            @endif
            {{--show euteller--}}
            @if(p('user.show.euteller'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle {{ $settings->{'show_euteller'} ? 'disabled' : null }}"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Show Euteller: {{ $settings->{'show_euteller'} ? '[Yes]' : "[No]" }}
                        @if(!$settings->{'show_euteller'}) <span class='caret'></span> @endif
                    </button>
                    <ul class="dropdown-menu">
                        @if($settings->{'show_euteller'} != 1)
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-show-euteller', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Show Euteller"
                                   data-dbody="Are you sure you want to show Euteller to this user <b>{{ $user->id }}</b>?"
                                   id="user-show-euteller">Show Euteller</a></li>
                        @endif
                    </ul>
                </div>
            @endif
            {{--fifo date--}}
            @if(p('user.update.fifo.date'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        {{ $settings->fifo_date ? "Current FIFO date {$settings->fifo_date}" : 'No FIFO date' }} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li class="dropdown-item">
                            <a href="{{ $app['url_generator']->generate('admin.user-update-fifo-date', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Update fifo date"
                                   data-dbody="Are you sure you want to update the FIFO date for the user <b>{{ $user->id }}</b>?"
                                   id="user-update-fifo">
                                Update FIFO date
                            </a>
                        </li>
                        <li class="dropdown-item bg-transparent text-reset">
                            <div class="form-group">
                                <input class="form-control" id="fifo-date-txt-field" name="fifo-date" type ="text" placeholder="Or set the date: YYYY-MM-DD"/>
                                <div id="fifo-submit" style="cursor: pointer;">Submit</div>
                            </div>
                        </li>
                    </ul>
                </div>
            @endif
            {{--bonus fraud flag--}}
            @if(p('fraud.section.remove.flag') && $settings->{'bonus-fraud-flag'})
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        Bonus fraud flag active <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                            <li class="dropdown-item">
                                <a href="{{ $app['url_generator']->generate('admin.user-remove-bonus-fraud-flag', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Remove bonus fraud flag"
                                   data-dbody="Are you sure you want to remove the bonus fraud flag for the user <b>{{ $user->id }}</b>?"
                                   id="user-remove-bonus-fraud-flag">Remove flag</a></li>
                    </ul>
                </div>
            @endif
            {{--ccard fraud flag--}}
            @if(p('fraud.section.remove.flag') && $settings->{'ccard-fraud-flag'})
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        Credit card fraud flag active <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-remove-ccard-fraud-flag', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="remove credit card fraud flag"
                                   data-dbody="Are you sure you want to remove the credit card fraud flag for the user <b>{{ $user->id }}</b>?"
                                   id="user-remove-ccard-fraud-flag">Remove flag</a></li>
                    </ul>
                </div>
            @endif
            {{--prevent card flag--}}
            @if(p('fraud.section.remove.flag.permanently'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        Prevent ccard flag {{ $settings->{'no-ccard-fraud-flag'} ? '[Active]' : '[No]' }} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        @if(!$settings->{'no-ccard-fraud-flag'})
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-active-prevent-ccard-flag', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Activate ccard flag"
                                   data-dbody="Are you sure you want to activate the fraud flag prevention to the user <b>{{ $user->id }}</b>?"
                                   id="user-activate-no-ccard-flag">Active</a></li>
                        @else
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-remove-prevent-ccard-flag', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Remove ccard flag"
                                   data-dbody="Are you sure you want to remove ccard fraud flag prevention for the user <b>{{ $user->id }}</b>?"
                                   id="user-remove-no-ccard-flag">Remove</a></li>
                        @endif
                    </ul>
                </div>
            @endif
            {{--prevent liability flag--}}
            @if(p('user.liability.prevent.flag'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        Prevent liability flag {{ $settings->{'liability-flag-prevent'} ? '[Date: '.$settings->{'liability-flag-prevent'}.']' : '[No]' }} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        @if(!$settings->{'liability-flag-prevent'})
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-prevent-liability-flag', ['user' => $user->id, 'action' => 'active']) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Activate liability prevent flag"
                                   data-dbody="Are you sure you want to activate the liability fraud flag prevention to the user <b>{{ $user->id }}</b>?"
                                   id="user-activate-no-ccard-flag">Active</a></li>
                        @else
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-prevent-liability-flag', ['user' => $user->id, 'action' => 'active']) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Set liability prevent date to now"
                                   data-dbody="Are you sure you want to set the liability prevent date to now for user <b>{{ $user->id }}</b>?"
                                   id="user-set-phoned-date">Set prevent date to now</a></li>
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-prevent-liability-flag', ['user' => $user->id, 'action' => 'delete']) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Delete liability prevent flag"
                                   data-dbody="Are you sure you want to delete the liability prevent flag to <b>{{ $user->id }}</b>?"
                                   id="user-unset-phoned-date">Delete prevent flag</a></li>
                        @endif
                    </ul>
                </div>
            @endif
            {{--manual card flag--}}
            @if(p('fraud.section.remove.flag.manual'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        Manual fraud flag {{ $settings->{'manual-fraud-flag'} ? '[Active]' : '[No]' }} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        @if(!$settings->{'manual-fraud-flag'})
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-active-manual-fraud-flag', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Activate manual fraud flag"
                                   data-dbody="Are you sure you want to active the manual fraud flag to the user <b>{{ $user->id }}</b>?"
                                   id="user-activate-manual-flag">Active</a></li>
                        @else
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-remove-manual-fraud-flag', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Remove manual fraud flag"
                                   data-dbody="Are you sure you want to remove the manual flag for the user <b>{{ $user->id }}</b>?"
                                   id="user-remove-manual-flag">Remove</a></li>
                        @endif
                    </ul>
                </div>
                @endif

                {{--Too many rollbacks flag--}}
                @if(p('fraud.section.remove.flag.too-many-rollbacks'))
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            Too many rollbacks fraud flag {{ $settings->{'too_many_rollbacks-fraud-flag'} ? '[Active]' : '[No]' }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @if(!$settings->{'too_many_rollbacks-fraud-flag'})
                                <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-active-too-many-rollbacks-fraud-flag', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Activate too many rollbacks fraud flag"
                                       data-dbody="Are you sure you want to active the too many rollbacks fraud flag to the user <b>{{ $user->id }}</b>?"
                                       id="user-activate-too_many_rollbacks-fraud-flag">Active</a></li>
                            @else
                                <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-remove-too-many-rollbacks-fraud-flag', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Remove too many rollbacks fraud flag"
                                       data-dbody="Are you sure you want to remove the too many rollbacks fraud flag for the user <b>{{ $user->id }}</b>?"
                                       id="user-remove-too_many_rollbacks-fraud-flag">Remove</a></li>
                            @endif
                        </ul>
                    </div>
                @endif

                {{--Total withdrawal amount limit reached flag--}}
                @if(p('fraud.section.remove.flag.total-withdrawal-amount-limit-reached'))
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            Total withdrawal amount limit reached flag {{ $settings->{'total-withdrawal-amount-limit-reached-fraud-flag'} ? '[Active]' : '[No]' }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @if(!$settings->{'total-withdrawal-amount-limit-reached-fraud-flag'})
                                <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-active-total-withdrawal-amount-limit-reached-fraud-flag', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Activate total withdrawal amount limit reached flag"
                                       data-dbody="Are you sure you want to active the total withdrawal amount limit reached flag to the user <b>{{ $user->id }}</b>?"
                                       id="user-activate-total-withdrawal-amount-limit-reached-fraud-flag">Active</a></li>
                            @else
                                <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-remove-total-withdrawal-amount-limit-reached-fraud-flag', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Remove total withdrawal amount limit reached flag"
                                       data-dbody="Are you sure you want to remove the total withdrawal amount limit reached flag for the user <b>{{ $user->id }}</b>?"
                                       id="user-remove-total-withdrawal-amount-limit-reached-fraud-flag">Remove</a></li>
                            @endif
                        </ul>
                    </div>
                @endif

                {{--Suspicious email flag--}}
                @if(p('fraud.section.remove.flag.suspicious-email'))
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            Suspicious email flag {{ $settings->{'suspicious-email-fraud-flag'} ? '[Active]' : '[No]' }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @if(!$settings->{'suspicious-email-fraud-flag'})
                                <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-active-suspicious-email-fraud-flag', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Activate suspicious email flag"
                                       data-dbody="Are you sure you want to active the suspicious email flag to the user <b>{{ $user->id }}</b>?"
                                       id="user-activate-suspicious-email-fraud-flag">Active</a></li>
                            @else
                                <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-remove-suspicious-email-fraud-flag', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Remove suspicious email flag"
                                       data-dbody="Are you sure you want to remove the suspicious email flag for the user <b>{{ $user->id }}</b>?"
                                       id="user-remove-suspicious-email-fraud-flag">Remove</a></li>
                            @endif
                        </ul>
                    </div>
                @endif

                {{--Negative balance since deposit flag--}}
                @if(p('fraud.section.remove.flag.negative-balance-since-deposit'))
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            Negative balance since deposit flag {{ $settings->{'negative-balance-since-deposit-fraud-flag'} ? '[Active]' : '[No]' }} <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            @if(!$settings->{'negative-balance-since-deposit-fraud-flag'})
                                <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-active-negative-balance-since-deposit-fraud-flag', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Activate negative balance since deposit flag"
                                       data-dbody="Are you sure you want to active the negative balance since deposit flag to the user <b>{{ $user->id }}</b>?"
                                       id="user-activate-negative-balance-since-deposit-fraud-flag">Active</a></li>
                            @else
                                <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-remove-negative-balance-since-deposit-fraud-flag', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Remove negative balance since deposit flag"
                                       data-dbody="Are you sure you want to remove the negative balance since deposit flag for the user <b>{{ $user->id }}</b>?"
                                       id="user-remove-negative-balance-since-deposit-fraud-flag">Remove</a></li>
                            @endif
                        </ul>
                    </div>
                @endif

                {{--sar flag--}}
                @if(p('fraud.section.remove.flag.manual'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        Manual SAR flag {{ $settings->{'sar-flag'} ? '[Active]' : '[No]' }} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        @if(!$settings->{'sar-flag'})
                        <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-toggle-flag', ['user' => $user->id, 'flag' => 'sar-flag', 'action' => 'on']) }}"
                               class="action-set-btn text-sm text-muted d-block" data-dtitle="Activate manual SAR flag"
                               data-dbody="Are you sure you want to active the manual SAR flag on the user <b>{{ $user->id }}</b>?"
                               id="user-activate-manual-flag">Active</a></li>
                        @else
                        <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-toggle-flag', ['user' => $user->id, 'flag' => 'sar-flag', 'action' => 'off']) }}"
                               class="action-set-btn text-sm text-muted d-block" data-dtitle="Remove manual SAR flag"
                               data-dbody="Are you sure you want to remove the manual SAR flag for the user <b>{{ $user->id }}</b>?"
                               id="user-remove-manual-flag">Remove</a></li>
                        @endif
                    </ul>
                </div>
                @endif

                {{--pepsl flag--}}
                @if(p('fraud.section.remove.flag.manual'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        Manual PEP / SL flag {{ $settings->{'pepsl-flag'} ? '[Active]' : '[No]' }} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        @if(!$settings->{'pepsl-flag'})
                        <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-toggle-flag', ['user' => $user->id, 'flag' => 'pepsl-flag', 'action' => 'on']) }}"
                               class="action-set-btn text-sm text-muted d-block" data-dtitle="Activate manual PEP / SL flag"
                               data-dbody="Are you sure you want to active the manual PEP / SL flag on the user <b>{{ $user->id }}</b>?"
                               id="user-activate-manual-flag">Active</a></li>
                        @else
                        <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-toggle-flag', ['user' => $user->id, 'flag' => 'pepsl-flag', 'action' => 'off']) }}"
                               class="action-set-btn text-sm text-muted d-block" data-dtitle="Remove manual PEP / SL flag"
                               data-dbody="Are you sure you want to remove the manual PEP / SL flag for the user <b>{{ $user->id }}</b>?"
                               id="user-remove-manual-flag">Remove</a></li>
                        @endif
                    </ul>
                </div>
                @endif


                {{--amlmonitor flag--}}
                @if(p('fraud.section.remove.flag.manual'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        Manual AML monitor flag {{ $settings->{'amlmonitor-flag'} ? '[Active]' : '[No]' }} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        @if(!$settings->{'amlmonitor-flag'})
                        <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-toggle-flag', ['user' => $user->id, 'flag' => 'amlmonitor-flag', 'action' => 'on']) }}"
                               class="action-set-btn text-sm text-muted d-block" data-dtitle="Activate manual AML monitor flag"
                               data-dbody="Are you sure you want to active the manual AML monitor flag on the user <b>{{ $user->id }}</b>?"
                               id="user-activate-manual-flag">Active</a></li>
                        @else
                        <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-toggle-flag', ['user' => $user->id, 'flag' => 'amlmonitor-flag', 'action' => 'off']) }}"
                               class="action-set-btn text-sm text-muted d-block" data-dtitle="Remove manual AML monitor flag"
                               data-dbody="Are you sure you want to remove the manual AML monitor flag for the user <b>{{ $user->id }}</b>?"
                               id="user-remove-manual-flag">Remove</a></li>
                        @endif
                    </ul>
                </div>
                @endif

                {{--amlmonitor flag--}}
                @if(p('fraud.section.remove.flag.manual'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        Manual AGE verification monitor flag {{ $settings->{'agemonitor-flag'} ? '[Active]' : '[No]' }} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        @if(!$settings->{'agemonitor-flag'})
                        <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-toggle-flag', ['user' => $user->id, 'flag' => 'agemonitor-flag', 'action' => 'on']) }}"
                               class="action-set-btn text-sm text-muted d-block" data-dtitle="Activate Manual AGE verification monitor flag"
                               data-dbody="Are you sure you want to active the Manual AGE verification flag on the user <b>{{ $user->id }}</b>?"
                               id="user-activate-manual-flag">Active</a></li>
                        @else
                        <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-toggle-flag', ['user' => $user->id, 'flag' => 'agemonitor-flag', 'action' => 'off']) }}"
                               class="action-set-btn text-sm text-muted d-block" data-dtitle="Remove Manual AGE verification flag"
                               data-dbody="Are you sure you want to remove the Manual AGE verification flag for the user <b>{{ $user->id }}</b>?"
                               id="user-remove-manual-flag">Remove</a></li>
                        @endif
                    </ul>
                </div>
                @endif




            {{--majority card flag--}}
            @if(p('fraud.section.remove.flag.majority') && $settings->{'majority-fraud-flag'})
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        Majority fraud flag [Active] <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-remove-majority-fraud-flag', ['user' => $user->id]) }}"
                               class="action-set-btn text-sm text-muted d-block" data-dtitle="Remove majority fraud flag"
                               data-dbody="Are you sure you want to remove the majority deposit method switch fraud flag for the user <b>{{ $user->id }}</b>?"
                               id="user-remove-majority-fraud-flag">Remove flag</a></li>
                    </ul>
                </div>
            @endif
            {{--allow to chat--}}
            @if(p('user.chat.view'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        @if ($settings->{'mp-chat-block'})
                            Not allowed to chat: @if($settings->{'mp-chat-block-unlock-date'})[Will be unlocked on {{ $settings->{'mp-chat-block-unlock-date'} }}]@else[Permanent]@endif
                        @else
                            Allowed to chat
                        @endif
                            <span class="caret"></span>
                    </button>
                    @if($settings->{'mp-chat-block'} && p('user.chat.allow'))
                        <ul class="dropdown-menu">
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-allow-to-chat', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Allow to chat"
                                   data-dbody="Are you sure you want to allow <b>{{ $user->id }}</b> to chat?"
                                   id="user-allow-to-chat">Remove block</a></li>
                        </ul>
                    @elseif(p('user.chat.block') || p('user.chat.block.permanent'))
                        <ul class="dropdown-menu">
                            @if (p('user.chat.block'))
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-block-to-chat-days', ['user' => $user->id]) }}"
                                       class="action-set-btn text-sm text-muted d-block" data-dtitle="Chat block"
                                       data-dbody="Are you sure you want to block for 7 days <b>{{ $user->id }}</b> in the Battle of Slots chat?"
                                       data-rg-action-type="chat-block"
                                       id="user-block-chat-7-days">Block for 7 days</a></li>
                            @endif
                            @if(p('user.chat.block.permanent'))
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-block-to-chat-permanent', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Chat block"
                                   data-dbody="Are you sure you want to block permanently to <b>{{ $user->id }}</b> in the Battle of Slots chat?"
                                   data-rg-action-type="chat-block"
                                   id="user-block-chat-permanent">Block permanent</a></li>
                            @endif
                        </ul>
                    @endif
                </div>
            @endif

            {{--bypass-au-playcheck flag--}}
            @if(p('user.bypass-au-playcheck.flag'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        Bypass AU Playcheck {{ !empty($settings->{'bypass-au-playcheck'}) ? '[Active]' : '[No]' }} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        @if(empty($settings->{'bypass-au-playcheck'}))
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-toggle-au-bypass', ['user' => $user->id, 'action' => 'on']) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Activate Bypass AU Playcheck"
                                   data-dbody="Are you sure you want to activate the Bypass AU Playcheck on the user <b>{{ $user->id }}</b>?"
                                   id="user-activate-au-bypass">Activate</a></li>
                        @else
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-toggle-au-bypass', ['user' => $user->id, 'action' => 'off']) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Deactivate Bypass AU Playcheck"
                                   data-dbody="Are you sure you want to deactivate the Bypass AU Playcheck for the user <b>{{ $user->id }}</b>?"
                                   id="user-deactivate-au-bypass">Deactivate</a></li>
                        @endif
                    </ul>
                </div>
            @endif

            {{--delete account--}}
            @if(p('user.account.delete'))
                <div class="btn-group">
                    <a href="{{ $app['url_generator']->generate('admin.user.delete', ['user' => $user->id]) }}"
                       class="action-set-btn"
                       data-dtitle="Delete user"
                       data-dbody="Are you sure you want to delete user <b>{{ $user->id }}</b>?"
                       id="user-account-delete"
                    >
                        <button type="button" class="btn btn-default btn-sm" aria-haspopup="true">
                            Delete account
                        </button>
                    </a>
                </div>
            @endif
            {{--export all user data--}}
            @if(p('user.account.all_user_data.export'))
                <div class="btn-group">
                    <span class="btn btn-default btn-sm">
                        {!! \App\Repositories\ExportRepository::getExportView($app, 'all_user_data', $user->id, \Carbon\Carbon::today()->endOfDay()->toDateTimeString()) !!}
                    </span>
                </div>
            @endif

            {{--Forget user account--}}
            @if(p('user.account.forget'))
                <div class="btn-group">
                    <button type="button"
                            class="btn btn-default btn-sm dropdown-toggle"
                            data-toggle="dropdown"
                            aria-haspopup="true"
                            aria-expanded="false"
                            {{(int)$settings->forgotten === 1 ? 'disabled' : ''}}
                    >
                        {{ (int)$settings->forgotten === 1 ? 'Forget Account [Forgotten]' : 'Forget Account' }}

                        @if((int)$settings->forgotten !== 1)
                            <span class="caret"></span>
                        @endif
                    <ul class="dropdown-menu">
                        @if(!$settings->{'bypass-au-playcheck'})
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-toggle-flag', ['user' => $user->id, 'flag' => 'bypass-au-playcheck', 'action' => 'on']) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Activate Bypass AU playcheck flag"
                                   data-dbody="Are you sure you want to active the bypass AU playcheck flag on the user <b>{{ $user->id }}</b>?"
                                   id="user-activate-manual-flag">Active</a></li>
                        @else
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.user-toggle-flag', ['user' => $user->id, 'flag' => 'bypass-au-playcheck', 'action' => 'off']) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Remove Bypass AU playcheck flag"
                                   data-dbody="Are you sure you want to remove the Bypass AU playcheck flag for the user <b>{{ $user->id }}</b>?"
                                   id="user-remove-manual-flag">Remove</a></li>
                        @endif
                    </ul>
                    </button>
                    @if((int)$settings->forgotten !== 1)
                        <ul class="dropdown-menu">
                            <li class="dropdown-item">
                                <a href="{{ $app['url_generator']->generate('admin.account.forget', ['user' => $user->id]) }}"
                                   class="action-set-btn text-sm text-muted d-block"
                                   data-dtitle="Forget Account"
                                   data-dbody="Are you sure you want to forget this account?"
                                   id="user-account-forget"
                                >
                                    Forget now
                                </a>
                            </li>
                        </ul>
                    @endif
                </div>
            @endif

            @if(p('user.force-password-change-on-login'))
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        Force password change on next login {{ $settings->{'pwd-change-on-next-login'} ? '[Active]' : '[No]' }} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        @if(!$settings->{'pwd-change-on-next-login'})
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.password-change-on-login', ['user' => $user->id, 'action' => 'on']) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Activate Force password on next login flag"
                                   data-dbody="Are you sure you want to activate the Force password on next login flag on the user <b>{{ $user->id }}</b>?"
                                   id="user-activate-manual-flag">Active</a></li>
                        @else
                            <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.password-change-on-login', ['user' => $user->id, 'action' => 'off']) }}"
                                   class="action-set-btn text-sm text-muted d-block" data-dtitle="Remove Force password on next login flag"
                                   data-dbody="Are you sure you want to remove the Force password on next login flag for the user <b>{{ $user->id }}</b>?"
                                   id="user-remove-manual-flag">Remove</a></li>
                        @endif
                    </ul>
                </div>
            @endif

            {{--manual flag--}}
            @if(p('user.account.flag.manual'))
                <div class="btn-group">
                    <span class="btn btn-default btn-sm manual-flag">
                        Add flag
                    </span>
                </div>
            @endif

            {{--transfer user--}}
            @if(p('user.account.transfer'))
                <div class="btn-group">
                    <span class="btn btn-default btn-sm transfer">
                        Transfer
                    </span>
                </div>
            @endif

            {{--Test account--}}
            @if(p('user.account.test'))
                @php
                    $userTestAccountSetting = $user->getSetting('test_account');
                    $canChangeTestUser = $app['vs.menu']['user.profile']['remove.testUserFlag'];
                    $class = (!$canChangeTestUser && $userTestAccountSetting) ? "disabled": "";
                @endphp
                    <div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle {{ $class }}" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            {{ empty($userTestAccountSetting) ? 'Normal user' : 'Test account' }} <span class="caret"></span>
                        </button>
                            <ul class="dropdown-menu">
                                @if(empty($userTestAccountSetting))
                                    <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.account.test', ['user' => $user->id]) }}"
                                           class="action-set-btn text-sm text-muted d-block" data-dtitle="Test account"
                                           data-dbody="Are you sure you want to set user <b>{{ $user->id }}</b> as test account?"
                                           id="user-verify-phone">Set user as test account</a></li>
                                @elseif($canChangeTestUser)
                                    <li class="dropdown-item"><a href="{{ $app['url_generator']->generate('admin.account.test', ['user' => $user->id]) }}"
                                           class="action-set-btn text-sm text-muted d-block" data-dtitle="Test account"
                                           data-dbody="Are you sure you want to set user <b>{{ $user->id }}</b> as normal account?"
                                           id="user-verify-phone">Set user as normal account</a></li>
                                @endif
                            </ul>
                    </div>
            @endif

            @php
                $shouldShowAccountClosureDropdown = p('user.account-closure.fraud_or_ml') ||
                    p('user.account-closure.rg_concerns') ||
                    p('user.account-closure.general_closure') ||
                    p('user.account-closure.duplicate_account') ||
                    p('user.account-closure.banned_account');
            @endphp
            @if($user->getSetting('closed_account'))
                <button type="button" class="btn btn-default btn-sm dropdown-toggle disabled" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Closed Account (reason: {{ $user->repo->getAccountClosureReason()  }})
                </button>
                @elseif ($shouldShowAccountClosureDropdown)
                <div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Account Closure <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        @if(p('user.account-closure.fraud_or_ml'))
                            <li class="dropdown-item">
                                <a href="{{ $app['url_generator']->generate('admin.account-closure', ['user' => $user->id, 'reason' => 'fraud_or_ml']) }}"
                                    class="action-set-btn form-control account-closure-dropdown-link" data-dtitle="Account Closure"
                                    data-dbody="Are you sure you want to close user account <b>{{ $user->id }}</b>?<br/>
                                        Selected account closure reason: <b>Fraud/ML related</b><br/>
                                        <i>Player account cannot be reactivated, player will be required to open a brand new account.</i>"
                                    data-rg-action-type="account-closure"
                                    id="account-closure">Fraud/ML related</a>
                            </li>
                        @endif
                        @if(p('user.account-closure.rg_concerns'))
                            <li class="dropdown-item">
                                <a href="{{ $app['url_generator']->generate('admin.account-closure', ['user' => $user->id, 'reason' => 'rg_concerns']) }}"
                                   class="action-set-btn form-control account-closure-dropdown-link" data-dtitle="Account Closure"
                                   data-dbody="Are you sure you want to close user account <b>{{ $user->id }}</b>?<br/>
                                        Selected account closure reason: <b>RG concerns</b><br/>
                                        <i>Player account cannot be reactivated, player will be required to open a brand new account.</i>"
                                   data-rg-action-type="account-closure"
                                   id="account-closure">RG concerns</a>
                            </li>
                        @endif
                        @if(p('user.account-closure.general_closure'))
                            <li class="dropdown-item">
                                <a href="{{ $app['url_generator']->generate('admin.account-closure', ['user' => $user->id, 'reason' => 'general_closure']) }}"
                                   class="action-set-btn form-control account-closure-dropdown-link" data-dtitle="Account Closure"
                                   data-dbody="Are you sure you want to close user account <b>{{ $user->id }}</b>?<br/>
                                        Selected account closure reason: <b>General closure request (no compliance or risk concerns)</b><br/>
                                        <i>Player account cannot be reactivated, player will be required to open a brand new account.</i>"
                                   data-rg-action-type="account-closure"
                                   id="account-closure">General closure request (no compliance or risk concerns)</a>
                            </li>
                        @endif
                        @if(p('user.account-closure.duplicate_account'))
                            <li class="dropdown-item">
                                <a href="{{ $app['url_generator']->generate('admin.account-closure', ['user' => $user->id, 'reason' => 'duplicate_account']) }}"
                                   class="action-set-btn form-control account-closure-dropdown-link" data-dtitle="Account Closure"
                                   data-dbody="Are you sure you want to close user account <b>{{ $user->id }}</b>?<br/>
                                        Selected account closure reason: <b>Duplicate account</b><br/>
                                        <i>Player account cannot be reactivated, player will be required to open a brand new account.</i>"
                                   data-rg-action-type="account-closure"
                                   id="account-closure">Duplicate account</a>
                            </li>
                        @endif
                        @if(p('user.account-closure.banned_account'))
                            <li class="dropdown-item">
                                <a href="{{ $app['url_generator']->generate('admin.account-closure', ['user' => $user->id, 'reason' => 'banned_account']) }}"
                                   class="action-set-btn form-control account-closure-dropdown-link" data-dtitle="Account Closure"
                                   data-dbody="Are you sure you want to close user account <b>{{ $user->id }}</b>?<br/>
                                        Selected account closure reason: <b>Banned account</b><br/>
                                        <i>Player account cannot be reactivated, player will be required to open a brand new account.</i>"
                                   data-rg-action-type="account-closure"
                                   id="account-closure">Banned account</a>
                            </li>
                        @endif
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="manual-flag" tabindex="-1" role="dialog" aria-labelledby="single-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card card-primary">
            <div class="modal-header modal-primary bg-primary text-white">
                <h4 class="modal-title w-100 text-center">Manual Flags</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form role="form" method="post" action="{{ $app['url_generator']->generate('user.account.flag.manual', ['user' => $user->id]) }}">
                    <div class="form-group">
                        <label for="manual-flags-flags" class="font-weight-bold">Flag Type</label>
                        <select class="form-control select2" id="manual-flags-flags" style="width: 100%;" data-placeholder="Select a flag">
                            <option value=""></option>
                            @foreach(\App\Helpers\DataFormatHelper::manualFlags($user->repo->getJurisdiction()) as $flag)
                                <option value="{{$flag->name}}">[{{$flag->name}}] {{$flag->indicator_name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="manual-flags-description" class="font-weight-bold">Comment</label>
                        <textarea id="manual-flags-description" class="form-control" rows="3" placeholder="Add comment here"></textarea>
                    </div>
                    <span id="manual-flags-help" class="help-block text-danger font-weight-bold"></span>
                    <button type="submit" id="manual-flags-submit" class="btn btn-primary btn-block" title="Confirm">
                        Submit
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>



<div class="modal fade" id="transfer" tabindex="-1" role="dialog" aria-labelledby="single-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content card card-primary">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title w-100 text-center">Transfer Admin User</h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form role="form" method="post" action="{{ $app['url_generator']->generate('user.account.transfer', ['user' => $user->id]) }}">
                    <div class="form-group">
                        <label for="transfer-brands" class="font-weight-bold">Transfer To</label>
                        <select class="form-control select2" id="transfer-brands" style="width: 100%;" data-placeholder="Select a brand">
                            <option value=""></option>
                            @foreach(\App\Helpers\DataFormatHelper::getTranferBrands() as $brand => $name)
                                <option value="{{$brand}}">{{$name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <span id="transfer-help" class="help-block text-danger font-weight-bold"></span>
                    <button type="submit" id="transfer-submit" class="btn btn-primary btn-block" title="Confirm">
                        Submit
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="intervention_cause_dropdown" class="d-none">
    <div class="form-group">
        <label for="intervention_cause">Intervention cause</label><br/>
        <select id="intervention_cause" class="form-control" style="width: 100%;" data-placeholder="Select one">
            <option value="">Select one</option>
            @foreach(\App\Helpers\DataFormatHelper::getInterventionCauses() as $key => $text)
                <option value="{{ $key }}">{{ $text }}</option>
            @endforeach
        </select>
    </div>
</div>

<style>
    .account-closure-dropdown-link {
        height: 40px;
        display: flex !important;
        align-items: center;
    }
</style>

@section('footer-javascript')
    @parent
    <script>
        $("#manual-flags-submit").click(function() {
            var target = $(this);
            target.attr('disabled', true);
            $.ajax({
                url: "{{ $app['url_generator']->generate('user.account.flag.manual', ['user' => $user->id]) }}",
                type: "POST",
                data: {
                    flag: $("#manual-flags-flags").val(),
                    description: $("#manual-flags-description").val()
                },
                success: function (data, textStatus, jqXHR) {
                    response = jQuery.parseJSON(data);

                    if (response['success'] === true) {
                        location.reload();
                    } else {
                        $('#manual-flags-help').text(response['message']);
                    }
                    target.removeAttr('disabled');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    target.removeAttr('disabled');
                    $('#manual-flags-help').text('Internal server error.');
                    alert('AJAX ERROR');
                }
            });
        });
        $("#manual-flags-flags").select2({
            placeholder: "Select Flag"
        });
        $('.manual-flag').on('click', function (e) {
            e.preventDefault();
            $("#manual-flag").modal('show');
        });


        //transfer
        $("#transfer-brands").select2({
            placeholder: "Select Brand"
        });

        $('.transfer').on('click', function (e) {
            e.preventDefault();
            $("#transfer").modal('show');
        });

        $("#transfer-submit").click(function() {
            var target = $(this);
            target.attr('disabled', true);
            $.ajax({
                url: "{{ $app['url_generator']->generate('user.account.transfer', ['user' => $user->id]) }}",
                type: "POST",
                data: {
                    brand: $("#transfer-brands").val()
                },
                success: function (data, textStatus, jqXHR) {
                    console.log(data);
                    response = jQuery.parseJSON(data);

                    if (response['success'] === true) {
                        location.reload();
                    } else {
                        $('#transfer-help').text(response['message']);
                    }
                    target.removeAttr('disabled');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    target.removeAttr('disabled');
                    $('#transfer-help').text('Internal server error.');
                    alert('AJAX ERROR');
                }
            });
        });


        $('#fifo-submit').click(function(e){
            var fifoDate = $('#fifo-date-txt-field').val();
            $.get("{{ $app['url_generator']->generate('admin.user-update-fifo-date', ['user' => $user->id]) }}", {"fifo-date": fifoDate}, function(res){
                Swal.fire({
                    title: "Updated FIFO",
                    text: res.message,
                    icon: 'success',
                    position: 'top',
                    confirmButtonText: 'OK',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-primary',
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                            location.reload();
                    }
                });
            }, 'json');
        });

        $('.self-exclusion-limit-link').on('click', function(e) {
            e.preventDefault();
            var self = $(this);
            $('#self-exclusion-key').val(self.data('key'));
            $('#self-exclusion-type').val(self.data('type'));
            $('#self-exclusion-subtype').val(self.data('subtype'));
            $('#self-exclusion-modal-title').text(self.data('title'));
            $('#self-exclusion-modal-input-label').text(self.data('label'));
            $('#self-exclusion-modal-input-field').attr("placeholder", self.data('newplaceholder'));
            $('#self-exclusion-modal-form #intervention_cause').val('');
            var save_button = $('#self-exclusion-modal .self-exclusion-modal-save-btn');
            $('#self-exclusion-modal .modal-footer').on('click mouseover', function () {
                $('#self-exclusion-modal-form #intervention_cause').trigger('change');
                if ($('#self-exclusion-modal .has-error').length > 0) {
                    save_button.prop('disabled', true);
                } else {
                    save_button.prop('disabled', false);
                }
            });
            $(document).off('change.intervention_cause_self_exlusion').on('change.intervention_cause_self_exlusion', '#intervention_cause', function(){
                $(this).closest('.form-group').toggleClass('has-error', ($(this).val() === ''));
            });
        });

        $('.self-exclusion-modal-save-btn').on('click', function(e) {
            e.preventDefault();

            var interventionData = {};
            @if (lic('showInterventionTypes', [], $user->id))
                interventionData = {
                    'intervention_type': 'self-exclusion',
                    'intervention_cause': $('#self-exclusion-modal-form #intervention_cause').val()
                };
            @endif

            var url = "{{ $app['url_generator']->generate('admin.user-edit-gaming-limits', ['user' => $user->id]) }}";
            var self = $(this);
            $.ajax({
                url: url,
                type: "POST",
                data: $('#self-exclusion-modal-form').serialize() + '&' + new URLSearchParams(interventionData).toString(),
                success: function (data, textStatus, jqXHR) {
                    response = jQuery.parseJSON(data);
                    $('#help-block-self-exclusion').text(response['message']);
                    if (response['success'] == true) {
                        $('#self-exclusion-modal-form').remove();
                        $('.self-exclusion-modal-save-btn').remove();
                        $('.self-exclusion-modal-close-btn').data('action', 'reload');
                    } else {
                        $('#self-exclusion-modal-group').addClass('has-error');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('AJAX ERROR');
                }
            });
        });

        $('.self-exclusion-modal-close-btn').on('click', function(e) {
            e.preventDefault();
            if ($(this).data('action') == 'reload') {
                location.reload();
            }
        });

        const INTERVENTION_TYPE_TO_SHOW = [
            "self-exclusion",
            "block",
            "super-block",
            "play-block",
            "deposit-block",
            "withdrawal-block",
            "restrict",
            "chat-block",
            "account-closure"
        ];

        function getInterventionCauseHtml(intervention_type) {

            @if (!lic('showInterventionTypes', [], $user->id))
                return '';
            @endif

            if (INTERVENTION_TYPE_TO_SHOW.includes(intervention_type)){
                return $('#intervention_cause_dropdown').html();
            }

            return '';
        }

        function getAccountClosureCheckcard(intervention_type) {

            if (intervention_type === 'account-closure'){
                return '<div class="form-group"><label><input id="account_closure_checkcard" type="checkcard"> I confirm to proceed account closure.</label></div>';
            }

            return '';
        }

        function interventionCauseValidationHandler() {
            $(document).off('change.intervention_cause').on('change.intervention_cause', '#intervention_cause', function(){
                $(this).closest('.form-group').toggleClass('has-error', ($(this).val() === ''));
            });
        }

        function accountClosureValidationHandler() {
            $(document).off('change.account_closure_checkcard').on('change.account_closure_checkcard', '#account_closure_checkcard', function(){
                $(this).closest('.form-group').toggleClass('has-error', $(this).prop('checked') === false);
            });
        }

        $('.action-set-btn').on('click', function(e) {
            e.preventDefault();

            var dialogTitle = $(this).data("dtitle");
            var dialogMessage = $(this).data("dbody");
            var dialogUrl = $(this).attr('href');
            var interventionType = $(this).data("rg-action-type");
            if($(this).data("disabled") != 1){
                showActionConfirmBtn(dialogTitle, dialogMessage, dialogUrl, interventionType);
            }
        });

        function showActionConfirmBtn(dialogTitle, dialogMessage, dialogUrl, interventionType){
            Swal.fire({
                title: '<h3 class="card-title">'+ (dialogTitle || "") +'</h3>',
                html: dialogMessage + getInterventionCauseHtml(interventionType) + getAccountClosureCheckcard(interventionType),
                position: 'top',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check"></i> Yes',
                cancelButtonText: '<i class="fas fa-times"></i> No',
                buttonsStyling: false,
                customClass: {
                    popup: "card card-primary",
                    confirmButton: "btn btn-sm btn-primary w-15",
                    cancelButton: "btn btn-sm btn-secondary w-15 ml-3",
                    title: "card-header",
                    htmlContainer: "card-body text-left text-sm p-3 m-0",
                    actions: "d-flex w-100 justify-content-end pr-3",
                },
                didOpen: (popup) => {
                    interventionCauseValidationHandler();
                    accountClosureValidationHandler();

                    const confirmButton = Swal.getConfirmButton();

                    popup.querySelectorAll("#intervention_cause, #account_closure_checkcard").forEach(element => {
                        element.addEventListener('change', () => {
                            const hasError = popup.querySelectorAll('.has-error').length > 0;
                            confirmButton.disabled = hasError;
                        });
                    });

                    popup.addEventListener('mouseover', () => {
                        popup.querySelectorAll("#intervention_cause, #account_closure_checkcard").forEach(input => {
                            const event = new Event('change');
                            input.dispatchEvent(event);
                        });
                    });
                },
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    let interventionData = {};
                    const popup = Swal.getPopup();

                    @if (lic('showInterventionTypes', [], $user->id))
                    if (INTERVENTION_TYPE_TO_SHOW.includes(interventionType)) {
                        const selectedInterventionType = popup.querySelector('input[name=intervention_type]:checked');
                        const interventionCause = popup.querySelector('#intervention_cause');

                        if (!selectedInterventionType) {
                            Swal.showValidationMessage('Please select an intervention type.');
                            return false;
                        }

                        interventionData = {
                            intervention_type: selectedInterventionType.value || interventionType,
                            intervention_cause: interventionCause.value
                        };
                    }
                    @endif

                    try {
                        var response = await $.ajax({
                            method: 'GET',
                            url: dialogUrl,
                            data: interventionData,
                            dataType: 'json'
                        });

                        if (response.success) {
                            displayNotifyMessage('success', response.message)

                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                setTimeout(() => location.reload(), 2000);
                            }
                        } else {
                            displayNotifyMessage('warning', response.message);
                            return Promise.reject(response.message);
                        }

                    } catch (error) {
                        displayNotifyMessage('error', 'Error connecting to the server');
                        return Promise.reject(error);
                    }
                }
            })
        }
    </script>
@endsection
