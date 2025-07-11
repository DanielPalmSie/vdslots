<div class="row">
    <div class="btn btn-md">
        <a href="{{ $app['url_generator']->generate('admin.user-create-source-of-income', ['user' => $user->id]) }}"
                    class="btn create-btn btn-default action-ajax-set-btn-3" data-dtitle="Create Source of Income document"
                    data-dbody="Are you sure you want to create a Source of Income document for <b>{{ $user->id }}</b>?"
                    id="create_document_source_of_income_button">
            Create document Source of Income
        </a>
    </div>

</div>

