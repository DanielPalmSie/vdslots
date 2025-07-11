<?php

declare(strict_types=1);

namespace Videoslots\ContactUs;

use Laraphive\Domain\Content\DataTransferObjects\ContactUs\SendContactUsData;

final class SendContactUsService
{
    /**
     * @param \Laraphive\Domain\Content\DataTransferObjects\ContactUs\SendContactUsData $data
     *
     * @return string
     */
    public function send(SendContactUsData $data): string
    {
        $mh = phive('MailHandler2');
        $domain = $mh->getSetting('domain', 'videoslots');
        $from_email = $mh->getSetting("default_from_email", "notifications@{$domain}.com");
        $to_email = $mh->buildToEmail();
        $content = "From: {$data->getEmail()} <br><br> Message:<br> {$data->getMessage()}";
        $mh->saveRawMail($data->getSubject(), $content, $from_email, $to_email, $data->getEmail(), 0);

        return 'email.successfully.sent';
    }
}
