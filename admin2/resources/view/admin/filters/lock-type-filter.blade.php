<label for="select-">Limit Type</label>
<select name="lock_type" id="select-lock_type" class="form-control select2-class"
        style="width: 100%;" data-placeholder="" data-allow-clear="true">
    <option value="all" {{$params['lock_type'] == 'all' ? "selected" : ''}}>All</option>
    <option value="betmax" {{$params['lock_type'] == 'betmax' ? "selected" : ''}}>Betmax</option>
    <option value="deposit" {{$params['lock_type'] == 'deposit' ? "selected" : ''}}>Deposit limit</option>
    <option value="exclude" {{$params['lock_type'] == 'exclude' ? "selected" : ''}}>Self Excluded</option>
    <option value="lock" {{$params['lock_type'] == 'lock' ? "selected" : ''}}>Self lock</option>
    <option value="login" {{$params['lock_type'] == 'login' ? "selected" : ''}}>Login limit</option>
    <option value="loss" {{$params['lock_type'] == 'loss' ? "selected" : ''}}>Loss limit</option>
    <option value="rc" {{$params['lock_type'] == 'rc' ? "selected" : ''}}>Reality check</option>
    <option value="lockgamescat" {{$params['lock_type'] == 'lockgamescat' ? "selected" : ''}}>Spelpaus 24</option>
    <option value="timeout" {{$params['lock_type'] == 'timeout' ? "selected" : ''}}>Timeout limit</option>
    <option value="wager" {{$params['lock_type'] == 'wager' ? "selected" : ''}}>Wager limit</option>
</select>