@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.cms.partials.topmenu')

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Banners Tags</h3>
            </div>
            <div class="card-body">
                <p>On this page you can connect bonus codes to existing banners, or to existing emails. </p>

                <p>After uploading banners for one specific bonus code, you can use this page to connect other bonus codes to the same set of images. </p>

                <p>For the pages /free-spins and /welcome-bonus, the text will also be connected to the bonus codes. However, when uploading banners for those pages, the accompanying texts need to be uploaded as well.</p>

            </div>
        </div>

        @include('admin.cms.partials.selectpage')

        @if(!empty($_GET['pagealias']) && !$is_email && !$only_text)
            @include('admin.cms.partials.selectbanneralias')
        @endif

        @if(!empty($_GET['pagealias']) && $is_email)
            @include('admin.cms.partials.selectemailalias')
        @endif

        @if(!empty($_GET['pagealias']) && $only_text)
            @include('admin.cms.partials.select_tco_alias')
        @endif

        @if(!empty($_GET['alias']) && !$only_text)
            @include('admin.cms.partials.banners')
            @include('admin.cms.partials.connect_alias_to_bonuscodes')
        @endif

        @if(!empty($_GET['alias']) && $only_text)
            @include('admin.cms.partials.connect_alias_to_bonuscodes')
        @endif

        @if(!empty($_GET['email_alias']))
            @include('admin.cms.partials.connect_emails_to_bonuscodes')
        @endif
    </div>

@endsection


@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script src="/phive/admin/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#select-alias').change(function() {
                var get_alias = '<?php echo $_GET['alias']; ?>';
                var get_pagealias = '<?php echo $_GET['pagealias']; ?>';

                var alias = $('#select-alias').val();
                if(alias != '' && alias != get_alias && typeof alias != 'undefined' && alias != null) {
                    window.location.href = '{{ $app['url_generator']->generate('bannertags') }}?pagealias='+get_pagealias+'&alias='+alias;
                }

            });

            $('#select-page').change(function() {
                var get_pagealias = '<?php echo $_GET['pagealias']; ?>';

                var pagealias = $('#select-page').val();
                if(pagealias != '' && pagealias != get_pagealias && typeof pagealias != 'undefined' && pagealias != null) {
                    window.location.href = '{{ $app['url_generator']->generate('bannertags') }}?pagealias='+pagealias;
                }

            });

            /* Bootstrap Duallist */
            var demo1 = $('select[name="duallistbox_bonuscodes[]"]').bootstrapDualListbox({
                nonSelectedListLabel: 'Available',
                selectedListLabel: 'Connected',
            });

            $("#select-page").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('pagename') }}");

            $("#select-alias").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('alias') }}");

            $("#seeallimages").click(function() {
                $("#first-banner").hide();
                $("#allimages").show();
            });
        });
    </script>
@endsection

@section('header-css')
    @parent
    {{ loadCssFile("/phive/admin/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css") }}
    {{ loadCssFile("/phive/admin/customization/styles/css/promotions.css") }}
@endsection
