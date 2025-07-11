<?php

namespace App\Controllers\Messaging;

use App\Helpers\CampaignHelper;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use App\Models\EmailTemplate;
use App\Models\MessagingCampaignTemplates;
use Silex\Api\ControllerProviderInterface;
use App\Models\VoucherTemplate;
use App\Models\BonusTypeTemplate;
use App\Models\NamedSearch;
use Imagick;
use ImagickDraw;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Classes\Premailer;
use App\Repositories\ReplacerRepository;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class EmailController implements ControllerProviderInterface
{
    private $c_type;
    private $media_service_url;

    public function __construct()
    {
        $this->c_type = new CampaignHelper(MessagingCampaignTemplates::TYPE_EMAIL);

        // skip default
        $this->media_service_url = getMediaServiceUrl(false);
    }

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/email/list-templates/', 'App\Controllers\Messaging\EmailController::listTemplates')
            ->bind('messaging.email-templates')
            ->before(function () use ($app) {
                if (!p('messaging.email')) {
                    $app->abort(403);
                }
            });

        $factory->get('/email/new-template/', 'App\Controllers\Messaging\EmailController::createTemplate')
            ->bind('messaging.email-templates.new')
            ->before(function () use ($app) {
                if (!p('messaging.email.new')) {
                    $app->abort(403);
                }
            });

        $factory->get('/email/edit-template/{template}/', 'App\Controllers\Messaging\EmailController::editTemplate')
            ->convert('template', $app['emailTemplateProvider'])
            ->bind('messaging.email-templates.edit')
            ->before(function () use ($app) {
                if (!p('messaging.email.edit')) {
                    $app->abort(403);
                }
            });

        $factory->match('/email/clone-template/{template}/', 'App\Controllers\Messaging\EmailController::duplicateTemplate')
            ->convert('template', $app['emailTemplateProvider'])
            ->bind('messaging.email-templates.duplicate')
            ->before(function () use ($app) {
                if (!p('messaging.email.campaign.new')) {
                    $app->abort(403);
                }
            })->method("GET|POST");

        $factory->get('/email/template/delete/{template}/', 'App\Controllers\Messaging\EmailController::deleteTemplate')
            ->convert('template', $app['emailTemplateProvider'])
            ->bind('messaging.email-templates.delete')
            ->before(function () use ($app) {
                if (!p('messaging.email.delete')) {
                    $app->abort(403);
                }
            });

        $factory->match('/email/template/save/', 'App\Controllers\Messaging\EmailController::save')
            ->bind('messaging.email-templates.save')
            ->before(function () use ($app) {
                if (!p('messaging.email')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        $factory->match('/email/templates-save-subject/', 'App\Controllers\Messaging\EmailController::saveSubject')
            ->bind('messaging.email-templates.subject.save')
            ->before(function () use ($app) {
                if (!p('messaging.email')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        /*
         * EDITOR STUFF
         */
        $factory->get('/email/{controller}/{id}/img/', 'App\Controllers\Messaging\EmailController::processImgRequest')
            ->bind('messaging.email-templates.editID.img')
            ->before(function () use ($app) {
                if (!p('messaging.email.new')) {
                    $app->abort(403);
                }
            });

        $factory->post('/email/templates-new/show/', 'App\Controllers\Messaging\EmailController::getEmailTemplate')
            ->bind('messaging.email-templates.show')
            ->before(function () use ($app) {
                if (!p('messaging.email.new')) {
                    $app->abort(403);
                }
            });

        $factory->get('/email/templates-duplicate/{id}/img/', 'App\Controllers\Messaging\EmailController::processImgRequest')
            ->bind('messaging.email-templates.duplicate.img')
            ->before(function () use ($app) {
                if (!p('messaging.email.new')) {
                    $app->abort(403);
                }
            });
        $factory->get('/email/{controller}/img/', 'App\Controllers\Messaging\EmailController::processImgRequest')
            ->bind('messaging.email-templates.edit.img')
            ->before(function () use ($app) {
                if (!p('messaging.email.new')) {
                    $app->abort(403);
                }
            });

        $factory->match('/email/templates-download/', 'App\Controllers\Messaging\EmailController::processDlRequest')
            ->bind('messaging.email-templates.download')
            ->before(function () use ($app) {
                if (!p('messaging.email')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        $factory->match('/email/templates-upload/', 'App\Controllers\Messaging\EmailController::processUploadRequest')
            ->bind('messaging.email-templates.upload')
            ->before(function () use ($app) {
                if (!p('messaging.email')) {
                    $app->abort(403);
                }
            })->method('GET|POST');


        $factory->get('/email/templates/default/', 'App\Controllers\Messaging\EmailController::getDefaultTemplate')
            ->bind('messaging.email-templates.default')
            ->before(function () use ($app) {
                if (!p('messaging.email')) {
                    $app->abort(403);
                }
            });

        /*
         * SCHEDULE STUFF
         */
        $factory->match('/email/schedule/', 'App\Controllers\Messaging\EmailController::schedule')
            ->bind('messaging.email-campaigns.new')
            ->before(function () use ($app) {
                if (!p('messaging.email.campaign.new')) {
                    $app->abort(403);
                }
            })->method("GET|POST");

        return $factory;
    }

    public function listTemplates(Application $app)
    {
        $data = EmailTemplate::all();
        $c_type = $this->c_type;

        return $app['blade']->view()->make('admin.messaging.emails.template-list', compact('app', 'data', 'c_type'))->render();
    }

    public function createTemplate(Application $app, Request $request)
    {
        $repo = new ReplacerRepository();
        $replacers = $repo->getAllowedReplacers();
        $c_type = $this->c_type;
        $subject = $request->get('subject');
        $language = $request->get('language');
        $template_name = $request->get('template_name');
        $consent = $request->get('consent');

        return $app['blade']->view()->make('admin.messaging.emails.editor', compact('app', 'replacers', 'c_type', 'subject', 'language', 'template_name', 'consent'))->render();
    }

    public function editTemplate(Application $app, EmailTemplate $template)
    {

        $repo = new ReplacerRepository();
        $replacers = $repo->getAllowedReplacers();
        $c_type = $this->c_type;
        $subject = $template->subject;
        $language = $template->language;
        $template_name = $template->template_name;
        $consent = $template->getConsent();
        return $app['blade']->view()->make('admin.messaging.emails.editor', compact('app', 'template', 'replacers', 'subject', 'language', 'c_type', 'template_name', 'consent'))->render();
    }



    public function duplicateTemplate(Application $app, EmailTemplate $template)
    {
        $template = $template->replicate();
        $repo = new ReplacerRepository();
        $replacers = $repo->getAllowedReplacers();
        $c_type = $this->c_type;

        $template_name = $app['request_stack']->getCurrentRequest()->get('template_name');
        $subject = $app['request_stack']->getCurrentRequest()->get('subject');
        $language = $app['request_stack']->getCurrentRequest()->get('language');
        $consent = $app['request_stack']->getCurrentRequest()->get('consent');

        $is_cloned = 1;

        return $app['blade']->view()->make('admin.messaging.emails.editor', compact('app', 'template', 'is_cloned', 'replacers', 'subject', 'language', 'c_type', 'template_name', 'consent'))->render();
    }

    private function replaceUploadImgSrcs($base_url, $html)
    {
        $search = [];
        $replace = [];

        $matches = [];

        $num_full_pattern_matches = preg_match_all('#<img.*?src="([^"]*?\/[^/]*\.[^"]+)#i', $html, $matches);
        for ($i = 0; $i < $num_full_pattern_matches; $i++) {
            if (stripos($matches[1][$i], "/img/?src=") !== false) {
                $src_matches = [];

                if (preg_match('#/img/\?src=(.*)&amp;method=(.*)&amp;params=(.*)#i', $matches[1][$i], $src_matches) !== false) {
                    $search[] = $matches[1][$i];
                    $replace[] = $base_url.end(explode('/', urldecode($src_matches[1])));
                }
            }
        }

        return str_ireplace($search, $replace, $html);
    }

    private function replaceDefaultImgSrcs($base_url, $html)
    {
        preg_match_all('#<img.*?src="/([^"]*?\/[^/]*\.[^"]+)#i', $html, $matches);
        foreach ($matches[1] as $match) {
            $html = str_ireplace('/' . $match, $base_url . $match, $html);
        }
        return $html;
    }

    public function save(Application $app, Request $request)
    {
        $form_fields = $request->request->all();
        $previous_template = EmailTemplate::where('hash', $form_fields['hash'])->first();

        $upload_url = $this->media_service_url;

        if (empty($upload_url)) {
            $upload_url = $app['mosaico']['base_url'] . $app['mosaico']['uploads_url'];
        } else {
            $upload_url .= '/email-templates/';
        }

        $html = $this->replaceUploadImgSrcs($upload_url, $form_fields['html']);
        $html = $this->replaceDefaultImgSrcs($app['mosaico']['base_url'], $html);

        $form_fields['html'] = $html;

        if ($previous_template) {
            $previous_template->fill($form_fields)->update();
            return new RedirectResponse($app['url_generator']->generate('messaging.email-templates'));
        } else {
            $new_template = new EmailTemplate();
            if ($new_template->fill($form_fields)->save()) {
                return $app->json(["status" => "success", "url" => $app['url_generator']->generate('messaging.email-templates.edit', ['template' => $new_template->id])]);
            }
        }

        return $app->json(["status" => "error"]); //todo check this one
    }

    public function deleteTemplate(Application $app, EmailTemplate $template)
    {
        $has_campaign = MessagingCampaignTemplates::where(['template_type' => MessagingCampaignTemplates::TYPE_EMAIL, 'template_id' => $template->id])->count();
        if ($has_campaign > 0) {
            $app['flash']->add('danger', "Cannot be deleted because is linked to a recurring email.");
        } else {
            $template->delete();
            $app['flash']->add('success', "Email template deleted successfully.");
        }
        return new RedirectResponse($app['url_generator']->generate('messaging.email-templates'));
    }

    //todo check this one is this one really working? In case it is I should port most of the functionality to a repository to reuse schedule sms code
    //todo the template list is crazy, it does not even show subject on template list and on first load it does not rely on language
    public function schedule(Application $app, Request $request)
    {
        $action = $request->get('action', 'new');
        $c_type = new CampaignHelper(MessagingCampaignTemplates::TYPE_EMAIL);
        if ($request->isMethod('POST')) {
            if (in_array($action, ['new', 'clone', 'edit'])) {
                if ($action == 'edit') {
                    $campaign_template = MessagingCampaignTemplates::find($request->get('campaign-template-id'));
                } else {
                    $campaign_template = new MessagingCampaignTemplates();
                    $campaign_template->template_type = MessagingCampaignTemplates::TYPE_EMAIL;
                }
                $request->request->set('recurring_days', implode(',', $request->request->get('recurring_days')));

                //to avoid mysql 1092 error / datetime not valid
                if (empty($request->get('recurring_end_date'))) {
                    $request->request->set('recurring_end_date', null);
                }

                $campaign_template->fill($request->request->all());
                if ($campaign_template->save()) {
                    $app['flash']->add('success', "Campaign scheduled successfully.");

                    $app['monolog']->addInfo("Email campaign scheduled successfully", [
                        'id' => $campaign_template->id,
                        'action' => $action,
                        'name' => $campaign_template->emailTemplate()->first()->template_name,
                        'recurring_type' => $campaign_template->getRecurringTypeName(),
                        'start_datetime' => $campaign_template->generateScheduledTime(),
                        'first_future_scheduled' => $campaign_template->getFirstFutureScheduled()
                    ]);

                    if($campaign_template->recurring_type === "one"){
                        $redirect = 'messaging.campaigns.list-scheduled';
                    }else{
                        $redirect = 'messaging.campaigns.list-recurring';
                    }
                    return new RedirectResponse($app['url_generator']->generate($redirect, ['type' => $c_type->getRawType()]));
                } else {
                    $app['flash']->add('danger', "Campaign not scheduled due to: {$campaign_template->getFirstError()[0]}");
                    $app['monolog']->addError("Email campaign could not be scheduled", [
                            'error' => $campaign_template->getFirstError()[0],
                            'template_id' => $request->get('template_id'),
                            'named_search_id' => $request->get('named_search_id'),
                            'recurring_type' => $request->get('recurring_type'),
                            'scheduled_time' => $request->get('scheduled_time')
                        ]
                    );
                }
            } else {
                $app->abort('404', "Action not supported");
            }
        } elseif ($action == 'edit') {
            $campaign_template = MessagingCampaignTemplates::find($request->get('campaign-template-id'));
            $named_searches = NamedSearch::where('language', $campaign_template->emailTemplate()->first()->language)->get();
        } elseif ($action == 'clone') {
            $campaign_template = MessagingCampaignTemplates::find($request->get('campaign-template-id'))->replicate(['id']);
            $named_searches = NamedSearch::where('language', $campaign_template->emailTemplate()->first()->language)->get();
        }

        if ($campaign_template) {
            $campaign_template->recurring_days = empty($campaign_template->recurring_days) ? [] : explode(',', $campaign_template->recurring_days);
        }

        $voucher_templates = VoucherTemplate::all();
        $bonus_templates = BonusTypeTemplate::all();
        $templates_list = EmailTemplate::all();

        $type_id = MessagingCampaignTemplates::TYPE_EMAIL;

        if (!empty($request->get('emailTemplate'))) {
            $template_obj = EmailTemplate::find($request->get('emailTemplate'));
            $named_searches = NamedSearch::where('language', $template_obj->language)->get();
        }

        $from_email = [
            $app['messaging']['transactional_from_email_name'],
            $app['messaging']['default_from_email_name']
        ];

        foreach($named_searches as $ns)
            unset($ns->sql_statement);

        return $app['blade']->view()->make('admin.messaging.sms.schedule', compact('app', 'campaign_template', 'named_searches', 'bonus_templates', 'voucher_templates', 'templates_list', 'template_obj', 'c_type', 'type_id', 'from_email'))->render();
    }


    //todo check this one that is used on schedule
    public function getEmailTemplate(Application $app, Request $request)
    {
        $template_obj = EmailTemplate::find($request->get('template'));

        $c_type = $this->c_type;
        if ($template_obj) {

            if ($request->get('rawHtml') == 1) {
                return $app->json(['html' => $template_obj->html]);
            } else {
                return $app->json([
                    'html' => $app['blade']->view()->make('admin.messaging.sms.partials.template-show', compact('app', 'type', 'c_type', 'template_obj'))->render(), //todo wtf is this???? template show from sms partials???
                    'contacts_list' => NamedSearch::where('language', $template_obj->language)->get()
                ]);
            }

        } else {
            return $app->json(['html' => '<p>Template not found.</p>']);
        }
    }

    public function getDefaultTemplate(Application $app, Request $request)
    {
        return $app['blade']->view()->make('admin.messaging.emails.default-template', compact('app', 'data'))->render();
    }

    // Note: For Mosaico to preview the email correctly, the processImgRequest function must be used
    // to resize the image dynamically. Hence, the urls returned in this function must be to the locally
    // stored images.
    public function processUploadRequest(Application $app, Request $request)
    {
        $files = array();

        $base_url = $app['mosaico']['base_url'] . $app['mosaico']['uploads_url'];

        $thumbnail_url = $this->media_service_url;
        if (empty($thumbnail_url))
            $thumbnail_url = $base_url;
        else
            $thumbnail_url .= '/email-templates/';

        if ($_SERVER["REQUEST_METHOD"] == "GET") {

            if (empty($this->media_service_url)) {
                $base_dir = $app['mosaico']['base_dir'] . $app['mosaico']['uploads_dir'];
                $dir = array_filter(scandir($base_dir), function($item) use ($base_dir) {
                     return is_file($base_dir . $item);
                });
                foreach ($dir as $item) {
                    $file = [
                        "name" => $item,
                        "url" => $base_url . $item,
                        "thumbnailUrl" => $thumbnail_url . 'thumbnail/' . $item,
                    ];

                    $files[] = $file;
                }
            } else {
                $dir = phive('Dmapi')->getListOfPublicFiles('email-templates');
                foreach ($dir['files'] as $item) {
                    $file_name = $item['name'];
                    $file = [
                        "name" => $file_name,
                        "url" => $base_url . $file_name,
                        "thumbnailUrl" => $thumbnail_url . 'thumbnail/' . $file_name,
                    ];

                    $files[] = $file;
                }
            }

        } else if (!empty($_FILES)) {

            foreach ($_FILES["files"]["error"] as $key => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES["files"]["tmp_name"][$key];
                    $file_name = $_FILES["files"]["name"][$key];
                    $file_path = $app['mosaico']['base_dir'] . $app['mosaico']['uploads_dir'] . $file_name;
                    if (move_uploaded_file($tmp_name, $file_path) === true) {
                        $image = new Imagick($file_path);
                        $image->resizeImage($app['mosaico']['thumbnail_width'], $app['mosaico']['thumbnail_height'], Imagick::FILTER_LANCZOS, 1.0, true);
                        $thumbnail_path = $app['mosaico']['base_dir'] . $app['mosaico']['thumbnails_dir'] . $file_name;
                        $image->writeImage($thumbnail_path);
                        $image->destroy();
                        if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
                            phive('Dmapi')->uploadPublicFile($file_path, 'email-templates', $file_name, '');
                            phive('Dmapi')->uploadPublicFile($thumbnail_path, 'email-templates', $file_name, 'thumbnail');
                        }

                        // Using mosaico base url even when using image service. This is the way we flag to mosaico that image fetches
                        // should be made through APIs return processImgRequest. The reason is that this is the only way we are given
                        // the oportunity to resize the image, which the editor for some reason requires.
                        $file = array(
                            "name" => $file_name,
                            "url" => $base_url . $file_name,
                            "thumbnailUrl" => $thumbnail_url . 'thumbnail/' . $file_name,
                        );
                        $files[] = $file;
                    } else {
                        return $app->json(array("files" => $files));
                    }
                } else {
                    return $app->json(array("files" => $files));
                }
            }
        }

        return $app->json(array("files" => $files));
    }

    /**
     * handler for img requests
     */
    public function processImgRequest(Application $app)
    {

        if ($_SERVER["REQUEST_METHOD"] == "GET") {
            $method = $_GET["method"];
            $params = explode(",", $_GET["params"]);
            $width = (int)$params[0];
            $height = (int)$params[1];

            if ($method == "placeholder") {
                $image = new Imagick();

                $image->newImage($width, $height, "#707070");
                $image->setImageFormat("png");

                $x = 0;
                $y = 0;
                $size = 40;

                $draw = new ImagickDraw();

                while ($y < $height) {
                    $draw->setFillColor("#808080");

                    $points = [
                        ["x" => $x, "y" => $y],
                        ["x" => $x + $size, "y" => $y],
                        ["x" => $x + $size * 2, "y" => $y + $size],
                        ["x" => $x + $size * 2, "y" => $y + $size * 2]
                    ];

                    $draw->polygon($points);

                    $points = [
                        ["x" => $x, "y" => $y + $size],
                        ["x" => $x + $size, "y" => $y + $size * 2],
                        ["x" => $x, "y" => $y + $size * 2]
                    ];

                    $draw->polygon($points);

                    $x += $size * 2;

                    if ($x > $width) {
                        $x = 0;
                        $y += $size * 2;
                    }
                }

                $draw->setFillColor("#B0B0B0");
                $draw->setFontSize($width / 5);
                $draw->setFontWeight(800);
                $draw->setGravity(Imagick::GRAVITY_CENTER);
                $draw->annotation(0, 0, $width . " x " . $height);

                $image->drawImage($draw);
                $headers = array(
                    'Content-Type' => 'image/png',
                    'Content-Disposition' => 'inline; filename="' . $image . '"');
                ob_end_clean();
                return new Response($image, 200, $headers);
            } else {
                $file_name = $_GET["src"];
                $file_name = urldecode($file_name);
                $path_parts = pathinfo($file_name);

                switch ($path_parts["extension"]) {
                    case "png":
                        $mime_type = "image/png";
                        break;

                    case "gif":
                        $mime_type = "image/gif";
                        break;

                    default:
                        $mime_type = "image/jpeg";
                        break;
                }

                $file_name = $path_parts["basename"];

                $media_service_url = $this->media_service_url;

                if (empty($media_service_url))
                    $image = $this->resizeImage($app['mosaico']['base_dir'] . $app['mosaico']['uploads_dir'] . $file_name, $method, $width, $height);
                else {
                    $handle = fopen($media_service_url . '/email-templates/' . $file_name, 'rb');
                    $image = new Imagick();
                    $image->readImageFile($handle);
                    fclose($handle);
                    $image = $this->resizeImagick($image, $method, $width, $height);
                }
                $headers = array(
                    'Content-Type' => $mime_type,
                    'Content-Disposition' => 'inline; filename="' . $image . '"');
                ob_end_clean();
                return new Response($image, 200, $headers);
            }
        }
    }

    /**
     * handler for dl requests
     */
    public function processDlRequest(Application $app)
    {
        //$premailer = Premailer::html( $_POST[ "html" ], true, "hpricot", self::$config[ 'base_url' ] );
        //$html = $premailer[ "html" ];

        $upload_url = $this->media_service_url;
        if (empty($upload_url)) {
            $upload_url = $app['mosaico']['base_url'] . $app['mosaico']['uploads_url'];
        } else {
            $upload_url .= '/email-templates/';
        }

        $html = $this->replaceUploadImgSrcs($upload_url, $_POST["html"]);
        $html = $this->replaceDefaultImgSrcs($app['mosaico']['base_url'], $html);

        /* create static versions of resized images */

//        NOTE: Trying out not using resized static images for downloaded emails.
//
//        $base_url = getMediaServiceUrl();
//        if (empty($base_url)) {
//            $base_url = $app['mosaico']['base_url'] . $app['mosaico']['uploads_url'];
//        } else {
//            $base_url .= '/email-templates/';
//        }
//
//        foreach ($this->getImgSrcs($html) as $url => $parts)
//        {
//            $file_name = urldecode($parts[1]);
//            $file_name = end(explode('/', $file_name));
//
//            $method = urldecode($parts[2]);
//            $params = urldecode($parts[3]);
//            $params = explode(",", $params);
//            $width = (int)$params[0];
//            $height = (int)$params[1];
//            $static_file_name = $method . "_" . $width . "x" . $height . "_" . $file_name;
//            $html = str_ireplace($url, $base_url . 'static/' . rawurlencode($static_file_name), $html);
//            $image = $this->resizeImage($app['mosaico']['base_dir'] . $app['mosaico']['uploads_dir'] . $file_name, $method, $width, $height);
//            $file_path = $app['mosaico']['base_dir'] . $app['mosaico']['static_dir'] . $static_file_name;
//            $image->writeImage($file_path);
//            if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
//                phive('Dmapi')->uploadPublicFile($file_path, 'email-templates', $static_file_name, 'static');
//            }
//        }

        switch ($_POST["action"]) {
            case "download":
                file_put_contents($_POST["filename"], $html);
                return $app
                    ->sendFile($_POST["filename"])
                    ->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($_POST["filename"]));
                break;
            case "email":
                $to = $_POST["rcpt"];
                $subject = $_POST["subject"];

                $headers = array();

                $headers[] = "MIME-Version: 1.0";
                $headers[] = "Content-type: text/html; charset=iso-8859-1";
                $headers[] = "To: $to";
                $headers[] = "Subject: $subject";

                $headers = implode("\r\n", $headers);

                if (mail($to, $subject, $html, $headers) === false) {
                    $http_return_code = 500;
                    return;
                }

                break;
        }
    }

    /**
     *
     * function to resize images using resize or cover methods
     */
    public function resizeImage($file_path, $method, $width, $height)
    {
        return $this->resizeImagick(new Imagick($file_path), $method, $width, $height);
    }

    public function resizeImagick($image, $method, $width, $height)
    {
        if ($method == "resize") {
            $image->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1.0);
        } else // $method == "cover"
        {
            $image_geometry = $image->getImageGeometry();
            $width_ratio = $image_geometry["width"] / $width;
            $height_ratio = $image_geometry["height"] / $height;
            $resize_width = $width;
            $resize_height = $height;
            if ($width_ratio > $height_ratio) {
                $resize_width = 0;
            } else {
                $resize_height = 0;
            }
            $image->resizeImage($resize_width, $resize_height, Imagick::FILTER_LANCZOS, 1.0);
            $image_geometry = $image->getImageGeometry();
            $x = ($image_geometry["width"] - $width) / 2;
            $y = ($image_geometry["height"] - $height) / 2;
            $image->cropImage($width, $height, $x, $y);
        }
        return $image;
    }
}
