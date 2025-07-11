<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddedRgPopupWarningEmails extends SeederTranslation
{
    protected array $data = [
        'en' => [
            'mail.RG5.popup.ignored.subject' => 'Welcome Back - Please Review Your Limits',
            'mail.RG5.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you\'ve returned after a period away from gaming. As part of our commitment to responsible gambling, we encourage you to review your limits using our responsible gambling tools before resuming play.</p>
                <p>It\'s important to ensure that you are playing within levels that are manageable and comfortable for you. These tools are in place to help you maintain control over your gaming experience.</p>
                <p>Please don\'t hesitate to reach out if you have any questions or need support.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG6.popup.ignored.subject' => 'Welcome Back - Please Review Your Limits',
            'mail.RG6.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you\'ve returned after a period away from gaming. As part of our commitment to responsible gambling, we encourage you to review your limits using our responsible gambling tools before resuming play.</p>
                <p>It\'s important to ensure that you are playing within levels that are manageable and comfortable for you. These tools are in place to help you maintain control over your gaming experience.</p>
                <p>Please don\'t hesitate to reach out if you have any questions or need support.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG8.popup.ignored.subject' => 'Take a Moment to Review Your Activity',
            'mail.RG8.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you\'ve been spending more time on our site recently. It may be helpful to take more frequent breaks in your play to maintain a balanced and enjoyable experience.</p>
                <p>As part of our commitment to responsible gambling, we encourage you to review your limits using our responsible gambling tools to ensure that you\'re playing within comfortable levels.</p>
                <p>Please feel free to reach out if you need any assistance.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG9.popup.ignored.subject' => 'Please Review Your Recent Limit Changes',
            'mail.RG9.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you have recently changed your deposit or wager limits several times. It\'s important to ensure that you\'re playing within levels that are comfortable for you.
                <p>We strongly recommend taking a moment to review your limits using our responsible gambling tools to help maintain a safe and enjoyable gaming experience.
                <p>If you have any questions or need assistance, please feel free to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG10.popup.ignored.subject' => 'Review Your Recent Deposit Activity',
            'mail.RG10.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed an increase in your recent deposit amounts. To help ensure a balanced and comfortable gaming experience. We encourage you to review your limits using our responsible gambling tools.</p>
                <p>Those tools can help you manage your play and keep it safe and enjoyable.</p>
                <p>If you need any assistance, please don\'t hesitate to contact us.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG11.popup.ignored.subject' => 'Increased Deposits - Please Review Your Limits',
            'mail.RG11.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you\'ve recently increased your deposits. It\'s important to ensure that you\'re playing within comfortable levels, as we recommend taking a moment to review your limits using a moment to review your limits using our responsible gaming tools.</p>
                <p>Those tools are designed to help you keep your gaming experience safe and enjoyable.</p>
                <p>If you have any questions or need support, feel free to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG12.popup.ignored.subject' => 'Changes in Your Daily Deposits - Please Review Your Limits',
            'mail.RG12.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed an increase in your daily deposits amounts compared to previous days. It\'s important to play within levels that are comfortable for you, so we recommend reviewing your limits using our responsible gambling tools.</p>
                <p>Those tools can help you maintain control and ensure that your gaming remains safe and enjoyable.</p>
                <p>If you need any assistance, please don\'t hesitate to contact us.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG13.popup.ignored.subject' => 'Review Your Deposit Activity for Today',
            'mail.RG13.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed an increase in your deposit amounts throughout today. It\'s important to ensure that you\'re playing within comfortable levels, so we recommend reviewing your limits using our responsible gambling tools.</p>
                <p>Those tools are designed to help you manage your play and keep it safe and enjoyable.</p>
                <p>If you need any support or have any questions, please feel free to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG14.popup.ignored.subject' => 'Congratulations on Your Win - Take a Moment to Review Your Limits',
            'mail.RG14.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>Congratulations on your recent win! After such a big win, it might be a good time to take a break and reflect on your gaming habits.</p>
                <p>We recommend reviewing your limits using our responsible gabling tools to ensure you continue playing at a safe and enjoyable level.</p>
                <p>If you have any questions or need assistance, please don\'t hesitate to contact us.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG15.popup.ignored.subject' => 'Please Review Your Recent Bet',
            'mail.RG15.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you\'ve just placed a bet equal to 100% of your last deposit. It\'s important to make sure that you\'re playing within comfortable limits.</p>
                <p>We encourage you to review your limits using our responsible gambling tools to ensure your gaming remains safe and enjoyable.</p>
                <p>If you have any questions or need assistance, feel free to contact us.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG16.popup.ignored.subject' => 'Account Activity and Responsible Gaming Support',
            'mail.RG16.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you have resumed activity on an account that was previously self-locked. Are you sure you\'re ready to come back?</p> 
                <p>If you feel the need for any additional support, we encourage you to review our responsible gambling tools or consider taking a break. These tools are here to help yu maintain a safe and enjoyable gaming experience.</p>
                <p>If you have any questions or need assistance, please don\'t hesitate to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG18.popup.ignored.subject' => 'Important: Review Your Recent Deposit Attempts',
            'mail.RG18.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you have made multiple failed deposit attempts today. It might be a good time to consider taking a break.</p> 
                <p>It\'s important to ensure you\'re playing within comfortable levels and that you have the necessary funds available for gambling. Remember, gambling should always be a fun and safe experience.</p>
                <p>If you have any questions or need assistance, feel free to contact us.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG19.popup.ignored.subject' => 'Consider Taking a Break and Reviewing Your Limits',
            'mail.RG19.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you\'ve sent a significant amount on gambling this month. It may be a good time to consider taking a break.</p>
                <p>It\'s important to play within levels that are comfortable for you, so we recommend reviewing your limits using our responsible gambling tools to keep your gaming safe and enjoyable.</p>
                <p>If you need any support or have questions, please feel free to contact us.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG20.popup.ignored.subject' => 'Consider Taking a Break and Reviewing Your Limits',
            'mail.RG20.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you\'ve spent a significant amount on gambling this month. It may be a good time to consider taking a break.</p>
                <p>It\'s important to play within levels that are comfortable for you, so we recommend reviewing your limits using our responsible gambling tools to keep your gaming safe and enjoyable.</p>
                <p>If you need any support or have questions, please feel free to contact us.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG21.popup.ignored.subject' => 'Change in Your Loss Limit - Please Review Your Limits',
            'mail.RG21.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed a change in your loss limit recently. This might be a good time to take a break and reflect on your gaming habits.</p>
                <p>It\'s important to play within levels that are comfortable for you, so we recommend reviewing your limits using our responsible gaming tools to ensure your gaming experience remains safe and enjoyable.</p>
                <p>If you have any questions or need support, please don\'t hesitate to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG24.popup.ignored.subject' => 'Unsuccessful Deposit Attempts - Please Review Your Spending',
            'mail.RG24.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed multiple declined deposit attempts on your account, including the use of different payment cards. This might be a good time to take a break and reflect on your gaming habits.</p>
                <p>It\'s important to play within levels that are comfortable for you, so we recommend reviewing your limits using our responsible gaming tools to ensure your gaming experience remains safe and enjoyable.</p>
                <p>If you have any questions or need support, please don\'t hesitate to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG25.popup.ignored.subject' => 'Multiple Failed Deposit Attempts - Please Review Your Activity',
            'mail.RG25.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed multiple failed deposit attempts on your account within a short time. It may be a good time to consider taking a break.</p>
                <p>It\'s important to play within levels that are comfortable for you, so we recommend reviewing your limits using our responsible gambling tools to keep your gaming safe and enjoyable.</p>
                <p>If you need any support or have questions, please feel free to contact us.</p>
                <p>Best regards,</p>__BRAND_NAME__</p>',
            'mail.RG27.popup.ignored.subject' => 'Reminder: Review Your Recent Activity',
            'mail.RG27.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We have noticed a recent deposit on your account.</p>
                <p>To help ensure you\'re playing within comfortable limits, we encourage you to take a moment to review your gaming activity and consider adjusting your limits using our responsible gambling tools. Maintaining a balanced and enjoyable gaming experience is our priority.</p>
                <p>If you have any questions or need support, we\'re here to assist you.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG28.popup.ignored.subject' => 'Increase in Your Gameplay - Please Review Your Limits',
            'mail.RG28.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed an increase in your gameplay recently. It\'s important to ensure that you are playing within comfortable levels, so we recommend taking a moment to review your limits using our responsible gambling tools.</p>
                <p>These tools are designed to help you manage your gaming experience and keep it safe and enjoyable.</p>
                <p>If you have any questions or need assistance, please feel free to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG29.popup.ignored.subject' => 'Increased Bet Levels - Please Review Your Limits',
            'mail.RG29.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed an increase in your bet level per spin. It\'s important to player within levels that are comfortable for you, so we recommend reviewing your limits using our responsible gambling tools.</p>
                <p>These tools can help you manage your gaming experience and ensure it remains safe and enjoyable.</p>
                <p>If you have any questions or need support, please don\'t hesitate to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG30.popup.ignored.subject' => 'Increased Gameplay Today - Please Review Your Limits',
            'mail.RG30.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you\'ve been playing more than usual today. It\'s important to ensure that you\'re playing within comfortable levels, so we recommend taking a moment to review your limits using our responsible gambling tools.
                <p>These tools are designed to help you maintain a safe and enjoyable gaming experience.</p>
                <p>If you have any questions or need assistance, please feel free to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG31.popup.ignored.subject' => 'Increased Gameplay Today - Please Review Your Limits',
            'mail.RG31.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you\'ve been playing more than usual today. It\'s important to ensure that you\'re playing within comfortable levels, so we recommend taking a moment to review your limits using our responsible gambling tools.</p>
                <p>These tools are designed to help you maintain a safe and enjoyable gaming experience.</p>
                <p>If you have any questions or need assistance, please feel free to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG32.popup.ignored.subject' => 'Increased Playtime - Please Consider Taking a Break',
            'mail.RG32.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed an increase in the time you\'ve spent playing recently. This may be a good time to consider taking a break.</p>
                <p>It\'s important to play within levels that are comfortable for you, so we recommend reviewing your limits using our responsible gambling tools to ensure your gaming experience remains safe and enjoyable.</p>
                <p>If you have any questions or need support, please don\'t hesitate to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG33.popup.ignored.subject' => 'Increased Playtime - Please Consider Taking a Break',
            'mail.RG33.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed an increase in the time you\'ve spent playing recently. This may be a good time to consider taking a break.</p>
                <p>It\'s important to play within levels that are comfortable for you, so we recommend reviewing your limits using our responsible gambling tools to ensure your gaming experience remains safe and enjoyable.</p>
                <p>If you have any questions or need support, please don\'t hesitate to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG34.popup.ignored.subject' => 'Increased Playtime - Please Consider Taking a Break',
            'mail.RG34.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed an increase in the time you\'ve spent playing recently. This may be a good time to consider taking a break.</p>
                <p>It\'s important to play within levels that are comfortable for you, so we recommend reviewing your limits using our responsible gambling tools to ensure your gaming experience remains safe and enjoyable.</p>
                <p>If you have any questions or need support, please don\'t hesitate to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG35.popup.ignored.subject' => 'Important: Review Your Recent Losses',
            'mail.RG35.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you have experienced significant losses over the past 30 days. We encourage you to reflect on whether you are comfortable with this situation.</p>
                <p>We strongly recommend taking a break and reviewing your limits using our responsible gambling tools. Remember, gambling should be a fun and safe activity. It\'s no longer enjoyable, it\'s important to stop.</p>
                <p>If you have any questions or need support, please feel free to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG37.popup.ignored.subject' => 'Important: Review Your Recent Activity',
            'mail.RG37.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve detected a notable chance in your gaming activity, and we want to ensure that you\'re playing safely and responsibly. To help you maintain a balanced and enjoyable experience, we strongly recommend reviewing your activity and adjusting your limits where necessary.</p>
                <p>Playing within comfortable levels is key to staying in control. If you feel you need support, our responsible gambling tools are available to help you manage your activity.</p>
                <p>If you have any questions or require assistance, don\'t hesitate to reach out to us.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG38.popup.ignored.subject' => 'Important: Review Your Recent Losses',
            'mail.RG38.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you have incurred significant losses over the past 30 days. We encourage you to reflect on whether you are comfortable with this situation.</p>
                <p>We strongly recommend taking a break and advice you to review your limits using our responsible gambling tools. Remember, gambling should be a fun and safe experience. It\'s no longer enjoyable, it\'s important to stop.</p>
                <p>If you have any questions or need assistance, please feel free to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG39.popup.ignored.subject' => 'Important: Review Your Recent Losses',
            'mail.RG39.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you have lost a substantial sum of money over the past 30 days. We encourage you to consider whether you are comfortable with this situation.</p>
                <p>We strongly recommend taking a break and urge you to review your limits using our responsible gambling tools. Remember, gambling should be a fun and safe experience. It\'s no longer enjoyable, it\'s important to stop.</p>
                <p>If you have any questions or need support, please feel free to reach out.</p>
                <br>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG58.popup.ignored.subject' => 'Reminder: Time to Take a Break',
            'mail.RG58.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that your gaming sessions have exceeded one hour on consecutive days.</p>
                <p>It\'s important to take regular breaks and ensure you\'re playing within comfortable levels. We encourage you to take a moment to pause your session.</p>
                <p>If you have any questions or need assistance, please feel free to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG59.popup.ignored.subject' => 'It\'s Time to Rest - Please Review Your Limits',
            'mail.RG59.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that it\'s quite late, and most people are likely getting ready for bed. We encourage you to consider taking a break and getting some rest.</p>
                <p>It\'s important to play within comfortable levels, so we recommend reviewing your limits using our responsible gambling tool to ensure that your gaming experience remains safe and enjoyable.</p>
                <p>If you have any questions or need assistance, please don\'t hesitate to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG62.popup.ignored.subject' => 'Taking a Break Can Help Maintain a Balanced Play',
            'mail.RG62.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you\'ve been logging into your account frequently in the last week. While gaming can be enjoyable, it\'s important to take regular breaks to ensure a balanced and responsible experience.</p>
                <p>We encourage you to consider taking a break and getting some rest.</p>
                <p>If you have any questions or need assistance, please don\'t hesitate to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG63.popup.ignored.subject' => 'Taking a Break Can Help Maintain a Balanced Play',
            'mail.RG63.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you\'ve been logging into your account frequently this month. While gaming can be enjoyable, it\'s important to take regular breaks to ensure a balanced and responsible experience.</p>
                <p>We encourage you to consider taking a break and getting some rest.</p>
                <p>If you have any questions or need support, please feel free to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG66.popup.ignored.subject' => 'Important: Review Your Recent Deposits',
            'mail.RG66.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We want to inform you that your net deposits have reached __DEPOSIT_AMOUNT__ within the last __DAYS__ days.</p>
                <p>We encourage you to consider reviewing your spending to ensure that you\'re playing within comfortable levels. Our responsible gaming tools are available to assist you in maintaining a safe and enjoyable gaming experience.</p>
                <p>If you have any questions or need assistance, please feel free to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG68.popup.ignored.subject' => 'Consider Taking a Break - Review Your Limits',
            'mail.RG68.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you have spent a significant amount on gambling this month. It may be a good idea to consider taking a break.</p>
                <p>It\'s important to play within levels that are comfortable for you, so we recommend reviewing your limits using our responsible gambling tools to ensure your gaming experience remains safe and enjoyable.</p>
                <p>If you have any questions or need support, please feel free to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG70.popup.ignored.subject' => 'Important: Review Your Gaming Activity and Financial Well-being',
            'mail.RG70.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve identified some important information related to your financial well-being, and we want to ensure that you\'re playing within safe and manageable limits. Please take a moment to review your recent activity and consider adjusting your limits using our responsible gambling tools.</p>
                <p>Responsible play is essential for a safe and enjoyable gaming experience, and we\'re here to help you maintain that balance.</p>
                <p>If you have any questions or need support, don\'t hesitate to reach out.</p>
                <b>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG72.popup.ignored.subject' => 'Important: Review Your Recent Deposit Activity',
            'mail.RG72.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that your net deposits have reached __DEPOSIT_AMOUNT__ within the last __TIME__ hours. We encourage you to take a moment to review your recent activity and ensure you\'re playing within comfortable limits.</p>
                <p>Responsible gaming is key to keeping your experience fun and safe. If needed, our responsible gambling tools are available to help you set and manage limits for your play.</p>
                <p>If you have any questions or need assistance, feel free to contact us.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG73.popup.ignored.subject' => 'Reminder: Time to Take a Break',
            'mail.RG73.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you\'ve played for __NUMBER_OF_HOURS_PLAYED__ hours in the last __HOURS_DURATION__ hours.</p>
                <p>It\'s important to take regular breaks and ensure that you\'re playing within comfortable limits. We encourage you to pause and review your gaming activity to maintain a health balance.</p>
                <p>If you have any questions or need assistance, our responsible gaming tools are available to support you.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG74.popup.ignored.subject' => 'Reminder: Review Your Recent Gaming Activity',
            'mail.RG74.popup.ignored.content' => '<br>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you\'ve wagered __AMOUNT_OF_SPINS__ spins in the last __HOURS_PERIOD__ hours.</p>
                <p>It\'s important to take regular breaks and ensure that you\'re playing within comfortable limits. We encourage you to reflect on you gaming activity and consider reviewing your limits to maintain a balanced and enjoyable experience.</p>
                <p>If you have any questions or need assistance, our responsible gaming tools are available to support you.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG75.popup.ignored.subject' => 'Reminder: Review Your Recent Wagering Activity',
            'mail.RG75.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>We\'ve noticed that you\'ve wagered __WAGER__ in the last __LAST_HOURS__ hours.</p>
                <p>It\'s important to ensure that you\'re playing within comfortable limits and that your gaming remains both safe and enjoyable. We encourage you to take a moment to review your activity and consider using our responsible gaming tools to help maintain a health balance.</p>
                <p>If you have any questions or need support, feel free to reach out.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
            'mail.RG76.popup.ignored.subject' => 'Reminder: Review Your Recent Wagering Activity',
            'mail.RG76.popup.ignored.content' => '<p>Dear __USERNAME__,</p>
                <p>Congratulations on your big win! You\'ve won over __MULTIPLIER__ times your bet - what an achievement!</p>
                <p>With such a great win, now might be a good time to take a break and enjoy your success. Remember, it\'s important to keep playing within comfortable limits to ensure that your gaming remains fun and safe.</p>
                <p>If you have any questions or would like to review your limits, our responsible gaming tools are always available to support you.</p>
                <p>Best regards,</br>__BRAND_NAME__</p>',
        ],
    ];
}