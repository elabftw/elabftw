#!/usr/bin/env bash
# @author Nicolas CARPi <nico-git@deltablot.email>
# @copyright 2023 Nicolas CARPi
# @see https://www.elabftw.net Official website
# @license AGPL-3.0
# @package elabftw

# Bring all the test containers down and remove them
docker stop elabtmp
docker rm elabtmp
docker stop mysqltmp
docker rm mysqltmp
