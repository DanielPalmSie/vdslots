<label for="select-">Action Limit</label>
<select name="action_limit" id="select-action_limit" class="form-control select2-class"
        style="width: 100%;" data-placeholder="" data-allow-clear="true">
    <option value="all" {{$params['action_limit'] == 'all' ? "selected" : ''}}>All</option>
    <option value="add" {{$params['action_limit'] == 'add' ? "selected" : ''}}>Add</option>
    <option value="increase" {{$params['action_limit'] == 'increase' ? "selected" : ''}}>Increase</option>
    <option value="decrease" {{$params['action_limit'] == 'decrease' ? "selected" : ''}}>Decrease</option>
    <option value="removal_of_limit" {{$params['action_limit'] == 'removal_of_limit' ? "selected" : ''}}>Removal of limit</option>
</select>