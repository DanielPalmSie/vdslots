<div class="col-12 d-flex">
    <div class="col-4">
        <span>New limit will be active on: <b>{{$changes_at ?? '-'}}</b></span>
        <hr class="mb-1 mt-1">
        <span>Limit forced until: <b>{{$forced_until ?? "-"}}</b></span>
    </div>
    <div class="col-8 actions" data-form="{{$form_id}}" data-has_removed_limits="{{$has_removed_limits}}">
        @if(p('edit.gaminglimits') || p('edit.account.limits.block'))
            <button class="btn btn-primary float-right ml-1" name="action" value="update-limits">{{$has_limit ? 'Update' : 'Set'}} limit</button>
            @if($limit != 'deposit' || !licSetting('disable_admin_remove_deposit_limit', $user->id))
                <button class="btn btn-primary float-right ml-1" name="action" value="remove" {{!$has_limit || $has_removed_limits ? 'disabled' : ''}}>Remove limit</button>
                @if(p('edit.gaminglimits.remove.no.cooling'))
                    <button class="btn btn-primary float-right ml-1" name="action" value="remove-no-cooling" {{$has_limit ? '' : 'disabled'}}>Delete limit - no cooling</button>
                @endif
            @endif
            <button class="btn btn-primary float-right ml-1" name="action" value="history">History</button>
            @if($limit != 'net_deposit')
                @if(!empty($forced_until))
                    @if(p('edit.gaminglimits.force'))
                        <button class="btn btn-danger float-right ml-1" name="action" value="remove-force-limit" {{$has_limit ? '' : 'disabled'}}>Remove forced limit</button>
                    @endif
                @else
                    <button class="btn btn-warning float-right ml-1" name="action" value="force-limit" {{$has_limit ? '' : 'disabled'}}>Set as forced</button>
                @endif
            @endif
        @elseif(p('view.gaminglimits'))
            <button class="btn btn-primary float-right ml-1" name="action" value="history">History</button>
        @endif
    </div>
</div>
