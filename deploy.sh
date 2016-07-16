#!/bin/sh

DST_DIR="/var/www/html/one-night-zinrou/"

cp -f client/* $DST_DIR

PROCESS=(`ps ax | grep "php server.php" | grep -v "grep" | sed -e 's/^\ *//' | cut -d " " -f 1`)
for i in "${PROCESS[@]}"
do
    kill $i
done

cd server
HOSTNAME=`hostname`
php server.php $HOSTNAME &
