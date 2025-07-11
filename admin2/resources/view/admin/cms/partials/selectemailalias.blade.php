<div class="card" id="select-email-alias-container">
    <div class="card-header">
        <h3 class="card-title">Select email alias</h3>
    </div>
    <div class="card-body">

        <form action="">
            <div class="form-group col-12 col-md-12 col-lg-8 col-xlg-6 col-fhd-4">
                <select id="select-email-alias" name="email-alias" class="form-control select2-class"
                        style="width: 100%;" data-placeholder="Select email alias" data-allow-clear="true">
                    <option></option>
                    @foreach($email_aliases as $email_alias)
                        <option value="{{$email_alias->mail_trigger}}" @if($email_alias->mail_trigger == $_GET['email_alias']) selected="selected" @endif>{{$email_alias->mail_trigger}}</option>
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

            $("#select-email-alias").change(function() {
                var get_email_alias = '<?php echo $_GET['email_alias']; ?>';
                var get_pagealias = '<?php echo $_GET['pagealias']; ?>';

                <?php
                if(strpos($_SERVER['REQUEST_URI'], 'bannertags') !== false) {
                    $route = 'bannertags';
                } else {
                    $route = 'banneruploads';
                }
                ?>

                var email_alias = $('#select-email-alias').val();
                if(email_alias != '' && email_alias != get_email_alias && typeof email_alias != 'undefined' && email_alias != null) {
                    window.location.href = '{{ $app['url_generator']->generate($route) }}?pagealias='+get_pagealias
                            +'&email_alias='+email_alias;
                }

            });

            $("#select-email-alias").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('email_alias') }}");

        });
    </script>
@endsection