<?php

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Classes\DateRange;
use App\Helpers\DataFormatHelper;
use App\Helpers\DateHelper;
use App\Models\Trophy;
use App\Models\LocalizedStrings;
use App\Models\User;
use App\Repositories\ActionRepository;
use App\Repositories\TrophiesRepository;
use App\Repositories\LocalizedStringsRepository;
use App\Repositories\GameRepository;
use App\Repositories\TrophyAwardsRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Extensions\Database\FManager as DB;

class TrophiesController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\TrophiesController::index')
            ->bind('trophies.index')
            ->before(function () use ($app) {
                if (!p('trophies.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/new/', 'App\Controllers\TrophiesController::newTrophy')
            ->bind('trophies.new')
            ->before(function () use ($app) {
                if (!p('trophies.new')) {
                    $app->abort(403);
                }
            });

        $factory->match('/edit/{trophy}/', 'App\Controllers\TrophiesController::editTrophy')
            ->convert('trophy', $app['trophyProvider'])
            ->bind('trophies.edit')
            ->before(function () use ($app) {
                if (!p('trophies.edit')) {
                    $app->abort(403);
                }
            });

        $factory->match('/template/{trophy}/', 'App\Controllers\TrophiesController::newTrophySetTemplate')
            ->convert('trophy', $app['trophyProvider'])
            ->bind('trophies.templateedit')
            ->before(function () use ($app) {
                if (!p('trophies.new')) {
                    $app->abort(403);
                }
            });

        $factory->match('/newtrophyset/', 'App\Controllers\TrophiesController::newTrophySet')
            ->bind('trophies.newtrophyset')
            ->before(function () use ($app) {
                if (!p('trophies.new')) {
                    $app->abort(403);
                }
            });

        $factory->match('/savetrophyset/', 'App\Controllers\TrophiesController::saveTrophySet')
            ->bind('trophies.savetrophyset')
            ->before(function () use ($app) {
                if (!p('trophies.edit')) {
                    $app->abort(403);
                }
            });

        $factory->match('/delete/{trophy}/', 'App\Controllers\TrophiesController::deleteTrophy')
            ->convert('trophy', $app['trophyProvider'])
            ->bind('trophies.delete')
            ->before(function () use ($app) {
                if (!p('trophies.delete')) {
                    $app->abort(403);
                }
            });

        $factory->match('/search/', 'App\Controllers\TrophiesController::searchTrophy')
            ->bind('trophies.search')
            ->before(function () use ($app) {
                if (!p('trophies.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/file-upload/', 'App\Controllers\TrophiesController::fileUpload')
            ->bind('trophies.fileupload')
            ->before(function () use ($app) {
                if (!p('trophies.fileupload')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    /**
     * @param Application $app
     */
    public function index(Application $app, Request $request, $users_list = null)
    {
        $repo    = new TrophiesRepository($app);
        $columns = $repo->getTrophiesSearchColumnsList();

        if (!isset($_COOKIE['trophies-search-no-visible'])) {
            foreach (array_keys($columns['list']) as $k) {
                if (!in_array($k, $columns['default_visibility'])) {
                    $columns['no_visible'][] = "col-$k";
                }
            }
            setcookie('trophies-search-no-visible', json_encode($columns['no_visible']));
            $_COOKIE['trophies-search-no-visible'] = json_encode($columns['no_visible']);
        } else {
            $columns['no_visible'] = json_decode($_COOKIE['trophies-search-no-visible'], true);
        }

        $res = $this->getTrophyList($request, $app, [
            'ajax'         => false,
            'length'       => 25,
            'sendtobrowse' => $request->get('sendtobrowse', 0),
            'users_list'   => $users_list
        ]);

        $pagination = [
            'data'           => $res['data'],
            'defer_option'   => $res['recordsTotal'],
            'initial_length' => 25
        ];

        $breadcrumb = 'List and Search';

        $view = ["new" => "Trophy", 'title' => 'Trophies', 'variable' => 'trophies', 'variable_param' => 'trophy'];

        return $app['blade']->view()->make('admin.gamification.trophies.index', compact('app', 'columns', 'pagination', 'breadcrumb', 'view'))->render();
    }

    private function getAllLanguagesMap($distinct_languages, $localized_strings)
    {
        $all_localized_strings = [];
        foreach ($distinct_languages as $language) {
            $language = trim($language);
            if (strlen($language) < 1) { // In case an empty langauge exists in db.
                continue;
            }

            $all_localized_strings[$language] = null;
            foreach ($localized_strings as $local_string) {
                if ($language == $local_string->language) {
                    $all_localized_strings[$language] = $local_string;
                    break;
                }
            }
        }

        return $all_localized_strings;
    }

    private function getAllDistinct()
    {
        $trophy                       = new Trophy();
        $all_distinct['type']         = array_merge([""], $trophy->getDistinct('type'));
        $all_distinct['subtype']      = array_merge([""], $trophy->getDistinct('subtype'));
        $all_distinct['category']     = array_merge([""], $trophy->getDistinct('category'));
        $all_distinct['sub_category'] = array_merge([""], $trophy->getDistinct('sub_category'));
        $all_distinct['time_span']    = array_merge([""], $trophy->getDistinct('time_span'));
        $all_distinct['countries']    = DataFormatHelper::getSelect2FormattedData(DataFormatHelper::getCountryList(), [
            "id" => 'iso',
            "text" => 'printable_name'
        ]);

        return $all_distinct;
    }

    private function fixTrophyData(&$trophy_data)
    {
        // Special treatment for awards and gameref, as if this is cleared in the form,
        // it's not showing up in the data, and thus not "clearing" it.
        // Doing that here then.
        if (!isset($trophy_data['award_id'])) {
            $trophy_data['award_id'] = 0;
        }
        if (!isset($trophy_data['award_id_alt'])) {
            $trophy_data['award_id_alt'] = 0;
        }
        if (!isset($trophy_data['game_ref'])) {
            $trophy_data['game_ref'] = "";
        }

        // Checkboxes are also special.
        // TODO: Better solution?
        if (isset($trophy_data['hidden'])) {
            $trophy_data['hidden'] = 1;
        } else {
            $trophy_data['hidden'] = 0;
        }
        if (isset($trophy_data['in_row'])) {
            $trophy_data['in_row'] = 1;
        } else {
            $trophy_data['in_row'] = 0;
        }
        if (isset($trophy_data['trademark'])) {
            $trophy_data['trademark'] = 1;
        } else {
            $trophy_data['trademark'] = 0;
        }
        if (isset($trophy_data['repeatable'])) {
            $trophy_data['repeatable'] = 1;
        } else {
            $trophy_data['repeatable'] = 0;
        }
        if (isset($trophy_data['excluded_countries'])) {
            $trophy_data['excluded_countries'] = implode(' ', $trophy_data['excluded_countries']);
        } else {
            $trophy_data['excluded_countries'] = "";
        }
        if (isset($trophy_data['included_countries'])) {
            $trophy_data['included_countries'] = implode(' ', $trophy_data['included_countries']);
        } else {
            $trophy_data['included_countries'] = "";
        }
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function newTrophy(Application $app, Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $t = new Trophy($data);
            if (!$t->validate()) {
                return $app->json(['success' => false, 'attribute_errors' => $t->getErrors()]);
            }

            $this->fixTrophyData($data);

            DB::shBeginTransaction(true);
            try {
                $new_trophy = Trophy::create($data);

                foreach ($data[$new_trophy['alias']] as $language => $text) {
                    if (strlen(trim($text)) > 0) {
                        $localized_string           = new LocalizedStrings();
                        $localized_string->alias    = 'trophyname.'.trim($new_trophy['alias']);
                        $localized_string->language = $language;
                        $localized_string->value    = $text;

                        if (!$localized_string->validate()) {
                            DB::shRollback(true);
                            return $app->json(['success' => false, 'attribute_errors' => $localized_string->getErrors()]);
                        }

                        $localized_string->save();
                    }
                }

            } catch (\Exception $e) {
                DB::shRollback(true);
                if ($e->getCode() === '23000') {
                    $e = "Alias already exists in the database.";
                }
                return $app->json(['success' => false, 'error' => $e]);
            }
            DB::shCommit(true);
            return $app->json(['success' => true, 'trophy' => $new_trophy]);
        }

        $all_distinct          = $this->getAllDistinct();

        $localized_strings_obj = new LocalizedStrings();
        $distinct_languages    = $localized_strings_obj->getDistinct('language');

        $localized_strings     = [];

        $all_localized_strings = $this->getAllLanguagesMap($distinct_languages, $localized_strings);

        $buttons['save-all']   = "Create New Trophy";

        $breadcrumb = 'New';

        return $app['blade']->view()->make('admin.gamification.trophies.new', compact('app', 'all_localized_strings', 'buttons', 'all_distinct', 'breadcrumb'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param Trophy $trophy
     * @return mixed
     */
    public function editTrophy(Application $app, Request $request, Trophy $trophy)
    {
        if (!$trophy) {
            return $app->json(['success' => false, 'Trophy not found.']);
        }

        if ($request->getMethod() == 'POST') {

            DB::shBeginTransaction(true);
            try {
                $previous_alias = $trophy->alias;
                $data = $request->request->all();
                $t = new Trophy($data);
                if (!$t->validate()) {
                    DB::shRollback(true);
                    return $app->json(['success' => false, 'attribute_errors' => $t->getErrors()]);
                }

                $this->fixTrophyData($data);
                $trophy->update($data);
                $new_alias = $trophy->alias;

                // Update Localized Strings too, if alias has changed.
                if ($previous_alias != $new_alias) {

                    $localized_string        = new LocalizedStrings();
                    $localized_string->alias = 'trophyname.'.trim($new_alias);

                    if (!$localized_string->validate()) {
                        DB::shRollback(true);
                        return $app->json(['success' => false, 'attribute_errors' => $localized_string->getErrors()]);
                    }

                    $repo_strings = new LocalizedStringsRepository($app);
                    $repo_strings->updateAlias($previous_alias, $new_alias);
                }
            } catch (\Exception $e) {
                DB::shRollback(true);
                return $app->json(['success' => false, 'error' => $e]);
            }
            DB::shCommit(true);
            return $app->json(['success' => true]);
        }

        $all_distinct     = $this->getAllDistinct();

        $repo             = new TrophiesRepository($app);
        $trophy_award     = $repo->getTrophyAwardById($trophy->award_id);
        $trophy_award_alt = $repo->getTrophyAwardById($trophy->award_id_alt);

        $localized_strings_obj = new LocalizedStrings();
        $distinct_languages    = $localized_strings_obj->getDistinct('language');

        $repo_strings          = new LocalizedStringsRepository($app);
        $localized_strings     = $repo_strings->getAllByAlias('trophyname.'.$trophy->alias);

        $game_repo = new GameRepository();
        $game      = $game_repo->getGameByExtGameName($trophy->game_ref);

        $all_localized_strings = $this->getAllLanguagesMap($distinct_languages, $localized_strings);

        $trophy_parts = explode('_', $trophy->alias);
        $trophy_main  = $trophy_parts[0];  // Extract first part of the name.

        $all_trophies = [];
        if (strlen($trophy->game_ref) > 0) {
            $all_trophies = $repo->getAllTrophiesByGameRef($trophy->game_ref);
        } else {
            $all_trophies = $repo->getAllTrophiesByAlias($trophy_main);
        }

        $repo->setBothTrophyImages($trophy);

        foreach ($all_trophies as &$t) {
            $repo->setBothTrophyImages($t);
        }

        $buttons['save']          = "Save";
        $buttons['save-as-new']   = "Save As New...";
        $buttons['save-all']      = "Save All";
        $buttons['save-language'] = "Save Localized Strings";
        //$buttons['delete']        = "Delete";

        $breadcrumb = 'Edit';

        return $app['blade']->view()->make('admin.gamification.trophies.edit', compact('app', 'game', 'buttons', 'trophy', 'all_distinct', 'trophy_award', 'trophy_award_alt', 'all_localized_strings', 'all_trophies', 'trophy_main', 'breadcrumb'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     */
    public function newTrophySetTemplate(Application $app, Request $request, Trophy $trophy)
    {
        $all_distinct = $this->getAllDistinct();

        $repo         = new TrophiesRepository($app);
        $columns      = $repo->getTrophiesSearchColumnsList();

        $trophy_parts = explode('_', $trophy->alias);
        $trophy_main  = $trophy_parts[0]; // Extract first part of the name.

        $all_trophies = [];
        if (strlen($trophy->game_ref) > 0) {
            $all_trophies = $repo->getAllTrophiesByGameRef($trophy->game_ref);
        } else {
            $all_trophies = $repo->getAllTrophiesByAlias($trophy_main);
        }

        $localized_strings_obj = new LocalizedStrings();
        $distinct_languages    = $localized_strings_obj->getDistinct('language');

        $repo_strings          = new LocalizedStringsRepository($app);

        $alias_to_id = [];
        $all_localized_strings = []; // This will contain all localized strings grouped by alias and language.

        $trophy_awards     = [];
        $trophy_award_alts = [];

        $game_repo = new GameRepository();
        $games     = [];

        foreach ($all_trophies as $index => $t) {
            $alias_to_id[$t->alias] = ['aliasid' => $t->id, 'index' => $index];
            foreach ($distinct_languages as $language) {
                $all_localized_strings[$t->alias][$language] = "";
            }

            $trophy_awards[$index]     = $repo->getTrophyAwardById($t->award_id);
            $trophy_award_alts[$index] = $repo->getTrophyAwardById($t->award_id_alt);
            $games[$index]             = $game_repo->getGameByExtGameName($t->game_ref);

            $localized_strings         = $repo_strings->getAllByAlias('trophyname.'.$t->alias);
            foreach ($localized_strings as $local_string) {
                foreach ($distinct_languages as $language) {
                    if ($local_string->language == $language) {
                        $alias = str_replace("trophyname.", "", $local_string->alias);
                        $all_localized_strings[$alias][$language] = $local_string->value;
                    }
                }
            }
        }

        $repo->setBothTrophyImages($trophy);

        foreach ($all_trophies as &$t) {
            $repo->setBothTrophyImages($t);
        }

        if (!isset($_COOKIE['trophyset-no-visible'])) {
            foreach (array_keys($columns['list']) as $k) {
                if (!in_array($k, $columns['default_visibility_trophyset'])) {
                    $columns['no_visible'][] = "col-$k";
                }
            }
            setcookie('trophyset-no-visible', json_encode($columns['no_visible']));
            $_COOKIE['trophyset-no-visible'] = json_encode($columns['no_visible']);
        } else {
            $columns['no_visible'] = json_decode($_COOKIE['trophyset-no-visible'], true);
        }

        return $app['blade']->view()->make('admin.gamification.trophies.template', compact('alias_to_id', 'games', 'columns', 'app', 'trophy', 'all_distinct', 'trophy_awards', 'trophy_award_alts', 'all_localized_strings', 'all_trophies', 'trophy_main'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     */
    public function newTrophySet(Application $app, Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $trophies  = [];
            $form_data = $request->request->all();

            foreach ($form_data['trophy'] as $index => $trophy) {
                unset($trophy['id']);
                $form_data['trophy'][$index] = $trophy;
            }

            DB::shBeginTransaction(true);
            try {
                foreach ($form_data['trophy'] as $index => $trophy) {

                    $t = new Trophy($trophy);
                    if (!$t->validate()) {
                        DB::shRollback(true);
                        return $app->json(['success' => false, 'attribute_errors' => $t->getErrors()]);
                    }

                    array_push($trophies, Trophy::create($trophy));

                    foreach ($form_data['localizedstrings'][$index] as $language => $text) {
                        if (strlen(trim($text)) > 0) {
                            $localized_string           = new LocalizedStrings();
                            $localized_string->alias    = 'trophyname.'.$trophy['alias'];
                            $localized_string->language = $language;
                            $localized_string->value    = $text;
                            $localized_string->save();
                        }
                    }
                }
            } catch (\Exception $e) {
                DB::shRollback(true);
                return $app->json(['success' => false, 'error' => $e, 'form_data' => $form_data]);
            }
            DB::shCommit(true);
            return $app->json(['success' => true, 'trophies' => $trophies]);
        }

        return $app->json(['success' => false, 'error', 'Expecting a POST message.']);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function saveTrophySet(Application $app, Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $repo_strings = new LocalizedStringsRepository($app);

            $trophies  = [];
            $form_data = $request->request->all();

            DB::shBeginTransaction(true);
            try {
                foreach ($form_data['trophy'] as $index => $trophy_data) {
                    $t = new Trophy($trophy_data);
                    if (!$t->validate()) {
                        DB::shRollback(true);
                        return $app->json(['success' => false, 'attribute_errors' => $t->getErrors()]);
                    }

                    $trophy = Trophy::where('id', $trophy_data['id'])->first();

                    $previous_alias = $trophy->alias;
                    $new_alias = trim($trophy_data['alias']);

                    // Update Localized Strings too, if alias has changed.
                    if ($previous_alias != $new_alias) {
                        $repo_strings = new LocalizedStringsRepository($app);
                        $repo_strings->updateAlias($previous_alias, $new_alias);
                    }

                    $this->fixTrophyData($trophy_data);
                    $trophy->update($trophy_data);
                    array_push($trophies, $trophy);

                    foreach ($form_data['localizedstrings'][$index] as $language => $text) {
                        $text = trim($text);

                        $localized_string = $repo_strings->getByAliasAndLanguage('trophyname.'.$trophy->alias, $language);
                        if (empty($localized_string) && strlen($text) > 0) { // LocalizedString doesn't exist in DB, but new text is not empty. Add new LocalizedString entry to DB.
                            $localized_string           = new LocalizedStrings();
                            $localized_string->alias    = 'trophyname.'.$trophy->alias;
                            $localized_string->language = $language;
                            $localized_string->value    = $text;
                            $localized_string->save();
                            phive('Localizer')->memSet('trophyname.'.$trophy->alias, $language, $text);
                        } else if (!empty($localized_string)) { // LocalizedString exists in DB...
                            if (empty($text)) { // ...text is empty, remove LocalizedString from DB.
                                $localized_string->delete();
                            } else if ($localized_string->value != $text) { // ...text differ, update LocalizedString in DB.
                                $localized_string->value = $text;
                                $localized_string->save();
                                phive('Localizer')->memSet('trophyname.'.$trophy->alias, $language, $text);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                DB::shRollback(true);
                return $app->json(['success' => false, 'error' => $e]);
            }
            DB::shCommit(true);
            return $app->json(['success' => true, 'trophies' => $trophies]);
        }

        return $app->json(['success' => false, 'error', 'Expecting a POST message.']);
    }


    /**
     * @param Application $app
     * @param Request $request
     * @param Trophy $trophy
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteTrophy(Application $app, Request $request, Trophy $trophy)
    {
        // TODO: Make sure user permision check works if you ever enable this.
        // TODO: Log the action with BoAuditLog if you enable this
        return $app->json(['success' => false, 'error' => 'Delete is disabled.']);

        /* // Disabled delete for now.
        DB::shBeginTransaction(true);
        try {
            $result = $trophy->delete();
            if (!$result) {
                DB::rollBack();
                return $app->json(['success' => false]);
            }

            $repo_strings      = new LocalizedStringsRepository($app);
            $localized_strings = $repo_strings->getAllByAlias('trophyname.'.$trophy->alias);
            foreach ($localized_strings as $s) {
                $ok = $s->delete();
                if (!$ok) {
                    DB::rollBack();
                    return $app->json(['success' => false]);
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $app->json(['success' => false, 'error' => $e]);
        }

        DB::shCommit(true);
        return $app->json(['success' => true]);
        */
    }

    private function createGrayScaleImage($file)
    {
        //$image = ImageCreateFromString(file_get_contents($file));
        $image = imagecreatefrompng($file);
        if (!$image) {
            return false;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        /*
         *  In case we work with palette PNG, convert it to true color.
         *  It is needed because IMG_FILTER_GRAYSCALE may create non-grayscale results with palette images.
         *  see https://www.php.net/manual/en/function.imagefilter.php
         */
        imagepalettetotruecolor($image);

        imagefilter($image, IMG_FILTER_GRAYSCALE);

        $grey_file = $file.'_grey.png';
        imagepng($image, $grey_file);
        imagedestroy($image);

        return $grey_file;
    }

    /**
     * @param Application $app
     * @param Request $request
     */
    public function fileUpload(Application $app, Request $request)
    {
        $base_destination = phive('Filer')->getSetting('UPLOAD_PATH').'/events';

        foreach ($_FILES as $file) {

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if ($ext == "csv") {
                $csv = array_map('str_getcsv', file($file['tmp_name']));
                return $app->json(['success' => true, 'csv' => $csv]);
            }

            $grey_file = $this->createGrayScaleImage($file['tmp_name']);
            if ($grey_file === false) {
                continue;
            }

            $color_file_destination = $base_destination.'/'.$file['name'];
            if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
                phive('Dmapi')->uploadPublicFile($file['tmp_name'], 'file_uploads', $file['name'], 'events');
                $grey_file_filename = str_replace('/tmp/', '', $grey_file);
                phive('Dmapi')->uploadPublicFile($grey_file, 'file_uploads', $grey_file_filename, 'events/grey');
            } else {
                if (move_uploaded_file($file['tmp_name'], $color_file_destination)) { // This function does checks regarding uploaded file so we depend on that for the grey image.
                    chmod($color_file_destination, 0777);
                    $grey_file_destination = $base_destination.'/grey/'.$file['name'];
                    rename($grey_file, $grey_file_destination);
                    chmod($grey_file_destination, 0777);
                } else {
                    unlink($grey_file);
                }
            }
        }

        return $app->json(['success' => true, 'files', $_FILES]);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param array $attributes
     * @return array
     * @throws
     */
    private function getTrophyList($request, $app, $attributes)
    {
        $repo           = new TrophiesRepository($app);
        $search_query   = null;
        $archived_count = 0;
        $total_records  = 0;
        $length         = 25;
        $order_column   = "alias";
        $start          = 0;
        $order_dir      = "ASC";

        if ($attributes['sendtobrowse'] != 1) {
            $search_query = $repo->getTrophySearchQuery($request);
        } else {
            $search_query = $repo->getTrophySearchQuery($request, false, $attributes['users_list']);
        }

        $exceptions = [
            'award_type' => 'a.type',
            'award_amount' => 'a.amount',
            'award_description' => 'a.description',
            'award_alt_type' => 'alt.type',
            'award_alt_amount' => 'alt.amount',
            'award_alt_description' => 'alt.description',
        ];

        // Search column-wise too.
        foreach($request->get('columns') as $value) {
            if (strlen($value['search']['value']) > 0) {
                $words = explode(" ", $value['search']['value']);
                foreach($words as $word) {
                    if (array_has($exceptions, $value['data'])) {
                        $search_query->where($exceptions[$value['data']], 'LIKE', "%".$word."%");
                    } else {
                        $search_query->where('t.'.$value['data'], 'LIKE', "%".$word."%");
                    }
                }
            }
        }

        $search = $request->get('search')['value'];
        if (strlen($search) > 0) {
            $s = explode(' ', $search);
            foreach ($s as $q) {
                $search_query->where('t.alias', 'LIKE', "%$q%");
                $search_query->orWhere('t.game_ref', 'LIKE', "%$q%");
                $search_query->orWhere('t.category', 'LIKE', "%$q%");
            }
        }

        $non_archived_count = DB::table(DB::raw("({$search_query->toSql()}) as a"))
            ->mergeBindings($search_query)
            ->count();

        if ($attributes['sendtobrowse'] != 1 && $app['vs.config']['archive.db.support'] && $repo->not_use_archived == false) {
            $archived_search_query = $repo->getTrophySearchQuery($request, true);
            try {
                $archived_count = DB::connection('videoslots_archived')->table(DB::raw("({$archived_search_query->toSql()}) as b"))
                    ->mergeBindings($search_query)
                    ->count();
            } catch (\Exception $e) {
            }
            $total_records = $non_archived_count + $archived_count;
        } else {
            $total_records = $non_archived_count;
        }

        if ($attributes['ajax'] == true) {
            $start        = $request->get('start');
            $length       = $request->get('length');
            $order        = $request->get('order')[0];
            $order_column = $request->get('columns')[$order['column']]['data'];
            $order_dir    = $order['dir'];
        } else {
            $length = $total_records < $attributes['length'] ? $total_records : $attributes['length'] ;
        }

        if ($attributes['sendtobrowse'] !== 1 && $app['vs.config']['archive.db.support'] && $archived_count > 0) {
            $non_archived_records     = $search_query->orderBy($order_column, $order_dir)->limit($length)->skip($start)->get();
            $non_archived_slice_count = count($non_archived_records);
            if ($non_archived_slice_count < $length) {
                $next_length = $length - $non_archived_slice_count;
                $next_start  = $start - $non_archived_count;
                if ($next_start < 0) {
                    $next_start = 0;
                }
                $archived_records = $archived_search_query->orderBy($order_column, $order_dir)->limit($next_length)->skip($next_start)->get();
                if ($non_archived_slice_count > 0) {
                    $data = array_merge($non_archived_records, $archived_records);
                } else {
                    $data = $archived_records;
                }
            } else {
                $data = $non_archived_records;
            }
        } else {
            $data = $search_query->orderBy($order_column, $order_dir)->limit($length)->skip($start)->get();
        }

        return [
            "draw"            => intval($request->get('draw')),
            "recordsTotal"    => intval($total_records),
            "recordsFiltered" => intval($total_records),
            "data"            => $data
        ];
    }


    /**
     * @param Application $app
     * @param Request $request
     */
    public function searchTrophy(Application $app, Request $request)
    {
        return $app->json($this->getTrophyList($request, $app, ['ajax' => true]));
    }

    /**
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return mixed
     */
    public function listUserTrophies(Application $app, User $user, Request $request)
    {
        $repo = new TrophiesRepository($app);
        $date_range = DateHelper::validateDateRange($request, 6);
        $sort = ['column' => 3, 'type' => "desc", 'start_date' => $date_range['start_date'], 'end_date' => $date_range['end_date']];

        $user_trophy_events = $repo->getTrophiesList($user, $date_range);

        foreach ($user_trophy_events as $index => &$te) {
            $te = (array)$te;
            $repo->setTrophyImage($te);
            $user_trophy_events[$index] = $te;
        }

        //todo check this function
        //$categories = phive('Trophy')->getCategories(cu($user->getKey()), 'category', '', 'trophy');
        $categories = $repo->getCategories();
        //$trophies = Trophy::where('category', 'activity')->get();
        $trophies = $repo->getPerCategory($user, 'activity');

        return $app['blade']->view()->make('admin.user.trophies', compact('app', 'user', 'sort', 'categories', 'trophies', 'user_trophy_events'))->render();
    }

    public function listNotActivatedRewards(Application $app, User $user, Request $request)
    {
        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_EMPTY);
        $repo = new TrophiesRepository($app);
        $rewards_not_activated = $repo->getNotActivatedRewardsList($user, $date_range, $request);

        $sort = ['column' => 0, 'type' => "desc"];
        return $app['blade']->view()->make('admin.user.bonus.not-activated', compact('app', 'user', 'sort', 'rewards_not_activated', 'date_range'))->render();
    }

    public function listRewardHistory(Application $app, User $user, Request $request)
    {
        $date_range = DateHelper::validateDateRange($request, 8);
        $repo = new TrophiesRepository($app);
        $trophyaward_repo = new TrophyAwardsRepository($app);

        $reward_history = $repo->getRewardHistoryList($user, $date_range);

        $legacy_user = cu($user->id);

        foreach ($reward_history as $index => &$reward) {
            $reward = (array)$reward; // Needs to be array for phive.
            $trophyaward_repo->setTrophyAwardImage($reward, $legacy_user);
            $reward_history[$index] = $reward;
        }

        $sort = ['column' => 2, 'type' => "desc", 'start_date' => $date_range['start_date'], 'end_date' => $date_range['end_date']];
        return $app['blade']->view()->make('admin.user.bonus.reward-history', compact('app', 'user', 'sort', 'reward_history'))->render();
    }

    /**
     * Add new trophy to an User.
     * todo port phive functions
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function addTrophyToUser(Application $app, User $user, Request $request)
    {
        if (!$request->isMethod('POST')) {
            $app->abort(405);
        }

        $repo = new TrophiesRepository($app);
        if (!is_numeric($request->get('trophy-id'))) {
            $app['flash']->add('warning', "Trophy id is not valid.");
            return new RedirectResponse($request->headers->get('referer'));
        }

        $legacy_user = cu($user->username);
        $legacy_trophy = phive('Trophy')->get($request->get('trophy-id'));

        if (phive('Trophy')->awardTrophy($legacy_trophy, $legacy_user->data) !== false) {
            ActionRepository::logAction($user, "Trophy {$legacy_trophy['alias']} with ID {$legacy_trophy['id']} manually added.", 'add_trophy');
            $app['flash']->add('success', "Trophy successfully added to the customer.");
        } else {
            $msg = $repo->getAddTrophyValidationMessage($user, $request);
            $app['flash']->add('danger', "There was an error and the trophy has not been added to the customer. Reason: $msg");
        }

        return new RedirectResponse($request->headers->get('referer'));
    }

    public function getTrophiesForCategory(Application $app, User $user, Request $request, $category)
    {
        $repo = new TrophiesRepository($app);
        return $repo->getPerCategory($user, $category);
    }

    public function addReward(Application $app, User $user, Request $request)
    {
        $repo = new TrophiesRepository($app);

        if ($request->isMethod('POST')) {
            $res = $repo->addReward($request, $user);
            if ($res) {
                return $app->json(['success' => true, 'message' => $res]);
            } else {
                return $app->json(['success' => false, 'message' => 'Unexpected error adding the reward']);
            }
        } elseif ($request->isMethod('GET')) {
            if ($request->get('list') == 1) {
                $type = $request->get('type');
                if ($request->isXmlHttpRequest()) {
                    $search_value = $request->get('search') ?? '';
                    return $app->json($repo->getRewardsByTypeSelect($type, $search_value));
                }
                return $repo->getRewardsByType($type);
            }
            $rewards_types = $repo->getRewardsTypes();
            return $app['blade']->view()->make('admin.user.bonus.add-reward', compact('app', 'user', 'rewards_types'))->render();

        } else {
            return new RedirectResponse($request->headers->get('referer'));
        }
    }

    /**
     * Delete Award Entry
     * todo improve the phive exception
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteAwardEntry(Application $app, User $user, Request $request)
    {
        try {
            phive('Trophy')->removeAward($request->get('award_id'), $user->getKey());
            ActionRepository::logAction($user, "Reward with ID {$request->get('award_id')} manually removed.", 'remove_reward');
        } catch (\Exception $e) {
            $app->abort(500, "Phive error");
        }

        return new RedirectResponse($request->headers->get('referer'));
    }

}
