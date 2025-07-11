
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ $params['identity_check_section']['report_title'] }}</h3>
    </div><!-- /.card-header -->
    <div class="card-body">
        <h5 class="card-title mb-2">Display last {{ $params['identity_check_section']['limit'] }} records</h5>
        <table id="obj-datatable" class="table table-striped table-bordered dt-responsive w-100 border-collapse">
            <thead>
            <tr>
                @foreach($params['identity_check_section']['columns'] as $k => $v)
                    <th>{{ $v }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            @foreach($params['identity_check_section']['data'] as $element)
                <tr class="text-dark">
                    @foreach($element as $k => $v)
                        <td class="col-{{ $k }}">{{ $v }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div><!-- /.card-body -->
</div>
