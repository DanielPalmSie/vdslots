<table class="table table-hover">
    <thead>
    <tr>
        <th class="col-4 border-top-0 border-bottom-0">Title</th>
        <th class="col-4 border-top-0 border-bottom-0">Score</th>
        <th class="col-4 border-top-0 border-bottom-0">Value</th>
    </tr>
    </thead>
    <tbody>
    @foreach($parent->found_children as $child)
        <tr>
            <td>{{$child->title}}</td>
            <td>{{$child->score}}</td>
            <td>{{$child->result}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
