<?php
namespace IT\Traits;

use DBUser;
use IT\Services\CountriesService;
use IT\Services\DocumentService;
use IT\Services\EmailAccountService;
use IT\Services\ProvincesService;
use IT\Services\RegistrationService;
use IT\Services\ResidenceService;
use IT\Services\SoftwareVersionCommunicationService;
use IT\Services\TaxCodeService;
use IT\Services\SubregistrationService;
use IT\Services\AAMSSession\AAMSSessionService;
use IT\Services\TrasversalSelfExclusionService;
use IT\Services\PlayerService;
use IT;

/**
 * Trait ServicesTrait
 * @package IT\Traits
 */
trait ServicesTrait
{
    /**
     * @param $user
     * @return EmailAccountService
     */
    protected function getEmailAccountService($user): EmailAccountService
    {
        return new EmailAccountService($user);
    }

    /**
     * Return a SoftwareVersionCommunicationService instance
     * @param IT $it
     * @return SoftwareVersionCommunicationService
     */
    protected function getSoftwareVersionCommunicationService(IT $it): SoftwareVersionCommunicationService
    {
        return new SoftwareVersionCommunicationService($it);
    }

    /**
     * Return a CountriesService instance
     * @return CountriesService
     */
    protected function getCountriesService(): CountriesService
    {
        return new CountriesService();
    }

    /**
     * Return a ResidenceService instance
     * @return ResidenceService
     */
    protected function getResidenceService(): ResidenceService
    {
        return new ResidenceService();
    }

    /**
     * Return a ProvincesService instance
     * @return ProvincesService
     */
    protected function getProvincesService(): ProvincesService
    {
        return new ProvincesService();
    }

    /**
     * @param DBUser|null $user
     * @return RegistrationService
     */
    protected function getRegistrationService(DBUser $user = null): RegistrationService
    {
        return new RegistrationService($user);
    }

    /**
     * @return TaxCodeService
     */
    protected function getTaxCodeService(): TaxCodeService
    {
        return new TaxCodeService();
    }

    /**
     * @return DocumentService
     */
    protected function getDocumentService(): DocumentService
    {
        return new DocumentService($this->getAllLicSettings());
    }

    /**
     * @param DBUser $user
     * @return SubregistrationService
     */
    protected function getSubregistrationService(DBUser $user): SubregistrationService
    {
        return new SubregistrationService($user);
    }

    /**
     * @param DBUser $user
     * @param int $management_type
     * @param int $self_exclusion_type
     * @return TrasversalSelfExclusionService
     */
    protected function getTrasversalSelfExclusionService(
        $user,
        int $management_type,
        int $self_exclusion_type
    ): TrasversalSelfExclusionService
    {
        return new TrasversalSelfExclusionService($user, $management_type, $self_exclusion_type);
    }

    /**
     * Returns the service that gives access to Session functionality
     * @param IT $it
     * @return AAMSSessionService
     */
    protected function getAAMSSessionService(IT $it): AAMSSessionService
    {
        return new AAMSSessionService($it);
    }

    /**
     * @return PlayerService
     */
    protected function getPlayerService(): PlayerService
    {
        return new PlayerService();
    }
}