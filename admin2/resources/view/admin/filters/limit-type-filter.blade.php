<label for="select-">Limit type</label>
<select name="limit_type" id="select-limit_type" class="form-control select2-class"
        style="width: 100%;" data-placeholder="" data-allow-clear="true">
    <option value="all" {{$params['limit_type'] == 'all' ? "selected" : ''}}>All</option>
    <option value="dep-lim" {{$params['limit_type'] == 'dep-lim' ? "selected" : ''}}>Deposit</option>
    <option value="lgawager-lim" {{$params['limit_type'] == 'lgawager-lim' ? "selected" : ''}}>Wager</option>
    <option value="lgaloss-lim" {{$params['limit_type'] == 'lgaloss-lim' ? "selected" : ''}}>Loss</option>
    <option value="betmax-lim" {{$params['limit_type'] == 'betmax-lim' ? "selected" : ''}}>Max bet</option>
    <option value="lgatime-lim" {{$params['limit_type'] == 'lgatime-lim' ? "selected" : ''}}>Time out limit</option>
</select>