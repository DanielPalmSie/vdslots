<style>
    .custom-btn {
        padding: 4px 12px;
    }

    .custom-box {
        border-top-width: 1px;
        margin: 0;
    }
    .grs-widget-container {
        display: flex;
        flex-wrap: wrap;
        flex-direction: row;
        justify-content: center;
        align-items: flex-start;
        gap: 10px 20px; /* row-gap column gap */
    }
    .grs-widget-item {
        flex-grow: 1;
        flex-basis: fit-content;
        flex-shrink: 2;
    }
</style>
<div class="card card-primary border border-primary">
    <div class="card-header">
        <h3 class="card-title text-lg">{{ $section }} Risk Profile Rating</h3>
        <div class="card-tools">
            <button class="btn btn-sm btn-tool text-white" data-card-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="grs-widget-container">
            <div class="grs-widget-item">
                <div class='text-center'><em>@if($has_completed_registration) Global Score @else Registration not completed yet @endif</em></div>
                <div class="text-center">
                    @include('admin.user.risk-profile-rating.score', compact('rating_score'))
                </div>
            </div>
            @if($remote)
                @foreach($remote as $remote_data)
                    @if(!empty($remote_data['found']))
                        <div class="grs-widget-item">
                            <div class='text-center'><em>{{ $remote_data['remote_brand_name'] }} Global Score</em></div>
                            <div class="text-center">
                                @include('admin.user.risk-profile-rating.score', ['rating_score' => $remote_data['rating_tags_statuses']])
                            </div>
                            <div class="text-center">
                                <div class=""><span class=''>Last Updated: {{$remote_data['last_update']}}</span></div>
                                <div>
                                    <a href="{{ $remote_data['remote_link'] }}" target="_blank">
                                        <button type="button" class="btn btn-flat btn-primary custom-btn">
                                            Go to {{$remote_data['remote_brand_name']}}
                                        </button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="grs-widget-item">
                            @if(!empty($remote_data['error']))
                                <div class='text-center red'><strong>There was an error retrieving the information from {{ $remote_data['remote_brand_name'] }}.</strong></div>
                            @else
                                <div class='text-center'><em>Customer doesn't have an account at or GRS records not found {{ $remote_data['remote_brand_name'] }}.</em></div>
                            @endif
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
        <br>

        <div id="accordion">
            @foreach($data as $index => $parent)
                <div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-top"
                         data-toggle="collapse"
                         data-target="#collapse-{{ $index }}"
                         aria-expanded="false"
                         aria-controls="collapse-{{ $index }}"
                         style="cursor: pointer;">
                        <h5 class="mb-0 text-md">{{ $parent->getReplacedTitle() }}</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-primary">
                                Score: <b>{{ $parent->score }}</b>
                            </button>
                            <i class="fa fa-plus fa-sm ml-2 toggle-icon text-gray"></i>
                        </div>
                    </div>

                    <div id="collapse-{{ $index }}" class="collapse">
                        <div class="p-3">
                            @if ($parent->found_children->isEmpty())
                                @if ($parent->message)
                                    <p><b>{{ $parent->message }}</b></p>
                                @else
                                    <p><b>Some configuration is missing so we didn't find data.</b></p>
                                @endif
                            @else
                                @include("admin.user.risk-profile-rating.partials." . ($parent->view ?? 'default'))
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div><!-- /.box-body -->
</div>

<script>
    document.querySelectorAll('[data-toggle="collapse"]').forEach(function (element) {
        element.addEventListener('click', function () {
            const target = document.querySelector(this.getAttribute('data-target'));
            const icon = this.querySelector('.toggle-icon');
            if (target.classList.contains('show')) {
                icon.classList.remove('fa-minus');
                icon.classList.add('fa-plus');
            } else {
                icon.classList.remove('fa-plus');
                icon.classList.add('fa-minus');
            }
        });
    });


    function updateUserCriminalRecord(section, jurisdiction, status) {
        $.ajax({
            type: "POST",
            url: "{{ $app['url_generator']->generate('admin.user-update-criminal-record', ['user' => $user->id]) }}",
            cache: false,
            dataType: 'json',
            data: {"section": section, "jurisdiction": jurisdiction, "status": status},
            success: function (response) {
                if (response['success']) {
                    displayNotifyMessage('success', response['message']);
                    setTimeout(() => location.reload(), 3000);
                } else {
                    displayNotifyMessage('error', response['message']);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                let response = JSON.parse(jqXHR.responseText);

                if(response.errors !== undefined && jqXHR.status == 422){
                    let message = "";
                    for (const [key, value] of Object.entries(response.errors)) {
                        let error = value.join("<br>");
                        message += error + "<br>";
                    }
                    displayNotifyMessage('error', message);
                } else {
                    displayNotifyMessage('error', errorThrown);
                }
            }
        });
    }

    $(document).ready(function () {
        $('input[name=criminal_record]').change(function () {
            let section = "{{ $section }}";
            let jurisdiction = "{{ $jurisdiction }}"
            updateUserCriminalRecord(section, jurisdiction, $(this).val());
        });
    });
</script>
