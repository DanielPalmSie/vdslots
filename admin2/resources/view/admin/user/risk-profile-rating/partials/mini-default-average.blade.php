<table class="table table-hover">
    <thead>
    <tr>
        <th class="col-4 border-top-0 border-bottom-0">Title</th>
        <th class="col-2 border-top-0 border-bottom-0">Score</th>
        <th class="col-2 border-top-0 border-bottom-0">Average last {{$parent->data['replacers']['_LAST_DAYS']}} days</th>
        <th class="col-2 border-top-0 border-bottom-0">Average previous {{$parent->data['replacers']['_PREVIOUS_DAYS']}} days</th>
        <th class="col-2 border-top-0 border-bottom-0">Increase ratio (%)</th>
    </tr>
    </thead>
    <tbody>
    @foreach($parent->found_children as $child)
        <tr>
            <td>{{$child->title}}</td>
            <td>{{$child->score}}</td>
            <td>{{$child->result['last']}}</td>
            <td>{{$child->result['prev']}}</td>
            <td>{{$child->result['res']}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
