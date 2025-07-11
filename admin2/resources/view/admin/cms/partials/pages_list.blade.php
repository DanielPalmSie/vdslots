<table class="table table-bordered">
    <tr>
        <th>Page&nbsp;ID</th>
        <th>Alias</th>
        <th>Cached path</th>
        <th>Background filename</th>
        <th>Action</th>
    </tr>
    @foreach($pages as $page)
        <tr>
            <td>{{ $page->page_id }}</td>
            <td>{{ $page->alias }}</td>
            <td>{{ $page->cached_path }}</td>
            <td><a href="{{ $page->background_url }}" target="_blank">{{ $page->filename }}</a></td>
            <td role="group" aria-haspopup="true">
                <a data-title="Change background for: {{ $page->cached_path }}"
                     data-pageid="{{ $page->page_id }}"
                     data-currenturl="{{ $page->background_url }}"
                     data-toggle="modal"
                     data-target="#change_page_background_modal"
                     data-label="Change background"
                     class="change_page_background_link"
                     id="change_page_background_{{ $page->page_id }}"
                >Change</a>
            </td>
        </tr>
    @endforeach
</table>

