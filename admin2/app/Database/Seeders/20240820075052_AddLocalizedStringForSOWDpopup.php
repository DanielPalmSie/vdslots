<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringForSOWDpopup extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'sowd_popup.form.industry_label' => "Industry",
            'job.title' => "Job Title",
            'footer.address' => 'Videoslots Ltd | Level 2 The Space | Alfred Graig Street | PTA 1313 Pieta | Malta'
        ]
    ];
}
