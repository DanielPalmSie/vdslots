<div class="row">
    <div class="col-6">
        <div class="card card-info">
            <div class="card-header with-border">
                <h3 class="card-title">{{ $c_type->getName() }} Progress</h3>
            </div>
            <div class="card-body">
                    <p><b>Contacts processed: {{ $progress['total'] }}/{{ $progress['total_contacts'] }}</b></p>
                    <div class="progress">
                        <div class="progress-bar progress-bar-primary progress-bar-striped" role="progressbar"
                             aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: {{ $progress['total_percentage'] }}%">
                            <span class="sr-only">Total</span>
                        </div>
                    </div>
                    <p><b>Sent: {{ $progress['sent'] }}</b></p>
                    <div class="progress">
                        <div class="progress-bar progress-bar-primary progress-bar-striped" role="progressbar"
                             aria-valuenow="{{ $progress['sent_percentage'] }}" aria-valuemin="0"
                             aria-valuemax="100"
                             style="width: {{ $progress['sent_percentage'] }}%">
                            <span class="sr-only">Sent</span>
                        </div>
                    </div>
                    <p><b>In queue: {{ $progress['queue'] }}</b></p>
                    <div class="progress">
                        <div class="progress-bar progress-bar-primary progress-bar-striped" role="progressbar"
                             aria-valuenow="{{ $progress['queue_percentage'] }}" aria-valuemin="0"
                             aria-valuemax="100"
                             style="width: {{ $progress['queue_percentage'] }}%">
                            <span class="sr-only">Queue</span>
                        </div>
                    </div>
            </div>
        </div>
    </div>

    <div class="col-3">
        <div class="card card-info">
            <div class="card-header with-border">
                <h3 class="card-title">Details</h3>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-unbordered">
                    <li class="list-group-item">
                        <b>Contact list</b>
                        <p class="float-right">{{ $campaign->campaignTemplate()->first()->namedSearch()->first()->name ?? $campaign->contacts_list_name }}</p>
                    </li>
                    <li class="list-group-item">
                        <b>Template</b>
                        <p class="float-right">{{ $campaign->campaignTemplate()->first()->template()->first()->getTemplateName() }}</p>
                    </li>
                    <li class="list-group-item">
                        <b>Promotion</b>
                    @if (!empty($campaign->bonus_name))
                        <p class="float-right">Bonus: {{ $campaign->bonus_name }}</p>
                    @elseif(!empty($campaign->voucher_name))
                        <p class="float-right">Voucher: {{ $campaign->voucher_name }}</p>
                    @else
                        <p class="float-right">No promotion</p>
                    @endif
                    </li>
                    <li class="list-group-item">
                        <b>Sent time</b>
                        <p class="float-right">{{ $campaign->sent_time }}</p>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-3">
        <div class="card card-info">
            <div class="card-header with-border">
                <h3 class="card-title">Stats</h3>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-unbordered">
                    @foreach(json_decode($campaign->stats)->fail as $reason => $count)
                        <li class="list-group-item">
                            <span style="@if($count > 0) font-weight: bold @else color: grey @endif">{{  ucfirst(str_replace('_', ' ', $reason)) }}</span>
                            <p class="float-right">{{ $count }}</p>
                        </li>
                    @endforeach
                    @if (json_last_error() > 0)
                        <p>{{$campaign->stats}}</p>
                    @endif

                </ul>
            </div>
        </div>
    </div>

</div>
