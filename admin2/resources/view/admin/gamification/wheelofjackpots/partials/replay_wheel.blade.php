@section('header-css') @parent
<link rel="stylesheet" href="/phive/admin/customization/styles/css/wheel.css" type="text/css" />
@endsection
<div
        id="wheelContainer"
        style="
                background-image: url('/file_uploads/wheel/bg_ui_{{$wheel_style['name']}}.jpg');
                background-repeat: no-repeat;
                background-position: center;
                background-size: contain;
                width: 1902px;
                height: 901px;
                margin: 0 auto;
                "
>
    <div id="completeWheel" style="position: relative; width: 1100px; height: 850px;/*! left:450px; */margin: auto;width: 50%;">
        <div class="canvas_box" id="canvasContainer" style="background: url(/file_uploads/wheel/wheelfortune_outsiderim_{{$wheel_style['name']}}.png) center no-repeat;background-size: cover;">
            <canvas id="canvas" width="901" height="836">
                <p style="color: grey" align="center">Sorry, your browser doesn't support canvas. </p>
            </canvas>
            <img id="prizePointer" src="/file_uploads/wheel/win_pointer_{{$wheel_style['name']}}.png" alt="Prize Pointer" width="70" />
        </div>
        <div class="spin_box" id="spin_button" onClick="startSpin( '{{ $win_slice }}' ,true );">
            <img id="spinButton2" src="/file_uploads/wheel/Spin-Button_pressed_{{$wheel_style['name']}}.png" alt="Spin Button" width="250px" height="250px" style="position: absolute; bottom: 8px; right: 0px;" />
            <img id="spinButton1" src="/file_uploads/wheel/Spin-Button_{{$wheel_style['name']}}.png" alt="Spin Button" width="250px" height="250px" style="position: absolute; bottom: 8px; right: 0px;" />
        </div>
    </div>
</div>


