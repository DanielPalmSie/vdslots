<div class="row">

    <div class="btn">
        <a href="{{ $app['url_generator']->generate('admin.user-create-internal-document', ['user' => $user->id]) }}"
                    class="btn create-btn btn-default action-ajax-set-btn-3" data-dtitle="Create internal document"
                    data-dbody="Are you sure you want to create an internal document for <b>{{ $user->id }}</b>?"
                    id="create_internal_document_button">
            Create internal document
        </a>
    </div>

</div>

