@if ($document['tag'] == 'creditcardpic' && p('ccard.deactivate'))
    <?php
        if($document['status'] != 'deactivated') {
            $status = 'deactivated';
            $data_dtitle = 'Deactivate';
            $data_dbody = 'deactivate';
        } else {
            $status = 'active';
            $data_dtitle = 'Activate';
            $data_dbody = 'activate';
        }
    ?>
    <a href="{{ $app['url_generator']->generate('admin.user-documents-updatestatus',
            [
                'user'          => $user->id,
                'actor_id'      => cuAttr('id'),
                'document_id'   => $document['id'],
                'status'        => $status,
                'document_type' => $document['tag']
            ]) }}"
            class="btn btn-default action-ajax-set-btn"
            data-dtitle="{{ $data_dtitle }} Document"
            data-dbody="Are you sure you want to <b>{{ $data_dbody }}</b> this document for user <b>{{ $user->id }}</b>?"
            data-ddocumentid="{{$document['id']}}"
            id="deactivate-document-{{$document['tag']}}-{{$document['id']}}"
            >
        @if ($document['status'] != 'deactivated')
            Deactivate
        @else
            Activate
        @endif
    </a>
@endif

