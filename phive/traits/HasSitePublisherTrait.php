<?php

use Laraphive\Contracts\SitePublisher\SitePublisherInterface;

trait HasSitePublisherTrait
{
    /**
     * @var \Laraphive\Contracts\SitePublisher\SitePublisherInterface
     */
    private SitePublisherInterface $sitePublisher;

    /**
     * @return \Laraphive\Contracts\SitePublisher\SitePublisherInterface
     */
    private function getSitePublisher(): SitePublisherInterface
    {
        if (!isset($this->sitePublisher)) {
            $this->sitePublisher = phiveApp(SitePublisherInterface::class);
        }

        return $this->sitePublisher;
    }
}
