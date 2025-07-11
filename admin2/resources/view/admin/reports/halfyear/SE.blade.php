@extends('admin.reports.halfyear.index')

@section('report-results')
    <div class="col-xs-12">
        <h3>Results</h3>

        <table class="table table-striped table-hover">
            <thead>
            <tr>
                <th>Category</th>
                <th>Subcategory</th>
                <th>Value</th>
            </tr>
            </thead>
            <tbody>
                @foreach($results as $result)
                    <tr>
                        <td>{{$result['category']}}</td>
                        <td>{{$result['subcategory']}}</td>
                        <td>{{$result['value']}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
