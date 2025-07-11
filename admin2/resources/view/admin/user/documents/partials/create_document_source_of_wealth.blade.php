<div class="row">
    <div class="btn btn-md text-md">
        <a href="{{ $app['url_generator']->generate('admin.user-create-source-of-funds', ['user' => $user->id]) }}"
                    class="btn create-btn btn-default action-ajax-set-btn-3" data-dtitle="Create Source of Wealth document"
                    data-dbody="Are you sure you want to create a Source of Wealth document for <b>{{ $user->id }}</b>?"
                    id="create_document_source_of_funds_button">
            Create document Source of Wealth
        </a>
    </div>
</div>

