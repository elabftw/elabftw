#!/usr/bin/env bash
docker rmi elabftw-elabimg
docker rmi elabftw/elabdev:edge
set -eu
# sudo because mysql files will be root owned
sudo rm -rv ${HOME}/.local/share/elabdev
rm -rv ${HOME}/.cache/elabdev
