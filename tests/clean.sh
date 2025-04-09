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
docker image rm elabtmp
docker image rm elab-cypress

# Remove codecept files as they can cause errors between changes
docker exec -it elabftw vendor/bin/codecept clean
