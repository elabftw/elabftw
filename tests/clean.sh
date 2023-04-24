#!/usr/bin/env bash
# @author Nicolas CARPi <nico-git@deltablot.email>
# @copyright 2023 Nicolas CARPi
# @see https://www.elabftw.net Official website
# @license AGPL-3.0
# @package elabftw

# Bring all the test containers down
docker stop elabtmp
docker stop mysqltmp
# Remove them
docker rm elabtmp
docker rm mysqltmp
# Remove elabtmp image
docker image rm elabtmp
# we keep mysql image because it's the official one
