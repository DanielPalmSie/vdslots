<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class LocalizedStringForGeoComplyNDBPopup extends SeederTranslation
{
    /* Example ['lang' => ['alias1' => 'value1',...]]*/
    protected array $data = [
        'en' => [
            'geocomply.inform.popup.title' => "GeoComply",
            'geocomply.inform.text.title' => "GeoComply is now in use.",
            'geocomply.inform.text.body' => "Lorem ipsum dolor sit amet consectetur. Enim consectetur et morbi fames donec tristique vulputate sem amet. Sit mi orci at quisque elementum laoreet gravida consequat. Volutpat pellentesque orci pellentesque nunc viverra. Amet aliquam sit potenti laoreet posuere mauris ut imperdiet tellus.",
        ]
    ];
}
