<div class="card card-solid card-primary">
    <div class="card-header">
        <h3 class="card-title">Edit Game Images</h3>
    </div>

    <div class="card-body">
        <div class="col-md-6">
            @for($i=1;$i<=4;$i++)
                <div class="form-group">
                    <label>Sidebar #{{ $i }} </label>
                    <a class="href-confirm float-right"
                       data-message="Are you sure you want to delete the Sidebar #{{ $i }} image?"
                       href="{{ $app['url_generator']->generate('settings.games.images.delete', ['game_id' => $game->id, 'image' => "sr{$i}"]) }}">
                        <i class="fa fa-trash"></i> Delete
                    </a>
                    <form action="<?php echo e($app['url_generator']->generate('games.images.upload')); ?>"
                          class="dropzone" id="sr{{$i}}_dropzone">
                        <input type="hidden" name="type" value="sr{{ $i }}">
                        <input type="hidden" name="id" value="{{ $game->id }}">
                        <div class="dz-message">
                            Drop <b>Sidebar #{{ $i }}</b> file here or click to select file to upload.<br/>
                        </div>
                    </form>
                </div>
            @endfor
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Background</label>
                <a class="href-confirm float-right"
                   data-message="Are you sure you want to delete the Background image?"
                   href="{{ $app['url_generator']->generate('settings.games.images.delete', ['game_id' => $game->id, 'image' => "background"]) }}">
                    <i class="fa fa-trash"></i> Delete
                </a>
                <form action="<?php echo e($app['url_generator']->generate('games.images.upload')); ?>"
                      class="dropzone" id="bg_dropzone">
                    <input type="hidden" name="type" value="background">
                    <input type="hidden" name="id" value="{{ $game->id }}">
                    <div class="dz-message">
                        Drop <b>Background image</b> file here or click to select file to upload.<br/>
                    </div>
                </form>
            </div>
            <div class="form-group">
                <label>Screen shot</label>
                <a class="href-confirm float-right"
                   data-message="Are you sure you want to delete the Screen shot image?"
                   href="{{ $app['url_generator']->generate('settings.games.images.delete', ['game_id' => $game->id, 'image' => "screen_shot"]) }}">
                    <i class="fa fa-trash"></i> Delete
                </a>
                <form action="<?php echo e($app['url_generator']->generate('games.images.upload')); ?>"
                      class="dropzone" id="ss_dropzone">
                    <input type="hidden" name="type" value="screen_shot">
                    <input type="hidden" name="id" value="{{ $game->id }}">
                    <div class="dz-message">
                        Drop <b>Screen shot image</b> file here or click to select file to upload.<br/>
                    </div>
                </form>
            </div>
            <div class="form-group">
                <label>Thumbnail</label>
                <a class="href-confirm float-right"
                   data-message="Are you sure you want to delete the Thumbnail image?"
                   href="{{ $app['url_generator']->generate('settings.games.images.delete', ['game_id' => $game->id, 'image' => "thumbnail"]) }}">
                    <i class="fa fa-trash"></i> Delete
                </a>
                <form action="<?php echo e($app['url_generator']->generate('games.images.upload')); ?>"
                      class="dropzone" id="th_dropzone">
                    <input type="hidden" name="type" value="thumbnail">
                    <input type="hidden" name="id" value="{{ $game->id }}">
                    <div class="dz-message">
                        Drop <b>Thumbnail image</b> file here or click to select file to upload.<br/>
                    </div>
                </form>
            </div>
            <div class="form-group">
                <label>Mobile Game Banner Image</label>
                <a class="href-confirm float-right"
                   data-message="Are you sure you want to delete the Mobile Game Banner image?"
                   href="{{ $app['url_generator']->generate('settings.games.images.delete', ['game_id' => $game->id, 'image' => "mobileGameBannerImage"]) }}">
                    <i class="fa fa-trash"></i> Delete
                </a>
                <form action="<?php echo e($app['url_generator']->generate('games.images.upload')); ?>"
                      class="dropzone" id="th_dropzone">
                    <input type="hidden" name="type" value="mobileGameBannerImage">
                    <input type="hidden" name="id" value="{{ $game->id }}">
                    <div class="dz-message">
                        Drop <b>Mobile Game Banner image</b> file here or click to select file to upload.<br/>
                    </div>
                </form>
            </div>
            <div class="form-group">
                <label>Desktop Game Banner Image</label>
                <a class="href-confirm float-right"
                   data-message="Are you sure you want to delete the Desktop Game Banner image?"
                   href="{{ $app['url_generator']->generate('settings.games.images.delete', ['game_id' => $game->id, 'image' => "desktopGameBannerImage"]) }}">
                    <i class="fa fa-trash"></i> Delete
                </a>
                <form action="<?php echo e($app['url_generator']->generate('games.images.upload')); ?>"
                      class="dropzone" id="th_dropzone">
                    <input type="hidden" name="type" value="desktopGameBannerImage">
                    <input type="hidden" name="id" value="{{ $game->id }}">
                    <div class="dz-message">
                        Drop <b>Desktop Game Banner image</b> file here or click to select file to upload.<br/>
                    </div>
                </form>
            </div>
        </div>
    <!--
            <div class="col-lg-3 col-md-4">
                <div class="form-group">
                    <label>Ribbon Pic</label>
                    <form action="<?php echo e($app['url_generator']->generate('games.images.upload')); ?>" class="dropzone" id="bg_dropzone">
                        <input type="hidden" name="type" value="ribbonpic">
                        <input type="hidden" name="id" value="{{ $game->id }}">
                        <div class="dz-message">
                            Drop <b>Ribbon Pic image</b> file here or click to select file to upload.<br/>
                        </div>
                    </form>
                </div>
            </div>
             -->
    </div>
</div>

<!-- Modal -->
<div id="modal_confirm" class="modal fade" role="dialog">
    <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Confirmation</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">YES</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">NO</button>
            </div>
        </div>

    </div>
</div>

@include('admin.partials.href-confirm')

@section('footer-javascript')
    @parent
    <script language="JavaScript">

        Dropzone.autoDiscover = false;
        var gameImages = {

            images: {!! json_encode($images) !!},

            init: function () {

                var dz = $('.dropzone').dropzone({
                    parallelUploads: 1,
                    maxFilesize: 100,
                    maxFiles: 1,
                    //filesizeBase: 1000,
                    resizeWidth: 200,
                    maxfilesexceeded: function (file) {
                        this.removeAllFiles();
                        this.addFile(file);
                    },
                    acceptedFiles: 'image/jpeg',
                    init: function () {
                        //console.log(this.element.type.value);
                        if (gameImages.images[this.element.type.value] && gameImages.images[this.element.type.value][0]) {
                            // Create the mock file:
                            var mockFile = {
                                name: gameImages.images[this.element.type.value][2],
                                size: gameImages.images[this.element.type.value][1],
                                accepted: true
                            };
                            // Call the default addedfile event handler
                            this.emit("addedfile", mockFile);
                            // And optionally show the thumbnail of the file:
                            this.emit("thumbnail", mockFile, gameImages.images[this.element.type.value][0]);
                            this.emit("complete", mockFile);
                            this.files.push(mockFile);
                        }

                        this.on("thumbnail", function (file, dataUrl) {
                            //$('.dz-image').last().find('img').attr({width: '100%', height: '100%'});
                        });
                        this.on("success", function (file) {
                            //$('.dz-image').css({"border-radius": '1px'});
                        });
                    },
                    headers : {
                        "X-CSRF-TOKEN" : document.querySelector('meta[name="csrf_token"]').content
                    }
                });
            }
        };

        $(document).ready(function () {
            gameImages.init();
        });
    </script>
    <style>
        .dz-preview .dz-image {
            border-radius: 1px !important;
            width: 250px !important;
            height: 250px !important;
            min-width: 150px !important;
            min-height: 150px !important;
        }

        .dz-image img {
            width: 150px;
        }
    </style>
@endsection
