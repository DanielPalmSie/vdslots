<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddContentForRg76Popup extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'RG76.rg.info.description.html' => 'Congratulations on your big win!  <br/>
You\'ve won over {{multiplier}}x your bet. It might be a good time to take a break and enjoy your success.  <br/>
Remember to always play within comfortable limits to keep the fun going.',
            'RG76.user.comment' => 'RG76 Flag was triggered. User had a big win of x{{multiplier}}+ their bet. An interaction via popup in gameplay was made to congratulate the customer on his win. We recommended him to take a break and review his limits to ensure safe and enjoyable gaming experience.'
        ]
    ];
}
