<div class='document' id='{{$document['tag'] . '_' . $document['id']}}'>

    <?php
    $headline = t("{$document['headline_tag']}.section.headline");
    /**
     * User can reject all documents with "user.reject.pic" permission, except for "idcard-pic" and "addresspic" after they are approved,
     * for that they need a new permission "user.reject.approved.pic"
     */
    $can_reject_document = false;
    if(p('user.reject.pic')) {
        if(in_array($document['tag'], ['idcard-pic','addresspic'])) {
            if($document['status'] != 'approved') {
                $can_reject_document = true;
            } else {
                if(p('user.reject.approved.pic')) {
                    $can_reject_document = true;
                }
            }
        } else {
            $can_reject_document = true;
        }
    }

    if ($document['expired'] == 1) {
        $document['status'] = 'requested';
    }

//    if ($document['tag'] == 'bankaccountpic') {
//        $phive_user   = phive('UserHandler')->getUser($user->id);
//        $bankname     = phive('CasinoCashier')->getBankFromAccNum($phive_user, $document['subtag']);
//        if(!empty($bankname['bank_name'])) {
//            $headline = $bankname['bank_name'];
//        }
//    }
    ?>

    {{--
//    @if($document['tag'] == 'sourceoffundspic')
//        @if($has_source_of_funds_data)
//            @include('admin.user.documents.partials.source_of_funds_modal')
//        @endif
//
//        @if($has_historical_source_of_funds_data)
//            @include('admin.user.documents.partials.historical_source_of_funds_modals')
//        @endif
//    @endif
        --}}

    <h3>{{$headline}}</h3>
    <h4 class="document-status {{$document['status']}}" id="documentstatus-{{$document['id']}}">{{ucfirst($document['status'])}}</h4>

    @if ($document['status'] != 'requested')

        <div class="btn-group btn-group-documents" role="group" aria-haspopup="true">

            {{-- Check permissions before showing the action buttons, and differentiate between credit card documents and other documents --}}
            @if (($document['tag'] == 'creditcardpic' && p('ccard.verify')) || ($document['tag'] != 'creditcardpic' && p('user.verify')))
                <a href="{{ $app['url_generator']->generate('admin.user-documents-updatestatus',
                        [
                            'user'           => $user->id,
                            'document_id'    => $document['id'],
                            'status'         => 'approved',
                            'document_type'  => $document['tag'],
                            'account_ext_id' => $document['account_ext_id'],
                            'account_id'     => $document['external_id'],
                            'supplier'       => $document['supplier'],
                            // TODO: Register account disabled https://videoslots.atlassian.net/browse/BAN-11430
                            //'add_bank_account_request_data' => $document['card_data']['add_bank_account_request_data'] ?? null,
                        ]) }}"
                        class="btn btn-default action-ajax-set-btn @if ($document['status'] == 'approved') disabled @endif"
                        data-dtitle="Verify Document"
                        data-dbody="Are you sure you want to verify this document for user <b>{{ $user->id }}</b>?"
                        data-ddocumentid="{{$document['id']}}"
                        id="verify-document-{{$document['tag']}}-{{$document['id']}}">Verify</a>
            @endif

            @if ($can_reject_document)
                <a href="{{ $app['url_generator']->generate('admin.user-documents-updatestatus',
                        [
                            'user'          => $user->id,
                            'document_id'   => $document['id'],
                            'status'        => 'rejected',
                            'document_type' => $document['tag']
                        ]) }}"
                        class="btn btn-default action-ajax-set-btn @if ($document['status'] == 'rejected') disabled @endif"
                        data-dtitle="Reject Document"
                        data-dbody="Are you sure you want to reject this document for user <b>{{ $user->id }}</b>?"
                        data-ddocumentid="{{$document['id']}}"
                        id="reject-document-{{$document['tag']}}-{{$document['id']}}">Reject</a>
            @endif

            @if (false && p('user.reject.pic'))   {{-- Temp disable this button --}}
                <a data-title="Reject Document"
                         data-documenttype="{{$document['tag']}}"
                         data-documentid="{{$document['id']}}"
                         data-elementid="{{$document['tag'] . '_' . $document['id']}}"
                         data-toggle="modal"
                         data-target="#reject_reason_modal"
                         data-label="Select reject reason"
                         data-url="{{ $app['url_generator']->generate('admin.user-documents-updatestatus', ['user' => $user->id]) }}"
                         class="btn btn-default reject_file_button @if ($document['status'] == 'rejected') disabled @endif"
                         id="reject2-document-{{$document['tag']}}-{{$file['id']}}"
                    >Reject 2</a>
            @endif

            {{-- Button for credit card activation is in a separate partial view --}}
            @include('admin.user.documents.partials.button_creditcard_deactivate')

            @if (($document['tag'] == 'creditcardpic' && p('ccard.delete')) || ($document['tag'] != 'creditcardpic' && p('user.delete.idpic')))
                <a href="{{ $app['url_generator']->generate('admin.user-documents-delete',
                        [
                            'user'          => $user->id,
                            'document_id'   => $document['id'],
                            'status'        => $document['status'],
                            'document_type' => $document['tag'],
                            'subtag'        => $document['subtag'],
                        ]) }}"
                        class="btn btn-default action-ajax-delete-btn" data-dtitle="Delete Document"
                        data-dbody="Are you sure you want to delete this document for user <b>{{ $user->id }}</b>?"
                        data-ddocumentid="{{$document['id']}}"
                        id="delete-document-{{$document['tag']}}-{{$document['id']}}">Delete</a>
            @endif

        </div>

    @endif

    {{-- Always show deactivate button for requested credit card documents --}}
    @if($document['status'] == 'requested' && $document['tag'] == 'creditcardpic')
        <div class="btn-group btn-group-documents" role="group" aria-haspopup="true">
            @include('admin.user.documents.partials.button_creditcard_deactivate')
        </div>
    @endif


    @if(!empty($document['subtag']) && $document['tag'] != 'sourceofincomepic')
        <h5> (#{{$document['subtag']}}) </h5>
    @endif

    <div class="uploaded_at">
        Document requested:<br>
        @if (!empty($document['created_at']))
            {{ $document['created_at'] }}
            {{-- TODO convert to local timezone: Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $document['created_at'], 'UTC')->setTimezone('Europe/Amsterdam')->format('Y-m-d H:i:s') --}}
        @else
            Unknown
        @endif
    </div>
    @if((bool)$document['expired'])
        <div class="uploaded_at">
            Requested due to missing physical files.
        </div>
    @endif
    @if ($document['status'] != 'requested' && $document['status'] != 'processing')
        <div class="last_status_change">
            Last status change: <br>
            @if (!empty($document['last_status_change_by']))
                <?php echo $document['last_status_change_by']; ?>
            @else
                Unknown
            @endif
            <br>

            @if (!empty($document['last_status_change_at']))
                {{ $document['last_status_change_at'] }}
                {{-- TODO convert to local timezone: Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $document['last_status_change_at'], 'UTC')->setTimezone('Europe/Amsterdam')->format('Y-m-d H:i:s') --}}
            @else
                Unknown
            @endif
        </div>
    @endif

    <div class="clear"></div>


    @if ($document['status'] != 'requested' && $document['tag'] == 'idcard-pic')
        <?php $action = $app['url_generator']->generate('admin.user-document-update-expiry-date', ['user' => $user->id]); ?>
        <div class="expiry-date">
            <form action="{{ $action }}" method="post">
                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                <div class="form-group">
                    <label for="before[register_date]">Expiry date</label>
                    <input type="hidden" name='document_id' value="{{$document['id']}}">
                    <input type="hidden" name='document_type' value="{{$document['tag']}}">
                    <input type="hidden" name='subtag' value="{{$document['subtag']}}">
                    <input data-provide="datepicker" data-date-format="yyyy-mm-dd" type="text" name="expiry_date"
                           class="form-control datepicker" placeholder="  Select a date" value="{{ $document['expiry_date'] }}">
                    <input type="submit" value="{{t("submit")}}" class="submit"/>
                </div>
            </form>
        </div>
    @endif

    {{-- First show non-deleted files with a processing status --}}
    @foreach ($document['files'] as $key => $file)
        @if(empty($file['deleted_at']) && ($file['status'] == 'processing' || $file['status'] == 'approved'))
            @include('admin.user.documents.partials.file', compact('can_reject_document'))
        @endif
    @endforeach

    {{-- Then show non-deleted files with other statuses--}}
    @foreach ($document['files'] as $key => $file)
        @if(empty($file['deleted_at']) && !($file['status'] == 'processing' || $file['status'] == 'approved')))
            @include('admin.user.documents.partials.file', compact('can_reject_document'))
        @endif
    @endforeach



        {{-- Show upload form only to users who has the correct permissions to upload those documents --}}
    <?php if(($document['tag'] == 'creditcardpic' && p('change.cardscan') && p('upload.cardscan'))
            || ($document['tag'] != 'creditcardpic' && p('upload.idpic'))) {
    ?>

        @if ($document['tag'] == 'idcard-pic')
            @include('admin.user.documents.partials.uploadform_idcard')
        @elseif ($document['tag'] == 'sourceoffundspic')
            @include('admin.user.documents.partials.sourceoffundsform')
        @elseif ($document['tag'] == 'sourceofincomepic')
            @include('admin.user.documents.partials.sourceofincomeform')
        @else
            @include('admin.user.documents.partials.uploadform')
        @endif

    <?php
    }
    ?>

    {{-- Each type of document will have some different info below the images --}}
    @if (p('view.account.documents'))
        @if ($document['status'] == 'processing' || $document['status'] == 'approved')
            @include('admin.user.documents.partials.extra_info')
        @endif
    @endif

    @if (p('card.date'))
        @if ($document['tag'] == 'creditcardpic')
            @include('admin.user.documents.partials.extra_info_creditcard')
        @endif
    @endif

        {{-- Next show deleted files for ID documents --}}
    @if($document['tag'] == 'idcard-pic')
        <hr>
        <p>Deleted files:</p>
        @foreach ($document['files'] as $key => $file)
            @if(!empty($file['deleted_at']))
                @include('admin.user.documents.partials.deletedfile')
            @endif
        @endforeach
    @endif

</div>


