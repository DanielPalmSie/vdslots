<table class="table table-bordered" id="list_source_of_funds_table">
    <tbody>
    <tr>
        <th width="10%">Status</th>
        <th width="10%">User id</th>
        <th width="30%">Document</th>
        <th width="30%">Wait time</th>
        <th width="10%">Country</th>
        <th width="10%">Currency</th>
    </tr>
    @if(empty($documents))
        <tr>
            <td>No users found</td>
        </tr>
    @endif
    @foreach($documents as $document)
        <tr>
            <td class="document-status {{$document['status']}}">{{ucfirst($document['status'])}}</td>
            <td>
                <a href="{{ $app['url_generator']->generate('admin.user-documents',
    ['user' => $document['user_id']]) }}">{{$document['user_id']}}</a>
            </td>
            <td>{{$document['tag_formatted']}}</td>
            <td class="{{$document['color']}}">{{$document['wait_time_formatted']}}</td>
            <td>{{$document['country']}}</td>
            <td>{{$document['currency']}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
