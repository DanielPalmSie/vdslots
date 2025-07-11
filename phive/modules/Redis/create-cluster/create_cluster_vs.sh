#!/usr/bin/env bash

#            'HOSTS' => $cluster_hosts,
#            'MP' => $mp_hosts,
#            'PEXEC' => $pexec_hosts,
#            'QCACHE' => $qcache_hosts,
#            'UACCESS' => $uaccess_hosts,
#            'LOCALIZER' => $localizer_nodes,
## create clusters for all the different clusters that we have in productions

clusters=('HOSTS' 'LOCALIZER' 'MP' 'PEXEC' 'QCACHE' 'UACCESS' 'LOCALIZER')

# each cluster will have 2 nodes, initial port is 7000. Use create-cluster.sh start start_port end_port to start 2 nodes
# then use create-cluster.sh create start_port end_port to create the cluster

# foreach clusters_hosts create a cluster
START_PORT=7000
END_PORT=7003

for cluster in "${clusters[@]}"
do
    echo "==== > Creating cluster for $cluster"
    echo "-> Starting nodes"
    ./create-cluster.sh start $START_PORT $END_PORT
    sleep 5
    echo "-> Creating cluster"
    ./create-cluster.sh create $START_PORT $END_PORT

    START_PORT=$((START_PORT+3))
    END_PORT=$((END_PORT+3))
done

echo "Done creating clusters"

