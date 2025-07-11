#!/usr/bin/env bash

# In order to run this script, you must have set up the redis cluster
#
# 1. Run 'create_cluster_vs.sh' and answer yes to the questions
# 2. configure the redis cluster in your local config file as follows


# ########## LOCAL CONFIGURATION - START ##########
# <?php   // Add this on the top of your local configuration file (local.php)
#function createClusterArray($host, $scheme, $startPort, $endPort) {
#    return array_map(function ($port) use ($host, $scheme) {
#        return ['host' => $host, 'scheme' => $scheme, 'port' => $port];
#    }, range($startPort, $endPort));
#}
#
#$host = 'localhost';
#$scheme = 'tcp';
#
#$clusters = [
#    'cluster' => createClusterArray($host, $scheme, 7001, 7003),
#    'mp' => createClusterArray($host, $scheme, 7004, 7006),
#    'pexec' => createClusterArray($host, $scheme, 7007, 7009),
#    'qcache' => createClusterArray($host, $scheme, 7010, 7012),
#    'uaccess' => createClusterArray($host, $scheme, 7013, 7015),
#    'localizer' => createClusterArray($host, $scheme, 7016, 7018),
#];
#
#
#return [
#
#
#        'REDIS' => [
#            'CLUSTER_MODE' => true,
#            'HOSTS' => $clusters['cluster'],
#            'MP' => $clusters['mp'],
#            'PEXEC' => $clusters['pexec'],
#            'QCACHE' => $clusters['qcache'],
#            'UACCESS' => $clusters['uaccess'],
#            'LOCALIZER' => $clusters['localizer'],
#        ],
#        'LOCALIZER' => [
#            'MEM_CLUSTER' => 'localizer',
#        ],
#
# ########## LOCAL CONFIGURATION - END ############

cd /var/www/videoslots/phive || exit

php ./vendor/bin/pest tests/Unit/Modules/RedisClusterTest.php
