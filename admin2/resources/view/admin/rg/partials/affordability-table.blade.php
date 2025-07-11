<div class="card card-solid card-primary">
    <div class="card-header">
        <h3 class="card-title">Affordability</h3>
    </div><!-- /.card-header -->
    <div class="card-body">
        <table id="obj-datatable" class="table table-striped table-bordered dt-responsive w-100 border-collapse"
               cellspacing="0" width="100%">
            <thead>
            <tr>
                @foreach($columns as $k => $v)
                    <th class="col-{{ $k }}">{{ $v }}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>

            @foreach($data as $col)
                <tr>
                    @foreach($columns as $k => $v)

                        <td class="col-{{ $k }}">{{ $col[$k] }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div><!-- /.card-body -->
</div>
