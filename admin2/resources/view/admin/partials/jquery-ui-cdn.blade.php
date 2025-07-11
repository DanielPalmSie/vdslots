@if(phive('BoxHandler')->getSetting('new_version_jquery_ui') === "jquery-ui-1.13.2/")
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
@else
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
@endif