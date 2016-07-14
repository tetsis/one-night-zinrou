#!/bin/sh

DST_DIR="/var/www/html/one-night-zinrou/"

cp -f index.html $DST_DIR
cp -f main.css $DST_DIR
cp -f zinrou.js $DST_DIR

PROCESS=(`ps ax | grep "php server.php" | grep -v "grep" | sed -e 's/^\ *//' | cut -d " " -f 1`)
for i in "${PROCESS[@]}"
do
    kill $i
done

HOSTNAME=`hostname`
php server.php $HOSTNAME &
