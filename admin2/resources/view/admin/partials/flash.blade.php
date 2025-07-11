<?php
/**
 * TODO move this to a provider
 * @var \Silex\Application $app
 */

?>

<script type="text/javascript">
    function displayNotifyMessage(type, message) {
        let alertType;

        switch (type) {
            case "success":
                alertType = "success";
                break;
            case "danger":
            case "error":
                alertType = "danger";
                break;
            case "warning":
                alertType = "warning";
                break;
            case "info":
            default:
                alertType = "info";
                break;
        }

        Swal.fire({
            icon: alertType === 'danger' ? 'error' : alertType,
            title: alertType.charAt(0).toUpperCase() + alertType.slice(1),
            text: message,
            timer: 4500,
            timerProgressBar: false,
            toast: true,
            iconColor: 'white',
            position: "top",
            showConfirmButton: false,
            customClass: {
                popup: 'text-white bg-' + alertType,
            },
            willClose: (toast) => {
                toast.style.opacity = "0";
            }
        });
    }

    @if($app['flash']->peekAll())
        $(document).ready(function() {
            @foreach($app['flash']->all() as $key => $messages)
                @foreach($messages as $message)
                    displayNotifyMessage("{{ $key }}", "{!! $message !!}");
                @endforeach
            @endforeach
        });
    @endif

</script>

<!--<button type="button" aria-hidden="true" class="close" data-notify="dismiss">×
</button>
<span data-notify="icon"></span>
<span data-notify="title"></span>
<span data-notify="message">Credit card deactivated, and document status updated</span>
<a href="#" target="_blank" data-notify="url"></a>

<div data-notify="container" class="col-xs-11 col-sm-4 alert alert-success animated fadeInDown" role="alert"
     data-notify-position="top-center"
     style="display: inline-block; margin: 0px auto; position: fixed; transition: all 0.5s ease-in-out 0s; z-index: 1031; top: 20px; left: 0px; right: 0px; animation-iteration-count: 1;">
    <button type="button" aria-hidden="true" class="close" data-notify="dismiss">×</button>
    <span data-notify="icon"></span>
    <span data-notify="title"></span>
    <span data-notify="message">Credit card activated, and document status updated</span>
    <a href="#" target="_blank" data-notify="url"></a>
</div>-->
