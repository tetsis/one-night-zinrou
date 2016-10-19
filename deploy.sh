#!/bin/sh

DST_DIR="/var/www/html/one-night-zinrou/"

cd client
mkdir tmp
cp *\.* tmp/
cd tmp
#sed -i -e "s/^.*console\.log.*$//" *.js
rm -f $DST_DIR/*
cp -f * $DST_DIR
chown -R apache:apache $DST_DIR
cd ..
rm -rf tmp
cd ..

PROCESS=(`ps ax | grep "php server.php" | grep -v "grep" | sed -e 's/^\ *//' | cut -d " " -f 1`)
for i in "${PROCESS[@]}"
do
    kill $i
done

cd server
HOSTNAME=`hostname`
php server.php $HOSTNAME &
