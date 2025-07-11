<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForIdScanInstructions extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'idscan.instructions' => '<div class="title">Instructions : </div>' .
                                        '<ul>' .
                                            '<li>Identity document should be laid on a flat surface best on a dark surface.</li>' .
                                            '<li>Take photo as directly above the ID as possible.</li>' .
                                            '<li>Avoid glares, shadows or using flash.</li>' .
                                        '</ul>'
            ]
        ];
}
