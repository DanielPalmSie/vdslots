@extends('admin.layout')

@section('header-css')
    @parent
    {{ loadCssFile("/phive/admin/customization/styles/css/documents.css") }}
@endsection

@section('content')
    @include('admin.user.partials.topmenu')

    <div class='card document-list'>
        <h1>List documents</h1>

        <p>Here is a list of documents that need to be processed:</p>

        <?php $i = 1; ?>
        <div class="row document-row">
            @if (!empty($documents_list))
                @foreach($documents_list as $document_type => $users)
                    <div class="document-list-item">
                        <h3>{{$document_type}}</h3>

                        @foreach($users as $user_data)
                            @if (($user_data['timestamp']) != 'unknown')
                            ({{$user_data['timestamp']}}) &nbsp;
                            <a href="{{ $app['url_generator']->generate('admin.user-documents', ['user' => $user_data['username']]) }}">
                                {{htmlspecialchars($user_data['username'])}}
                            </a>
                            <br>
                            @endif
                        @endforeach

                    </div>
                    @if ($i % 5 == 0)
                        </div>
                        <div class="row document-row">
                    @endif
                <?php $i++; ?>
                @endforeach
            @else
               <p><b>Currently there are no documents to process.</b></p>
            @endif
        </div>

    </div>
@endsection


