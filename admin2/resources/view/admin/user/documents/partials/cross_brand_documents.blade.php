<div class='document' >
    <?php
    $headline = t("{$document['headline_tag']}.section.headline");

    if ($document['expired'] == 1) {
        $document['status'] = 'requested';
    }

    ?>
    <h3>{{$headline}}</h3>
    <h4 class="document-status {{$document['status']}}" >{{ucfirst($document['status'])}}</h4>

    @if (lic('shouldDisplayUploadFormForCrossBrandDocument', [$document['tag'], $document['status']], $user->id))
        @if ($document['tag'] === 'idcard-pic')
            @include('admin.user.documents.partials.uploadform_idcard')
        @else
            @include('admin.user.documents.partials.uploadform')
        @endif
    @endif
</div>
