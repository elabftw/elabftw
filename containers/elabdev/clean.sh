#!/usr/bin/env bash
docker rmi elabftw-elabimg
docker rmi elabftw/elabdev:edge
set -eu
# sudo because mysql files will be root owned
sudo rm -rv -- "${HOME:?HOME must be set}/.local/share/elabdev"
rm -rv -- "${HOME:?HOME must be set}/.cache/elabdev"
