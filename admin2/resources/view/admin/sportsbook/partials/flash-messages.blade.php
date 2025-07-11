@if($event_found_success)
    <div class="alert alert-success">
        "Event with ID {{$event_id}} retrieved successfully!"
    </div>
@endif

@if($event_found_error)
    <div class="alert alert-danger">
        "Event with ID {{$event_id}} not found!"
    </div>
@endif

@if($event_removed_success)
    <div class="alert alert-success">
        "Event with ID {{$remove_event_id}} closed successfully!"
    </div>
@endif

@if($event_removed_error)
    <div class="alert alert-danger">
        "An error occured while trying to close event with ID {{$remove_event_id}}!"
    </div>
@endif