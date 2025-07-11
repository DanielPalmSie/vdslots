# How to test Redis Cluster

### A. Regression Test (before setting the cluster):

Go into phive directory

Run
````shell
 ./vendor/bin/pest tests/Unit/Modules/RedisTest.php
````
### B. Cluster test

Go into `phive/modules/Redis/create-cluter` directory

Create the cluster with the command


````shell
bash create_cluster_vs.sh
````

Update your local configuration from `config/local.php`

Add this on the top of the file before the return statement

````php
function createClusterArray($host, $scheme, $startPort, $endPort)
{
    return array_map(function ($port) use ($host, $scheme) {
        return ['host' => $host, 'scheme' => $scheme, 'port' => $port];
    }, range($startPort, $endPort));
}

$host = 'localhost';
$scheme = 'tcp';

$clusters = [
    'cluster' => createClusterArray($host, $scheme, 7001, 7003),
    'mp' => createClusterArray($host, $scheme, 7004, 7006),
    'pexec' => createClusterArray($host, $scheme, 7007, 7009),
    'qcache' => createClusterArray($host, $scheme, 7010, 7012),
    'uaccess' => createClusterArray($host, $scheme, 7013, 7015),
    'localizer' => createClusterArray($host, $scheme, 7016, 7018),
];
````

b. In the same file update or add the configuration of Redis to use the cluster, also MEM_CLUSTER needs to be set to localizer

````php
    'REDIS' => [
        'CLUSTER_MODE' => true,
        'HOSTS' => $clusters['cluster'],
        'MP' => $clusters['mp'],
        'PEXEC' => $clusters['pexec'],
        'QCACHE' => $clusters['qcache'],
        'UACCESS' => $clusters['uaccess'],
        'LOCALIZER' => $clusters['localizer'],
    ],
    'LOCALIZER' => [
        'MEM_CLUSTER' => 'localizer',
    ],
````


4. Go into phive directory

5. Run the command

````shell
./vendor/bin/pest tests/Unit/Modules/RedisClusterTest.php
````

6. Clean the environment

    a. Go into phive/modules/Redis/create-cluster

    b. Run
```bash
bash create-cluster.sh stop 7001 7018 && bash create-cluster.sh clean
```
7. Remove or comment changes done on local configs
