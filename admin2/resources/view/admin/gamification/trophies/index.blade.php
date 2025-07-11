@extends('admin.layout')


@push('footer-javascript-addition')

<script type="text/javascript">

    function getIDLink() {
        var id_link_trophies = {
            "targets": "col-id",
            "render": function ( data ) {
                var edit_link = "{{ $app['url_generator']->generate($view['variable'].'.edit', [$view['variable_param'] => -1]) }}";
                edit_link = edit_link.replace("-1", data);

                var template_link = "{{ $app['url_generator']->generate('trophies.templateedit', ['trophy' => -1]) }}";
                template_link = template_link.replace("-1", data);

                return '<a href="'+edit_link+'"><i class="far fa-edit"></i></a>&nbsp;|&nbsp;<a href="'+template_link+'"><i class="far fa-file-alt"></i></a>&nbsp;|&nbsp;&nbsp;'+data;
            }
        };

        return id_link_trophies;
    }

</script>

@endpush

@include('admin.gamification.partials.index')
