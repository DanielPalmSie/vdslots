<?php

namespace App\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Repositories\ImageAliasRepository;
use App\Repositories\CmsRepository;
use App\Models\ImageData;
use App\Models\ImageAlias;
use App\Extensions\Database\FManager as DB;

class CmsController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\CmsController::dashboard')
            ->bind('cms-dashboard')
            ->before(function () use ($app) {
                if (!p('cms.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/bannertags/', 'App\Controllers\CmsController::connectBanners')
            ->bind('bannertags')
            ->before(function () use ($app) {
                if (!p('cms.banners.tag')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/banneruploads/', 'App\Controllers\CmsController::uploadBanners')
            ->bind('banneruploads')
            ->before(function () use ($app) {
                if (!p('cms.banners.upload')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->get('/showselectbanneralias/', 'App\Controllers\CmsController::showSelectBannerAlias')
            ->bind('showselectbanneralias')
            ->before(function () use ($app) {
                if (!p('cms.banners.upload')) {
                    $app->abort(403);
                }
            });

        $factory->get('/showuploadedbanners/', 'App\Controllers\CmsController::showUploadedBanners')
            ->bind('showuploadedbanners')
            ->before(function () use ($app) {
                if (!p('cms.banners.upload')) {
                    $app->abort(403);
                }
            });

        $factory->get('/showformuploadbanners/', 'App\Controllers\CmsController::showFormUploadBanners')
            ->bind('showformuploadbanners')
            ->before(function () use ($app) {
                if (!p('cms.banners.upload')) {
                    $app->abort(403);
                }
            });

        $factory->get('/showuploadlocalizedstrings/', 'App\Controllers\CmsController::showUploadLocalizedStrings')
            ->bind('showuploadlocalizedstrings')
            ->before(function () use ($app) {
                if (!p('cms.banners.upload')) {
                    $app->abort(403);
                }
            });

        $factory->get('/showeditemail/', 'App\Controllers\CmsController::showEditEmail')
            ->bind('showeditemail')
            ->before(function () use ($app) {
                if (!p('cms.banners.upload')) {
                    $app->abort(403);
                }
            });

        $factory->get('/showeditemailform/', 'App\Controllers\CmsController::showEditEmailForm')
            ->bind('showeditemailform')
            ->before(function () use ($app) {
                if (!p('cms.banners.upload')) {
                    $app->abort(403);
                }
            });

        $factory->post('/save-email/', 'App\Controllers\CmsController::saveEmail')
            ->bind('save-email')
            ->before(function () use ($app) {
                if (!p('cms.banners.upload')) {
                    $app->abort(403);
                }
            });

        $factory->get('/get-type-of-page/', 'App\Controllers\CmsController::getTypeOfPage')
            ->bind('get-type-of-page')
            ->before(function () use ($app) {
                if (!p('cms.banners.upload')) {
                    $app->abort(403);
                }
            });

        $factory->get('/deletefile/', 'App\Controllers\CmsController::deleteFile')
            ->bind('banner-deletefile')
            ->before(function () use ($app) {
                if (!(p('cms.banners.upload') || p('cms.upload.images'))) {
                    $app->abort(401);
                }
            });

        $factory->get('/delete-file-by-filename/', 'App\Controllers\CmsController::deleteFileByFilename')
            ->bind('delete-file-by-filename')
            ->before(function () use ($app) {
                if (!(p('cms.upload.files'))) {
                    $app->abort(401);
                }
            });

        $factory->match('/delete-selected-files/', 'App\Controllers\CmsController::deleteSelectedFiles')
            ->bind('delete-selected-files')
            ->before(function () use ($app) {
                if (!(p('cms.upload.files') || p('cms.upload.images') || p('cms.banners.upload'))) {
                    $app->abort(401);
                }
            })
            ->method('GET|POST');

        $factory->match('/uploadimages/', 'App\Controllers\CmsController::uploadImages')
            ->bind('uploadimages')
            ->before(function () use ($app) {
                if (!p('cms.upload.images')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/uploadfiles/', 'App\Controllers\CmsController::uploadFiles')
            ->bind('uploadfiles')
            ->before(function () use ($app) {
                if (!p('cms.upload.files')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/uploadfiles-dropzone/', 'App\Controllers\CmsController::uploadFilesDropzone')
            ->bind('uploadfiles-dropzone')
            ->before(function () use ($app) {
                if (!p('cms.upload.files')) {
                    $app->abort(403);
                }
            });

        $factory->post('/uploadimages-dropzone/', 'App\Controllers\CmsController::uploadImagesDropzone')
            ->bind('uploadimages-dropzone')
            ->before(function () use ($app) {
                if (!(p('cms.upload.files') || p('cms.upload.images') || p('cms.banners.upload'))) {
                    $app->abort(403);
                }
            });

        $factory->get('/get-image_aliasses/', 'App\Controllers\CmsController::getImageAliasses')
            ->bind('get-image_aliasses')
            ->before(function () use ($app) {
                if (!p('cms.upload.images')) {
                    $app->abort(403);
                }
            });

        $factory->get('/get-landing-pages/', 'App\Controllers\CmsController::getLandingPages')
            ->bind('get-landing-pages')
            ->before(function () use ($app) {
                if (!p('cms.upload.images')) {
                    $app->abort(403);
                }
            });

        $factory->get('/show-images/', 'App\Controllers\CmsController::showImages')
            ->bind('show-images')
            ->before(function () use ($app) {
                if (!p('cms.upload.images')) {
                    $app->abort(403);
                }
            });

        $factory->get('/show-file-list/', 'App\Controllers\CmsController::showFileList')
            ->bind('show-file-list')
            ->before(function () use ($app) {
                if (!p('cms.upload.files')) {
                    $app->abort(403);
                }
            });

        $factory->match('/pagebackgrounds/', 'App\Controllers\CmsController::pageBackgrounds')
            ->bind('pagebackgrounds')
            ->before(function () use ($app) {
                if (!p('cms.change.pagebackgrounds')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/change-page-background/', 'App\Controllers\CmsController::changePageBackground')
            ->bind('change-page-background')
            ->before(function () use ($app) {
                if (!p('cms.change.pagebackgrounds')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/show-image-boxes/', 'App\Controllers\CmsController::showImageBoxes')
            ->bind('show-image-boxes')  // getImageBoxes
            ->before(function () use ($app) {
                if (!p('cms.upload.images')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        return $factory;
    }

    /**
     * Renders the CMS dashboard
     *
     * @param  Application $app
     * @return mixed
     */
    public function dashboard(Application $app)
    {
        return $app['blade']->view()->make('admin.cms.index', compact('app'))->render();
    }

    public function connectBanners(Application $app, Request $request)
    {
        $image_repo = new ImageAliasRepository($app);

        if ($request->isMethod('post') && !empty($request->get('alias'))) {
            $result = true;
            if (strpos($request->get('alias'), '.html') === false) {
                $result = $image_repo->connectBonusCodesToBanners($request->get('duallistbox_bonuscodes'), $request->get('alias'));

                if ($result) {
                    $app['session']->getFlashBag()->add('success', 'The bonus codes were succesfully connected to the images');
                } else {
                    $app['session']->getFlashBag()->add('error', 'Something went wrong trying to connect the bonus codes to the images');
                }
            }

            // check if this page has a text
            if ($result && !empty($request->get('has_text'))) {
                $result2 = $image_repo->connectBonusCodesToLocalizedStrings($request->get('pagealias'), $request->get('alias'), $request->get('duallistbox_bonuscodes'));

                if ($result2) {
                    $app['session']->getFlashBag()->add('success', 'The bonus codes were succesfully connected to the texts');
                } else {
                    $app['session']->getFlashBag()->add('error', 'Something went wrong trying to connect the bonus codes to the texts');
                }
            }

            return new RedirectResponse($request->headers->get('referer'));

        }

        if ($request->isMethod('post') && !empty($request->get('email_alias'))) {
            $result = $image_repo->connectBonusCodesToEmails($request->get('email_alias'), $request->get('duallistbox_bonuscodes'), $request->get('pagealias'));

            if ($result) {
                $app['session']->getFlashBag()->add('success', 'The bonus codes were succesfully connected to the email');
            } else {
                $app['session']->getFlashBag()->add('error', 'Something went wrong trying to connect the bonus codes to the email');
            }
        }

        $pages = $image_repo->getBannerPages();

        $aliases = '';
        $bonus_codes = '';
        $is_email = false;
        $only_text = false;
        if ($request->isMethod('get') && !empty($request->get('pagealias'))) {
            if ($image_repo->pageHasTextOnly($pages, $request->get('pagealias'))) {
                $only_text = true;
                $tco_aliasses = $image_repo->getAllTcoAliases($request->get('pagealias'), false);
            }

            if ($image_repo->pageIsEmail($pages, $request->get('pagealias'))) {
                $is_email = true;
                $email_aliases = $image_repo->getAllEmailAliases($request->get('pagealias'), false);
                $languages = $image_repo->getAllLanguages();
            }

            if (!empty($request->get('email_alias'))) {
                $bonus_codes = $image_repo->getBonusCodes($request->get('email_alias'), 'emails');
            }

            $aliases = $image_repo->getBannerAliases($request->get('pagealias'));
        }

        $all_images = '';
        if ($request->isMethod('get') && !empty($request->get('alias'))) {
            $image_id = $image_repo->getImageID($request->get('alias'));

            if (!$only_text) {
                $bonus_codes = $image_repo->getBonusCodes($request->get('alias'), 'images');
            } else {
                $bonus_codes = $image_repo->getBonusCodes($request->get('alias'), 'localized_strings');
            }

            $all_images = $image_repo->getAllImagesForID($image_id, $request->get('alias'));
        }

        return $app['blade']->view()->make('admin.cms.connect-banners', compact('app', 'pages', 'aliases', 'bonus_codes', 'all_images', 'email_aliases', 'languages', 'is_email', 'only_text', 'tco_aliasses'))->render();
    }


    public function uploadBanners(Application $app, Request $request)
    {
        $image_repo       = new ImageAliasRepository($app);
        $pages            = $image_repo->getBannerPages();

        return $app['blade']->view()
            ->make('admin.cms.upload-banners', compact('app', 'pages'))
            ->render();
    }


    public function showSelectBannerAlias(Application $app, Request $request)
    {
        $image_repo = new ImageAliasRepository($app);
        $page_alias = $request->get('page_alias');

        $aliases    = $image_repo->getAllBannerAliases($page_alias);

        $pages      = $image_repo->getBannerPages();
        if ($image_repo->pageHasTextOnly($pages, $page_alias)) {
            $aliases   = $image_repo->getAllTcoAliases($page_alias);
        }

        if ($image_repo->pageIsEmail($pages, $page_alias)) {
            $aliases   = $image_repo->getAllEmailAliases($page_alias);
        }

        return $app['blade']->view()
            ->make('admin.cms.partials.selectbanneralias', compact('app', 'aliases'))
            ->render();
    }

    public function showUploadedBanners(Application $app, Request $request)
    {
        $image_repo = new ImageAliasRepository($app);

        $image_id   = $image_repo->getImageID($request->get('alias'));
        $pages      = $image_repo->getBannerPages();
        $dimensions = $image_repo->getImageDimensionsForPage($pages, $request->get('page_alias'));

        $all_images = [];
        if (!empty($image_id)) {
            $all_images = $image_repo->getAllImagesForID($image_id, $request->get('alias'), $dimensions);
        }

        return $app['blade']->view()
            ->make('admin.cms.partials.uploaded_banners', compact('app', 'all_images'))
            ->render();
    }


    public function showFormUploadBanners(Application $app, Request $request)
    {
        $image_repo = new ImageAliasRepository($app);

        $dropzone_upload_url = $app['url_generator']->generate('uploadimages-dropzone');

        // get the dimensions the images should have on the selected page
        $pages      = $image_repo->getBannerPages();
        $dimensions = $image_repo->getImageDimensionsForPage($pages, $request->get('page_alias'));
        if(empty($request->get('new_alias'))) {
            $image_id   = $image_repo->getImageID($request->get('alias'));
        }

        return $app['blade']->view()
            ->make('admin.cms.partials.banner-upload-form', compact('app', 'dimensions', 'image_id', 'dropzone_upload_url'))
            ->render();
    }


    public function showUploadLocalizedStrings(Application $app, Request $request)
    {
        $image_repo = new ImageAliasRepository($app);

        $type_of_page  = $image_repo->getTypeOfPage($request->get('page_alias'));
        $alias         = $request->get('alias');

        return $app['blade']->view()
            ->make('admin.cms.partials.edit_text', compact('app', 'type_of_page', 'alias'))
            ->render();
    }


    public function showEditEmail(Application $app, Request $request)
    {
        $image_repo = new ImageAliasRepository($app);

        $languages = $image_repo->getAllLanguages();

        return $app['blade']->view()
            ->make('admin.cms.partials.editmail', compact('app', 'languages'))
            ->render();
    }


    public function showEditEmailForm(Application $app, Request $request)
    {
        $mail_trigger  = $request->get('alias');
        $language      = $request->get('language');

        // check if mail trigger exists
        $mail = [];
        if (!empty(phive('MailHandler2')->hasMailTrigger($mail_trigger))) {
            // check if this mail_trigger has strings for the selected language, if not then do not get mail
            if (phive('MailHandler2')->hasMailForLanguage($mail_trigger, $language)) {
                // get the email from the database
                $mail = phive('MailHandler2')->getMail($mail_trigger, $language);
            }
        }

        return $app['blade']->view()
            ->make('admin.cms.partials.edit-email-form', compact('app', 'mail', 'mail_trigger'))
            ->render();
    }


    public function saveEmail(Application $app, Request $request)
    {
        // TODO form validation

        $mail_trigger = $request->get('mail_trigger');

        if (!phive("MailHandler2")->hasMailForLanguage($mail_trigger, $request->get('language'))) {
            phive("MailHandler2")->addMail($mail_trigger, $request->get('subject'), $request->get('content'), $request->get('replacers'), $request->get('language'));

        } else {
            phive("MailHandler2")->editMail($mail_trigger, $request->get('subject'), $request->get('content'), $request->get('replacers'), $request->get('language'));
        }

        $app['session']->getFlashBag()->add('success', 'The email was successfully saved');

        return json_encode(['success' => true]);
    }


    public function getTypeOfPage(Application $app, Request $request)
    {
        $image_repo = new ImageAliasRepository($app);

        $type_of_page = $image_repo->getTypeOfPage($request->get('page_alias'));

        return $type_of_page;
    }

    /**
     * Deletes a single image
     *
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function deleteFile(Application $app, Request $request)
    {
        $file_id = $request->get('file_id');

        $image_repo = new ImageAliasRepository($app);

        if ($image_repo->deleteImage($file_id)) {
            return json_encode(['success' => true, 'message' => "File deleted."]);
        } else {
            return json_encode(['success' => false, 'message' => "Unable to delete the file"]);
        }
    }

    /**
     * Delete a public file
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return mixed
     */
    public function deleteFileByFilename(Application $app, Request $request)
    {
        $filename   = $request->get('filename');
        $folder     = $request->get('folder');
        $subfolder  = $request->get('subfolder');

        // If the folder is the same as the subfolder it means were are requesting files from the root folder
        if($folder == $subfolder) {
            $subfolder = '';
        }

        if(phive('DBUserHandler')->getSetting('send_public_files_to_dmapi')) {
            $result = phive('Dmapi')->deletePublicFile($folder, $filename, $subfolder);

            if(!empty($result['errors'])) {
                return json_encode(['success' => false, 'message' => "Unable to delete the file"]);
            }

            // Delete grey version for files from folder /events. It should have the same filename as the original file.
            if($subfolder == 'events') {
                phive('Dmapi')->deletePublicFile($folder, $filename, $subfolder . '/events');
            }

            return json_encode(['success' => true, 'message' => "File deleted."]);
        } else {
            $path = phive('Filer')->getSetting('UPLOAD_PATH');
            if (!empty($subfolder)) {
                $path .= '/' . $subfolder;
            }
            $path .= '/' . $filename;
            if(!unlink($path)) {
                return json_encode(['success' => false, 'message' => "Unable to delete the file"]);
            }
            return json_encode(['success' => true, 'message' => "File deleted."]);
        }
    }

    /**
     * Delete all public images that were selected by the user
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return mixed
     */
    public function deleteSelectedFiles(Application $app, Request $request)
    {
        $data = $request->request->all();

        $all_files_deleted = true;
        $image_data_ids = [];
        foreach ($data['selected'] as $image_data_id) {
            $image_data_ids[] = $image_data_id;

            // Deleting one by one so we can know if any images were not deleted
            if(!DB::table('image_data')->delete($image_data_id)) {
                $app['flash']->add('danger', "Unable to delete image with image_data_id {$image_data_id}");
                $all_files_deleted = false;
            }
        }

        if($all_files_deleted) {
            $app['flash']->add('success', "All selected files were deleted.");
        }

        return json_encode(['success' => true, 'message' => "All files deleted."]);
    }

    /**
     * Uploads files to the file_uploads folder
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return mixed
     */
    public function uploadFiles(Application $app, Request $request)
    {
        $folder = phive('Filer')->getSetting('UPLOAD_PATH_URI');

        $repo = new CmsRepository();
        $subfolders = $repo->getSubfolders('file_uploads');

        $dropzone_upload_url = $app['url_generator']->generate('uploadfiles-dropzone');

        return $app['blade']->view()
            ->make('admin.cms.upload-files', compact('app', 'folder', 'subfolders', 'dropzone_upload_url'))
            ->render();
    }

    /**
     * Uploads images to the image_uploads folder
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return mixed
     */
    public function uploadImages(Application $app, Request $request)
    {
        $folder = phive('ImageHandler')->getSetting('UPLOAD_PATH_URI');

        $dropzone_upload_url = $app['url_generator']->generate('uploadimages-dropzone');

        $image_service_url = phive('DBUserHandler')->getSetting('image_service_base_url');

        // we need to select the alias if it was passed as a get parameter
        $get_alias = $request->get('alias');
        if(empty($get_alias)) {
            $get_alias = ''; // make sure it's an empty string and not null
        }

        return $app['blade']->view()
            ->make('admin.cms.upload-images', compact('app', 'folder', 'dropzone_upload_url', 'image_service_url', 'get_alias'))
            ->render();
    }

    /**
     * Uploads multiple files using Dropzone.js
     *
     * @param Application $app
     * @param Request $request
     */
    public function uploadFilesDropzone(Application $app, Request $request)
    {
        $base_destination = phive('Filer')->getSetting('UPLOAD_PATH');

        $folder     = $request->get('folder');
        $subfolder  = $request->get('subfolder');

        if(!empty($subfolder) && $subfolder != 'file_uploads') {
            $base_destination .= '/' . $subfolder;
        }

        foreach ($_FILES as $file) {

            if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
                $result = phive('Dmapi')->uploadPublicFile($file['tmp_name'], $folder, $file['name'], $subfolder);
                if(!empty($result['errors'])) {
                    $app['flash']->add('danger', "Unable to save image {$file['name']} to the media service");
                    return $app->json(['success' => false], 500);
                }

            } else {
                $file_destination = $base_destination.'/'.$file['name'];
                if (move_uploaded_file($file['tmp_name'], $file_destination)) {
                    chmod($file_destination, 0777);
                } else {
                    $app['flash']->add('danger', "Unable to save image {$file['name']} to filesystem");
                    return $app->json(['success' => false], 500);
                }
            }

        }

        return $app->json(['success' => true, 'files' => $_FILES]);
    }

    /**
     * Uploads multiple files using Dropzone.js
     *
     * @param Application $app
     * @param Request $request
     */
    public function uploadImagesDropzone(Application $app, Request $request)
    {
        $image_repo = new ImageAliasRepository($app);

        $base_destination = phive('ImageHandler')->getSetting('UPLOAD_PATH');

        $folder      = $request->get('folder');
        $subfolder   = $request->get('subfolder');
        $image_id    = $request->get('image_id');
        $image_alias = $request->get('image_alias');
        $height      = $request->get('image_height');
        $width       = $request->get('image_width');

        if(empty($image_id) && !empty($image_alias)) {
            $image_id = $image_repo->getImageID($image_alias);

            if(empty($image_id)) {
                // First we need to determine the image_id, which is the next auto-increment value from table image_data
                $image_id = $image_repo->getNextAutoID('image_data');

                // We are dealing with a new image_alias, so we need to save it before we can save the uploaded images
                if(!$image_repo->createNewImageAlias($image_id, $image_alias)) {
                    $app['flash']->add('danger', "Unable to save new image alias {$image_alias} in database");
                } else {
                    $pages = $image_repo->getBannerPages();
                    if (!empty($image_repo->pageHasText($pages, $request->get('page_alias')))) {
                        // create a new localized string for all languages
                        $image_repo->createLocalizedStringsForBonusCodes($app, $image_alias);
                    }
                }
            }
        }

        foreach ($_FILES as $file) {

            if(!$image_repo->validateUploadedImage($file)) {
                $app['flash']->add('danger', "This image does not have the right extension {$file['name']}, please only upload jpg, png or gif");
                return $app->json(['success' => false, 'files', $_FILES]);
            }

            if(empty($width)) {
                $image_size_info = getimagesize($file['tmp_name']);
                $width = $image_size_info[0];
            }
            if(empty($height)) {
                $image_size_info = getimagesize($file['tmp_name']);
                $height = $image_size_info[1];
            }

            // - For each image, insert or update into image_data
            $new_filename   = $image_repo->generateUniqueFilename($file['name']);
            $filename_data  = $image_repo->parseImageFilename($file['name']);

            if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
                $result = phive('Dmapi')->uploadPublicFile($file['tmp_name'], $folder, $new_filename, $subfolder);
                if(!empty($result['success'])) {

                    if(!$image_repo->saveImageData($filename_data, $new_filename, $file['name'], $image_id, $height, $width)) {
                        $app['flash']->add('danger', "Unable to save image {$file['name']} in database");
                    } else {
                        // remove old image from Image Service
//                        phive('Dmapi')->deletePublicFile($folder, $file['name'], $subfolder);
                    }

                } else {
                    $app['flash']->add('danger', "Unable to save image {$file['name']} to filesystem");
                    return $app->json(['success' => false, 'files', $_FILES]);
                }

            } else {
                $file_destination = $base_destination.'/'.$new_filename;
                if (move_uploaded_file($file['tmp_name'], $file_destination)) {
                    chmod($file_destination, 0777);
                    if(!$image_repo->saveImageData($filename_data, $new_filename, $file['name'], $image_id, $height, $width)) {
                        $app['flash']->add('danger', "Unable to save image {$file['name']} in database");
                    } else {
                        // remove old image from filesystem
//                        unlink($base_destination.'/'.$file['name']);
                    }
                } else {
                    $app['flash']->add('danger', "Unable to save image {$file['name']} to filesystem");
                    return $app->json(['success' => false, 'files', $_FILES]);
                }
            }

        }

        return $app->json(['success' => true, 'files', $_FILES]);
    }


    public function getImageAliasses(Application $app, Request $request)
    {
        $image_aliasses = ImageAliasRepository::getImageAliasses($request->get('search_string'));

        return json_encode($image_aliasses);
    }

    public function getLandingPages(Application $app, Request $request)
    {
        $repo = new CmsRepository();
        $landing_pages = $repo->getLandingPages($request->get('search_string'));

        return json_encode($landing_pages);
    }

    public function showImageBoxes(Application $app, Request $request)
    {
        $repo = new ImageAliasRepository();
        $image_boxes = $repo->getImageBoxes($request->get('page_id'));

        return $app['blade']->view()
            ->make('admin.cms.partials.image_boxes', compact('app', 'image_boxes'))
            ->render();
    }

    public function showImages(Application $app, Request $request)
    {
        $folder = phive('ImageHandler')->getSetting('UPLOAD_PATH_URI');

        $dropzone_upload_url = $app['url_generator']->generate('uploadimages-dropzone');

        $images = ImageData::where('image_id', $request->get('image_id'))->get();

        $dimensions = ImageAliasRepository::getDimensionsForImageID($request->get('image_id'));

        $image_service_url = getMediaServiceUrl();

        return $app['blade']->view()
            ->make('admin.cms.partials.preview_image_alias', compact('app', 'images', 'image_service_url', 'folder', 'dropzone_upload_url', 'dimensions'))
            ->render();
    }

    public function showFileList(Application $app, Request $request): JsonResponse
    {
        $offset = $request->get('start');
        $rows_per_page = $request->get('length');
        $searchValue = $request->get('search')['value'] ?? '';

        $folder     = $request->get('folder');
        $subfolder  = $request->get('subfolder');

        // If the folder is the same as the subfolder it means were are requesting files from the root folder
        if($folder == $subfolder) {
            $subfolder = '';
        }

        $repo = new CmsRepository();
        [$files, $total_files_count] = $repo->getPaginatedListOfPublicFiles($subfolder, $offset, $rows_per_page, $searchValue);

        return $app->json([
            'files' => $files,
            'recordsFiltered' => $total_files_count,
        ]);
    }

    /**
     * Show a list of all pages with backgrounds.
     *
     * NOTE: it is better to restructure the files on the file system,
     * so that all page backgrounds are together in the same folder,
     * for example a folder named page_backgrounds, without any other files in that folder.
     *
     * @param Application $app
     * @param Request $request
     */
    public function pageBackgrounds(Application $app, Request $request)
    {
        // Get all pages that have page_setting 'landing_bkg' with the background filename
        $repo  = new CmsRepository();
        $pages = $repo->getListOfPagesWithBackgrounds();

        // Get a list of all files in the file_uploads root folder.
        // Any page backgrounds should be in this folder
        $files = $repo->getListOfPublicFiles('file_uploads', 'backgrounds');  // TODO: filter out files that are not images

        return $app['blade']->view()
            ->make('admin.cms.partials.change_page_backgrounds', compact('app', 'pages', 'files'))
            ->render();
    }


    public function changePageBackground(Application $app, Request $request)
    {
        $repo  = new CmsRepository();
        $result = $repo->changePageBackground($request->get('page_id'), $request->get('new_background_filename'));

        if($result) {
            $app['flash']->add('success', "Background changed successfully");
        } else {
            $app['flash']->add('danger', "Unable to change the background");
        }

        return new RedirectResponse($app['url_generator']->generate('pagebackgrounds'));
    }

}
