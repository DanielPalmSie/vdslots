<nav class="main-header navbar navbar-expand navbar-primary" role="navigation">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars text-white"></i></a>
            </li>
            @if($app['vs.config']['active.sections']['user.profile'] && p($app['vs.menu']['user.profile']['permission']))
                <li class="nav-item d-none d-sm-block">
                    <form id="quick-search-form" class="form-inline ml-3" role="search"
                          action="{{ $app['url_generator']->generate('user.search') }}" method="post">
                        <div class="input-group input-group-sm mt-1">
                            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                            <input name="user[id]" type="search" class="form-control form-control-navbar" id="navbar-search-input"
                                   value="{{ $app['request_stack']->getCurrentRequest()->get('user')['id'] }}"
                                   placeholder="User quick search">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-navbar"><i class="fa fa-search" aria-hidden="true"></i></button>
                            </div>
                        </div>
                    </form>
                </li>
            @endif
        </ul>
        <ul class="navbar-nav ml-auto d-sm-flex justify-content-between align-content-between">
            @if($app['vs.config']['active.sections']['user.profile'])
                <?php $recent_user_list = json_decode($_SESSION['recent-users'],true) ?>
                @if (count($recent_user_list) > 0 && p('users.section'))
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link" data-toggle="dropdown">
                            <i class="fa fa-users text-white"></i>
                            <span class="badge badge-warning navbar-badge">{{ count($recent_user_list) }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                            <span class="dropdown-header">Recently opened profiles</span>
                                <div class="dropdown-divider"></div>
                                @foreach($recent_user_list as $user_id => $username)
                                    <a href="{{ $app['url_generator']->generate('admin.userprofile', ['user' => $username]) }}" class="dropdown-item">
                                        <i class="far fa-circle text-lightblue"></i> {{ $user_id }}
                                    </a>
                                @endforeach
                                <div class="dropdown-divider"></div>
                            <a class="dropdown-item dropdown-footer" href="{{ $app['url_generator']->generate('user.delete.recent') }}">Clear list</a>
                        </div>
                    </li>
                @endif
            @endif
            <li class="nav-item dropdown">
                <a href="#"
                   class="nav-link"
                   data-toggle="dropdown"
                   aria-expanded="false"
                >
                    @if(getenv('APP_SHORT_NAME') === 'VS')
                        <img src="/diamondbet/images/videoslotslogo2.png" class="brand-image img-sm" alt="Logo">
                    @endif
                    <span class="text-white text-sm ml-2">
                       @if(cu()->getAttr('firstname')) {{ cu()->getAttr('firstname') }} @else {{ cu()->getAttr('username') }} @endif
                    </span>
                </a>
                <ul class="nav-item dropdown-menu bg-white">
                    <li class="dropdown-item" style="background-color: #ffffff;">
                        <a href="/admin_log/?logout" class="btn btn-sm btn-default float-right">Sign out</a>
                    </li>
                </ul>
            </li>
        </ul>
</nav>

@section('footer-javascript')
    @parent
    <script>

        function navbar_search(form, field) {
            if(field.val() == "") {
                field.attr('placeholder','Field cannot be empty');
                return false;
            } else {
                form.submit();
            }
        }

        $(function () {
            var quick_search_form = $("#quick-search-form");
            var quick_search_field = $("#navbar-search-input");

            quick_search_form.find('button').on( "click", function(event) {
                navbar_search(quick_search_form, quick_search_field);
            });

            quick_search_field.on( "keydown", function(event) {
                if(event.which == 13){
                    navbar_search(quick_search_form, quick_search_field);
                }
            });
        });
    </script>
@endsection
