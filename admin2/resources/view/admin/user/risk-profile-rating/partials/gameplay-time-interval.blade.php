<table class="table table-hover table-sm">
    <thead>
    <tr>
        <th class="border-top-0 border-bottom-0">Title</th>
        <th class="border-top-0 border-bottom-0">Score</th>
    </tr>
    </thead>
    <tbody>
    @foreach($parent->found_children as $index => $child)
        <tr>
            <td>
                <span>{{$child->title}}</span>

                <!-- Collapse button with the right icon -->
                <button class="btn btn-sm btn-default float-right collapse-button"
                        data-toggle="collapse"
                        data-target="#collapse-{{$index}}"
                        aria-expanded="false"
                        aria-controls="collapse-{{$index}}">
                    <i class="fa fa-plus toggle-icon"></i>
                </button>

                <span class="badge badge-secondary float-right" style="margin-top: 10px;margin-right: 10px;">{{$child->metadata['percent']}} %</span>

                <!-- Collapsible Content -->
                <div id="collapse-{{$index}}" class="collapse mt-3 mb-3 w-100">
                    <div class="table-responsive-sm">
                        <table class="table table-bordered table-hover table-striped table-sm">
                            <thead>
                            <tr>
                                <th>Total number of gameplay sessions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>{{ $child->metadata['count'] }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </td>
            <td>{{$child->score}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
