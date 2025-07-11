<?php
namespace IT\Services\Traits;

/**
 * Trait LoadJsonTait
 * @package IT\Services\Traits
 */
trait LoadFileTrait
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
    protected function getPath(): string
    {
        throw new \Exception('It is necessary implement the getPath');
    }

    /**
     * @param $key
     * @return array
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
            $data = json_decode(file_get_contents($this->getPath()), true);
            $this->setCache($this->getCacheKey(), $data, $this->getCacheTime());
        }

        return $data;
    }
}