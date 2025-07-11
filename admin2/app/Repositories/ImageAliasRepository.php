<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use App\Extensions\Database\FManager as DB;
use Silex\Application;
use App\Models\ImageData;
use App\Models\ImageAlias;
use App\Models\Mails;
use App\Models\MailsConnections;
use App\Models\LocalizedStrings;
use App\Models\LocalizedStringsConnection;
use App\Models\ImageAliasConnection;
use Webpatser\Uuid\Uuid;


class ImageAliasRepository
{
    /** @var Application $app */
    protected $app;


    /**
     *
     * @return array
     */
    public function getAllBonusCodes()
    {
        $all_bonus_codes = DB::shsSelect('bonus_types', "SELECT bonus_code FROM bonus_types WHERE bonus_code != '' GROUP BY bonus_code");

        $result_array = json_decode(json_encode($all_bonus_codes), true);

        return $result_array;
    }

    /**
     * Get all bonus codes that are connected to an image_alias
     *
     * @param string $image_alias
     * @return array
     */
    public function getConnectedBonusCodes($image_alias)
    {

        $bonus_codes = DB::shsSelect('image_aliases_connections', 
                "SELECT bonus_code FROM image_aliases_connections WHERE image_alias = :image_alias",
                ['image_alias' => $image_alias]);

        // add selected to each item
        $bonus_codes_array = [];
        foreach ($bonus_codes as $key => $bonus_code) {
            $bonus_codes_array[$key]['bonus_code'] = $bonus_code->bonus_code;
            $bonus_codes_array[$key]['selected'] = 'selected';
        }

        return $bonus_codes_array;
    }

    /**
     * Get all available bonus codes for use in Dualselect box
     *
     * @param string $alias
     * @param string $for_type Can be images, emails, or localized_strings
     * @return array
     */
    public function getBonusCodes($alias, $for_type)
    {
        $all_bonus_codes = $this->getAllBonusCodes();

        switch ($for_type) {
            case 'images':
                $connected_bonus_codes = $this->getConnectedBonusCodes($alias);
                break;

            case 'emails':
                $connected_bonus_codes = $this->getBonusCodesConnectedToEmails($alias);
                break;

            case 'localized_strings':
                $connected_bonus_codes = $this->getBonusCodesConnectedToLocalizedStrings($alias);
                break;

            default:
                break;
        }

//        $connected_bonus_codes = $this->getConnectedBonusCodes($image_alias);

        // combine the 2 arrays
        foreach ($all_bonus_codes as $key => $bonus_code) {

            $found = array_search($bonus_code['bonus_code'], array_column($connected_bonus_codes, 'bonus_code'));
            if ($found !== false) {
                unset($all_bonus_codes[$key]);
            }
        }

        $bonus_codes = array_merge($all_bonus_codes, $connected_bonus_codes);

        return $bonus_codes;
    }

    /**
     *
     * @param string $email_alias
     * @return array
     */
//    public function getBonusCodesForEmails($email_alias)
//    {
//        $all_bonus_codes = $this->getAllBonusCodes();
//
//        $connected_bonus_codes = $this->getBonusCodesConnectedToEmails($email_alias);
//
//        // combine the 2 arrays
//        foreach ($all_bonus_codes as $key => $bonus_code) {
//
//            $found = array_search($bonus_code['bonus_code'], array_column($connected_bonus_codes, 'bonus_code'));
//            if($found !== false) {
//                unset($all_bonus_codes[$key]);
//            }
//        }
//
//        $bonus_codes = array_merge($all_bonus_codes, $connected_bonus_codes);
//
//        return $bonus_codes;
//    }

    /**
     *
     * @param string $email_alias
     * @return array
     */
    public function getBonusCodesConnectedToEmails($email_alias)
    {
        $email_alias = mysqli_real_escape_string(mysqli_init(),$email_alias); 

        $bonus_codes = DB::shsSelect('mails_connections', "SELECT bonus_code FROM mails_connections WHERE mail_trigger_target = '{$email_alias}'");

        // add selected to each item
        $bonus_codes_array = [];
        foreach ($bonus_codes as $key => $bonus_code) {
            $bonus_codes_array[$key]['bonus_code'] = $bonus_code->bonus_code;
            $bonus_codes_array[$key]['selected'] = 'selected';
        }

        return $bonus_codes_array;

    }

    /**
     *
     * @param string $pagealias
     * @return array
     */
    public function getBannerAliases($pagealias)
    {
        $search_string = "banner.{$pagealias}%";
        $aliases = DB::shsSelect('image_aliases', 
                "SELECT * FROM image_aliases WHERE alias LIKE :search_string AND alias NOT LIKE '%default'",
                ['search_string' => $search_string]);

        return $aliases;
    }

    /**
     *
     * @param string $pagealias
     * @return array
     */
    public function getAllBannerAliases($pagealias)
    {
        $search_string = "banner.{$pagealias}%";
        $aliases = DB::shsSelect('image_aliases', 
                "SELECT * FROM image_aliases WHERE alias LIKE :search_string", 
                ['search_string' => $search_string]);

        return $aliases;
    }

    /**
     *
     * @param string $pagealias
     * @param bool $show_default
     * @return array
     */
    public function getAllEmailAliases($pagealias, $show_default = true)
    {

        $pagealias = mysqli_real_escape_string(mysqli_init(),$pagealias);

        if ($show_default) {
            $email_aliases = DB::shsSelect('mail_trigger', 
                    "SELECT * FROM mails WHERE mail_trigger LIKE :search_string",
                    ['search_string' => $search_string]);
        } else {
            $email_aliases = DB::shsSelect('mails', 
                            "SELECT * FROM mails WHERE mail_trigger LIKE :search_string AND mail_trigger != :pagealias",
                            ['search_string' => $search_string, 'pagealias' => $pagealias]);
        }

        foreach ($email_aliases as $email_alias) {
            $email_alias->alias = $email_alias->mail_trigger;
        }

        return $email_aliases;
    }


    /**
     * Get all aliasses for the Terms and conditions page
     *
     * @param string $pagealias
     * @return array
     */
    public function getAllTcoAliases($pagealias, $show_default = true)
    {
        $search_string = "bannertext.{$pagealias}%";
        if ($show_default) {
            $aliases = DB::shsSelect('localized_strings', 
                       "SELECT * FROM localized_strings WHERE alias LIKE :search_string GROUP BY alias",
                       ['search_string' => $search_string]);
        } else {
            $aliases = DB::shsSelect('localized_strings', 
                       "SELECT * FROM localized_strings WHERE alias LIKE :search_string AND alias NOT LIKE '%default%' GROUP BY alias",
                       ['search_string' => $search_string]);
        }

        return $aliases;
    }

    /**
     *
     * @return array
     */
    public function getAllLanguages()
    {
        $languages = DB::shsSelect('languages', "SELECT language FROM languages");

        return $languages;
    }

    /**
     *
     * @param int $image_id
     * @param string $image_alias
     * @return array
     */
    public function getAllImagesForID($image_id, $image_alias, $dimensions = [])
    {
        $images = ImageData::where('image_id', '=', $image_id)->get()->toArray();
        $leftover_images = [];

        if(empty($images) && !empty($dimensions)) {
            $width   = $dimensions['width'];
            $height  = $dimensions['height'];
        } else {
            $width   = $images[0]['width'];
            $height  = $images[0]['height'];
        }

        // for welcomebonus and freecash-default, we need to show a grid with all possible language-currency combinations
        // Second condition: this means the combination for freecash with a bonus code is only showing the languages
        if (strpos($image_alias, 'welcomebonus') !== false
            || (strpos($image_alias, 'freecash') !== false && strpos($image_alias, 'default') !== false)) {
            $combinations = DB::shsSelect('languages', "SELECT l.language AS lang, c.code AS currency FROM languages l CROSS JOIN currencies c");

            // convert to normal array
            $combinations = json_decode(json_encode($combinations), true);

            // Add 'any' as another possible language
            $currencies = DB::shsSelect('currencies', "SELECT code AS currency FROM currencies");
            foreach ($currencies as $currency) {
                $combinations[] = ['lang' => 'any', 'currency' => $currency->currency];
            }

            // populate combination array with image data
            foreach ($combinations as &$combination) {
                foreach ($images as $image) {
                    if ($image['currency'] == $combination['currency'] && $image['lang'] == $combination['lang']) {
                        $combination = $image;
                    }
                }

                if (empty($combination['filename'])) {
                    $combination['filename'] = "missing_banner_placeholder_{$width}_{$height}.jpg";
                    // use width and height of the image
                    $combination['width']   = $width;
                    $combination['height']  = $height;
                }
            }

            $images = $combinations;
        }

        // for freespins, we only show the languages (Currencies are not relevant)
        if (strpos($image_alias, 'freespins') !== false
            || (strpos($image_alias, 'freecash') !== false && strpos($image_alias, 'default') === false)) {
            $combinations = DB::shsSelect('languages', "SELECT language AS lang FROM languages");

            // convert to normal array
            $combinations = json_decode(json_encode($combinations), true);

            // Add 'any' as another possible language
            $combinations[] = ['lang' => 'any'];

            // populate combination array with image data
            foreach ($combinations as &$combination) {
                foreach ($images as $key => $image) {

                    if ($image['lang'] == $combination['lang']) {
                        $combination = $image;
                        unset($images[$key]);
                    }
                }

                if (empty($combination['filename'])) {
                    $combination['filename'] = "missing_banner_placeholder_{$width}_{$height}.jpg";
                    // use width and height of the image
                    $combination['width']   = $width;
                    $combination['height']  = $height;
                }
            }

            $leftover_images = array_values($images);

            $images = $combinations;
        }

        return ['images' => $images, 'leftover_images' => $leftover_images];
    }

    /**
     * Connect (or disconnect) bonus codes to banners
     *
     * @param array $bonus_codes
     * @param string $image_alias
     *
     * @return bool
     */
    public function connectBonusCodesToBanners($bonus_codes, $image_alias)
    {
        $connected_bonus_codes = $this->getConnectedBonusCodes($image_alias);

        // the posted bonus codes are the only ones that should be connected.
        // So, if some bonus codes are connected that are not posted, they should be removed.
        foreach ($connected_bonus_codes as $key => $connected_bonus_code) {
            if (!in_array($connected_bonus_code['bonus_code'], $bonus_codes)) {
                // remove the bonus code connection
                if (!ImageAliasConnection::where('image_alias', $image_alias)->where('bonus_code', $connected_bonus_code['bonus_code'])->delete()) {
                    return false;
                }
            }
        }

        // create image aliasses for the posted bonus codes, if they do not exist already
        foreach ($bonus_codes as $key => $bonus_code) {
            if (empty(ImageAliasConnection::where('image_alias', $image_alias)->where('bonus_code', $bonus_code)->first())) {
                if (empty(ImageAliasConnection::create(['image_alias' => $image_alias, 'bonus_code' => $bonus_code]))) {
                    return false;
                }

            }
        }

        return true;
    }

    /**
     *
     * @param string $email_alias
     * @param array $bonus_codes
     * @return boolean
     */
    public function connectBonusCodesToEmails($email_alias, $bonus_codes, $pagealias)
    {
        $connected_bonus_codes = $this->getBonusCodesConnectedToEmails($email_alias);

        // the posted bonus codes are the only ones that should be connected.
        // So, if some bonus codes are connected that are not posted, their aliasses should be removed.
        foreach ($connected_bonus_codes as $key => $connected_bonus_code) {
            if (!in_array($connected_bonus_code['bonus_code'], $bonus_codes)) {
                // remove the connected mail trigger
                if (!MailsConnections::where('mail_trigger_target', $email_alias)->where('bonus_code', $connected_bonus_code['bonus_code'])->delete()) {
                    return false;
                }
            }
        }

        // create mail_trigger_connections for the posted bonus codes, if they do not exist already
        foreach ($bonus_codes as $key => $bonus_code) {

            if (count(MailsConnections::where('mail_trigger_target', $email_alias)->where('bonus_code', $bonus_code)->get()) === 0) {
                if (empty(MailsConnections::create(['mail_trigger_target' => $email_alias, 'bonus_code' => $bonus_code]))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     *
     * @param string $pagealias
     * @param string $alias Can be image_alias or localized_string_alias for pages that do not have any banners
     * @param array $bonus_codes
     * @return boolean
     */
    public function connectBonusCodesToLocalizedStrings($pagealias, $alias, $bonus_codes)
    {
        $target_alias = $alias;
        if (strpos($alias, 'bannertext') === false) {
            $target_alias = str_replace('banner', 'bannertext', $alias) . '.html';
        }

        $connected_bonus_codes = $this->getBonusCodesConnectedToLocalizedStrings($target_alias);

        // the posted bonus codes are the only ones that should be connected.
        // So, if some bonus codes are connected that are not posted, they should be removed.
        foreach ($connected_bonus_codes as $connected_bonus_code) {
            if (!in_array($connected_bonus_code['bonus_code'], $bonus_codes)) {
                // remove the connected alias
                if (!LocalizedStringsConnection::where('target_alias', $target_alias)->where('bonus_code', $connected_bonus_code['bonus_code'])->delete()) {
                    return false;
                }
            }
        }

        // create records for the posted bonus codes, if they do not exist already
        foreach ($bonus_codes as $bonus_code) {
            if (empty(LocalizedStringsConnection::where('target_alias', $target_alias)->where('bonus_code', $bonus_code)->first())) {
                if (empty(LocalizedStringsConnection::create(['target_alias' => $target_alias, 'bonus_code' => $bonus_code]))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     *
     * @param string $target_alias
     * @return array
     */
    public function getBonusCodesConnectedToLocalizedStrings($target_alias)
    {
        $localized_string_connections = LocalizedStringsConnection::where('target_alias', "{$target_alias}")->get();

        $bonus_codes = [];
        foreach ($localized_string_connections as $key => $localized_string_connection) {
            $bonus_codes[$key]['bonus_code'] = $localized_string_connection->bonus_code;
            $bonus_codes[$key]['selected'] = 'selected';
        }

        return $bonus_codes;

//        $bonus_codes = DB::select("SELECT bonus_code FROM image_aliases_connections WHERE image_alias = '{$image_alias}'");
//
//        // add selected to each item
//        $bonus_codes_array = [];
//        foreach($bonus_codes as $key => $bonus_code) {
//            $bonus_codes_array[$key]['bonus_code'] = $bonus_code->bonus_code;
//            $bonus_codes_array[$key]['selected'] = 'selected';
//        }
//
//        return $bonus_codes_array;
    }

    /**
     * Create new localized strings for bonus codes in all languages
     * for pages that have promotion banners
     *
     * @param Application $app
     * @param string $new_image_alias
     *
     * @return void
     */
    public function createLocalizedStringsForBonusCodes(Application $app, $new_image_alias)
    {
        $alias = str_replace('banner', 'bannertext', $new_image_alias) . '.html';

        $languages = $this->getAllLanguages();
        foreach ($languages as $language) {

            $localized_string = new LocalizedStrings();
            $localized_string->alias     = $alias;
            $localized_string->language  = $language->language;
            $localized_string->value     = '';

            if(!$localized_string->save()) {
                $app['flash']->add('danger', "Unable to create a new localized string for {$new_image_alias} for language {$language->language} in database");
            }
        }
    }

    /**
     * Creates a new images alias
     * 
     * This function has to be called during the uploading of images,
     * because we are saving the next auto ID of image_data in the table
     * even before any records are inserted in image_data.
     *
     * If this causes problems in the future when multiple admins are
     * uploading images at the same time, then we have no choice but
     * to alter both tables and make image_id an auto incremented
     * column in image_aliases (instead of the other way around).
     *
     * @param int $image_alias
     * @return boolean
     */
    public function createNewImageAlias($image_id, $image_alias)
    {
//        // First we need to determine the image_id, which is the next auto-increment value from table image_data
//        $image_id = $this->getNextAutoID('image_data');

        // save the new image_alias
        $image_alias_model            = new ImageAlias();
        $image_alias_model->alias     = $image_alias;
        $image_alias_model->image_id  = $image_id;

        return $image_alias_model->save();
    }

    /**
     *
     * @param string $alias
     * @return boolean
     */
    public function getImageID($alias)
    {
        $image_alias = ImageAlias::find($alias);

        if (!empty($image_alias->image_id)) {
            return $image_alias->image_id;
        }

        return false;
    }

    /**
     * Gets all pages where banners are shown
     *
     * @return array
     */
    public function getBannerPages()
    {
        // For now harcode the pages
        $pages = [
            [
                'name'          => 'Registration',
                'alias'         => 'registration',
                'type_of_page'  => 'only_banners',
                'width'         => '362',
                'height'        => '180'
            ],
            [
                'name'          => 'Homepage',
                'alias'         => 'homepage',
                'type_of_page'  => 'only_banners',
                'width'         => '340',
                'height'        => '133'
            ],
            [
                'name'          => 'Freespins',
                'alias'         => 'freespins',
                'type_of_page'  => 'has_text',
                'has_text'      => true,
                'width'         => '961',
                'height'        => '307'
            ],
            [
                'name'          => 'Welcomebonus',
                'alias'         => 'welcomebonus',
                'type_of_page'  => 'has_text',
                'has_text'      => true,
                'width'         => '961',
                'height'        => '307'
            ],
            [
                'name'          => 'Mobile homepage',
                'alias'         => 'mobilehomepage',
                'type_of_page'  => 'has_text',
                'has_text'      => true,
                'width'         => '780',
                'height'        => '380'   // shown as 400x195
            ],
            [
                // This page does not have any banners
                'name'          => 'Terms and conditions General',
                'alias'         => 'termsconditionsgeneral',
                'type_of_page'  => 'only_text',
                'has_text'      => true,
                'only_text'     => true,
                'width'         => '0',
                'height'        => '0'
            ],
            [
                // This page does not have any banners
                'name'          => 'Terms and conditions MGA Specific',
                'alias'         => 'termsconditionsmgaspecific',
                'type_of_page'  => 'only_text',
                'has_text'      => true,
                'only_text'     => true,
                'width'         => '0',
                'height'        => '0'
            ],
            [
                'name'          => 'Email welcomemail',
                'alias'         => 'welcome.mail',
                'type_of_page'  => 'is_email',
                'is_email'      => true,
                'width'         => '765',
                'height'        => '647'
            ],
            [
                'name'          => 'Email No deposit weekly',
                'alias'         => 'no-deposit-weekly',  // TODO: not sure what to use, nodeposit-reminder OR no-deposit-weekly OR no-deposit-weekly2 (OR ALL ??)
                'type_of_page'  => 'is_email',
                'is_email'      => true,
                'width'         => '765',
                'height'        => '647'
            ],
            // TODO: enable Gamereview and Game suppliers when those pages are live
//            [
//                'name' => 'Gamereview',
//                'alias' => 'gamereview',
//                'width' => '250',
//                'height' => '115'
//            ],
//            [
//                'name' => 'Game suppliers',
//                'alias' => 'gamesuppliers',
//                'width' => '250',
//                'height' => '98'
//            ],
        ];

        return $pages;
    }

    /**
     *
     * @param string $page_alias
     * @return string
     */
    public function getTypeOfPage($page_alias)
    {
        $pages = $this->getBannerPages();

        foreach ($pages as $page) {
            if ($page['alias'] == $page_alias) {
                return $page['type_of_page'];
            }
        }
    }

    /**
     *
     * @param array $pages
     * @param string $pagealias
     * @return boolean
     */
    public static function pageHasText($pages, $pagealias)
    {
        $has_text = false;
        foreach ($pages as $page) {
            if ($page['alias'] == $pagealias && !empty($page['has_text'])) {
                $has_text = true;
            }
        }

        return $has_text;
    }

    /**
     *
     * @param array $pages
     * @param string $pagealias
     * @return boolean
     */
    public static function pageIsEmail($pages, $pagealias)
    {
        $is_email = false;
        foreach ($pages as $page) {
            if ($page['alias'] == $pagealias && !empty($page['is_email'])) {
                $is_email = true;
            }
        }

        return $is_email;
    }


    public function pageHasTextOnly($pages, $pagealias)
    {
        $only_text = false;
        foreach ($pages as $page) {
            if ($page['alias'] == $pagealias && !empty($page['only_text'])) {
                $only_text = true;
            }
        }

        return $only_text;
    }

    /**
     *
     * @param array $pages
     * @param string $pagealias
     * @return array
     */
    public function getImageDimensionsForPage($pages, $pagealias)
    {
        $dimensions = [];
        foreach ($pages as $page) {
            if ($page['alias'] == $pagealias) {
                $dimensions['width']  = $page['width'];
                $dimensions['height'] = $page['height'];
            }
        }

        return $dimensions;
    }

    /**
     * Gets the original dimensions for an image_id
     *
     * @param int $image_id
     * @return array
     */
    public static function getDimensionsForImageID($image_id)
    {
        $image_data = ImageData::where('image_id', $image_id)
                ->where('original', 1)
                ->first();

        $dimensions = [];
        if(!empty($image_data)) {
            $dimensions['width']  = $image_data->width;
            $dimensions['height'] = $image_data->height;
        }

        return $dimensions;
    }

    /**
     *
     * @param int $file_id
     * @return boolean
     */
    public function deleteImage($file_id)
    {
        $image = ImageData::where('id', $file_id)
            ->first();

        if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
            // remove physical image from dmapi
            $result = phive('Dmapi')->deletePublicFile('image_uploads', $image->filename, '');
            if(empty($result['success'])) {
                return false;
            }
        } else {
            // remove physical image from local storage
            if (!unlink(getcwd() . '/image_uploads/' . $image->filename)) {
                return false;
            }
        }

        if ($image->delete()) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Gets a list of image_aliasses starting with $search_string in a format for Select2
     *
     * @param string $search_string
     *
     * @return array
     */
    public function getImageAliasses($search_string)
    {
        $result = DB::select("SELECT alias, image_id FROM image_aliases
            WHERE alias LIKE :search_string
            AND alias NOT LIKE 'banner.%'
            ", ['search_string' => '%'.$search_string.'%']);

        $image_aliasses = [];
        foreach ($result as $key => $image_alias) {
            $image_aliasses[] = ['id' => $image_alias->image_id, 'text' => $image_alias->alias];
        }

        return ['results' => $image_aliasses, 'more' => false];
    }

    /**
     * Getting language and currency from the filename
     * Filenames need to be in this fashion: some-description_EUR_EN.ext
     *
     * @param  type  $filename
     * @return array 
     */
    public function parseImageFilename($filename)
    {
        $string     = substr_replace($filename, '', strrpos($filename, '.'));
        $language   = trim(substr($string, strrpos($filename, '_')), '_');
        $string     = rtrim(rtrim($string, $language), '_');
        $currency   = strtoupper(trim(substr($string, strrpos($string, '_')), '_'));

        $iso_currencies = [];
        $currencies = DB::select("SELECT `code` FROM `currencies`");
        foreach($currencies as $currency_from_db) {
            $iso_currencies[] = $currency_from_db->code;
        }

        if (!in_array($currency, $iso_currencies)) {
            $currency = phive('Currencer')->baseCur();
        }

        $all_languages = [];
        $languages = DB::select("SELECT language FROM languages");
        foreach($languages as $language_from_db) {
            $all_languages[] = $language_from_db->language;
        }
        $all_languages = array_merge($all_languages, lic('getBannerJurisdictions') ?? []);

        $language = strtolower($language);
        if (!in_array($language, $all_languages)) {
            $language = 'any';
        }

        $data = [];
        $data['language'] = $language;
        $data['currency'] = $currency;

        return $data;
    }

    /**
     * Generates a unique filename with the same extension
     *
     * @param string $filename
     * @return string
     */
    public function generateUniqueFilename($filename)
    {
        $uuid = Uuid::generate(4);
        
        $extension = substr($filename, strrpos($filename, '.'));

        $new_filename = $uuid . $extension;

        return $new_filename;
    }


    public function saveImageData($filename_data, $new_filename, $old_filename, $image_id, $height = '', $width = '')
    {
        $image = ImageData::where('image_id', $image_id)
                ->where('lang', $filename_data['language'])
                ->where('currency', $filename_data['currency'])
                ->first();

        if(empty($image)) {
            $image = new ImageData();
            $image->image_id = $image_id;
            $image->lang     = $filename_data['language'];
            $image->currency = $filename_data['currency'];
            $image->original = 1;

        } else {
            $old_filename = $image->filename;
        }

        if(!empty($height) && !empty($width)) {
            $image->width  = $width;
            $image->height = $height;
        } else {
            // Get width and height from another record with same image_id.
            $similar_image = ImageData::where('image_id', $image_id)
                ->where('original', 1)
                ->first();

            if(!empty($similar_image)) {
                $image->width  = $similar_image->width;
                $image->height = $similar_image->height;
            } else {
                // we cannot determine the size of the image from the data in the database.
                // We have no choice but to determine the size from the image that was uploaded. (Not sure if this will ever happen)

                // NOTE: for image_uploads we are already getting the width and height from the uploaded images
                // We needed that after deleting all images for an alias.
            }
        }

        $image->filename = $new_filename;

        if($image->save()) {
            if(!empty($old_filename)) {
                $this->deleteOldImageFile('image_uploads', $old_filename);
            }
            return true;
        }

        return false;
    }

    /**
     * Deletes the old file from the file system or the external image service. 
     *
     * @param string $base_destination
     * @param string $folder
     * @param string $old_filename
     * @param string $subfolder
     */
    public function deleteOldImageFile($folder, $old_filename)
    {
        if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
            phive('Dmapi')->deletePublicFile($folder, $old_filename, '');
        } else {
            // remove old image from filesystem
            $base_destination = phive('ImageHandler')->getSetting('UPLOAD_PATH');
            unlink($base_destination.'/'.$old_filename);
        }
    }

    /**
     * Validate an uploaded image
     *
     * @param array $file
     * @return boolean
     */
    public function validateUploadedImage($file)
    {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

        $info = pathinfo($file['name']);
        $extension = strtolower($info['extension']);

        if(!in_array(strtolower($extension), $allowed_extensions)) {
            return false;
        }

        $mime_type = mime_content_type($file['tmp_name']);

        if(!in_array($mime_type, $allowed_mime_types)) {
            return false;
        }

        return true;
    }

    /**
     * Get the next auto_increment for a table
     *
     * @param string $table
     * @return int
     */
    public function getNextAutoID($table)
    {
//        $table = mysqli_real_escape_string($table);  // returns ''
        $query = "SHOW TABLE STATUS LIKE '{$table}'";
        $result = DB::select($query);

        return $result[0]->Auto_increment;
    }


    public function getImageBoxes($page_id)
    {
        $query = "SELECT b.box_id, b.box_class, ba.attribute_value AS banner_num FROM boxes b
                  LEFT JOIN boxes_attributes ba ON b.box_id = ba.box_id AND ba.attribute_name = 'banner_num'
                  WHERE b.page_id = {$page_id}
                  AND b.box_class IN ('FullImageBox', 'DynamicImageBox', 'JsBannerRotatorBox')";

        $image_boxes = DB::shsSelect('boxes', $query);

        foreach ($image_boxes as $value) {
            switch ($value->box_class) {
                case 'FullImageBox':
                    $value->aliasses = [
                        'fullimagebox.'.$value->box_id
                    ];
                    break;
                
                case 'DynamicImageBox':
                    $value->aliasses = [
                        'fullimage.loggedin.'.$value->box_id,
                        'fullimage.loggedout.'.$value->box_id,
                    ];
                    break;

                case 'JsBannerRotatorBox':
                    $value->aliasses = [];
                    for($i=1; $i<=$value->banner_num; $i++) {
                        $value->aliasses[] = 'top.media.'.$i.'.'.$value->box_id;
                    }
                    break;
                
                default:
                    break;
            }
            
        }

        return $image_boxes;
    }

}
