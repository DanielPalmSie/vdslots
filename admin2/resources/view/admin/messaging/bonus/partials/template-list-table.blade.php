<table id="bonus-template-list-databable" class="table table-striped table-bordered" cellspacing="0" width="100%">
    <thead>
    <tr>
        <th>Template ID</th>
        <th>Template Name</th>
        <th>Bonus Name</th>
        <th>Bonus Type</th>
        <th>Reward</th>
        <th>Created At</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @foreach($data as $element)
        <tr>
            <td>{{ $element->id }}</td>
            <td>{{ $element->template_name }}</td>
            <td>{{ $element->bonus_name }}</td>
            <td>{{ $element->bonus_type }}</td>
            <td>{{ $element->reward_desc }}</td>
            <td>{{ $element->created_at }}</td>
            <td>@if ($show_actions == true)
                    @if(p('messaging.promotions.bonus-templates.edit'))
                    <a href="{{ $app['url_generator']->generate('messaging.bonus.create-template', ['action' => 'edit', 'template-id' => $element->id]) }}">
                        <i class="fa fa-edit"></i> Edit</a>
                    @endif
                    @if(p('messaging.promotions.bonus-templates.delete'))
                    - <a class="href-confirm" data-message="Are you sure you want to delete the Bonus code template?" href="{{ $app['url_generator']->generate('messaging.bonus.delete-template', ['template-id' => $element->id]) }}">
                        <i class="fa fa-trash"></i> Delete</a>
                    @endif
                @else
                    <a class="select-bonus-link" onclick="bonusTemplates.setBonusField('{{ $element->id  }}', '{{ $element->bonus_name }}');return false;" href="#"><i class="fa fa-circle-o"></i> Select</a>
                @endif
                <a href="#" class="detail-link" onclick="bonusTemplates.viewDetails('{{ $element->id  }}');return false;"><i class="fa fa-eye"></i> View details</a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

@include('admin.partials.href-confirm')

<div id="detail-view-modal_bonus" class="modal fade">
    <div class="modal-dialog dialog-width">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detailed View</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div id="detail_modal_body_bonus" class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@section('footer-javascript')
    @parent
    <script>
        var bonusTemplates = {

            setBonusField: function(bonus_id, bonus_name) {
                $("#bonus-desc-input").val(bonus_name);
                $("#bonus-id-input").val(bonus_id);
                $("#award-desc-input").val('');
                $("#award-id-input").val('');
            },

            viewDetails:  function viewDetails(id) {
                console.log(id);
                $.ajax({
                    url: "{{ $app['url_generator']->generate('messaging.bonus.get-bonus-type-template-details') }}",
                    type: "POST",
                    data: {bonus: id},
                    success: function (response, textStatus, jqXHR) {
                        $("#detail_modal_body_bonus").html(response['html']);
                        $('#detail-view-modal_bonus').modal('show');
                        return false;
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            }
        };

        $(function () {
            $("#bonus-type-list-databable").DataTable({
                pageLength: 25,
                language: {
                    emptyTable: "No results found.",
                    lengthMenu: "Display _MENU_ records per page"
                },
                order: [[0, "desc"]],
                columnDefs: [{targets: 4, orderable: false, searchable: false}]
            });
        });
    </script>
@endsection