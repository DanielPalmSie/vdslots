@extends('admin.layout')

@section('header-css')
    @parent
    <link rel="stylesheet" type="text/css" href="/phive/admin/customization/styles/css/promotions.css">
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.cms.partials.topmenu')

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">File Uploads</h3>
            </div>
            <div class="card-body">
                <p>On this page you can upload files to the folder 'file_uploads'. </p>

                <p>Please select a subfolder</p>

                <form action="">
                    <div class="form-group col-12 col-md-12 col-lg-8 col-xlg-6 col-fhd-4">
                        <select id="select-folder" name="folder" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Select folder" data-allow-clear="true">
                            <option></option>
                            @foreach($subfolders as $subfolder)
                                <option value="{{$subfolder}}">{{$subfolder}}</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                @include('admin.cms.partials.uploadfiles-dropzone')

                <div id="filelist" style="display: none">
                    @include('admin.cms.partials.file_list')
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">
        var dropzone = null;
        let filesTable = null;

        $("#select-folder").change(function() {
            if($("#select-folder").val() == '') {
                $("#dropzone_container").hide();
                $("#filelist").hide();
            } else {
                $("#dropzone_container").show();
                dropzone.removeAllFiles();

                const subfolder = $("#select-folder").val();
                $("#subfolder").val(subfolder);
                $("#filelist").show();
                showFileList(subfolder);
            }
        });

        function drawTableEventHandler() {
            $('.action-set-btn').off('click', bindClickEventToDeleteButtons);
            $('.action-set-btn').on('click', bindClickEventToDeleteButtons);
        }

        function bindClickEventToDeleteButtons(e) {
            e.preventDefault();
            var dialogTitle    = $(this).data("dtitle");
            var dialogMessage  = $(this).data("dbody");
            var dialogUrl      = $(this).attr('href');
            var filename       = $(this).data('dfilename');
            if($(this).data("disabled") != 1){
                // show confirm button, and pass function removeRow() with param filename to remove this row after the file is deleted
                showConfirmBtnAjax(dialogTitle, dialogMessage, dialogUrl, removeRow, filename);
            }
        }

        $(document).ready(function() {
            enableDropZone();
        });

        // Renders the preview partial view with ajax
        function showFileList(subfolder) {
            $("#filelist").show();

            const params = `?folder=file_uploads&subfolder=${subfolder}`;

            if (filesTable) {
                filesTable.destroy();
            }

            filesTable = $('#file-list-table')
                .on('draw.dt', drawTableEventHandler)
                .DataTable({
                    processing: true,
                    serverSide: true,
                    ordering: false,
                    searchDelay: 2000,
                    language: {
                        infoFiltered: ''
                    },
                    ajax: {
                        url: '{{$app['url_generator']->generate('show-file-list')}}' + params,
                        dataSrc: 'files',
                    },
                    columns: [
                        {
                            data: 'name',
                            render: function (data, type, row) {
                                return `<a href="${row.url}" target="_blank">${row.name}</a>`;
                            }
                        },
                        { data: 'size' },
                        {
                            render: function (data, type, row) {
                                const prefix = '{{ getMediaServiceUrl() . $image_tag_base_url }}';
                                return `<a onclick="showHtml('${prefix}/${row.name}')">HTML</a></td>`;
                            }
                        },
                        {
                            render: function (data, type, row) {
                                const filename = encodeURIComponent(row.name);
                                const fileNameParam = `&subfolder=${subfolder}&filename=${filename}`;
                                const deleteUrl = `{{$app['url_generator']->generate('delete-file-by-filename',
                                    [
                                        'folder'    => 'file_uploads',
                                    ])
                                }}` + fileNameParam;

                                return `
                                    <a class="fa fa-trash action-set-btn"
                                       href="${deleteUrl}"
                                       data-dtitle="Delete file"
                                       data-dbody="Are you sure you want to delete file <b>${row.name}</b>?"
                                       data-dfilename="${row.name}"
                                    >
                                    </a>
                                `;
                            }
                        },
                    ]
                });
        }

        // This is an old phive function
        function showHtml(filename) {
            window.prompt("Copy to clipboard: Ctrl+C, Enter", '<img src="'+filename+'" />');
        }

        Dropzone.autoDiscover = false;

        function enableDropZone() {
            dropzone = new Dropzone('.dropzone', {
                init: function() {
                    this.on("queuecomplete", function(file) {
                        var subfolder = $("#select-folder").val();
                        showFileList(subfolder);
                    });
                },
                parallelUploads: 1,
                maxFilesize: 3,
                filesizeBase: 1000,
                headers : {
                    "X-CSRF-TOKEN" : document.querySelector('meta[name="csrf_token"]').content
                }
            });
        }

        function removeRow(filename) {
            $("#filelist tr:contains('"+filename+"')").hide(1000);
        }

        $("#select-folder").select2().val("");
    </script>
@endsection


