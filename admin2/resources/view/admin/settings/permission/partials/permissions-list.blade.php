<div class="col-4">
    <div class="card card-info">
        <div class="card-header with-border"><h3 class="card-title">Group <b>{{ $group->name }}</b> details</h3></div>
        <div class="pre-scrollable card-body p-0">
            <table id="permissions-list-table" class="table table-hover">
                <tr>
                    <th>Permission tag</th>
                </tr>
                @foreach($group_permissions as $permission)
                    <tr>
                        <td>{{ $permission->tag }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
