@extends('admin.layout')

@section('content')
    @include('admin.fraud.partials.topmenu')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Fraud Groups</h3>
        </div>
        <div class="card-body">
            <div id="fraud-dates-wrapper">
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
            </div>
            <table class="table table-striped table-bordered dt-responsive w-100 border-collapse"
                   id="liability-section-currency">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Tag</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Description</th>
                    <th>Is Active</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($groups as $row)
                    <tr>
                        <td>{{ $row['id'] }}</td>
                        <td>{{ $row['tag'] }}</td>
                        <td>{{ $row['start_date'] }}</td>
                        <td>{{ $row['end_date'] }}</td>
                        <td>{{ $row['description'] }}</td>
                        <td>{{ ($row['is_active'] == 1) ? 'YES' : 'NO' }}</td>
                        <td><a href="/admin2/fraud/fraud-groups/{{ $row['id'] }}/">Edit</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>

        </div>
    </div>

@endsection


@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function () {
            $('#fraud-dates-wrapper .datetimepicker').each(function () {
                const $el = $(this);
                $el.val("")


                if ($el.data('daterangepicker')) {
                    $el.data('daterangepicker').remove();
                }
                if ($el.data('datepicker')) {
                    $el.datepicker('destroy');
                }
                if ($el.hasClass('hasDatepicker')) {
                    $el.datepicker('destroy');
                }

                $el.daterangepicker({
                    singleDatePicker: true,
                    timePicker: true,
                    timePicker24Hour: true,
                    timePickerSeconds: true,
                    autoUpdateInput: false,
                    startDate: false,
                    locale: {
                        format: 'YYYY-MM-DD HH:mm:ss',
                        cancelLabel: 'Clear'
                    }
                }, function (start) {
                    $el.val(start.format('YYYY-MM-DD HH:mm:ss'));
                });

                $el.on('cancel.daterangepicker', function () {
                    $(this).val('');
                });

            });
        });
    </script>
@endsection
