#!/bin/sh

DST_DIR="/var/www/html/one-night-zinrou/"

mkdir tmp
cp -rf client/* tmp/
cd tmp
<<<<<<< HEAD
sed -i -e "s/^.*console\.log.*$//" *.js
rm -f $DST_DIR/*
cp -f * $DST_DIR
=======
#sed -i -e "s/^.*console\.log.*$//" *.js
rm -rf $DST_DIR/*
cp -rf * $DST_DIR
>>>>>>> design
chown -R apache:apache $DST_DIR
cd ..
rm -rf tmp

PROCESS=(`ps ax | grep "php server.php" | grep -v "grep" | sed -e 's/^\ *//' | cut -d " " -f 1`)
for i in "${PROCESS[@]}"
do
    kill $i
done

cd server
HOSTNAME=`hostname`
php server.php $HOSTNAME &
