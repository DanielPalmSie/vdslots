<table class="table table-hover">
    <form>
        <thead>
        <tr>
            @foreach($parent->settings as $child)
                <th class="col-2 border-top-0 border-bottom-0">Title</th>
                <th class="col-2 border-top-0 border-bottom-0">Score</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        <tr>

            @foreach($parent->settings as $child)
                <td>
                    <input
                            type="radio" name="criminal_record"
                            id="{{ $child->data['slug'] }}"
                            value="{{ $child->data['slug'] }}"
                            @if($parent->found_children->first()->data['slug'] == $child->data['slug']) checked @endif
                    >
                    <label class="criminal-record-option" for="{{ $child->data['slug'] }}">{{$child->title }}</label>
                </td>
                <td>{{$child->score}}</td>
            @endforeach
        </tr>
        </tbody>
    </form>
</table>
