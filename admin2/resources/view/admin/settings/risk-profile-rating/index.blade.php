@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>{{$main_section}} Profile Settings</h1>
            </div>

            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('settings-dashboard') }}">Settings</a></li>
                    <li class="breadcrumb-item active">{{$main_section}} Profile Settings</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">

        @include('admin.settings.partials.topmenu')

        <div class="card card-solid card-primary rpr-main-box">
            <div class="card-header">
                <h3 class="card-title">{{$main_section}} Profile Settings</h3>
            </div>
            <div id="risk-profile-accordion">
                <div class="card-body">
                    @foreach($data as $parent)
                        <form action="{{ $app['url_generator']->generate('settings.aml-profile.crud') }}" method="POST" id="{{$parent->name}}-create">
                            <input type="hidden" name="action" value="create">
                            <input type="hidden" name="parent" value="{{$parent->name}}">
                            <input type="hidden" name="type" value="{{$parent->type}}">
                            <input type="hidden" name="section" value="{{$parent->section}}">
                            <input type="hidden" name="jurisdiction" value="{{$jurisdiction}}">
                        </form>
                    @endforeach

                    <form method='post' id="rpr-form">
                        <div class="card card-solid select-jurisdiction">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-3">
                                        <label for="select-jurisdiction">Jurisdiction</label>
                                        <select name="jurisdiction" id="select-jurisdiction" class="form-control select2-class">
                                            @foreach($country_jurisdiction_map as $key => $value)
                                                <option @if($jurisdiction == $value) selected @endif value="{{ $value }}">{{ $key }} ({{ strtoupper($value) }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">

                        @foreach($data as $parent)
                            <div class="card" id="{{$parent->name}}">
                                <div class="card-header">
                                    <button class="btn p-0 d-block w-100" type="button" data-toggle="collapse" data-target="#collapse-{{ $loop->index }}" aria-expanded="true" aria-controls="collapse-{{ $loop->index }}">
                                        <h2 class="card-title">{{$parent->getReplacedTitle()}} </h2>
                                        <i class="fa fa-plus float-right"></i>
                                    </button>
                                </div>

                                <div id="collapse-{{ $loop->index }}" class="collapse" aria-labelledby="heading-{{ $loop->index }}" data-parent="#risk-profile-accordion">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-8">
                                                <table class="table table-bordered dt-responsive" cellspacing="0" width="100%">
                                                    <thead>
                                                    <tr>
                                                        <th class="text-right">Name</th>
                                                        <th class="text-center">Value</th>
                                                        <th>Score</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach($parent->children as $kid)
                                                        <tr>
                                                            <td class=" text-right">{{ $kid->title }}</td>
                                                            <td class=" text-center">{{ $kid->name }}</td>
                                                            <td>
                                                                <div class="row">
                                                                    <div class="col-8">
                                                                        <input type="text" name="score[{{$kid->id}}]" value="{{$kid->score}}" class="form-control" >
                                                                    </div>
                                                                    @if(in_array($parent->type, ['multiplier', 'interval']))
                                                                        <div class="col-4">
                                                                            <button class="btn btn-danger delete-row"
                                                                                data-category="{{$kid->category}}"
                                                                                data-section="{{$kid->section}}"
                                                                                data-jurisdiction="{{$kid->jurisdiction}}"
                                                                                data-name="{{$kid->name}}"
                                                                                data-title="{{$kid->title}}">Delete</button>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                    @include("admin.settings.risk-profile-rating.partials.{$parent->type}")
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="col-4">
                                                <table class="table table-bordered dt-responsive" cellspacing="0" width="100%">
                                                    <thead>
                                                    <tr>
                                                        <th>Additional Configuration</th>
                                                        <th>Value</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @if (!empty($parent->data))
                                                        @foreach($parent->data['replacers'] as $key => $value)
                                                            <tr>
                                                                <td>{{$key}}</td>
                                                                <td><input type="text" name="data[{{$parent->id}}][{{$key}}]" value="{{$value}}"></td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                    </tbody>
                                                </table>

                                                @if(in_array($parent->type, ['multiplier', 'interval']))
                                                    <div class="col-12" style="border: 1px solid #f4f4f4;">
                                                        <h4><b>Form examples:</b></h4>

                                                        <div class="row">
                                                            <span class="col-4"><25%</span>
                                                            <span class="col-8">start=0, end=25</span>
                                                        </div>
                                                        <div class="row">
                                                            <span class="col-4">>75%</span>
                                                            <span class="col-8">start=75, end=leave this input empty</span>
                                                        </div>
                                                        <div class="row">
                                                            <span class="col-4">25% - 50%</span>
                                                            <span class="col-8">start=25, end=50</span>
                                                        </div>
                                                        <hr>
                                                        <div class="row">
                                                            <span class="col-4">10X</span>
                                                            <span class="col-8">name=10X, value=10</span>
                                                        </div>
                                                        <hr>
                                                        <div class="row">
                                                            <span class="col-4">€0 - €4,999</span>
                                                            <span class="col-8">start=0, end=4999</span>
                                                        </div>
                                                        <div class="row">
                                                            <span class="col-4">€5000 - €5,999</span>
                                                            <span class="col-8">start=5000, end=5999</span>
                                                        </div>
                                                        <div class="row">
                                                            <span class="col-4">€5,999+</span>
                                                            <span class="col-8">start=5999, end=leave this input empty</span>
                                                        </div>
                                                        <hr>
                                                        <div class="row">
                                                            <span class="col-4">0 - 5</span>
                                                            <span class="col-8">start=0, end=5</span>
                                                        </div>
                                                        <div class="row">
                                                            <span class="col-4">5 - 10</span>
                                                            <span class="col-8">start=5, end=10</span>
                                                        </div>
                                                        <div class="row">
                                                            <span class="col-4">10+</span>
                                                            <span class="col-8">start=10, end=leave this input empty</span>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </form>
                    <div class="row">
                        <div class="col-12">
                            <div class="input-group justify-content-end">
                                <span class="input-group-btn ">
                                    <button type="button" id="aml-form-submit"
                                            class="btn btn-success float-right">Update</button>
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="overlay loading-overlay d-none">
                <i class="fas fa-sync fa-spin"></i>
            </div>
        </div>
    </div>

    <style>
        .btn-danger:hover, .btn-danger:active, .btn-danger.hover {
            background-color: #d73925 !important;
        }
        .collapsed-box .action-set-btn {
            display: none;
        }
        .box.select-jurisdiction {
            width: 25%;
        }
    </style>

    <script>
        $(document).ready(function () {
            $('#aml-form-submit').on('click', function () {
                $(".rpr-main-box .loading-overlay").removeClass('d-none');
                $.ajax({
                    url: "{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}",
                    type: "POST",
                    data: $('#rpr-form').serialize(),
                    success: function (data) {
                        displayNotifyMessage(data['status'], data['message']);
                        $(".rpr-main-box .loading-overlay").addClass('d-none');
                    },
                    error: function (jqXHR, textStatus) {
                        displayNotifyMessage("error", "Something went wrong.");
                        $(".rpr-main-box .loading-overlay").addClass('d-none');
                        console.info(textStatus);
                    }
                });
            });

            $( "#select-jurisdiction" ).change(function() {
                location.href = '?jurisdiction=' + $(this).val();
            });

            $(".delete-row").click(function (e) {
                e.preventDefault();
                let $element = $(this);
                let title = $(this).data('title');

                $.ajax({
                    url: "{{ $app['url_generator']->generate('settings.aml-profile.crud') }}",
                    type: "POST",
                    data: {
                        category: $(this).data('category'),
                        section: $(this).data('section'),
                        jurisdiction: $(this).data('jurisdiction'),
                        name: $(this).data('name'),
                        action: 'delete',
                    },
                    success: function (data) {
                        if (data.status === 'success') {
                            $element.parent().parent().remove();
                            displayNotifyMessage("success", title + " deleted successfully.");
                        } else {
                            displayNotifyMessage("error", "Something went wrong.");
                            console.info(data);
                        }


                    },
                    error: function (jqXHR, textStatus) {
                        displayNotifyMessage("error", "Something went wrong.");
                        console.info(textStatus);
                    }
                });
            })

            $('.collapse')
                .on("show.bs.collapse", function () {
                    $(this)
                        .prev(".card-header")
                        .find(".fa")
                        .removeClass("fa-plus")
                        .addClass("fa-minus");
                })
                .on("hide.bs.collapse", function () {
                    $(this)
                        .prev(".card-header")
                        .find(".fa")
                        .removeClass("fa-minus")
                        .addClass("fa-plus");
                    });
        });

    </script>
@endsection

@include('admin.fraud.partials.fraud-footer')
