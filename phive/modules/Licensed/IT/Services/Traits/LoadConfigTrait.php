<?php
namespace IT\Services\Traits;

/**
 * Trait LoadJsonTait
 * @package IT\Services\Traits
 */
trait LoadConfigTrait
{
    /**
     * @return int
     */
    protected function getCacheTime(): int
    {
        return 86400;
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function getCacheKey(): int
    {
        throw new \Exception('It is necessary implement the cacheKey');
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getConfigTag(): string
    {
        throw new \Exception('It is necessary implement the configTag');
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getConfigName(): string
    {
        throw new \Exception('It is necessary implement the configName');
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getConfig(): array
    {
        $config_tag = $this->getConfigTag();
        $config_return = $this->getCache($config_tag);
        if (empty($config)) {
            $config = phive('Config')->getByTags($config_tag, true);
            $config_return = $config[$config_tag];
            $this->setCache($config_tag, $config_return, $this->getCacheTime());
        }
        return $config_return;
    }

    /**
     * @param $key
     * @return string
     */
    protected function getCache($key): array
    {
        return json_decode(gzuncompress(phMget($key) ?? ''), true) ?? [];
    }

    /**
     * @param string $key
     * @param array $data
     * @param int $cache_time
     */
    protected function setCache(string $key, array $data, int $cache_time)
    {
        phMset($key, gzcompress(json_encode($data)), $cache_time);
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function loadFromCache(): array
    {
        $data = $this->getCache($this->getCacheKey());
        if (empty($data)) {
            $config = $this->getConfig();
            $data = $config[$this->getConfigName()] ?? [];
            if (! empty($data)) {
                $this->setCache($this->getCacheKey(), $data, $this->getCacheTime());
            }
        }

        return $data;
    }
}