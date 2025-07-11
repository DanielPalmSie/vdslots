<div class="row">

    <div class="btn">
        <a href="{{ $app['url_generator']->generate('admin.user-create-proofofwealth-document', ['user' => $user->id]) }}"
                    class="btn create-btn btn-default action-ajax-set-btn-3" data-dtitle="Create Proof of Wealth document"
                    data-dbody="Are you sure you want to create a Proof of Wealth document for <b>{{ $user->username }}</b>?"
                    id="create_proofofwealth_document_button">
            Create Proof of source of Wealth
        </a>
    </div>

</div>

