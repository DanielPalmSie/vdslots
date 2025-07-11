<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ getenv('APP_TITLE') }}</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="icon" type="image/png" sizes="16x16" href="/diamondbet/images/<?= brandedCss() ?>mobile/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/diamondbet/images/<?= brandedCss() ?>mobile/favicon-32x32.png">
    <link rel="icon" href="/diamondbet/images/<?= brandedCss() ?>favicon.ico">
    @section('header-css')
        <!-- load plugins of AdminLTE 3.2.0 -->
        {{ loadCssFile("/phive/admin/plugins/fontawesome-free/css/all.min.css") }}
        {{ loadCssFile("/phive/admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css") }}
        {{ loadCssFile("/phive/admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css") }}
        {{ loadCssFile("/phive/admin/plugins/daterangepicker/daterangepicker.css") }}
        {{ loadCssFile("/phive/admin/plugins/select2/css/select2.min.css") }}
        {{ loadCssFile("/phive/admin/plugins/dropzone/min/dropzone.min.css") }}
        {{ loadCssFile("/phive/admin/plugins/sweetalert2/sweetalert2.min.css") }}
        {{ loadCssFile("/phive/admin/plugins/jquery-ui/jquery-ui.min.css") }}
        {{ loadCssFile("/phive/admin/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css") }}

        <!-- load compiled AdminLTE css -->
        {{ loadCssFile("/phive/admin/dist/css/adminlte.min.css") }}

        <!-- load legacy AdminLTE (2.3.0) css files if required -->
        {{ loadCssFile("/phive/admin/customization/styles/css/admin.css") }}

        <!-- load customizations if required -->
        {{ loadCssFile("/phive/admin/customization/plugins/datepicker/css/bootstrap-datepicker3.min.css") }}
        {{ loadCssFile("/phive/admin/customization/plugins/bootstrap-toggle/bootstrap-toggle.min.css") }}
    @show

    {{ loadJsFile("/phive/admin/plugins/jquery/jquery.min.js") }}
    @section('header-javascript')
       <!--[if lt IE 9]>
        <script src="/phive/admin/customization/plugins/scripts/html5shiv.min.js"></script>
        <script src="/phive/admin/customization/scripts/html5shiv.min.js"></script>
        <script src="/phive/admin/customization/plugins/es5-shim/es5-shim.min.js"></script>
        <![endif]-->
    @show

    <meta name="csrf_token" content="<?php echo $_SESSION['token'];?>"/>
    <script type="text/javascript">
        // See config at http://api.jquery.com/jquery.ajax/
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf_token"]').attr('content')
            }
            , statusCode: {
                // response 403 returned by csrf token verification for ajax calls
                // response 403 returned also by permission check
                403: function(response) {
                    var message = "You do not have permission to do this.",
                        json = response.responseJSON;
                    if(json && json.error) {
                        switch(json.error) {
                            case 'invalid_token': message = json.message; break;
                            default: message = 'Something went wrong.'
                        }
                    }
                    alert(message);
                }
            }
        });
    </script>
    <script>
        function showAjaxFlashMessages() {
            // Show flash messages that were created during an ajax request
            $.get('{{$app['url_generator']->generate('show-flash-messages')}}', function(response) {
                $('#flash_message_holder').html(response);
            });
        }
    </script>
</head>

<body class="hold-transition {{ getenv('APP_SKIN') }} sidebar-mini @if(isset($_COOKIE['new-bo-sidebar-collapsed']) && $_COOKIE['new-bo-sidebar-collapsed'] == 1) sidebar-collapse @endif">


<div class="wrapper">
    @include('admin.partials.navbar-top')

    <!--  Include the flash partial here so it can be used anywhere in the BO  -->
    <div id="flash_message_holder">
        @include('admin.partials.flash')
    </div>

    @include('admin.partials.main-sidebar')

    <div class="content-wrapper">
        <div class="content-header">
            @yield('content-header')
        </div>
        <div class="content">
            @yield('content')
        </div>
    </div>

    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            <b>Version</b> {{ \App\Helpers\GitHelper::getLastChangeset() }}
            ({{ \App\Helpers\GitHelper::getLastDate() }})
        </div>
        @if($app['debug'])
            @include('admin.partials.responsive-debug')
        @else
            <strong>&copy; {{ date('Y') }} <a href="{{ getenv('WEB_BASE_URL') }}">
                    {{ getenv('APP_COMPANY_NAME') }}</a></strong> All rights reserved.
        @endif
    </footer>
</div>

@if($app['debug']) @include('admin.partials.content-profilling') @endif
@if($app['slow-queries']) <?php \App\Helpers\Common::logSlowQueries($app); ?> @endif

@section('footer-javascript')
    {{ loadJsFile("/phive/admin/plugins/popper/umd/popper.min.js") }}
    {{ loadJsFile("/phive/admin/plugins/bootstrap/js/bootstrap.bundle.min.js") }}
    {{ loadJsFile("/phive/admin/plugins/jquery-ui/jquery-ui.min.js") }}
    {{ loadJsFile("/phive/js/utility.js") }}
    {{ loadJsFile("/phive/admin/plugins/sweetalert2/sweetalert2.min.js") }}
    {{ loadJsFile("/phive/admin/plugins/datatables/jquery.dataTables.min.js") }}
    {{ loadJsFile("/phive/admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js") }}
    {{ loadJsFile("/phive/admin/plugins/datatables-responsive/js/dataTables.responsive.min.js") }}
    {{ loadJsFile("/phive/admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js") }}
    {{ loadJsFile("/phive/admin/plugins/fastclick/fastclick.js") }}
    {{ loadJsFile("/phive/admin/plugins/moment/moment.min.js") }}
    {{ loadJsFile("/phive/admin/plugins/daterangepicker/daterangepicker.js") }}
    {{ loadJsFile("/phive/admin/plugins/dropzone/dropzone.js") }}
    {{ loadJsFile("/phive/admin/customization/plugins/js-cookie/js.cookie.min.js") }}
    {{ loadJsFile("/phive/admin/customization/plugins/clipboard/clipboard.min.js") }}
    {{ loadJsFile("/phive/admin/customization/plugins/datepicker/js/bootstrap-datepicker.min.js") }}
    {{ loadJsFile("/phive/admin/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js") }}
    {{ loadJsFile("/phive/admin/plugins/select2/js/select2.min.js") }}
    {{ loadJsFile("/phive/admin/customization/scripts/adminapp.js") }}
    {{ loadJsFile("/phive/admin/dist/js/adminlte.min.js") }}

    <script type="text/javascript">
        $(function () {
            var sidebarState = Cookies.get('new-bo-sidebar-collapsed');

            // Apply the sidebar state immediately after DOM is loaded
            if (sidebarState == 1) {
                // Apply the sidebar collapse class
                document.body.classList.add('sidebar-collapse');
            } else {
                document.body.classList.remove('sidebar-collapse');
            }

            $("[data-widget='pushmenu']").click(function () {
                if ($("body").hasClass("sidebar-collapse")) {
                    Cookies.set("new-bo-sidebar-collapsed", 0, { expires: 30, path: '/' });
                } else {
                    Cookies.set("new-bo-sidebar-collapsed", 1, { expires: 30, path: '/' });
                }
            });
        });
    </script>

    <script type="text/javascript">
        // override standard error returned from datatables failed AJAX so it can be catched by jquery "statusCode" error handler
        // https://datatables.net/reference/option/%24.fn.dataTable.ext.errMode
        $.fn.dataTable.ext.errMode = function( settings, techNote, message ) {
            // if we want to manage a custom error for datatables we can refer to this event
            // https://datatables.net/reference/event/error
        }
    </script>
    <script>
        $(document).on('select2:open', function (e) {
            try {
                setTimeout(() => {
                    const input = document.querySelector('.select2-container--open .select2-search__field');
                    if (input) input.focus();
                }, 50);
            } catch (err) {
                console.warn('Select2 focus failed:', err);
            }
        });
    </script>

@show

</body>
</html>
