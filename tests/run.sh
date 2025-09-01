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

# when the script stops (or is stopped), say goodbye
cleanup() {
    echo "Buh bye!"
}
trap cleanup EXIT

# if there are no custom env_file, touch one, as this will trigger an error
if [ ! -f tests/elabftw-user.env ]; then
    touch tests/elabftw-user.env
fi

# launch a fresh environment if needed
if [ -z "$(docker ps -q -f name=mysqltmp)" ]; then
    docker compose -f tests/docker-compose.yml up -d --quiet-pull
    # give some time for containers to start
    echo -n "Waiting for containers to start..."
    while [ "$(docker inspect -f {{.State.Health.Status}} elabtmp)" != "healthy" ]; do echo -n .; sleep 2; done; echo
fi
# we need to add the parser because it's in cache/ and it's tmpfs mounted now
docker exec -it elabtmp yarn buildparser
if [ "${SKIP_TWIGCS:-0}" -ne 1 ]; then
    echo "▶ Running twigcs. Use SKIP_TWIGCS=1 to disable."
    docker exec -it elabtmp yarn twigcs
fi
# fix permissions on cache folders
docker exec -it elabtmp mkdir -p cache/purifier/{HTML,CSS,URI} cache/{elab,mpdf,twig}
worker_user=$(docker exec -it elabtmp tail -n1 /etc/shadow |awk -F ":" '{print $1}')
docker exec -it elabtmp chown -R "$worker_user":"$worker_user" cache

# populate the database
if [ "${SKIP_POPULATE:-0}" -ne 1 ]; then
    echo "▶ Running populate script. Use SKIP_POPULATE=1 to disable."
    docker exec -it elabtmp bin/init db:populate src/tools/populate-config.yml.dist -y --fast
fi
# RUN TESTS
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
    if [ -z "$(docker images -q elab-cypress 2>/dev/null)" ]; then
        echo "Building fresh cypress image..."
        docker build -q -t elab-cypress -f tests/elab-cypress.Dockerfile .
    fi
    if [ -z "$(docker container ls -aq -f name=^elab-cypress$)" ]; then
        echo "Launching fresh cypress container..."
        docker run --name elab-cypress -d elab-cypress
    elif [ -z "$(docker container ls -q -f name=^elab-cypress$)" ]; then
        echo "Starting existing cypress container..."
        docker start elab-cypress
    fi
    echo "Running cypress..."
    docker exec -it elab-cypress cypress run
    # copy the artifacts in cypress output folder
    docker cp elab-cypress:/home/node/tests/cypress/videos/. ./tests/cypress/videos
    docker cp elab-cypress:/home/node/tests/cypress/screenshots/. ./tests/cypress/screenshots
    # copy codecoverage reports
    docker cp elabtmp:/elabftw/tests/_output/c3tmp/codecoverage.tar ./tests/_output/cypress-coverage.tar
    mkdir -p ./tests/_output/cypress-coverage-html \
        && tar -xf ./tests/_output/cypress-coverage.tar -C ./tests/_output/cypress-coverage-html
    docker cp elabtmp:/elabftw/tests/_output/c3tmp/codecoverage.clover.xml ./tests/_output/cypress-coverage.clover.xml
else
    docker exec -it elabtmp php vendor/bin/codecept run --skip cypress --coverage --coverage-html
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
