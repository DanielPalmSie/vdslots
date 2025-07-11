<?php

namespace FormerLibrary\CSRF;

use FormerLibrary\CSRF\Exceptions\InvalidCsrfToken;

class CsrfToken
{
    const EXPIRATION_TIMESPAN = 3600; // 1 Hour

    private string $key;

    /**
     * @var string
     */
    protected string $session_prefix = 'simple_csrf';

    public function __construct(string $key = '')
    {
        $this->setKey($key);
    }

    /**
     * Generate a CSRF token.
     *
     * @return string
     * @throws \Exception
     */
    public function generate(): string
    {
        $token = $this->createToken();
        $this->set($token);

        return $this->get();
    }

    /**
     * Check the CSRF token is valid.
     *
     * @param string $token The token string (usually found in $_POST)
     * @throws InvalidCsrfToken
     */
    public function check(string $token)
    {
        $timespan = self::EXPIRATION_TIMESPAN;

        if (!$token) {
            throw new InvalidCsrfToken('Empty token', ['redis-key' => 'csrf'.session_id(), 'token' => $token]);
        }

        $sessionToken = $this->get();
        if (!$sessionToken) {
            throw new InvalidCsrfToken('Empty session token', ['redis-key' => 'csrf'.session_id(), 'session_token' => $sessionToken, 'token' => $token]);
        }

        if ($this->referralHash() !== substr(base64_decode($sessionToken), 10, 40)) {
            throw new InvalidCsrfToken('Invalid referral hash', ['referral_hash' => $this->referralHash(), 'token_hash' => substr(base64_decode($sessionToken), 10, 40)]);
        }

        if ($token !== $sessionToken) {
            throw new InvalidCsrfToken('Invalid CSRF token', ['redis-key' => 'csrf'.session_id(), 'session_token' => $sessionToken, 'token' => $token]);
        }

        // Check for token expiration
        if (is_int($timespan) && (intval(substr(base64_decode($sessionToken), 0, 10)) + $timespan) < time()) {
            throw new InvalidCsrfToken('CSRF token expired', ['redis-key' => 'csrf'.session_id(), 'session_token' => $sessionToken, 'token' => $token]);
        }
    }

    /**
     * Sanitize the session key.
     *
     * @param string $key
     * @return void
     */
    protected function setKey(string $key): void
    {
        $this->key = implode('_', [$this->session_prefix . preg_replace('/[^a-zA-Z0-9]+/', '', $key)]);
    }

    /**
     * Create a new token.
     *
     * @return string
     * @throws \Exception
     */
    protected function createToken(): string
    {
        // time() is used for token expiration
        return base64_encode(time() . $this->referralHash() . $this->randomString());
    }

    /**
     * Return a unique referral hash.
     *
     * @return string
     */
    protected function referralHash(): string
    {
        $remoteIp = remIp();
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return sha1($remoteIp);
        }

        return sha1($remoteIp . $_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Generate a random string.
     *
     * @return string
     * @throws \Exception
     */
    protected function randomString(): string
    {
        return sha1(random_bytes(32));
    }

    /**
     * @param string $token
     */
    private function set(string $token)
    {
        phMset('csrf'.session_id(), $token);
    }

    /**
     * @return mixed|string
     */
    private function get()
    {
        return phMget('csrf'.session_id()) ?? '';
    }

    /**
     *
     */
    public function clearToken(): void
    {
        phMdel('csrf'.session_id());
    }
}
