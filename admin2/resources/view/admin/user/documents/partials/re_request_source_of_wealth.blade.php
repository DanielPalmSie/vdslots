<div class="row">

    <div class="btn">
        <a href="{{ $app['url_generator']->generate('admin.user-create-source-of-funds', [
                            'user'          => $user->id,
                            'document_id'   => $sourceoffunds_document_id,
                            'status'        => 'archived',
                            'document_type' => 'sourceoffundspic'
                        ]) }}"
                    class="btn create-btn btn-default action-set-btn" data-dtitle="Re-request Source of Wealth document"
                    data-dbody="Are you sure you want to re-request the Source of Wealth document for <b>{{ $user->id }}</b>?"
                    data-element_id="sourceoffundspic_{{ $sourceoffunds_document_id }}"
                    id="rerequest_document_source_of_funds_button">
            Re-request Source of Wealth
        </a>
    </div>

</div>

