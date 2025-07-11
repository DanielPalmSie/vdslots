@extends('admin.layout')

@section('content')
    @include('admin.fraud.partials.topmenu')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Fraud Rules</h3>
        </div>
        <div class="card-body">

            <?php
            echo $oFormBuilder->createForm([
                    'template' => '/',
                    'notag' => false, // ommit key or set to true or false to surround with form tags or not
                    'attr' => [
                            'action' => '',
                            'target' => \App\Classes\FormBuilder\Elements\ElementInterface::FORM_TARGET_SELF,
                            'method' => \App\Classes\FormBuilder\Elements\ElementInterface::FORM_POST
                    ]
            ]);
            ?>

            <table class="table table-striped table-bordered dt-responsive w-100 border-collapse"
                   id="liability-section-currency">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Group</th>
                    <th>Country</th>
                    <th>Tbl</th>
                    <th>Field</th>
                    <th>Start value</th>
                    <th>End value</th>
                    <th>Like value</th>
                    <th>Not Like value</th>
                    <th>Value exists</th>
                    <th>Alternative ids</th>
                    <th>Value IN</th>
                    <th>Value NOT IN</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $row)
                    <tr>
                        <td>{{ $row['id'] }}</td>
                        <td>{{ $groups_output[$row['group_id']] }}</td>
                        <td>{{ $row['country'] }}</td>
                        <td>{{ $row['tbl'] }}</td>
                        <td>{{ $row['field'] }}</td>
                        <td>{{ $row['start_value'] }}</td>
                        <td>{{ $row['end_value'] }}</td>
                        <td>{{ $row['like_value'] }}</td>
                        <td>{{ $row['not_like_value'] }}</td>
                        <td>{{ $row['value_exists'] }}</td>
                        <td>{{ $row['alternative_ids'] }}</td>
                        <td>{{ $row['value_in'] }}</td>
                        <td>{{ $row['value_not_in'] }}</td>
                        <td>
                            <a href="/admin2/fraud/fraud-rules/{{ $row['id'] }}/">Edit</a>
                            <a href="#" onclick="fraudRules.del({{ $row['id'] }});return false;">Delete</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

        </div>
    </div>

@endsection


@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/sweetalert2/sweetalert2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {

        });

        var fraudRules = {

            getFields: function(tbl) {

                $.get('/admin2/fraud/fraud-rules/get-fields?tbl='+tbl, function(data) {

                    var res = jQuery.parseJSON(data);
                    var opt = $('#fraud_rule_fields');
                    opt.find('option').remove();

                    $.each(res, function(item, val) {
                        opt.append($("<option />").val(val).text(val));
                    });
                });

            },

            del: function(id) {
                Swal.fire({
                    title: 'Delete Rule',
                    text: 'Are you sure you want to delete rule with ID ' + id + '?',
                    position: 'top',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    buttonsStyling: false,
                    customClass: {
                        title: 'pb-3 text-bold bg-primary',
                        actions: 'd-flex justify-content-around mt-3 w-50', /* Spaced buttons */
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-danger'
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('/admin2/fraud/fraud-rules/delete/', { id: id }, function(data) {
                            location.reload();
                        });
                    }
                });

            }
        };
    </script>
@endsection
