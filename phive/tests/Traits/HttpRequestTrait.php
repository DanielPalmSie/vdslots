<?php

namespace Tests\Traits;

trait HttpRequestTrait
{
    /**
     * Perform a GET request on the ajax endpoint
     *
     * @param $params
     * @return array|false|string
     */
    public function getAjax($params)
    {
        return $this->getSelf('/phive/modules/Micro/ajax.php', $params);
    }

    /**
     * @param string $uri '/phive/modules/Micro/ajax.php'
     * @param array|null $params
     * @return array|false|string
     */
    public function getSelf(string $uri, ?array $params = [])
    {
        return phive()->get($this->getFullDomainWithSchema() . $uri . $this->getQueryString($params));
    }

    /**
     * @param $req_params
     * @return string
     */
    private function getQueryString($params): string
    {
        return is_array($params) ? '?' . http_build_query($params) : '';
    }

    /**
     * @return string
     */
    private function getFullDomainWithSchema(): string
    {
        return 'https://' . $this->getFullDomain();
    }

    /**
     * @return string
     */
    public function getFullDomain(): string
    {
        return phive()->getSetting('full_domain');
    }
}