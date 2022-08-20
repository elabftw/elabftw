#!/usr/bin/env sh
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
# detect if we are in scrutinizer ci (https://scrutinizer-ci.com/g/elabftw/elabftw/)
scrutinizer=${SCRUTINIZER:-false}

# when the script stops (or is stopped), replace the test config with the dev config
cleanup() {
    if (! $scrutinizer); then
        cp -v config.php.dev config.php
    fi
}
trap cleanup EXIT

# make a backup of the current (dev) config
if (! $scrutinizer); then
    cp -v config.php config.php.dev
fi
cp -v tests/config-home.php config.php

# if there are no custom env_file, touch one, as this will trigger an error
if [ ! -f tests/elabftw-user.env ]; then
    touch tests/elabftw-user.env
    if ($scrutinizer); then
        printf "ELABFTW_USER=scrutinizer\nELABFTW_GROUP=scrutinizer\nELABFTW_USERID=1001\nELABFTW_GROUPID=1001\n" > tests/elabftw-user.env
    fi
fi

# launch a fresh environment if needed
if [ ! "$(docker ps -q -f name=mysqltmp)" ]; then
    if ($scrutinizer); then
        # Use the freshly built elabtmp image
        sed -i 's#elabftw/elabimg:hypernext#elabtmp#' tests/docker-compose.yml
        export DOCKER_BUILDKIT=1 BUILDKIT_PROGRESS=plain COMPOSE_DOCKER_CLI_BUILD=1
        docker build -q -t elabtmp -f tests/scrutinizer.dockerfile .
    fi
    docker-compose -f tests/docker-compose.yml up -d --quiet-pull
    # give some time for containers to start
    echo -n "Waiting for containers to start..."
    while [ "`docker inspect -f {{.State.Health.Status}} elabtmp`" != "healthy" ]; do echo -n .; sleep 2; done; echo
fi
if ($scrutinizer); then
    # install and initial tests
    docker exec -it elabtmp yarn install --silent --non-interactive --frozen-lockfile
    docker exec -it elabtmp yarn csslint
    docker exec -it elabtmp yarn jslint-ci
    docker exec -it elabtmp yarn buildall:dev
    docker exec -it elabtmp composer install --no-progress -q
    docker exec -it elabtmp yarn phpcs-dry
    # allow tmpfile, used by phpstan
    docker exec -it elabtmp sed -i 's/tmpfile, //' /etc/php81/php.ini
    # extend open_basedir
    # /usr/bin/psalm, //autoload.php, /root/.cache/ are for psalm
    # /usr/bin/phpstan, /proc/cpuinfo is for phpstan, https://github.com/phpstan/phpstan/issues/4427 https://github.com/phpstan/phpstan/issues/2965
    docker exec -it elabtmp sed -i 's|^open_basedir*|&:/usr/bin/psalm://autoload\.php:/root/\.cache/:/usr/bin/phpstan:/proc/cpuinfo|' /etc/php81/php.ini
fi
# install the database
echo "Initializing the database..."
docker exec -it elabtmp bin/install start -r -q
if ($scrutinizer); then
    docker exec -it elabtmp yarn static
fi
# populate the database
docker exec -it elabtmp bin/console dev:populate tests/populate-config.yml
# RUN TESTS
# install xdebug in the container so we can do code coverage
docker exec -it elabtmp bash -c 'apk add --update php81-pecl-xdebug && if [ ! -f "/etc/php81/conf.d/42_xdebug.ini" ]; then printf "zend_extension=xdebug.so\nxdebug.mode=coverage" > /etc/php81/conf.d/42_xdebug.ini; fi'
if ($scrutinizer); then
    # fix permissions on test output and cache folders
    sudo mkdir -p cache/purifier/{HTML,CSS,URI}
    sudo chown -R scrutinizer:scrutinizer cache
    sudo chmod -R 777 cache
    sudo chmod -R 777 tests/_output
    sudo chown -R scrutinizer:scrutinizer uploads
    sudo chmod -R 777 uploads
fi
# when trying to use a bash variable to hold the skip api options, I ran into issues that this option doesn't exist, so the command is entirely duplicated instead
if [ "${1:-}" = "unit" ]; then
    docker exec -it elabtmp php vendor/bin/codecept run --skip api --skip apiv2 --coverage --coverage-html --coverage-xml
else
    docker exec -it elabtmp php vendor/bin/codecept run --coverage --coverage-html --coverage-xml
fi

# in scrutinizer we copy the coverage in current directory
if ($scrutinizer); then
    docker cp elabtmp:/elabftw/tests/_output/coverage.xml .
fi
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
