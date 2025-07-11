<div>
    <p>This landing page has the following image boxes,<br>
        please click an alias to upload the images:</p>

    @foreach($image_boxes as $image_box)
    <div>
        <strong>{{$image_box->box_class}}</strong><br>
        Aliasses:
        @foreach($image_box->aliasses as $value)
            <a href="" onclick="event.preventDefault(); selectAlias('{{$value}}')">
                {{$value}}
            </a>
            <br>
        @endforeach
        
    </div>
    @endforeach
</div>

<br>
<br>

