#! /bin/sh
#
# @author Nicolas CARPi <nico-git@deltablot.email>
# @copyright 2012 Nicolas CARPi
# @see https://www.elabftw.net Official website
# @license AGPL-3.0
# @package elabftw
#
# This script will spawn a temporary elabftw install, populate it with fake data and run the full test suite

# stop on failure
set -eu

# make sure we tear down everything when script ends
cleanup() {
    docker-compose -f tests/docker-compose.yml down
    sudo cp config.php.dev config.php
    sudo chown 100:101 config.php
}
trap cleanup EXIT

# sudo is needed because config file for docker is owned by 100:101
sudo cp config.php config.php.dev
sudo cp tests/config-home.php config.php
sudo chmod +r config.php
# launch a fresh environment
docker-compose -f tests/docker-compose.yml up -d
# give some time for the mysql process to start
echo "Waiting for MySQL to start..."
sleep 20
# install the database
docker exec -it elabtmp bin/install start
# populate the database
docker exec -it elabtmp bin/console dev:populate tests/populate-config.yml
# run tests
# the tests are split in two parts, api and acceptance, and later unit with coverage
# this is because for some weird reason, when xdebug is enabled, there is an ssl verification error
# during the acceptance/api tests
if [ "${1:-}" != "unit" ]; then
    docker exec -it elabtmp php vendor/bin/codecept run --skip unit
fi
# now install xdebug in the container so we can do code coverage
docker exec -it elabtmp bash -c "apk add --update php8-pecl-xdebug && echo 'zend_extension=xdebug.so' >> /etc/php8/php.ini && echo 'xdebug.mode=coverage' >> /etc/php8/php.ini"
# generate the coverage, results will be available in _coverage directory
docker exec -it elabtmp php vendor/bin/codecept run --skip acceptance --skip api --coverage --coverage-html
# all tests succeeded, display a koala
cat << WALAEND


        .yyhyys/.                            -/++/:'
    -+ssydo+///ohh/                      '+yhyoo+osm'
 'ohs+////////////yh.                  .ydo//////+sysso:'
:do/////osyyyo/////om-                odo//////////////shs.
yhh+//ydo:--:+ydo///sm'             'hy//////+ooo+///////om:
-No//ms.''''''.:dy///N:             hs////shs+//+ohho////osm
hy//yd'''''''''.:No++Ns//+++++//::-+d////ds..'''''.-hd///oN+
No/:hs''''''''.-:dmdhysooooooooooosss+/:do-.''''''''.hh://do
ms//sd'''''''.:sdy+/////////////////////shs:.''''''''+N://sm
+m///ds.''''-+dy+/////////////////////////sdo-.''''''sm://sd
 od+//yh/.'.+mo/////////+//////////////////+ds-.''''+m+///do
  -yho//syyyNo///////////////////////////////m+-.-/hh+///hy'
    .+syssymy////////////////////////////////smyhhy+//+sdo'
       '..oN//////////+oooo+//////////////////Myooosyhyo.
          hy/+sydy///hyhddmmd/////ssdy+///////mdsso+:.'
          No/No/NNh:dhydddddmh:/:dy:dNm://////do
         'M+/ymNNmo/MhddddddmN///smmNmy://///+do
          ms//+++///NmmddddmmNo////++///////++m/
          od////////smNmmmmNmd/////////////++sm'
          'dy/////////ossssoo/////////////++sm:
           'yho////////oosso+///////////++ohh-
             /yho+////////////////////+oshhyy/'
             -yhyh++////:::::::///+osyhys+///sh/'
           'ohooh-'..----------::-:/:--////////yy-
          -hy/od-'''''.............''''-////////od+'
         :do/+m-''''''''.'''''''''''''''-/////////hs'
        :m+//m+''''''''..''''''''''''''''-/////////hy'
       -mo//sd'''''''''.-.'''''''''''''''.:///+s////ds
      'ds///m/''''''''''-...''''''''''''''.///:ho////N:
      /m///+N.''.'''''''...'''''''.'''''''':///+m////sd
      sh///om''...'''''''''''''''...'''''''-///:ms///+M'
      +my+/sd''..-.''''''''''''''..-.''''''.////dy+oydm
      hmNmmhm''''''''''''''''''''''..''''''.////hdhmNmN-
      -oydy/M.'''''''''''''''''''''''''''''-////do+dhs+
            do...'''''''''''''''''''''''''.:////N-
            /m/:--......'''''''''''''....-:////+N
             dy///:+++/:----------://+sys//////hs
             .Nysss+soshmosssssssso++Nhysysos+oN.
             ommNmmmdNsd+            mmmNdNmmmdy
             :hddmNmmd+:             ohddmNmmy.


WALAEND
