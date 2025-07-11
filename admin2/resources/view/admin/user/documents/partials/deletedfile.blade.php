<div class='filecontainer @if ($file['status'] == 'expired') expired @endif' id="filecontainer-{{$file['id']}}">

    

    {{-- Check if we have a .pdf file or an image --}}

    <a href="{{$file['links'][0]['url']}}" target="_blank">
        @if(strpos($file['links'][0]['url'], '.pdf') !== false)
            <div class="pdf-file">
                View PDF
            </div>
        @else
            <img src="{{$file['links'][0]['url']}}" class="file-image">
        @endif
    </a>

    <div class='filestatus {{$file['status']}}' id="filestatus-{{$file['id']}}">
        <!--File {{$file['status']}}-->
    </div>

    @if ($file['status'] != 'requested')
        <div class="uploaded_at">
            File uploaded: <br>
            @if (!empty($file['created_at']))
                {{ $file['created_at'] }}
            @else
                Unknown
            @endif
        </div>
        <div class="uploaded_at">
            File deleted: <br>
            @if (!empty($file['deleted_at']))
                {{ $file['deleted_at'] }}
            @else
                Unknown
            @endif
        </div>
        @if ($file['status'] != 'processing')
            <div class="last_status_change">
                Last status change: <br>
                @if (!empty($file['last_status_change_by']))
                    <?php echo $file['last_status_change_by']; ?>
                @else
                    Unknown
                @endif
                <br>
                @if (!empty($file['last_status_change_at']))
                    {{ $file['last_status_change_at'] }}
                @else
                    Unknown
                @endif
            </div>
        @endif

        @if($file['exists_on_disk'] === false)
            <div style="margin-left: 5px" class="uploaded_at">
                Missing physical file
            </div>
        @endif

        <div class="clear"></div>
    @endif

</div>


