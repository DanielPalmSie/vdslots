@include('admin.partials.flash')

<table id="file-list-table" class="table table-bordered">
    <thead>
        <tr>
            <th style="width: 200px">Filename</th>
            <th style="width: 100px">Size</th>
            <th style="width: 100px">Html</th>
            <th style="width: 100px">Action</th>
        </tr>
    </thead>
    <tbody>
    @foreach($files as $file)
        <tr>
            <td><a href="{{ $file['url'] }}" target="_blank">{{ $file['name'] }}</a></td>
            <td>{{ $file['size'] }}</td>
            <td>
                <a onclick="showHtml('{{ getMediaServiceUrl() . $image_tag_base_url . $file['name'] }}')">HTML</a>
            </td>
            <td role="group" aria-haspopup="true">
                <a class="fa fa-trash action-set-btn"
                   href="{{$app['url_generator']->generate('delete-file-by-filename',
                               [
                                    'filename'  => $file['name'],
                                    'folder'    => 'file_uploads',
                                    'subfolder' => $subfolder,
                                ])}}"
                   data-dtitle="Delete file"
                   data-dbody="Are you sure you want to delete file <b>{{ $file['name']}}</b>?"
                   data-dfilename="{{$file['name']}}"
                   >
                </a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

