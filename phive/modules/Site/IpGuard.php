<?php
require_once __DIR__ . '/../../api/ExtModule.php';

/*
 * This class contains basic logic for restricting IP access by way of the allowed_ips table.
 *
 */

class IpGuard extends ExtModule
{

    public string $table = 'allowed_ips';

    function table(): string
    {
        return $this->table;
    }

    /**
     * If page settings indicate that we need to check the IP we call check()
     *
     * @param string $to A URL to redirect to in case access is not allowed.
     */
    public function block($to)
    {
        if ($this->getSetting('test') === true) {
            return true;
        }

        $toplvl = phive('Pager')->getPageAtLevel(0);
        $block = phive('Pager')->fetchSetting('ipblock', $toplvl['page_id']);

        if (!empty($block)) {
            $this->check($to);
        }
    }

    /**
     * Checks if an IP is added to the allowed ips table, if it is we do nothing
     * if it is not we redirect or show an error message, in any case we stop
     * execution.
     *
     * Typically used to protect particularly sensitive parts of a BO.
     *
     * @param string $to URL to redirect to if we want to do that.
     *
     * @return null
     */
    public function check($to = '')
    {
        if ($this->getSetting('test') === true)
            return true;

        if (!$this->isWhitelistedIp(remIp())) {
            if (empty($to)) {
                echo "You are not on an approved address.";
            } else {
                header("Location: http://$to");
            }
            exit;
        }
    }

    public function getWhitelistedIps(): array
    {
        return phive('SQL')->load1Darr("SELECT * FROM " . $this->table, 'ipnum');
    }

    /**
     * Checks if an IP is added to the allowed ips table
     *
     * @param string $ip The IP to add.
     */
    public function isWhitelistedIp(string $ip): bool
    {
        $ips = $this->getWhitelistedIps();
        return in_array($ip, $ips);
    }
}
