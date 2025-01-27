#!/usr/bin/env bash
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

# the behavior between local dev and ci pipeline is slightly different
# use env vars set in ci to guess where we run
# https://scrutinizer-ci.com/g/elabftw/elabftw
# https://scrutinizer-ci.com/docs/build/environment-variables
# https://circleci.com/docs/variables
# the both define a CI bool, so let's use that
ci=${CI:-false}

# when the script stops (or is stopped), say goodbye
cleanup() {
    echo "Buh bye!"
}
trap cleanup EXIT

# if there are no custom env_file, touch one, as this will trigger an error
if [ ! -f tests/elabftw-user.env ]; then
    touch tests/elabftw-user.env
    if (${SCRUTINIZER:-false}); then
        printf "ELABFTW_USER=scrutinizer\nELABFTW_GROUP=scrutinizer\nELABFTW_USERID=1001\nELABFTW_GROUPID=1001\n" > tests/elabftw-user.env
    fi
fi

# launch a fresh environment if needed
if [ ! "$(docker ps -q -f name=mysqltmp)" ] && [ ! "$(docker container ls -q -f name=elab-cypress)" ]; then
    if ($ci); then
        # Use the freshly built elabtmp image
        # use DOCKER_BUILDKIT env instead of calling "docker buildx" or it fails in scrutinizer
        export DOCKER_BUILDKIT=1 BUILDKIT_PROGRESS=plain COMPOSE_DOCKER_CLI_BUILD=1
        docker build -q -t elabtmp -f tests/elabtmp.Dockerfile .
    fi
    docker compose -f tests/docker-compose.yml up -d --quiet-pull
    # give some time for containers to start
    echo -n "Waiting for containers to start..."
    while [ "$(docker inspect -f {{.State.Health.Status}} elabtmp)" != "healthy" ]; do echo -n .; sleep 2; done; echo
fi
if ($ci); then
    # install and initial tests
    docker exec -it elabtmp yarn install --silent --immutable
    docker exec -it elabtmp yarn csslint
    docker exec -it elabtmp yarn jslint-ci:all
    docker exec -it elabtmp yarn buildall:dev
    docker exec -it elabtmp composer install --no-progress -q
    docker exec -it elabtmp yarn phpcs-dry
else
    # we need to add the parser because it's in cache/ and it's tmpfs mounted now
    docker exec -it elabtmp yarn buildparser
    docker exec -it elabtmp yarn twigcs
fi
# fix permissions on cache folders
docker exec -it elabtmp mkdir -p cache/purifier/{HTML,CSS,URI} cache/{elab,mpdf,twig}
worker_user=$(docker exec -it elabtmp tail -n1 /etc/shadow |awk -F ":" '{print $1}')
docker exec -it elabtmp chown -R "$worker_user":"$worker_user" cache

if ($ci); then
    docker exec -it elabtmp yarn static
fi
# populate the database
docker exec -it elabtmp bin/init db:populate src/tools/populate-config.yml.dist -y
# RUN TESTS
if ($ci); then
    # fix permissions on test output and uploads
    sudo chmod -R 777 tests/_output
    sudo chmod -R 777 uploads
    if (${SCRUTINIZER:-false}); then
        sudo chown -R scrutinizer:scrutinizer cache
        sudo chown -R scrutinizer:scrutinizer uploads
    fi
fi
# when trying to use a bash variable to hold the skip api options, I ran into issues that this option doesn't exist, so the command is entirely duplicated instead
if [ "${1:-}" = "unit" ]; then
    # Remove "unit" from the list of arguments, we are going to allow multiple arguments (test files) so this one should be excluded from the list.
    shift
    # Run one or multiple specific tests if the path is specified.
    TEST_FILES=("$@")
    if [ ${#TEST_FILES[@]} -gt 0 ]; then
        echo "Running unit tests. Test file(s): ${TEST_FILES[*]}"
        # Loop through each test file and run it individually
        for TEST_FILE in "${TEST_FILES[@]}"; do
          echo "Running test: $TEST_FILE"
          # verbose flag allows to see fwrite debug statements
          docker exec -it elabtmp php vendor/bin/codecept run "$TEST_FILE" --verbose --skip apiv2 --skip cypress
        done
    else
        echo "Running all unit tests."
        # Run all tests if no file is specified
        docker exec -it elabtmp php vendor/bin/codecept run --verbose --skip apiv2 --skip cypress --coverage --coverage-html --coverage-xml
    fi
elif [ "${1:-}" = "api" ]; then
    docker exec -it elabtmp php vendor/bin/codecept run --skip unit --skip cypress --coverage --coverage-html --coverage-xml
# acceptance with cypress
elif [ "${1:-}" = "cy" ]; then
    if [ ! "$(docker images elab-cypress)" ]; then
        echo "Building fresh cypress image..."
        docker build -q -t elab-cypress -f tests/elab-cypress.Dockerfile .
    fi
    if [ ! "$(docker container ls -q -f name=elab-cypress)" ]; then
        echo "Launching fresh cypress container..."
        docker run --name elab-cypress -d elab-cypress
    fi
    echo "Running cypress..."
    docker exec -it elab-cypress cypress run
    # copy the artifacts in cypress output folder
    docker cp elab-cypress:/home/node/tests/cypress/videos/. ./tests/cypress/videos
    docker cp elab-cypress:/home/node/tests/cypress/screenshots/. ./tests/cypress/screenshots
    # copy codecoverage reports
    docker exec -it elabtmp bash /elabftw/tests/merge-coverage-reports.sh
    docker cp elabtmp:/elabftw/tests/_output/c3tmp/codecoverage.tar ./tests/_output/cypress-coverage.tar
    mkdir -p ./tests/_output/cypress-coverage-html \
        && tar -xf ./tests/_output/cypress-coverage.tar -C ./tests/_output/cypress-coverage-html
    docker cp elabtmp:/elabftw/tests/_output/c3tmp/codecoverage.clover.xml ./tests/_output/cypress-coverage.clover.xml
else
    docker exec -it elabtmp php vendor/bin/codecept run --skip cypress --coverage --coverage-html --coverage-xml
fi

# in ci we copy the coverage output file in current directory
if ($ci); then
    docker cp elabtmp:/elabftw/tests/_output/coverage.xml .
fi

if [ "${1:-}" != "cy" ]; then
    # make a copy with adjusted path for local sonar scanner
    ROOT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && cd .. && pwd )
    sed -e "s:/elabftw/:$ROOT_DIR/:g" tests/_output/coverage.xml > tests/_output/coverage-sonar.xml
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
