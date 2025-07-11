<?= "<?php ";?>

use App\Extensions\Database\Seeder\SeederTranslation;

class <?= $className ?> extends SeederTranslation
{
    private const TRIGGER_NAME = "<?= $this->container['trigger_name'] ?>";

    protected array $data = [
        'en' => [
            "mail." . self::TRIGGER_NAME . ".popup.ignored.subject" => '',
            "mail." . self::TRIGGER_NAME . ".popup.ignored.content" => '',
        ],
    ];
}
