{{-- TODO: if a future requirement will be that an admin needs to submit the formdata to a requested document,
            we need to be able to show the current version of the form --}}

<p>{{ t($document['tag'].'.section.confirm.info') }}</p>
@if($has_source_of_funds_data)
    <a data-title="Source of wealth form"
            data-documenttype="{{$document['tag']}}"
            data-documentid="{{$document['id']}}"
            data-toggle="modal"
            data-target="#source_of_funds_modal"
            data-label="Upload image"
            class="btn btn-default source_of_funds_button"
            id="view_source_of_funds_form"
       >View Source of wealth form</a>
@endif

@if(!empty($document['historical_source_of_funds_data']))

    <br>
    <br>
    <p>
        Previously submitted forms:
    </p>

    <table style="border-collapse: separate; border-spacing: 5px;">
        <tr style="text-align: center;">
            <th colspan="2" style="text-align: right; width: 120px; ">Submitted by</th>
            <th style="text-align: center; width: 80px; ">Last status</th>
            <th style="text-align: center;">Date</th>
        </tr>
        @foreach($document['historical_source_of_funds_data'] as $key => $record)
            <tr>
                <td>
                    <a data-title="Source of wealth form"
                        data-documenttype="{{$document['tag']}}"
                        data-documentid="{{$document['id']}}"
                        data-toggle="modal"
                        data-target="#source_of_funds_modal_{{$key}}"
                        data-label="Upload image"
                        class="btn btn-default source_of_funds_button"
                        id="view_source_of_funds_form"
                   >View</a>&nbsp;
                </td>
                <td>{{$record['actor_id']}}</td>
                <td style="text-align: center;">{{$record['status_when_replaced']}}</td>
                <td style="text-align: center;">{{$record['created_at']}}</td>
            </tr>
        @endforeach
    </table>
        
@endif
