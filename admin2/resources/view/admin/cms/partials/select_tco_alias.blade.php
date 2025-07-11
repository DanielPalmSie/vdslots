<div class="card" id="select-tco-alias-container">
    <div class="card-header">
        <h3 class="card-title">Select alias</h3>
    </div>
    <div class="card-body">

        <form action="">
            <div class="form-group col-12 col-md-12 col-lg-8 col-xlg-6 col-fhd-4">
                <select id="select-tco-alias" name="tco-alias" class="form-control select2-class"
                        style="width: 100%;" data-placeholder="Select alias" data-allow-clear="true">
                    <option></option>
                    @foreach($tco_aliasses as $alias)
                        <option value="{{$alias->alias}}" @if($alias->alias == $_GET['alias']) selected="selected" @endif>{{$alias->alias}}</option>
                    @endforeach
                </select>
            </div>
        </form>

    </div>
</div>

@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(document).ready(function() {

            $('#select-tco-alias').change(function() {
                var get_alias = '<?php echo $_GET['alias']; ?>';
                var get_pagealias = '<?php echo $_GET['pagealias']; ?>';

                <?php
                if(strpos($_SERVER['REQUEST_URI'], 'bannertags') !== false) {
                    $route = 'bannertags';
                } else {
                    $route = 'banneruploads';
                }
                ?>

                var alias = $('#select-tco-alias').val();
                if(alias != '' && alias != get_alias && typeof alias != 'undefined' && alias != null) {
                    window.location.href = '{{ $app['url_generator']->generate($route) }}?pagealias='+get_pagealias
                            +'&alias='+alias;
                }

            });

            $("#select-tco-alias").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('alias') }}");
        });
    </script>
@endsection