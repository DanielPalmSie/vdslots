<?php
use App\Extensions\Database\Seeder\SeederTranslation;

class AddRG80PopupWarningEmail extends SeederTranslation
{
    private const TRIGGER_NAME = "RG80";

    protected array $data = [
        'en' => [
            "mail." . self::TRIGGER_NAME . ".popup.ignored.subject" => 'Please Take a Moment to Review Your Play',
            "mail." . self::TRIGGER_NAME . ".popup.ignored.content" => '<p>Dear __USERNAME__,</p>
                <p>You are currently one of the __TOP_YOUNG_LOSERS_COUNT__ highest losing customers who have registered in the last __MONTHS__ months. We encourage you to take a moment to reflect on your recent activity and ensure you\'re playing within comfortable limits.</p>
                <p>Gambling should always be safe and enjoyable. Consider reviewing your activity and taking a break if needed. Our Responsible Gambling tools are available to help you stay in control.</p>
                <p>If you have any questions or need support, we’re here for you.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
        ],
    ];
}
