#!/usr/bin/env bash
# @author Nicolas CARPi <nico-git@deltablot.email>
# @copyright 2023 Nicolas CARPi
# @see https://www.elabftw.net Official website
# @license AGPL-3.0
# @package elabftw

# Bring all the test containers down
docker stop elabtmp
docker stop mysqltmp
docker stop elab-cypress

# Remove them
docker rm elabtmp
docker rm mysqltmp
docker rm elab-cypress

# Remove elabtmp and elab-cypress images
# we keep mysql image because it's the official one
docker image rm elabftw/elabimg:edge
docker image rm elabftw/elabtmp:edge
docker image rm elab-cypress

# Remove test output (coverage reports, phpunit cache)
rm -rfv tests/_output
