#!/bin/bash

#Baseline / No cache
date_suffix=$(date +%Y-%m-%d-%H-%M)
concurrency=5
requests=100
adapter=$1

dat_file="./build/bin/plots/nocache.dat"
img_file="./build/bin/plots/${adapter}_nocache_${date_suffix}.png"
ab -k -c ${concurrency} -n ${requests} -g ${dat_file} juggernaut.local/examples/${adapter}/LoadTesting/Baseline.php
sh ./build/bin/abplot.sh "Baseline: No Cache" "${dat_file}" "${img_file}"
#rm -rf ${dat_file}

php ./examples/${adapter}/LoadTesting/Reset.php

#No flood protection
dat_file="./build/bin/plots/cache-flood-off.dat"
img_file="./build/bin/plots/${adapter}_cache-flood-off_${date_suffix}.png"
ab -k -c ${concurrency} -n ${requests} -g ${dat_file} juggernaut.local/examples/${adapter}/LoadTesting/FloodOff.php
sh ./build/bin/abplot.sh "Cache: Flood Protection Off" "${dat_file}" "${img_file}"
#rm -rf ${dat_file}

php ./examples/${adapter}/LoadTesting/Reset.php

#Flood protection
dat_file="./build/bin/plots/cache-flood-on.dat"
img_file="./build/bin/plots/${adapter}_cache-flood-on_${date_suffix}.png"
ab -k -c ${concurrency} -n ${requests} -g ${dat_file} juggernaut.local/examples/${adapter}/LoadTesting/FloodOn.php
sh ./build/bin/abplot.sh "Cache: Flood Protection On" "${dat_file}" "${img_file}"
#rm -rf ${dat_file}

php ./examples/${adapter}/LoadTesting/Reset.php