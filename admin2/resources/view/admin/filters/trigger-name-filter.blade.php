<label for="select-trigger-name">Trigger Name</label>
<select name="trigger_name" id="select-trigger-name" class="form-control select2-class"
        style="width: 100%;" data-placeholder="Shows all triggers if not selected" data-allow-clear="true">
    <option value="all">All</option>

    @foreach(\App\Helpers\TriggersHelper::getTriggersList($params['trigger_type']) as $trigger)
        <option value="{{ $trigger['name'] }}" {{$params['trigger_name'] == $trigger['name'] ? "selected" : ''}}>{{ $trigger['name'] }} ({{ $trigger['indicator_name'] }})</option>
    @endforeach
</select>

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $("#select-trigger-name").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('trigger_name', 'all') }}").change();
        });
    </script>
@endsection