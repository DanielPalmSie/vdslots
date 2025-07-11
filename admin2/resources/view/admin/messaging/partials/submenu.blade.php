<?php
$route = $app['request_stack']->getCurrentRequest()->get('_route');
?>

@if($c_type->isEmail())
    @includeIf("admin.messaging.partials.modal-new-template")
@endif

<ul class="nav nav-tabs">
    @if($route == "messaging.{$c_type->getName(true)}-templates")
        <li class="nav-item"><a class="nav-link active">List {{ $c_type->getName() }} templates</a></li>
    @elseif(p("messaging.{$c_type->getName(true)}"))
        <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate("messaging.{$c_type->getName(true)}-templates") }}">List {{ $c_type->getName() }} templates</a></li>
    @endif

    @if($route == 'messaging.campaigns.list-recurring')
        <li class="nav-item"><a class="nav-link active">List recurring {{ $c_type->getName() }}</a></li>
    @elseif(p("messaging.{$c_type->getName(true)}.campaign.list"))
        <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate("messaging.campaigns.list-recurring", ['type' => $c_type->getRawType()]) }}">List recurring {{ $c_type->getName() }}</a></li>
    @endif

    @if($route == 'messaging.campaigns.list-scheduled')
        <li class="nav-item"><a class="nav-link active">List scheduled {{ $c_type->getName() }}</a></li>
    @elseif(p("messaging.{$c_type->getName(true)}.campaign.list"))
        <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate("messaging.campaigns.list-scheduled", ['type' => $c_type->getRawType()]) }}">List scheduled {{ $c_type->getName() }}</a></li>
    @endif

    @if($route == 'messaging.campaigns.list-past')
        <li class="nav-item"><a class="nav-link active">List past {{ $c_type->getName() }} campaigns</a></li>
    @elseif(p("messaging.{$c_type->getName(true)}.campaign.list"))
        <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate("messaging.campaigns.list-past", ['type' => $c_type->getRawType()]) }}">List past {{ $c_type->getName() }} campaigns</a></li>
    @endif

    @if($route == "messaging.{$c_type->getName(true)}-templates.new" || $route == "messaging.{$c_type->getName(true)}-templates.edit")
        <li class="nav-item"><a class="nav-link active"><i class="fa fa-plus-square"></i> Editing {{ $c_type->getName() }} template</a></li>
    @elseif(p("messaging.{$c_type->getName(true)}.new") || p("messaging.{$c_type->getName(true)}.edit"))
        <li class="nav-item"><a class="nav-link" id="newTemplate" href="{{ $app['url_generator']->generate("messaging.{$c_type->getName(true)}-templates.new") }}">
             <i class="fa fa-plus-square"></i> Create {{ $c_type->getName() }} template</a></li>
    @endif

    @if($route == "messaging.{$c_type->getName(true)}-campaigns.new")
        <li class="nav-item"><a class="nav-link active">
             <i class="far fa-calendar-plus"></i> Schedule {{ $c_type->getName() }} campaign</a></li>
    @elseif(p("messaging.{$c_type->getName(true)}"))
        <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate("messaging.{$c_type->getName(true)}-campaigns.new") }}">
             <i class="far fa-calendar-plus"></i> Schedule {{ $c_type->getName() }} campaign</a></li>
    @endif
</ul>
