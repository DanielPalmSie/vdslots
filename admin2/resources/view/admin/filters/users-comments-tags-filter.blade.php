<label for="select-">User Comments tags</label>
<select name="user_comment_tags" id="select-user_comment_tags" class="form-control select2-class"
        style="width: 100%;" data-placeholder="" data-allow-clear="true">
    <option value="all">All</option>
    <option value="complaint" {{$params['user_comment_tags'] == 'complaint' ? "selected" : ''}}>Complaint</option>
    <option value="limits" {{$params['user_comment_tags'] == 'limits' ? "selected" : ''}}>Discussion about RG limits</option>
</select>