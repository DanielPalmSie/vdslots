<div class='filecontainer' id="filecontainer-{{$file['id']}}">

    <div class="btn-group-vertical file-status-buttons" role="group" aria-haspopup="true">

        {{-- Check permissions before showing the action buttons --}}
        @if (($document['tag'] == 'creditcardpic' && p('ccard.verify')) || ($document['tag'] != 'creditcardpic' && p('user.verify')))
            <a href="{{ $app['url_generator']->generate('admin.user-documents-updatefilestatus',
                    [
                        'user'          => $user->id,
                        'file_id'       => $file['id'],
                        'status'        => 'approved',
                        'document_type' => $document['tag']
                    ]) }}"
                    class="btn btn-default action-ajax-set-btn @if ($file['status'] == 'approved') disabled @endif"
                    data-dtitle="Verify File"
                    data-dbody="Are you sure you want to verify this file for user <b>{{ $user->id }}</b>?"
                    id="verify-file-{{$document['tag']}}-{{$file['id']}}">Verify</a>
        @endif

        @if ($can_reject_document)
            <a href="{{ $app['url_generator']->generate('admin.user-documents-updatefilestatus',
                    [
                        'user'          => $user->id,
                        'file_id'       => $file['id'],
                        'status'        => 'rejected',
                        'document_type' => $document['tag']
                    ]) }}"
                    class="btn btn-default action-ajax-set-btn @if ($file['status'] == 'rejected') disabled @endif"
                    data-dtitle="Reject File"
                    data-dbody="Are you sure you want to reject this file for user <b>{{ $user->id }}</b>?"
                    id="reject-file-{{$document['tag']}}-{{$file['id']}}">Reject</a>
        @endif

        @if (false && p('user.reject.pic'))  {{-- Temp disable this button --}}
            <a data-title="Reject file 2"
                     data-documenttype="{{$document['tag']}}"
                     data-documentid="{{$document['id']}}"
                     data-fileid="{{$file['id']}}"
                     data-elementid="{{$document['tag'] . '_' . $document['id']}}"
                     data-toggle="modal"
                     data-target="#reject_reason_modal"
                     data-label="Select reject reason"
                     data-url="{{ $app['url_generator']->generate('admin.user-documents-updatefilestatus', ['user' => $user->id]) }}"
                     class="btn btn-default reject_file_button @if ($file['status'] == 'rejected') disabled @endif"
                     id="reject2-file-{{$document['tag']}}-{{$file['id']}}"
                >Reject 2</a>
        @endif


        @if (($document['tag'] == 'creditcardpic' && p('ccard.delpic')) || ($document['tag'] != 'creditcardpic' && p('user.delete.idpic')))
            <a href="{{ $app['url_generator']->generate('admin.user-documents-deletefile',
                    [
                        'user'          => $user->id,
                        'file_id'       => $file['id'],
                        'document_type' => $document['tag'],
                        'status'        => $file['status'],
                        'uploaded_name' => $file['uploaded_name'],
                        'subtag'        => $document['subtag'],
                    ]) }}"
                    class="btn btn-default action-ajax-deletefile-btn"
                    data-dtitle="Delete File"
                    data-dbody="Are you sure you want to delete this file for user <b>{{ $user->id }}</b>?"
                    id="delete-file-{{$document['tag']}}-{{$file['id']}}">Delete</a>
        @endif

        @if (p('ccard.verify'))
            <a data-title="Replace file"
                     data-documenttype="{{$document['tag']}}"
                     data-documentid="{{$document['id']}}"
                     data-fileid="{{$file['id']}}"
                     data-subtag="{{$document['subtag']}}"
                     data-toggle="modal"
                     data-target="#replace_file_modal"
                     data-label="Upload image"
                     class="btn btn-default replace_file_button"
                     id="replace-file-{{$document['tag']}}-{{$file['id']}}"
                >Replace</a>
        @endif

    </div>

    {{-- Check if we have a .pdf file or an image --}}

    <a href="{{$file['links'][0]['url']}}" target="_blank">
        @if(strpos($file['links'][0]['url'], '.pdf') !== false)
            <div class="pdf-file">
                View PDF
            </div>
        @else
            @if($file['links'][0]['thumb_url'])
                <img src="{{$file['links'][0]['thumb_url']}}" class="file-image" loading="lazy">
            @else
                <img src="{{$file['links'][0]['url']}}" class="file-image" loading="lazy">
            @endif
        @endif
    </a>

    <div class='filestatus {{$file['status']}}' id="filestatus-{{$file['id']}}">File {{$file['status']}}{{$file['exists_on_disk'] === false ? ' - Missing physical file' : ''}}</div>

    @if ($file['status'] != 'requested')
        <div class="uploaded_at">
            File uploaded:
            @if ($document['tag'] == 'sourceofincomepic')
                {{ t('select.income.types.' . $file['tag']) }}
                <br>
            @endif
            @if (!empty($file['created_at']))
                {{ $file['created_at'] }}
                {{-- TODO convert to local timezone: Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $file['created_at'], 'UTC')->setTimezone('Europe/Amsterdam')->format('Y-m-d H:i:s') --}}
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
                    {{-- TODO convert to local timezone: Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $file['last_status_change_at'], 'UTC')->setTimezone('Europe/Amsterdam')->format('Y-m-d H:i:s') --}}
                @else
                    Unknown
                @endif
            </div>
        @endif
            
        <div class="clear"></div>
    @endif
        
</div>
