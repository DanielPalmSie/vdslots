<?= "<?php ";?>

use App\Extensions\Database\Seeder\SeederTranslation;

class <?= $className ?> extends SeederTranslation
{
    private const TRIGGER_NAME = "<?= $this->container['trigger_name'] ?>";

    protected array $data = [
        "en" => [
            self::TRIGGER_NAME . ".rg.info.description.html" => "",
            self::TRIGGER_NAME . ".user.comment" => "",
        ],
    ];
}
