@extends('admin.layout')

@section('content')

    @include('admin.promotions.races.partials.topmenu')

    <div class="card card-solid card-primary">
        <div class="card-header">
            <h3 class="card-title">@if($id == 0) Add @else Edit @endif Race</h3>
            <div class="float-right">
                <a href="{{ $app['url_generator']->generate('promotions.races.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
            </div>
        </div>
        <div class="card-body">
            <div id="races-dates-wrapper">
                @php
                    echo $oFormBuilder->createForm([
                        'template' => '/',
                        'notag' => false, // ommit key or set to true or false to surround with form tags or not
                        'attr' => [
                            'action' => '',
                            'id' => 'frm_races',
                            'target' => \App\Classes\FormBuilder\Elements\ElementInterface::FORM_TARGET_SELF,
                            'method' => \App\Classes\FormBuilder\Elements\ElementInterface::FORM_POST
                        ]
                    ]);
                @endphp
            </div>
            <div class="card-footer">
                <button type="button" onclick="races.save('');return false;" class="btn btn-primary">Save</button>
                <button type="button" onclick="races.save(-1);return false;" class="btn btn-danger">Save As New</button>
                <a href="{{ $app['url_generator']->generate('promotions.races.index') }}" class="btn btn-default float-right">Cancel</a>
            </div>
        </div>
    </div>

@endsection


@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function () {
            $('#races-dates-wrapper .datetimepicker').each(function () {
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

        var races = {
            // this function is a hack for using normal buttons instead of formBuilder
            save: function(val) {
                $('#frm_save_val').val(val);
                $('#frm_races').submit();
            }
        };
    </script>
@endsection

@section('header-css')
    @parent
    <link rel="stylesheet" type="text/css" href="/phive/admin/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css">
    <link rel="stylesheet" type="text/css" href="/phive/admin/customization/styles/css/promotions.css">
@endsection
