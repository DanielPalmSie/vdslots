<?php 
use App\Extensions\Database\Seeder\SeederTranslation;

class AddLocalizedStringsForSourceOfWealthDeclaration extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'source.of.funds.form.title' => 'Declaration of Source Wealth',
            'source.of.funds.header' => 'As part of your documentary requirements to have an account with Videoslots ltd, regulated under EU law, 
                                        understand that I am required to declare the origin of funds that I have been depositing into the account including future deposits.',
            'your.annual.income' => 'Your average annual net income (provide details)',
            'confirm.funds.are.legit' => 'I further confirm that these funds are derived from legitimate sources as stated above and 
                                        that I will also provide that required evidence of the I declare the foregoing to be true.',
            'footer.address' => 'Videoslots Ltd | Level 2 The Space | Alfred Graig Street | PTA 1313 Pieta | Malta'
        ]
    ];
}