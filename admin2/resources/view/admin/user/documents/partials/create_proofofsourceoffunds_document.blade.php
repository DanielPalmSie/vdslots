<div class="row">

    <div class="btn">
        <a href="{{ $app['url_generator']->generate('admin.user-create-proofofsourceoffunds-document', ['user' => $user->id]) }}"
                    class="btn create-btn btn-default action-ajax-set-btn-3" data-dtitle="Create Proof of Source of Funds document"
                    data-dbody="Are you sure you want to create a Proof of Source of Funds document for <b>{{ $user->id }}</b>?"
                    id="create_proofofsourceoffunds_document_button">
            Create Proof of Source of Funds
        </a>
    </div>

</div>

